<?php
// src/api/products.php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';

// Cho phép CORS (dev mode)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ====== HÀM HỖ TRỢ ======
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// ====== KẾT NỐI CÁC DB ======
$pg1 = getDBConnection(1); // Render
$pg2 = getDBConnection(2); // Neon

$SYNC_TO_DB2 = true;

if (!$pg1) jsonResponse(["error" => "❌ Không thể kết nối Render DB"], 500);

$inputRaw = file_get_contents('php://input');
$input = json_decode($inputRaw, true);
if (!is_array($input)) $input = [];
$method = $_SERVER['REQUEST_METHOD'];

// ====== LOG DEBUG ======
function debugLog($msg) {
    error_log("[DEBUG] " . $msg);
}

// ====== GET ======
if ($method === 'GET') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : null;
    $limit = intval($_GET['limit'] ?? 50);
    $offset = intval($_GET['offset'] ?? 0);

    if ($id) {
        $res = pg_query_params(
            $pg1,
            "SELECT p.*, c.name as category_name 
             FROM products p 
             LEFT JOIN categories c ON p.category_id = c.id 
             WHERE p.id = $1",
            [$id]
        );
        if (!$res) jsonResponse(["error" => pg_last_error($pg1)], 500);
        jsonResponse(pg_fetch_assoc($res) ?: [], 200);
    }

    $res = pg_query_params(
        $pg1,
        "SELECT p.*, c.name as category_name 
         FROM products p 
         LEFT JOIN categories c ON p.category_id = c.id 
         ORDER BY p.id DESC LIMIT $1 OFFSET $2",
        [$limit, $offset]
    );
    if (!$res) jsonResponse(["error" => pg_last_error($pg1)], 500);
    jsonResponse(pg_fetch_all($res) ?: [], 200);
}

// ====== POST ======
if ($method === 'POST') {
    debugLog("Raw POST input: " . $inputRaw);

    $sku = trim($input['sku'] ?? '');
    $name = trim($input['name'] ?? '');
    $desc = trim($input['description'] ?? '');
    $qty = intval($input['quantity'] ?? 0);
    $price = floatval($input['unit_price'] ?? 0.0);
    $category_id = intval($input['category_id'] ?? 0);

    if ($name === '') jsonResponse(["error" => "Tên sản phẩm không được để trống"], 400);

    $query = "INSERT INTO products (sku, name, description, quantity, unit_price, category_id) 
              VALUES ($1, $2, $3, $4, $5, $6) RETURNING id";
    $params = [$sku, $name, $desc, $qty, $price, $category_id];

    debugLog("Query DB1: $query with params: " . json_encode($params));

    $res = pg_query_params($pg1, $query, $params);
    if (!$res) {
        debugLog("DB1 Error: " . pg_last_error($pg1));
        jsonResponse(["error" => pg_last_error($pg1)], 500);
    }

    $row = pg_fetch_assoc($res);
    $insertedId = $row['id'];

    // 🔁 Đồng bộ sang DB2 nhưng không block nếu fail
    if ($SYNC_TO_DB2 && $pg2) {
        $res2 = @pg_query_params($pg2, $query, $params);
        if (!$res2) {
            debugLog("⚠️ Đồng bộ DB2 thất bại: " . pg_last_error($pg2));
        } else {
            debugLog("Đồng bộ DB2 thành công: ID " . $insertedId);
        }
    }

    jsonResponse(["success" => true, "id" => $insertedId], 201);
}

// ====== PUT / DELETE ======
if ($method === 'PUT' || $method === 'DELETE') {
    if (!isset($_GET['id'])) jsonResponse(["error" => "Thiếu id"], 400);
    $id = intval($_GET['id']);

    if ($method === 'PUT') {
        $sku = trim($input['sku'] ?? '');
        $name = trim($input['name'] ?? '');
        $desc = trim($input['description'] ?? '');
        $qty = intval($input['quantity'] ?? 0);
        $price = floatval($input['unit_price'] ?? 0.0);
        $category_id = intval($input['category_id'] ?? 0);

        $query = "UPDATE products 
                  SET sku=$1, name=$2, description=$3, quantity=$4, unit_price=$5, 
                      category_id=$6, updated_at=NOW() 
                  WHERE id=$7";
        $params = [$sku, $name, $desc, $qty, $price, $category_id, $id];
    } else { // DELETE
        $query = "DELETE FROM products WHERE id=$1";
        $params = [$id];
    }

    debugLog("$method Query DB1: $query with params: " . json_encode($params));
    $res = pg_query_params($pg1, $query, $params);
    if (!$res) {
        debugLog("DB1 Error: " . pg_last_error($pg1));
        jsonResponse(["error" => pg_last_error($pg1)], 500);
    }

    if ($SYNC_TO_DB2 && $pg2) {
        $res2 = @pg_query_params($pg2, $query, $params);
        if (!$res2) debugLog("⚠️ Đồng bộ DB2 thất bại: " . pg_last_error($pg2));
    }

    jsonResponse(["success" => true], 200);
}

jsonResponse(["error" => "Method không được hỗ trợ"], 405);
?>
