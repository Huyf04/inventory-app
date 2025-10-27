<?php
// src/config.php

$pg1 = pg_connect(sprintf(
    "host=%s port=%s dbname=%s user=%s password=%s",
    getenv("DB_HOST"),
    getenv("DB_PORT") ?: 5432,
    getenv("DB_NAME"),
    getenv("DB_USER"),
    getenv("DB_PASS")
));

$pg2 = pg_connect(sprintf(
    "host=%s port=%s dbname=%s user=%s password=%s",
    getenv("DB2_HOST"),
    getenv("DB2_PORT") ?: 5432,
    getenv("DB2_NAME"),
    getenv("DB2_USER"),
    getenv("DB2_PASS")
));

// ✅ Thêm DB3 - Supabase (ở Mỹ)
$pg3 = pg_connect(sprintf(
    "host=%s port=%s dbname=%s user=%s password=%s",
    getenv("DB3_HOST"),
    getenv("DB3_PORT") ?: 5432,
    getenv("DB3_NAME"),
    getenv("DB3_USER"),
    getenv("DB3_PASS")
));

if (!$pg1) die("❌ Không thể kết nối Render DB");
if (!$pg2) echo "⚠️ Không kết nối được Neon DB\n";
if (!$pg3) echo "⚠️ Không kết nối được Supabase DB\n";

function getDBConnection($which = 1) {
    global $pg1, $pg2, $pg3;
    return $which == 3 ? $pg3 : ($which == 2 ? $pg2 : $pg1);
}
?>
