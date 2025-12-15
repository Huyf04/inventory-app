<?php
// src/api/stats_top5.php

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);  // Tắt hiển thị lỗi trên production
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/stats_top5.log');  // Log vào file (tạo thư mục logs/ nếu cần)

require_once __DIR__ . '/../config.php';

// Hàm helper để log và trả về lỗi JSON
function returnError($message, $code = 500) {
    error_log("Stats Top 5 API Error: $message");  // Log chi tiết
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
    error_log("Connected to DB$dbChoice successfully for stats_top5.");  // Log thành công

    // Truy vấn: lấy Top 5 sản phẩm theo số lượng tồn nhiều nhất
    $res = pg_query($pg,
        "SELECT name, quantity
         FROM products
         ORDER BY quantity DESC
         LIMIT 5"
    );
    if (!$res) {
        returnError('Lỗi truy vấn Top 5 sản phẩm: ' . pg_last_error($pg));
    }

    $rows = pg_fetch_all($res) ?: [];
    echo json_encode($rows, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    returnError('Lỗi không mong muốn: ' . $e->getMessage());
}
exit;
