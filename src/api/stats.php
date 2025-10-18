<?php
// src/api/stats.php

header('Content-Type: application/json; charset=utf-8');
// Tắt hiển thị lỗi ngoài màn hình
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../config.php';

// Lấy dữ liệu thống kê
$res1 = pg_query($pg, "SELECT COUNT(*) AS cnt FROM products");
$row1 = pg_fetch_assoc($res1);
$totalProducts = intval($row1['cnt']);

$res2 = pg_query($pg, "SELECT COUNT(*) AS cnt FROM products WHERE quantity <= 10");
$row2 = pg_fetch_assoc($res2);
$lowStockCount = intval($row2['cnt']);

$res3 = pg_query($pg, "SELECT COUNT(*) AS cnt FROM categories");
$row3 = pg_fetch_assoc($res3);
$totalCategories = intval($row3['cnt']);

echo json_encode([
    'totalProducts' => $totalProducts,
    'lowStockCount' => $lowStockCount,
    'totalCategories' => $totalCategories
], JSON_UNESCAPED_UNICODE);
exit;
