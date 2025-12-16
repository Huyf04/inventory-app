<?php
// src/api/inventory.php

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_log("Inventory API called: " . $_SERVER['REQUEST_METHOD']);  // Debug

require_once __DIR__ . '/../assets/auth.php';

if (!isLoggedIn()) {
    error_log("Inventory API: Not logged in");
    http_response_code(401);
    echo json_encode(['error' => 'Chưa đăng nhập']);
    exit;
}
if (!hasRole('admin') && !hasRole('warehouse_staff')) {
    error_log("Inventory API: No permission");
    http_response_code(403);
    echo json_encode(['error' => 'Không có quyền']);
    exit;
}

require_once __DIR__ . '/../config.php';

$pg = getDBConnection(1);
if (!$pg) {
    error_log("Inventory API: DB connection failed");
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    error_log("Inventory POST input: " . json_encode($input));  // Debug
    if (!$input || !isset($input['product_id'], $input['quantity'], $input['type'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Thiếu dữ liệu']);
        exit;
    }
    $product_id = intval($input['product_id']);
    $quantity = intval($input['quantity']);
    $type = $input['type'];
    $note = $input['note'] ?? '';

    if ($type === 'out') {
        $res = pg_query_params($pg, "SELECT quantity FROM products WHERE id = $1", [$product_id]);
        if (!$res || pg_num_rows($res) === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Sản phẩm không tồn tại']);
            exit;
        }
        $current = pg_fetch_assoc($res)['quantity'];
        if ($current < $quantity) {
            http_response_code(400);
            echo json_encode(['error' => 'Tồn kho không đủ']);
            exit;
        }
        $new_quantity = $current - $quantity;
    } elseif ($type === 'in') {
        $res = pg_query_params($pg, "SELECT quantity FROM products WHERE id = $1", [$product_id]);
        if (!$res) {
            http_response_code(500);
            echo json_encode(['error' => pg_last_error($pg)]);
            exit;
        }
        $current = pg_fetch_assoc($res)['quantity'];
        $new_quantity = $current + $quantity;
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Type không hợp lệ']);
        exit;
    }

    $res = pg_query_params($pg, "UPDATE products SET quantity = $1 WHERE id = $2", [$new_quantity, $product_id]);
    if (!$res) {
        error_log("Inventory API: Update failed - " . pg_last_error($pg));
        http_response_code(500);
        echo json_encode(['error' => pg_last_error($pg)]);
        exit;
    }

    pg_query_params($pg, "INSERT INTO inventory_logs (product_id, type, quantity, note) VALUES ($1, $2, $3, $4)", [$product_id, $type, $quantity, $note]);

    echo json_encode(['success' => true, 'message' => 'Thực hiện thành công']);
} elseif ($method === 'GET') {
    $res = pg_query($pg, "SELECT p.name AS product_name, l.quantity, l.type, l.created_at FROM inventory_logs l JOIN products p ON l.product_id = p.id ORDER BY l.created_at DESC LIMIT 50");
    if (!$res) {
        http_response_code(500);
        echo json_encode(['error' => pg_last_error($pg)]);
        exit;
    }
    $data = pg_fetch_all($res) ?: [];
    echo json_encode($data);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

exit;
?>
