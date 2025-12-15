<?php
/* =====================================================
   PRODUCTS API - SKU BASED + SYNC 2 DB (FIX PK ERROR)
   ===================================================== */

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

if (!$pg1) jsonResponse(["error" => "Không thể kết nối DB1"], 500);
if (!$pg2) debugLog("⚠️ DB2 connection FAILED");

/* ===== INPUT ===== */
$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

/* =====================================================
   ======================= GET =========================
   ===================================================== */
if ($method === 'GET') {

    if (isset($_GET['sku'])) {
        $res = pg_query_params(
            $pg1,
            "SELECT p.*, c.name AS category_name
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.sku = $1
             LIMIT 1",
            [$_GET['sku']]
        );
        jsonResponse(pg_fetch_assoc($res) ?: []);
    }

    $res = pg_query(
        $pg1,
        "SELECT p.*, c.name AS category_name
         FROM products p
         LEFT JOIN categories c ON p.category_id = c.id
         ORDER BY p.id DESC"
    );
    jsonResponse(pg_fetch_all($res) ?: []);
}

/* =====================================================
   ======================= POST ========================
   ===================================================== */
if ($method === 'POST') {

    $sku  = trim($input['sku'] ?? '');
    $name = trim($input['name'] ?? '');

    if ($sku === '' || $name === '') {
        jsonResponse(["error" => "SKU và tên sản phẩm không được để trống"], 400);
    }

    $params = [
        $sku,
        $name,
        $input['description'] ?? '',
        (int)($input['quantity'] ?? 0),
        (float)($input['unit_price'] ?? 0),
        (int)($input['category_id'] ?? 0)
    ];

    /* ===== DB1 UPSERT ===== */
    $q1 = "
        INSERT INTO products (sku, name, description, quantity, unit_price, category_id)
        VALUES ($1,$2,$3,$4,$5,$6)
        ON CONFLICT (sku) DO UPDATE SET
            name = EXCLUDED.name,
            description = EXCLUDED.description,
            quantity = EXCLUDED.quantity,
            unit_price = EXCLUDED.unit_price,
            category_id = EXCLUDED.category_id,
            updated_at = NOW()
    ";
    if (!pg_query_params($pg1, $q1, $params)) {
        jsonResponse(["error" => pg_last_error($pg1)], 500);
    }

    /* ===== DB2 INSERT (NO ID GENERATED) ===== */
    $q2_insert = "
        INSERT INTO products (sku, name, description, quantity, unit_price, category_id)
        SELECT $1,$2,$3,$4,$5,$6
        WHERE NOT EXISTS (
            SELECT 1 FROM products WHERE sku = $1
        )
    ";
    pg_query_params($pg2, $q2_insert, $params);

    /* ===== DB2 UPDATE ===== */
    $q2_update = "
        UPDATE products SET
            name = $2,
            description = $3,
            quantity = $4,
            unit_price = $5,
            category_id = $6,
            updated_at = NOW()
        WHERE sku = $1
    ";
    pg_query_params($pg2, $q2_update, $params);

    jsonResponse(["success" => true, "sku" => $sku], 201);
}

/* =====================================================
   ======================= PUT =========================
   ===================================================== */
if ($method === 'PUT') {

    $sku = trim($input['sku'] ?? '');
    if ($sku === '') jsonResponse(["error" => "Thiếu SKU"], 400);

    $params = [
        $sku,
        $input['name'],
        $input['description'] ?? '',
        (int)$input['quantity'],
        (float)$input['unit_price'],
        (int)$input['category_id']
    ];

    $q = "
        UPDATE products SET
            name = $2,
            description = $3,
            quantity = $4,
            unit_price = $5,
            category_id = $6,
            updated_at = NOW()
        WHERE sku = $1
    ";

    pg_query_params($pg1, $q, $params);
    pg_query_params($pg2, $q, $params);

    jsonResponse(["success" => true]);
}

/* =====================================================
   ===================== DELETE ========================
   ===================================================== */
if ($method === 'DELETE') {

    $sku = $_GET['sku'] ?? '';
    if ($sku === '') jsonResponse(["error" => "Thiếu SKU"], 400);

    pg_query_params($pg1, "DELETE FROM products WHERE sku = $1", [$sku]);
    pg_query_params($pg2, "DELETE FROM products WHERE sku = $1", [$sku]);

    jsonResponse(["success" => true]);
}

jsonResponse(["error" => "Method không được hỗ trợ"], 405);
ob_end_flush();
