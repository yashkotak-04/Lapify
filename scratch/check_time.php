<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getPdoConnection();
$dbTime = $pdo->query('SELECT NOW() as db_time')->fetch(PDO::FETCH_ASSOC)['db_time'];
$phpTime = date('Y-m-d H:i:s');
echo "MySQL NOW(): " . $dbTime . PHP_EOL;
echo "PHP date():  " . $phpTime . PHP_EOL;
