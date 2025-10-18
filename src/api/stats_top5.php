<?php
// src/api/stats_top5.php

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../config.php';

// Truy vấn: lấy Top 5 sản phẩm theo số lượng tồn nhiều nhất
$res = pg_query($pg,
    "SELECT name, quantity
     FROM products
     ORDER BY quantity DESC
     LIMIT 5"
);
if (!$res) {
    http_response_code(500);
    echo json_encode(['error' => pg_last_error($pg)]);
    exit;
}

$rows = pg_fetch_all($res) ?: [];
echo json_encode($rows, JSON_UNESCAPED_UNICODE);
exit;
