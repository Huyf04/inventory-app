<?php
/* =====================================================
   PRODUCTS API - SKU BASED + SYNC 2 DB
   ===================================================== */

/* ===== CHỐNG PHP WARNING PHÁ JSON ===== */
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);
ob_start();

/* ===== HEADER ===== */
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config.php';

/* ===== HELPER ===== */
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function debugLog($msg) {
    error_log('[PRODUCT_API] ' . $msg);
}

/* ===== DB CONNECTION ===== */
$pg1 = getDBConnection(1); // DB chính (Render)
$pg2 = getDBConnection(2); // DB sync (Neon)

$SYNC_TO_DB2 = true;

if (!$pg1) {
    jsonResponse(["error" => "Không thể kết nối DB chính"], 500);
}

if ($SYNC_TO_DB2 && !$pg2) {
    debugLog("⚠️ DB2 connection FAILED");
}

/* ===== INPUT ===== */
$method   = $_SERVER['REQUEST_METHOD'];
$rawInput = file_get_contents('php://input');
$input    = json_decode($rawInput, true);
if (!is_array($input)) $input = [];

/* =====================================================
   ======================= GET =========================
   ===================================================== */
if ($method === 'GET') {

    /* ===== GET BY ID (FIX EDIT FORM) ===== */
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);

        $res = pg_query_params(
            $pg1,
            "SELECT p.*, c.name AS category_name
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.id = $1
             LIMIT 1",
            [$id]
        );

        if (!$res) jsonResponse(["error" => pg_last_error($pg1)], 500);
        jsonResponse(pg_fetch_assoc($res) ?: []);
    }

    /* ===== GET BY SKU ===== */
    $sku = trim($_GET['sku'] ?? '');
    if ($sku !== '') {
        $res = pg_query_params(
            $pg1,
            "SELECT p.*, c.name AS category_name
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.sku = $1
             LIMIT 1",
            [$sku]
        );

        if (!$res) jsonResponse(["error" => pg_last_error($pg1)], 500);
        jsonResponse(pg_fetch_assoc($res) ?: []);
    }

    /* ===== GET LIST ===== */
    $limit  = intval($_GET['limit'] ?? 50);
    $offset = intval($_GET['offset'] ?? 0);

    $res = pg_query_params(
        $pg1,
        "SELECT p.*, c.name AS category_name
         FROM products p
         LEFT JOIN categories c ON p.category_id = c.id
         ORDER BY p.id DESC
         LIMIT $1 OFFSET $2",
        [$limit, $offset]
    );

    if (!$res) jsonResponse(["error" => pg_last_error($pg1)], 500);
    jsonResponse(pg_fetch_all($res) ?: []);
}

/* =====================================================
   ======================= POST ========================
   ===================================================== */
if ($method === 'POST') {

    debugLog("POST RAW: " . $rawInput);

    $sku         = trim($input['sku'] ?? '');
    $name        = trim($input['name'] ?? '');
    $desc        = trim($input['description'] ?? '');
    $qty         = intval($input['quantity'] ?? 0);
    $price       = floatval($input['unit_price'] ?? 0);
    $category_id = intval($input['category_id'] ?? 0);

    if ($sku === '' || $name === '') {
        jsonResponse(["error" => "SKU và tên sản phẩm không được để trống"], 400);
    }

    /* ===== INSERT DB1 ===== */
    $queryInsert = "
        INSERT INTO products (sku, name, description, quantity, unit_price, category_id)
        VALUES ($1, $2, $3, $4, $5, $6)
        RETURNING id
    ";

    $params = [$sku, $name, $desc, $qty, $price, $category_id];

    $res = pg_query_params($pg1, $queryInsert, $params);
    if (!$res) {
        jsonResponse(["error" => pg_last_error($pg1)], 500);
    }

    $insertedId = pg_fetch_assoc($res)['id'];

    /* ===== SYNC DB2 (UPSERT THEO SKU) ===== */
    if ($SYNC_TO_DB2 && $pg2) {
        $querySync = "
            INSERT INTO products (sku, name, description, quantity, unit_price, category_id)
            VALUES ($1, $2, $3, $4, $5, $6)
            ON CONFLICT (sku) DO UPDATE SET
                name = EXCLUDED.name,
                description = EXCLUDED.description,
                quantity = EXCLUDED.quantity,
                unit_price = EXCLUDED.unit_price,
                category_id = EXCLUDED.category_id,
                updated_at = NOW()
        ";

        if (!pg_query_params($pg2, $querySync, $params)) {
            debugLog("⚠️ DB2 SYNC ERROR: " . pg_last_error($pg2));
        }
    }

    jsonResponse([
        "success" => true,
        "id"  => $insertedId,
        "sku" => $sku
    ], 201);
}

/* =====================================================
   ======================= PUT =========================
   ===================================================== */
if ($method === 'PUT') {

    $sku         = trim($input['sku'] ?? '');
    $name        = trim($input['name'] ?? '');
    $desc        = trim($input['description'] ?? '');
    $qty         = intval($input['quantity'] ?? 0);
    $price       = floatval($input['unit_price'] ?? 0);
    $category_id = intval($input['category_id'] ?? 0);

    if ($sku === '') jsonResponse(["error" => "Thiếu SKU"], 400);

    $query = "
        UPDATE products SET
            name = $2,
            description = $3,
            quantity = $4,
            unit_price = $5,
            category_id = $6,
            updated_at = NOW()
        WHERE sku = $1
    ";

    $params = [$sku, $name, $desc, $qty, $price, $category_id];

    if (!pg_query_params($pg1, $query, $params)) {
        jsonResponse(["error" => pg_last_error($pg1)], 500);
    }

    if ($SYNC_TO_DB2 && $pg2) {
        if (!pg_query_params($pg2, $query, $params)) {
            debugLog("⚠️ DB2 PUT ERROR: " . pg_last_error($pg2));
        }
    }

    jsonResponse(["success" => true]);
}

/* =====================================================
   ===================== DELETE ========================
   ===================================================== */
if ($method === 'DELETE') {

    $sku = trim($_GET['sku'] ?? '');
    if ($sku === '') jsonResponse(["error" => "Thiếu SKU"], 400);

    $query  = "DELETE FROM products WHERE sku = $1";
    $params = [$sku];

    if (!pg_query_params($pg1, $query, $params)) {
        jsonResponse(["error" => pg_last_error($pg1)], 500);
    }

    if ($SYNC_TO_DB2 && $pg2) {
        if (!pg_query_params($pg2, $query, $params)) {
            debugLog("⚠️ DB2 DELETE ERROR: " . pg_last_error($pg2));
        }
    }

    jsonResponse(["success" => true]);
}

/* ===== METHOD NOT ALLOWED ===== */
jsonResponse(["error" => "Method không được hỗ trợ"], 405);

ob_end_flush();
