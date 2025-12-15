<?php
// src/assets/auth.php

require_once __DIR__ . '/../config.php';  // Kết nối DB

session_start();  // Bắt đầu session

// Hàm đăng nhập
function login($username, $password) {
    global $pg;  // Dùng DB chính (hoặc getDBConnection(1))
    $pg = getDBConnection(1);  // Giả sử dùng DB1

    $stmt = pg_prepare($pg, "login_query", "SELECT id, username, role, password FROM users WHERE username = $1");
    $res = pg_execute($pg, "login_query", [$username]);
    if (!$res || pg_num_rows($res) === 0) return false;

    $user = pg_fetch_assoc($res);
    if (password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        return true;
    }
    return false;
}

// Hàm kiểm tra đã đăng nhập
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Hàm kiểm tra quyền (role)
function hasRole($requiredRole) {
    if (!isLoggedIn()) return false;
    $userRole = $_SESSION['role'];
    // Admin có toàn quyền
    if ($userRole === 'admin') return true;
    // Kiểm tra role cụ thể
    return $userRole === $requiredRole;
}

// Hàm logout
function logout() {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Hàm lấy user hiện tại
function getCurrentUser() {
    return isLoggedIn() ? [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'role' => $_SESSION['role']
    ] : null;
}
?>