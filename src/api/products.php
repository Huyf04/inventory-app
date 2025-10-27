<?php
// src/api/products.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';

// Cho phép CORS (chỉ nên bật khi dev)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ====== HÀM TIỆN ÍCH ======
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ====== CHỌN DB ======
$pg1 = getDBConnection(1); // Render
$pg2 = getDBConnection(2); // Neon
$pg3 = getDBConnection(3); // Supabase

$SYNC_TO_DB2 = true; // Ghi cả DB2
$SYNC_TO_DB3 = true; // Ghi cả DB3

if (!$pg1) jsonResponse(["error" => "Không thể kết nối DB chính (Render)"], 500);

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) $input = [];
$method = $_SERVER['REQUEST_METHOD'];

// ====== GET (chỉ đọc DB chính) ======
if ($method === 'GET') {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $res = pg_query_params($pg1, "
            SELECT p.*, c.name AS category_name 
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.id = $1
        ", [$id]);
        if (!$res) jsonResponse(["error" => pg_last_error($pg1)], 500);
        jsonResponse(pg_fetch_assoc($res) ?: [], 200);
    }

    if (isset($_GET['q'])) {
        $q = "%" . $_GET['q'] . "%";
        $res = pg_query_params($pg1, "
            SELECT p.*, c.name AS category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.name ILIKE $1 OR p.sku ILIKE $1 OR p.description ILIKE $1
            ORDER BY p.updated_at DESC
        ", [$q]);
        if (!$res) jsonResponse(["error" => pg_last_error($pg1)], 500);
        jsonResponse(pg_fetch_all($res) ?: [], 200);
    }

    $limit = intval($_GET['limit'] ?? 50);
    $offset = intval($_GET['offset'] ?? 0);
    $res = pg_query_params($pg1, "
        SELECT p.*, c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        ORDER BY p.id DESC
        LIMIT $1 OFFSET $2
    ", [$limit, $offset]);
    if (!$res) jsonResponse(["error" => pg_last_error($pg1)], 500);
    jsonResponse(pg_fetch_all($res) ?: [], 200);
}

// ====== POST (thêm) ======
if ($method === 'POST') {
    $sku = trim($input['sku'] ?? '');
    $name = trim($input['name'] ?? '');
    $desc = trim($input['description'] ?? '');
    $qty = intval($input['quantity'] ?? 0);
    $price = floatval($input['unit_price'] ?? 0);
    $category_id = intval($input['category_id'] ?? 0);

    if ($name === '') jsonResponse(["error" => "Tên sản phẩm không được để trống"], 400);

    $query = "INSERT INTO products (sku, name, description, quantity, unit_price, category_id) 
              VALUES ($1, $2, $3, $4, $5, $6) RETURNING id";
    $params = [$sku, $name, $desc, $qty, $price, $category_id];

    $res = pg_query_params($pg1, $query, $params);
    if (!$res) jsonResponse(["error" => pg_last_error($pg1)], 500);
    $insertedId = pg_fetch_result($res, 0, 'id');

    if ($SYNC_TO_DB2 && $pg2) @pg_query_params($pg2, $query, $params);
    if ($SYNC_TO_DB3 && $pg3) @pg_query_params($pg3, $query, $params);

    jsonResponse(["success" => true, "id" => $insertedId], 201);
}

// ====== PUT (cập nhật) ======
if ($method === 'PUT') {
    if (!isset($_GET['id'])) jsonResponse(["error" => "Thiếu id"], 400);
    $id = intval($_GET['id']);

    $sku = trim($input['sku'] ?? '');
    $name = trim($input['name'] ?? '');
    $desc = trim($input['description'] ?? '');
    $qty = intval($input['quantity'] ?? 0);
    $price = floatval($input['unit_price'] ?? 0);
    $category_id = intval($input['category_id'] ?? 0);

    $query = "UPDATE products 
              SET sku=$1, name=$2, description=$3, quantity=$4, unit_price=$5,
                  category_id=$6, updated_at=NOW()
              WHERE id=$7";
    $params = [$sku, $name, $desc, $qty, $price, $category_id, $id];

    $res = pg_query_params($pg1, $query, $params);
    if (!$res) jsonResponse(["error" => pg_last_error($pg1)], 500);

    if ($SYNC_TO_DB2 && $pg2) @pg_query_params($pg2, $query, $params);
    if ($SYNC_TO_DB3 && $pg3) @pg_query_params($pg3, $query, $params);

    jsonResponse(["success" => true], 200);
}

// ====== DELETE ======
if ($method === 'DELETE') {
    if (!isset($_GET['id'])) jsonResponse(["error" => "Thiếu id"], 400);
    $id = intval($_GET['id']);

    $res = pg_query_params($pg1, "DELETE FROM products WHERE id=$1", [$id]);
    if (!$res) jsonResponse(["error" => pg_last_error($pg1)], 500);

    if ($SYNC_TO_DB2 && $pg2) @pg_query_params($pg2, "DELETE FROM products WHERE id=$1", [$id]);
    if ($SYNC_TO_DB3 && $pg3) @pg_query_params($pg3, "DELETE FROM products WHERE id=$1", [$id]);

    jsonResponse(["success" => true], 200);
}

jsonResponse(["error" => "Method không được hỗ trợ"], 405);
?>
