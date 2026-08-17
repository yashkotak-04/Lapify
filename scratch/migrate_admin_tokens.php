<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getPdoConnection();
$pdo->exec("ALTER TABLE admins ADD COLUMN IF NOT EXISTS reset_token VARCHAR(255) DEFAULT NULL, ADD COLUMN IF NOT EXISTS reset_expires DATETIME DEFAULT NULL");
$cols = $pdo->query('SHOW COLUMNS FROM admins')->fetchAll(PDO::FETCH_COLUMN);
echo "UPDATED ADMIN COLS: " . json_encode($cols) . PHP_EOL;
