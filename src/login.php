<?php
// src/api/auth.php
session_start();  // Thêm dòng này ở đầu (hỗ trợ sessions cho auth)
header('Content-Type: application/json; charset=utf-8');

// Cấu hình / kết nối DB
require_once __DIR__ . '/../config.php';  // Đã có session_start từ config

// Cho phép CORS (giống products.php)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Nếu là preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Hàm trợ giúp trả JSON (giống products.php)
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?: [];

if ($method === 'POST') {
    // Xử lý login
    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($username) || empty($password)) {
        jsonResponse(["error" => "Username và password không được để trống"], 400);
    }

    // Query user từ DB
    $res = pg_query_params($pg, "SELECT id, username, password_hash, role FROM users WHERE username = $1", [$username]);
    if (!$res) {
        jsonResponse(["error" => pg_last_error($pg)], 500);
    }

    $user = pg_fetch_assoc($res);
    if (!$user || !password_verify($password, $user['password_hash'])) {
        jsonResponse(["error" => "Username hoặc password không đúng"], 401);
    }

    // Set session nếu OK
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];

    jsonResponse([
        "success" => true,
        "message" => "Đăng nhập thành công",
        "user" => ["id" => $user['id'], "username" => $user['username'], "role" => $user['role']]
    ], 200);

} elseif ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'logout') {
    // Xử lý logout
    if (isset($_SESSION['user_id'])) {
        session_destroy();
        jsonResponse(["success" => true, "message" => "Đăng xuất thành công"], 200);
    } else {
        jsonResponse(["error" => "Chưa đăng nhập"], 401);
    }

} else {
    // Kiểm tra session hiện tại (cho AJAX check login status)
    if (isset($_SESSION['user_id'])) {
        jsonResponse([
            "success" => true,
            "user" => [
                "id" => $_SESSION['user_id'],
                "username" => $_SESSION['username'],
                "role" => $_SESSION['role']
            ]
        ], 200);
    } else {
        jsonResponse(["error" => "Chưa đăng nhập"], 401);
    }
}
?>
