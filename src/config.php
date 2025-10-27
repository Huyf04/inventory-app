<?php
// src/config.php

// -------------------
// DB1 - Render (chính)
// -------------------
$pg1 = pg_connect(sprintf(
    "host=%s port=%s dbname=%s user=%s password=%s sslmode=require",
    getenv("DB_HOST"),
    getenv("DB_PORT") ?: 5432,
    getenv("DB_NAME"),
    getenv("DB_USER"),
    getenv("DB_PASS")
));

// -------------------
// DB2 - Neon (phụ)
// -------------------
$pg2 = pg_connect(sprintf(
    "host=%s port=%s dbname=%s user=%s password=%s sslmode=require",
    getenv("DB2_HOST"),
    getenv("DB2_PORT") ?: 5432,
    getenv("DB2_NAME"),
    getenv("DB2_USER"),
    getenv("DB2_PASS")
));

// -------------------
// DB3 - Supabase (US)
// -------------------
$pg3 = pg_connect(sprintf(
    "host=%s port=%s dbname=%s user=%s password=%s sslmode=require",
    getenv("DB3_HOST"),
    getenv("DB3_PORT") ?: 5432,
    getenv("DB3_NAME"),
    getenv("DB3_USER"),
    getenv("DB3_PASS")
));

// -------------------
// Kiểm tra kết nối
// -------------------
if (!$pg1) die("❌ Không thể kết nối Render DB\n");
if (!$pg2) error_log("⚠️ Không kết nối được Neon DB: " . pg_last_error());
if (!$pg3) error_log("⚠️ Không kết nối được Supabase DB: " . pg_last_error());

// -------------------
// Hàm chọn DB
// -------------------
function getDBConnection($which = 1) {
    global $pg1, $pg2, $pg3;
    if ($which == 3) return $pg3;
    if ($which == 2) return $pg2;
    return $pg1;
}
if ($pg3) {
    echo "✅ Supabase connected successfully";
} else {
    echo "❌ Supabase connection failed";
}

?>
