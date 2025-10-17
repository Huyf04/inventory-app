<?php
// src/api/products.php

header('Content-Type: application/json; charset=utf-8');

// cấu hình / kết nối DB
require_once __DIR__ . '/../config.php';

// Cho phép CORS (chỉ dùng cho dev, sản xuất nên giới hạn domain)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Nếu là preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Hàm trợ giúp trả JSON
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Kết nối tới DB (từ config.php)
global $pg;

// Đọc body JSON nếu có
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = [];  // nếu không parse được, đặt mảng rỗng
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Lấy 1 bản ghi theo id
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $res = pg_query_params($pg, "SELECT * FROM products WHERE id = $1", [$id]);
        if (!$res) {
            jsonResponse(["error" => pg_last_error($pg)], 500);
        }
        $row = pg_fetch_assoc($res);
        jsonResponse($row ?: [], 200);
    }

    // Tìm kiếm theo q
    if (isset($_GET['q'])) {
        $q = "%" . $_GET['q'] . "%";
        $res = pg_query_params(
            $pg,
            "SELECT * FROM products 
             WHERE name ILIKE $1 OR sku ILIKE $1 OR description ILIKE $1
             ORDER BY updated_at DESC",
            [$q]
        );
        if (!$res) {
            jsonResponse(["error" => pg_last_error($pg)], 500);
        }
        $rows = pg_fetch_all($res) ?: [];
        jsonResponse($rows, 200);
    }

    // Lấy danh sách (có limit / offset) - PHẦN ĐÃ CẬP NHẬT
    $limit = intval($_GET['limit'] ?? 50);
    $offset = intval($_GET['offset'] ?? 0);
    $res = pg_query_params(
        $pg,
        "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC LIMIT $1 OFFSET $2",
        [$limit, $offset]
    );
    if (!$res) {
        jsonResponse(["error" => pg_last_error($pg)], 500);
    }
    $rows = pg_fetch_all($res) ?: [];
    jsonResponse($rows, 200);
}


if ($method === 'POST') {
    // Tạo mới
    $sku = trim($input['sku'] ?? '');
    $name = trim($input['name'] ?? '');
    $desc = trim($input['description'] ?? '');
    $qty = intval($input['quantity'] ?? 0);
    $price = floatval($input['unit_price'] ?? 0.0);
$category_id = intval($input['category_id'] ?? 0);  // Giả sử là ID từ categories
    // Có thể kiểm tra validate (ví dụ: name không rỗng)
    if ($name === '') {
        jsonResponse(["error" => "Tên sản phẩm không được để trống"], 400);
    }

    $res = pg_query_params(
                $pg,
        "INSERT INTO products (sku, name, description, quantity, unit_price, category_id) 
         VALUES ($1, $2, $3, $4, $5, $6) RETURNING id",
        [$sku, $name, $desc, $qty, $price, $category_id]
    );
    if (!$res) {
        jsonResponse(["error" => pg_last_error($pg)], 500);
    }
    $row = pg_fetch_assoc($res);
    jsonResponse(["success" => true, "id" => $row['id']], 201);
}

if ($method === 'PUT') {
    // Cập nhật
    if (!isset($_GET['id'])) {
        jsonResponse(["error" => "Thiếu id"], 400);
    }
    $id = intval($_GET['id']);
    $sku = trim($input['sku'] ?? '');
    $name = trim($input['name'] ?? '');
    $desc = trim($input['description'] ?? '');
    $qty = intval($input['quantity'] ?? 0);
    $price = floatval($input['unit_price'] ?? 0.0);
    $category_id = intval($input['category_id'] ?? 0);

    if ($name === '') {
        jsonResponse(["error" => "Tên sản phẩm không được để trống"], 400);
    }

    $res = pg_query_params(
        $pg,
        "UPDATE products
         SET sku = $1, name = $2, description = $3, quantity = $4, unit_price = $5, category_id = $6, updated_at = NOW()
         WHERE id = $7",
        [$sku, $name, $desc, $qty, $price, $category_id, $id]
    );
    if (!$res) {
        jsonResponse(["error" => pg_last_error($pg)], 500);
    }
    jsonResponse(["success" => true], 200);
    
}

if ($method === 'DELETE') {
    if (!isset($_GET['id'])) {
        jsonResponse(["error" => "Thiếu id"], 400);
    }
    $id = intval($_GET['id']);
    $res = pg_query_params($pg, "DELETE FROM products WHERE id = $1", [$id]);
    if (!$res) {
        jsonResponse(["error" => pg_last_error($pg)], 500);
    }
    jsonResponse(["success" => true], 200);
}

// Nếu không phải các method trên
jsonResponse(["error" => "Method không được hỗ trợ"], 405);
