<?php
// src/api/users.php

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../assets/auth.php';  // Kiểm tra auth

// Kiểm tra đăng nhập và quyền admin
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Chưa đăng nhập']);
    exit;
}
if (!hasRole('admin')) {
    http_response_code(403);
    echo json_encode(['error' => 'Không có quyền quản lý người dùng']);
    exit;
}

require_once __DIR__ . '/../config.php';

$pg = getDBConnection(1);
if (!$pg) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Lấy danh sách users hoặc user cụ thể
    $id = isset($_GET['id']) ? intval($_GET['id']) : null;
    if ($id) {
        $res = pg_query_params($pg, "SELECT id, username, role, created_at FROM users WHERE id = $1", [$id]);
    } else {
        $res = pg_query($pg, "SELECT id, username, role, created_at FROM users ORDER BY id DESC");
    }
    if (!$res) {
        http_response_code(500);
        echo json_encode(['error' => pg_last_error($pg)]);
        exit;
    }
    $data = pg_fetch_all($res) ?: [];
    echo json_encode($data);

} elseif ($method === 'POST') {
    // Thêm user mới
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['username'], $input['password'], $input['role'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Thiếu dữ liệu: username, password, role']);
        exit;
    }
    $username = trim($input['username']);
    $password = $input['password'];  // Plain text
    $role = $input['role'];
    if (!in_array($role, ['admin', 'warehouse_staff', 'accountant'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Role không hợp lệ']);
        exit;
    }
    $res = pg_query_params($pg, "INSERT INTO users (username, password, role) VALUES ($1, $2, $3)", [$username, $password, $role]);
    if (!$res) {
        http_response_code(500);
        echo json_encode(['error' => pg_last_error($pg)]);
        exit;
    }
    echo json_encode(['success' => true, 'message' => 'Thêm user thành công']);

} elseif ($method === 'PUT') {
    // Sửa user
    $id = isset($_GET['id']) ? intval($_GET['id']) : null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Thiếu id']);
        exit;
    }
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['username'], $input['password'], $input['role'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Thiếu dữ liệu: username, password, role']);
        exit;
    }
    $username = trim($input['username']);
    $password = $input['password'];  // Plain text
    $role = $input['role'];
    if (!in_array($role, ['admin', 'warehouse_staff', 'accountant'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Role không hợp lệ']);
        exit;
    }
    $res = pg_query_params($pg, "UPDATE users SET username = $1, password = $2, role = $3 WHERE id = $4", [$username, $password, $role, $id]);
    if (!$res) {
        http_response_code(500);
        echo json_encode(['error' => pg_last_error($pg)]);
        exit;
    }
    echo json_encode(['success' => true, 'message' => 'Cập nhật user thành công']);

} elseif ($method === 'DELETE') {
    // Xóa user
    $id = isset($_GET['id']) ? intval($_GET['id']) : null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Thiếu id']);
        exit;
    }
    $res = pg_query_params($pg, "DELETE FROM users WHERE id = $1", [$id]);
    if (!$res) {
        http_response_code(500);
        echo json_encode(['error' => pg_last_error($pg)]);
        exit;
    }
    echo json_encode(['success' => true, 'message' => 'Xóa user thành công']);

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

exit;
?>
