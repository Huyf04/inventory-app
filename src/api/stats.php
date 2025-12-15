<?php
// src/api/stats.php

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);  // Tắt hiển thị lỗi trên production
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/stats.log');  // Log vào file (tạo thư mục logs/ nếu cần)

require_once __DIR__ . '/../config.php';

// Hàm helper để log và trả về lỗi JSON
function returnError($message, $code = 500) {
    error_log("Stats API Error: $message");  // Log chi tiết
    http_response_code($code);
    echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

// Chọn DB: Mặc định DB1 (Render), hoặc từ tham số ?db=2 (Neon)
$dbChoice = isset($_GET['db']) ? intval($_GET['db']) : 1;
if ($dbChoice !== 1 && $dbChoice !== 2) $dbChoice = 1;

try {
    $pg = getDBConnection($dbChoice);
    if (!$pg || pg_connection_status($pg) !== PGSQL_CONNECTION_OK) {
        returnError('Không thể kết nối đến cơ sở dữ liệu (DB' . $dbChoice . '). Kiểm tra credentials.', 500);
    }
    error_log("Connected to DB$dbChoice successfully.");  // Log thành công

    // Lấy tham số low_stock
    $lowStockThreshold = isset($_GET['low_stock']) ? intval($_GET['low_stock']) : 10;
    if ($lowStockThreshold < 0) $lowStockThreshold = 10;

    // Truy vấn tổng sản phẩm
    $res1 = pg_query($pg, "SELECT COUNT(*) AS cnt FROM products");
    if (!$res1) returnError('Lỗi truy vấn tổng sản phẩm: ' . pg_last_error($pg));
    $row1 = pg_fetch_assoc($res1);
    $totalProducts = intval($row1['cnt'] ?? 0);

    // Truy vấn tồn kho thấp
    $query2 = "SELECT COUNT(*) AS cnt FROM products WHERE quantity <= $1";
    $res2 = pg_query_params($pg, $query2, [$lowStockThreshold]);
    if (!$res2) returnError('Lỗi truy vấn tồn kho thấp: ' . pg_last_error($pg));
    $row2 = pg_fetch_assoc($res2);
    $lowStockCount = intval($row2['cnt'] ?? 0);

    // Truy vấn tổng danh mục
    $res3 = pg_query($pg, "SELECT COUNT(*) AS cnt FROM categories");
    if (!$res3) returnError('Lỗi truy vấn danh mục: ' . pg_last_error($pg));
    $row3 = pg_fetch_assoc($res3);
    $totalCategories = intval($row3['cnt'] ?? 0);

    // Trả về JSON (loại bỏ totalValue)
    echo json_encode([
        'totalProducts' => $totalProducts,
        'lowStockCount' => $lowStockCount,
        'totalCategories' => $totalCategories,
        'lowStockThreshold' => $lowStockThreshold,
        'dbUsed' => 'DB' . $dbChoice
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    returnError('Lỗi không mong muốn: ' . $e->getMessage());
}
exit;
