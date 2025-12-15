<?php
// src/api/stats.php

header('Content-Type: application/json; charset=utf-8');
// Tắt hiển thị lỗi ra màn hình để không phá JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../config.php';  // Đường dẫn tới config.php — điều chỉnh nếu cần

// Hàm helper để trả về lỗi JSON
function returnError($message, $code = 500) {
    http_response_code($code);
    echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

// Kiểm tra kết nối DB
if (!$pg || pg_connection_status($pg) !== PGSQL_CONNECTION_OK) {
    returnError('Không thể kết nối đến cơ sở dữ liệu.', 500);
}

// Lấy tham số từ query string (mặc định low_stock = 10)
$lowStockThreshold = isset($_GET['low_stock']) ? intval($_GET['low_stock']) : 10;
if ($lowStockThreshold < 0) $lowStockThreshold = 10;  // Bảo vệ giá trị âm

// Lấy tổng số sản phẩm
$res1 = pg_query($pg, "SELECT COUNT(*) AS cnt FROM products");
if (!$res1) returnError('Lỗi truy vấn tổng sản phẩm: ' . pg_last_error($pg));
$row1 = pg_fetch_assoc($res1);
$totalProducts = intval($row1['cnt'] ?? 0);

// Lấy số sản phẩm tồn kho thấp
$query2 = "SELECT COUNT(*) AS cnt FROM products WHERE quantity <= $1";
$res2 = pg_query_params($pg, $query2, [$lowStockThreshold]);
if (!$res2) returnError('Lỗi truy vấn tồn kho thấp: ' . pg_last_error($pg));
$row2 = pg_fetch_assoc($res2);
$lowStockCount = intval($row2['cnt'] ?? 0);

// Lấy tổng số danh mục
$res3 = pg_query($pg, "SELECT COUNT(*) AS cnt FROM categories");
if (!$res3) returnError('Lỗi truy vấn danh mục: ' . pg_last_error($pg));
$row3 = pg_fetch_assoc($res3);
$totalCategories = intval($row3['cnt'] ?? 0);

// Thêm thống kê mới: Tổng giá trị kho (giả sử cột price và quantity tồn tại)
$query4 = "SELECT SUM(quantity * price) AS total_value FROM products WHERE quantity > 0";
$res4 = pg_query($pg, $query4);
if (!$res4) returnError('Lỗi truy vấn giá trị kho: ' . pg_last_error($pg));
$row4 = pg_fetch_assoc($res4);
$totalValue = floatval($row4['total_value'] ?? 0);

// Trả về JSON
echo json_encode([
    'totalProducts' => $totalProducts,
    'lowStockCount' => $lowStockCount,
    'totalCategories' => $totalCategories,
    'totalValue' => $totalValue,  // Thêm mới
    'lowStockThreshold' => $lowStockThreshold  // Để frontend biết
], JSON_UNESCAPED_UNICODE);
exit;