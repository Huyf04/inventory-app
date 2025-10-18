<?php
// Không có dòng trống hay ký tự nào TRƯỚC <?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Xóa toàn bộ output buffer trước khi tạo PDF
if (ob_get_length()) {
    ob_end_clean();
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';
use Mpdf\Mpdf;


try {
    // Tạo đối tượng mPDF
    $mpdf = new Mpdf();

    // Lấy dữ liệu tổng hợp
    $statsQuery = $con->query("
        SELECT 
            (SELECT COUNT(*) FROM products) AS totalProducts,
            (SELECT COUNT(*) FROM categories) AS totalCategories,
            (SELECT COUNT(*) FROM products WHERE quantity < 10) AS lowStockCount
    ");
    $stats = $statsQuery->fetch_assoc();

    // Lấy thống kê sản phẩm theo danh mục
    $categoryStats = $con->query("
        SELECT c.name AS category_name, COUNT(p.id) AS product_count
        FROM categories c
        LEFT JOIN products p ON p.category_id = c.id
        GROUP BY c.id
    ");

    // Nội dung HTML
    $html = '
    <h2 style="text-align:center;">📊 Báo cáo thống kê kho vật tư</h2>
    <p><b>Ngày tạo:</b> ' . date('d/m/Y H:i') . '</p>
    <hr>
    <h3>I. Tổng quan</h3>
    <table border="1" cellspacing="0" cellpadding="6" width="100%">
        <tr>
            <th>Tổng số sản phẩm</th>
            <th>Tổng số danh mục</th>
            <th>Sản phẩm tồn kho thấp (&lt;10)</th>
        </tr>
        <tr>
            <td align="center">' . $stats['totalProducts'] . '</td>
            <td align="center">' . $stats['totalCategories'] . '</td>
            <td align="center">' . $stats['lowStockCount'] . '</td>
        </tr>
    </table>
    <br>
    <h3>II. Sản phẩm theo danh mục</h3>
    <table border="1" cellspacing="0" cellpadding="6" width="100%">
        <tr>
            <th>Danh mục</th>
            <th>Số lượng sản phẩm</th>
        </tr>
    ';

    while ($row = $categoryStats->fetch_assoc()) {
        $html .= '
        <tr>
            <td>' . htmlspecialchars($row['category_name']) . '</td>
            <td align="center">' . $row['product_count'] . '</td>
        </tr>';
    }

    $html .= '</table><br><br><i>Hệ thống thống kê kho vật tư</i>';

    // Ghi nội dung ra PDF
    $mpdf->WriteHTML($html);

    // Gửi về trình duyệt
    $mpdf->Output('thongke_kho.pdf', 'I'); // 'I' = inline view, 'D' = download

} catch (Exception $e) {
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Lỗi khi xuất PDF: ' . $e->getMessage();
}
