<?php
session_start();  // Thêm dòng này ở đầu (hỗ trợ sessions cho auth)
$pg = pg_connect(sprintf(
    "host=%s port=%s dbname=%s user=%s password=%s",
    getenv("DB_HOST"),
    getenv("DB_PORT") ?: 5432,
    getenv("DB_NAME"),
    getenv("DB_USER"),
    getenv("DB_PASS")
));

if (!$pg) {
    die("Connection failed: " . pg_last_error());
}

