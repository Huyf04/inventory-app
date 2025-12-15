<?php
// src/assets/login.php

require_once 'auth.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (login($username, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'Sai username hoặc password!';
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded shadow-md w-96">
        <h1 class="text-2xl font-bold mb-4">Đăng nhập</h1>
        <?php if (isset($error)) echo "<p class='text-red-500'>$error</p>"; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" class="w-full p-2 border mb-4" required>
            <input type="password" name="password" placeholder="Password" class="w-full p-2 border mb-4" required>
            <button type="submit" class="w-full bg-blue-500 text-white p-2 rounded">Đăng nhập</button>
        </form>
    </div>
</body>
</html>