<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getPdoConnection();
$colsAdmin = $pdo->query('SHOW COLUMNS FROM admins')->fetchAll(PDO::FETCH_COLUMN);
echo "ADMIN COLS: " . json_encode($colsAdmin) . PHP_EOL;

$colsUser = $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_COLUMN);
echo "USER COLS: " . json_encode($colsUser) . PHP_EOL;
