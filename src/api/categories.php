<?php
// src/api/categories.php

header('Content-Type: application/json; charset=utf-8');

// cấu hình / kết nối DB
require_once __DIR__ . '/../config.php';

// Cho phép CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

global $pg;  // Kết nối DB từ config.php

if (!isset($pg)) {
    error_log('Lỗi: Kết nối DB không tồn tại');  // Log lỗi để debug
    jsonResponse(["error" => "Kết nối DB thất bại"], 500);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = [];
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Lấy 1 bản ghi theo id
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $res = pg_query_params($pg, "SELECT * FROM categories WHERE id = $1", [$id]);
        if (!$res) {
            error_log('Lỗi query GET id: ' . pg_last_error($pg));  // Log lỗi
            jsonResponse(["error" => pg_last_error($pg)], 500);
        }
        $row = pg_fetch_assoc($res);
        jsonResponse($row ?: [], 200);
    }

    // Lấy danh sách (có limit / offset)
    $limit = intval($_GET['limit'] ?? 50);
    $offset = intval($_GET['offset'] ?? 0);
    $res = pg_query_params(
        $pg,
        "SELECT * FROM categories ORDER BY id DESC LIMIT $1 OFFSET $2",
        [$limit, $offset]
    );
    if (!$res) {
        error_log('Lỗi query GET list: ' . pg_last_error($pg));  // Log lỗi
        jsonResponse(["error" => pg_last_error($pg)], 500);
    }
    $rows = pg_fetch_all($res) ?: [];
    jsonResponse($rows, 200);
}

if ($method === 'POST') {
    // Tạo mới danh mục
    $name = trim($input['name'] ?? '');
    $description = trim($input['description'] ?? '');

    if ($name === '') {
        jsonResponse(["error" => "Tên danh mục không được để trống"], 400);
    }

    $res = pg_query_params(
        $pg,
        "INSERT INTO categories (name, description) VALUES ($1, $2) RETURNING id",
        [$name, $description]
    );
    if (!$res) {
        error_log('Lỗi query POST: ' . pg_last_error($pg));  // Log lỗi
        jsonResponse(["error" => pg_last_error($pg)], 500);
    }
    $row = pg_fetch_assoc($res);
    jsonResponse(["success" => true, "id" => $row['id']], 201);
}

if ($method === 'PUT') {
    // Cập nhật danh mục
    if (!isset($_GET['id'])) {
        jsonResponse(["error" => "Thiếu id"], 400);
    }
    $id = intval($_GET['id']);
    $name = trim($input['name'] ?? '');
    $description = trim($input['description'] ?? '');

    if ($name === '') {
        jsonResponse(["error" => "Tên danh mục không được để trống"], 400);
    }

    $res = pg_query_params(
        $pg,
        "UPDATE categories SET name = $1, description = $2 WHERE id = $3",
        [$name, $description, $id]
    );
    if (!$res) {
        error_log('Lỗi query PUT: ' . pg_last_error($pg));  // Log lỗi
        jsonResponse(["error" => pg_last_error($pg)], 500);
    }
    jsonResponse(["success" => true], 200);
}

if ($method === 'DELETE') {
    if (!isset($_GET['id'])) {
        jsonResponse(["error" => "Thiếu id"], 400);
    }
    $id = intval($_GET['id']);
    $res = pg_query_params($pg, "DELETE FROM categories WHERE id = $1", [$id]);
    if (!$res) {
        error_log('Lỗi query DELETE: ' . pg_last_error($pg));  // Log lỗi
        jsonResponse(["error" => pg_last_error($pg)], 500);
    }
    jsonResponse(["success" => true], 200);
}

jsonResponse(["error" => "Method không được hỗ trợ"], 405);
?>