<?php
// -------------------
// Kết nối DB1 - Render (Singapore)
// -------------------
$pg1 = pg_connect(sprintf(
    "host=%s port=%s dbname=%s user=%s password=%s",
    getenv("DB_HOST"),
    getenv("DB_PORT") ?: 5432,
    getenv("DB_NAME"),
    getenv("DB_USER"),
    getenv("DB_PASS")
));

// -------------------
// Kết nối DB2 - Neon (Tokyo hoặc Mỹ)
// -------------------
$pg2 = pg_connect(sprintf(
    "host=%s port=%s dbname=%s user=%s password=%s",
    getenv("DB2_HOST"),
    getenv("DB2_PORT") ?: 5432,
    getenv("DB2_NAME"),
    getenv("DB2_USER"),
    getenv("DB2_PASS")
));

// Kiểm tra kết nối
if (!$pg1) {
    die("❌ Kết nối Render DB thất bại: " . pg_last_error());
}
if (!$pg2) {
    echo "⚠️ Cảnh báo: Không kết nối được Neon DB (DB2)\n";
}

// Hàm chọn kết nối theo vùng hoặc mục đích
function getDBConnection($which = 1) {
    global $pg1, $pg2;
    return $which == 2 ? $pg2 : $pg1;
}
?>
