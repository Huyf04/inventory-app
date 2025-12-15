<?php
// src/assets/auth.php

require_once __DIR__ . '/../config.php';

session_start();

function login($username, $password) {
    $pg = getDBConnection(1);
    if (!$pg) {
        error_log("Login: DB connection failed");
        return false;
    }

    $res = pg_query_params($pg, "SELECT id, username, role, password FROM users WHERE username = $1", [$username]);
    if (!$res || pg_num_rows($res) === 0) {
        error_log("Login: User '$username' not found or query failed - " . pg_last_error($pg));
        return false;
    }

    $user = pg_fetch_assoc($res);
    $storedPassword = $user['password'];
    error_log("Login: User '$username' found, stored password: '$storedPassword'");

    // So sánh plain text (tạm thời, vì DB lưu plain text)
    if ($password === $storedPassword) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        error_log("Login: Success for '$username'");
        return true;
    } else {
        error_log("Login: Password mismatch for '$username'");
        return false;
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function hasRole($requiredRole) {
    if (!isLoggedIn()) return false;
    $userRole = $_SESSION['role'];
    if ($userRole === 'admin') return true;
    return $userRole === $requiredRole;
}

function logout() {
    session_destroy();
    header('Location: login.php');
    exit;
}

function getCurrentUser() {
    return isLoggedIn() ? [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'role' => $_SESSION['role']
    ] : null;
}
?>
