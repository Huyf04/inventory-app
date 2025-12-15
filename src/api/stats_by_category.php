<?php
// src/api/stats_by_category.php

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);  // Tắt hiển thị lỗi trên production
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/stats_by_category.log');  // Log vào file (tạo thư mục logs/ nếu cần)

require_once __DIR__ . '/../config.php';

// Hàm helper để log và trả về lỗi JSON
function returnError($message, $code = 500) {
    error_log("Stats by Category API Error: $message");  // Log chi tiết
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
    error_log("Connected to DB$dbChoice successfully for stats_by_category.");  // Log thành công

    // Truy vấn: đếm số sản phẩm mỗi danh mục
    $res = pg_query($pg,
        "SELECT c.name AS category_name, COUNT(p.id) AS count
         FROM categories c
         LEFT JOIN products p ON p.category_id = c.id
         GROUP BY c.name
         ORDER BY count DESC"
    );
    if (!$res) {
        returnError('Lỗi truy vấn thống kê theo danh mục: ' . pg_last_error($pg));
    }

    $rows = pg_fetch_all($res) ?: [];
    echo json_encode($rows, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    returnError('Lỗi không mong muốn: ' . $e->getMessage());
}
exit;
