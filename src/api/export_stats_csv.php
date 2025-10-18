<?php
// src/api/export_stats_csv.php
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="report_stats_'.date('Y-m-d').'.csv"');
ini_set('display_errors', 0);
require_once __DIR__ . '/../config.php';

// Lấy dữ liệu
$res1 = pg_query($pg, "SELECT COUNT(*) AS cnt FROM products");
$row1 = pg_fetch_assoc($res1);
$totalProducts = intval($row1['cnt']);

$res2 = pg_query($pg, "SELECT COUNT(*) AS cnt FROM products WHERE quantity <= 10");
$row2 = pg_fetch_assoc($res2);
$lowStockCount = intval($row2['cnt']);

$res3 = pg_query($pg, "SELECT COUNT(*) AS cnt FROM categories");
$row3 = pg_fetch_assoc($res3);
$totalCategories = intval($row3['cnt']);

// Mở output
$out = fopen('php://output', 'w');
fputcsv($out, ['Chỉ số', 'Giá trị']);
fputcsv($out, ['Tổng số sản phẩm', $totalProducts]);
fputcsv($out, ['Sản phẩm tồn kho thấp (≤10)', $lowStockCount]);
fputcsv($out, ['Tổng số danh mục', $totalCategories]);
fclose($out);
exit;
