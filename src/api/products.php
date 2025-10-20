<?php
// src/api/products.php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';

// Cho phép CORS (chỉ nên mở cho dev)
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
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ====== CHỌN DB CHÍNH ======
$pg = getDBConnection(1);   // DB1 = Render
$pg2 = getDBConnection(2);  // DB2 = Neon (nếu có)
$SYNC_TO_DB2 = true;        // true = ghi cả 2 DB

if (!$pg) jsonResponse(["error" => "Không thể kết nối DB chính"], 500);

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) $input = [];
$method = $_SERVER['REQUEST_METHOD'];

// ====== GET ======
if ($method === 'GET') {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $res = pg_query_params(
            $pg,
            "SELECT p.*, c.name as category_name 
             FROM products p 
             LEFT JOIN categories c ON p.category_id = c.id 
             WHERE p.id = $1",
            [$id]
        );
        if (!$res) jsonResponse(["error" => pg_last_error($pg)], 500);
        $row = pg_fetch_assoc($res);
        jsonResponse($row ?: [], 200);
    }

    if (isset($_GET['q'])) {
        $q = "%" . $_GET['q'] . "%";
        $res = pg_query_params(
            $pg,
            "SELECT p.*, c.name as category_name 
             FROM products p 
             LEFT JOIN categories c ON p.category_id = c.id 
             WHERE p.name ILIKE $1 OR p.sku ILIKE $1 OR p.description ILIKE $1
             ORDER BY p.updated_at DESC",
            [$q]
        );
        if (!$res) jsonResponse(["error" => pg_last_error($pg)], 500);
        $rows = pg_fetch_all($res) ?: [];
        jsonResponse($rows, 200);
    }

    $limit = intval($_GET['limit'] ?? 50);
    $offset = intval($_GET['offset'] ?? 0);
    $category_id = intval($_GET['category_id'] ?? 0);

    $query = "SELECT p.*, c.name as category_name 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id";
    $params = [];

    if ($category_id > 0) {
        $query .= " WHERE p.category_id = $" . (count($params) + 1);
        $params[] = $category_id;
    }

    $query .= " ORDER BY p.id DESC LIMIT $" . (count($params) + 1) . " OFFSET $" . (count($params) + 2);
    $params[] = $limit;
    $params[] = $offset;

    $res = pg_query_params($pg, $query, $params);
    if (!$res) jsonResponse(["error" => pg_last_error($pg)], 500);
    $rows = pg_fetch_all($res) ?: [];
    jsonResponse($rows, 200);
}

// ====== POST ======
if ($method === 'POST') {
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

    $res = pg_query_params($pg, $query, $params);
    if (!$res) jsonResponse(["error" => pg_last_error($pg)], 500);

    $row = pg_fetch_assoc($res);
    $insertedId = $row['id'];

    // Đồng bộ sang DB2 nếu bật
    if ($SYNC_TO_DB2 && $pg2) {
        @pg_query_params($pg2, $query, $params);
    }

    jsonResponse(["success" => true, "id" => $insertedId], 201);
}

// ====== PUT ======
if ($method === 'PUT') {
    if (!isset($_GET['id'])) jsonResponse(["error" => "Thiếu id"], 400);
    $id = intval($_GET['id']);

    $sku = trim($input['sku'] ?? '');
    $name = trim($input['name'] ?? '');
    $desc = trim($input['description'] ?? '');
    $qty = intval($input['quantity'] ?? 0);
    $price = floatval($input['unit_price'] ?? 0.0);
    $category_id = intval($input['category_id'] ?? 0);

    if ($name === '') jsonResponse(["error" => "Tên sản phẩm không được để trống"], 400);

    $query = "UPDATE products 
              SET sku=$1, name=$2, description=$3, quantity=$4, unit_price=$5, 
                  category_id=$6, updated_at=NOW() 
              WHERE id=$7";
    $params = [$sku, $name, $desc, $qty, $price, $category_id, $id];

    $res = pg_query_params($pg, $query, $params);
    if (!$res) jsonResponse(["error" => pg_last_error($pg)], 500);

    if ($SYNC_TO_DB2 && $pg2) {
        @pg_query_params($pg2, $query, $params);
    }

    jsonResponse(["success" => true], 200);
}

// ====== DELETE ======
if ($method === 'DELETE') {
    if (!isset($_GET['id'])) jsonResponse(["error" => "Thiếu id"], 400);
    $id = intval($_GET['id']);

    $res = pg_query_params($pg, "DELETE FROM products WHERE id = $1", [$id]);
    if (!$res) jsonResponse(["error" => pg_last_error($pg)], 500);

    if ($SYNC_TO_DB2 && $pg2) {
        @pg_query_params($pg2, "DELETE FROM products WHERE id = $1", [$id]);
    }

    jsonResponse(["success" => true], 200);
}

// ====== DEFAULT ======
jsonResponse(["error" => "Method không được hỗ trợ"], 405);
?>
