<?php
ini_set('display_errors', 0);
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

use Mpdf\Mpdf;

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="thong_ke_kho.pdf"');

// Lấy dữ liệu thống kê
$totalProducts = 0;
$lowStockCount = 0;
$totalCategories = 0;

$result = $con->query("SELECT COUNT(*) AS total FROM products");
if ($row = $result->fetch_assoc()) {
  $totalProducts = $row['total'];
}

$result = $con->query("SELECT COUNT(*) AS total FROM products WHERE quantity < 5");
if ($row = $result->fetch_assoc()) {
  $lowStockCount = $row['total'];
}

$result = $con->query("SELECT COUNT(*) AS total FROM categories");
if ($row = $result->fetch_assoc()) {
  $totalCategories = $row['total'];
}

// Tạo nội dung PDF
$html = '
<h1 style="text-align:center;">BÁO CÁO THỐNG KÊ KHO VẬT TƯ</h1>
<p><strong>Ngày tạo:</strong> ' . date('d/m/Y H:i') . '</p>
<hr>
<table border="1" cellspacing="0" cellpadding="8" width="100%">
  <tr style="background-color:#f0f0f0;">
    <th>Chỉ số</th>
    <th>Giá trị</th>
  </tr>
  <tr><td>Tổng số sản phẩm</td><td>' . $totalProducts . '</td></tr>
  <tr><td>Sản phẩm tồn kho thấp (&lt;5)</td><td>' . $lowStockCount . '</td></tr>
  <tr><td>Tổng số danh mục</td><td>' . $totalCategories . '</td></tr>
</table>
<br><p style="text-align:center;">--- Hết báo cáo ---</p>
';

try {
    $mpdf = new Mpdf();
    $mpdf->WriteHTML($html);
    $mpdf->Output();
} catch (Exception $e) {
    echo 'Lỗi khi tạo PDF: ' . $e->getMessage();
}
