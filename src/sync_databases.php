<?php
// src/sync_databases.php
// Đồng bộ dữ liệu giữa Render, Neon và Supabase

require_once __DIR__ . '/config.php';

// Lấy kết nối
$db1 = getDBConnection(1); // Render
$db2 = getDBConnection(2); // Neon
$db3 = getDBConnection(3); // Supabase (nếu có)

if (!$db1) die("❌ Không thể kết nối DB1 (Render)\n");
if (!$db2) echo "⚠️ Không thể kết nối DB2 (Neon)\n";
if (!$db3) echo "⚠️ Không thể kết nối DB3 (Supabase)\n";

// ====== Danh sách bảng cần sync ======
$tables = ['categories', 'products'];

foreach ($tables as $table) {
    echo "\n🔄 Đang đồng bộ bảng: $table...\n";

    // Lấy toàn bộ dữ liệu từ DB1 (Render)
    $res = pg_query($db1, "SELECT * FROM $table");
    if (!$res) {
        echo "❌ Lỗi khi đọc $table từ DB1: " . pg_last_error($db1) . "\n";
        continue;
    }
    $rows = pg_fetch_all($res) ?: [];

    // Xóa sạch bảng bên DB2 và DB3 (cách đơn giản nhất)
    if ($db2) pg_query($db2, "TRUNCATE TABLE $table RESTART IDENTITY CASCADE");
    if ($db3) pg_query($db3, "TRUNCATE TABLE $table RESTART IDENTITY CASCADE");

    // Ghi lại dữ liệu
    foreach ($rows as $r) {
        $cols = array_keys($r);
        $vals = array_values($r);

        // Tạo câu query insert động
        $placeholders = [];
        for ($i = 1; $i <= count($vals); $i++) {
            $placeholders[] = "\$$i";
        }

        $query = "INSERT INTO $table (" . implode(",", $cols) . ") VALUES (" . implode(",", $placeholders) . ")";
        if ($db2) @pg_query_params($db2, $query, $vals);
        if ($db3) @pg_query_params($db3, $query, $vals);
    }

    echo "✅ Đồng bộ bảng $table hoàn tất. (".count($rows)." bản ghi)\n";
}

echo "\n🎉 Hoàn tất đồng bộ tất cả dữ liệu!\n";
?>
