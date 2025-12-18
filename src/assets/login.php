<?php
// src/assets/login.php

require_once 'auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Log để debug (sẽ xuất hiện trong logs Render)
    error_log("Login attempt: Username='$username', Password length=" . strlen($password));
    
    if (login($username, $password)) {
        error_log("Login success: Redirecting to ../index.php");
        header('Location: ../index.php');  // Đường dẫn từ assets/ lên src/
        exit;
    } else {
        $error = 'Sai username hoặc password!';
        error_log("Login failed for '$username'");
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập - Quản lý vật tư</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Custom animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }
        /* Gradient background */
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        /* Button hover effect */
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        /* Input focus */
        .input-focus:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        .loading-overlay.show {
            display: flex;
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="bg-white p-6 rounded-lg shadow-lg text-center">
            <i class="fas fa-spinner fa-spin text-2xl text-blue-500 mb-2"></i>
            <p class="text-gray-700">Đang xử lý...</p>
        </div>
    </div>

    <!-- Login Form -->
    <div class="bg-white p-8 rounded-xl shadow-2xl w-full max-w-md fade-in">
        <!-- Logo Section -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full mb-4">
                <i class="fas fa-boxes text-2xl text-white"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Đăng nhập</h1>
            <p class="text-gray-600">Quản lý vật tư chuyên nghiệp</p>
        </div>

        <!-- Error Message -->
        <?php if (isset($error)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                <i class="fas fa-exclamation-triangle mr-3"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form id="loginForm" method="POST" class="space-y-6">
            <!-- Username Field -->
            <div class="relative">
                <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Tên đăng nhập</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-user text-gray-400"></i>
                    </div>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="Nhập tên đăng nhập"
                        class="input-focus w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none transition-all duration-200"
                        required
                    >
                </div>
            </div>

            <!-- Password Field -->
            <div class="relative">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Mật khẩu</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-lock text-gray-400"></i>
                    </div>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Nhập mật khẩu"
                        class="input-focus w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none transition-all duration-200"
                        required
                    >
                </div>
            </div>

            <!-- Submit Button -->
            <button
                type="submit"
                class="btn-login w-full bg-gradient-to-r from-blue-500 to-purple-600 text-white py-3 px-4 rounded-lg font-semibold shadow-lg transition-all duration-200 flex items-center justify-center"
            >
                <i class="fas fa-sign-in-alt mr-2"></i>
                Đăng nhập
            </button>
        </form>

        <!-- Footer -->
        <div class="mt-8 text-center text-sm text-gray-500">
            <p>&copy; 2023 Quản lý vật tư. Tất cả quyền được bảo lưu.</p>
            <p class="mt-1">Liên hệ: support@inventory.com</p>
        </div>
    </div>

    <script>
        // Show loading on form submit
        document.getElementById('loginForm').addEventListener('submit', function() {
            document.getElementById('loadingOverlay').classList.add('show');
        });

        // Auto-focus username field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });
    </script>
</body>
</html>
