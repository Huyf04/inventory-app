<?php
// src/api/inventory.php

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../assets/auth.php';

if (!isLoggedIn() || (!hasRole('admin') && !hasRole('warehouse_staff'))) {
    http_response_code(401);
    echo json_encode(['error' => 'Không có quyền']);
    exit;
}

require_once __DIR__ . '/../config.php';

$pg1 = getDBConnection(1);  // DB chính
$pg2 = getDBConnection(2);  // DB phụ (có thể null nếu không kết nối)

if (!$pg1) {
    http_response_code(500);
    echo json_encode(['error' => 'DB1 connection failed']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Lấy lịch sử từ DB1 (hoặc DB2 nếu cần)
    $res = pg_query($pg1, "SELECT p.name AS product_name, l.quantity, l.type, l.created_at FROM inventory_logs l JOIN products p ON l.product_id = p.id ORDER BY l.created_at DESC LIMIT 50");
    if (!$res) {
        http_response_code(500);
        echo json_encode(['error' => pg_last_error($pg1)]);
        exit;
    }
    $data = pg_fetch_all($res) ?: [];
    echo json_encode($data);

} elseif ($method === 'POST') {
    // Thực hiện nhập/xuất trên cả DB1 và DB2
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['product_id'], $input['quantity'], $input['type'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Thiếu dữ liệu']);
        exit;
    }
    $product_id = intval($input['product_id']);
    $quantity = intval($input['quantity']);
    $type = $input['type'];
    $note = $input['note'] ?? '';

    // Hàm helper để update một DB
    function updateInventory($pg, $dbName, $product_id, $quantity, $type, $note) {
        if (!$pg) {
            error_log("Sync $dbName: DB not available");
            return false;
        }

        if ($type === 'out') {
            $res = pg_query_params($pg, "SELECT quantity FROM products WHERE id = $1", [$product_id]);
            if (!$res || pg_num_rows($res) === 0) {
                error_log("Sync $dbName: Product not found");
                return false;
            }
            $current = pg_fetch_assoc($res)['quantity'];
            if ($current < $quantity) {
                error_log("Sync $dbName: Insufficient stock");
                return false;
            }
            $new_quantity = $current - $quantity;
        } elseif ($type === 'in') {
            $res = pg_query_params($pg, "SELECT quantity FROM products WHERE id = $1", [$product_id]);
            if (!$res) {
                error_log("Sync $dbName: Query failed - " . pg_last_error($pg));
                return false;
            }
            $current = pg_fetch_assoc($res)['quantity'];
            $new_quantity = $current + $quantity;
        } else {
            error_log("Sync $dbName: Invalid type");
            return false;
        }

        $res = pg_query_params($pg, "UPDATE products SET quantity = $1 WHERE id = $2", [$new_quantity, $product_id]);
        if (!$res) {
            error_log("Sync $dbName: Update failed - " . pg_last_error($pg));
            return false;
        }

        pg_query_params($pg, "INSERT INTO inventory_logs (product_id, type, quantity, note) VALUES ($1, $2, $3, $4)", [$product_id, $type, $quantity, $note]);
        error_log("Sync $dbName: Success");
        return true;
    }

    // Update DB1 (bắt buộc)
    $success1 = updateInventory($pg1, 'DB1', $product_id, $quantity, $type, $note);
    if (!$success1) {
        http_response_code(500);
        echo json_encode(['error' => 'Cập nhật DB1 thất bại']);
        exit;
    }

    // Update DB2 (tùy chọn, log nếu lỗi)
    $success2 = updateInventory($pg2, 'DB2', $product_id, $quantity, $type, $note);
    if (!$success2) {
        error_log("DB2 sync failed, but DB1 updated successfully");
    }

    echo json_encode(['success' => true, 'message' => 'Thực hiện thành công (DB1 synced, DB2 ' . ($success2 ? 'synced' : 'failed but logged') . ')']);

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

exit;
?>
