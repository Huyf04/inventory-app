<?php
// src/api/export_stats_pdf.php

ini_set('display_errors', 0);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../vendor/autoload.php';  // nếu dùng composer

use Dompdf\Dompdf;

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

// Tạo HTML
$html = '
<html>
<head><meta charset="UTF-8"><style>
  body { font-family: DejaVu Sans, sans-serif; }
  table { width:100%; border-collapse: collapse; }
  th, td { border:1px solid #000; padding:8px; text-align:left; }
  th { background-color:#eee; }
</style></head>
<body>
  <h2>Báo cáo thống kê kho vật tư</h2>
  <table>
    <tr><th>Chỉ số</th><th>Giá trị</th></tr>
    <tr><td>Tổng số sản phẩm</td><td>'.$totalProducts.'</td></tr>
    <tr><td>Sản phẩm tồn kho thấp (≤10)</td><td>'.$lowStockCount.'</td></tr>
    <tr><td>Tổng số danh mục</td><td>'.$totalCategories.'</td></tr>
  </table>
</body>
</html>
';

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('report_stats_'.date('Y-m-d').'.pdf', ["Attachment" => true]);
exit;
