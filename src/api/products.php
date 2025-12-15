<?php
// src/api/categories.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';

// Cho phép CORS
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

// ====== KẾT NỐI DB ======
$pg1 = getDBConnection(1); // Render 🇸🇬
$pg2 = getDBConnection(2); // Neon 🇯🇵

// Bật đồng bộ
$SYNC_TO_DB2 = true;  // Đã bật như products.php

if (!$pg1) jsonResponse(["error" => "Không thể kết nối DB chính"], 500);

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
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $res = pg_query_params($pg1, "SELECT * FROM categories WHERE id = $1", [$id]);
        if (!$res) jsonResponse(["error" => pg_last_error($pg1)], 500);
        $row = pg_fetch_assoc($res);
        jsonResponse($row ?: [], 200);
    }

    $limit = intval($_GET['limit'] ?? 50);
    $offset = intval($_GET['offset'] ?? 0);
    $res = pg_query_params(
        $pg1,
        "SELECT * FROM categories ORDER BY id DESC LIMIT $1 OFFSET $2",
        [$limit, $offset]
    );
    if (!$res) jsonResponse(["error" => pg_last_error($pg1)], 500);
    $rows = pg_fetch_all($res) ?: [];
    jsonResponse($rows, 200);
}

// ====== POST ======
if ($method === 'POST') {
    debugLog("Raw POST input: " . $inputRaw);

    $name = trim($input['name'] ?? '');
    $description = trim($input['description'] ?? '');

    if ($name === '') jsonResponse(["error" => "Tên danh mục không được để trống"], 400);

    $query = "INSERT INTO categories (name, description) VALUES ($1, $2) RETURNING id";
    $params = [$name, $description];

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

// ====== PUT ======
if ($method === 'PUT') {
    if (!isset($_GET['id'])) jsonResponse(["error" => "Thiếu id"], 400);
    $id = intval($_GET['id']);
    $name = trim($input['name'] ?? '');
    $description = trim($input['description'] ?? '');

    if ($name === '') jsonResponse(["error" => "Tên danh mục không được để trống"], 400);

    $query = "UPDATE categories SET name = $1, description = $2, updated_at = NOW() WHERE id = $3";
    $params = [$name, $description, $id];

    debugLog("PUT Query DB1: $query with params: " . json_encode($params));
    $res = pg_query_params($pg1, $query, $params);
    if (!$res) {
        debugLog("DB1 Error: " . pg_last_error($pg1));
        jsonResponse(["error" => pg_last_error($pg1)], 500);
    }

    // 🔁 Đồng bộ sang DB2 nhưng không block nếu fail
    if ($SYNC_TO_DB2 && $pg2) {
        $res2 = @pg_query_params($pg2, $query, $params);
        if (!$res2) debugLog("⚠️ Đồng bộ DB2 thất bại: " . pg_last_error($pg2));
    }

    jsonResponse(["success" => true], 200);
}

// ====== DELETE ======
if ($method === 'DELETE') {
    if (!isset($_GET['id'])) jsonResponse(["error" => "Thiếu id"], 400);
    $id = intval($_GET['id']);

    $query = "DELETE FROM categories WHERE id = $1";
    $params = [$id];

    debugLog("DELETE Query DB1: $query with params: " . json_encode($params));
    $res = pg_query_params($pg1, $query, $params);
    if (!$res) {
        debugLog("DB1 Error: " . pg_last_error($pg1));
        jsonResponse(["error" => pg_last_error($pg1)], 500);
    }

    // 🔁 Đồng bộ sang DB2 nhưng không block nếu fail
    if ($SYNC_TO_DB2 && $pg2) {
        $res2 = @pg_query_params($pg2, $query, $params);
        if (!$res2) debugLog("⚠️ Đồng bộ DB2 thất bại: " . pg_last_error($pg2));
    }

    jsonResponse(["success" => true], 200);
}

// ====== DEFAULT ======
jsonResponse(["error" => "Method không được hỗ trợ"], 405);
?>
