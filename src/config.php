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
// Kiểm tra kết nối
// -------------------
if (!$pg1) die("❌ Không thể kết nối Render DB\n");
if (!$pg2) error_log("⚠️ Không kết nối được Neon DB: " . pg_last_error());

// -------------------
// Hàm chọn DB
// -------------------
function getDBConnection($which = 1) {
    global $pg1, $pg2;
    return ($which == 2) ? $pg2 : $pg1;
}

?>
