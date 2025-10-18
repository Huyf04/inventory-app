<?php
// src/api/stats_by_category.php

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../config.php';

// Truy vấn: đếm số sản phẩm mỗi danh mục
$res = pg_query($pg,
    "SELECT c.name AS category_name, COUNT(p.id) AS count
     FROM categories c
     LEFT JOIN products p ON p.category_id = c.id
     GROUP BY c.name
     ORDER BY count DESC"
);
if (!$res) {
    http_response_code(500);
    echo json_encode(['error' => pg_last_error($pg)]);
    exit;
}

$rows = pg_fetch_all($res) ?: [];
echo json_encode($rows, JSON_UNESCAPED_UNICODE);
exit;
