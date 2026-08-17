<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getPdoConnection();
$u = $pdo->query('SELECT id, full_name, email FROM users')->fetchAll(PDO::FETCH_ASSOC);
echo "USERS:\n";
foreach ($u as $row) {
    echo "ID: {$row['id']} | Name: {$row['full_name']} | Email: {$row['email']}\n";
}
$a = $pdo->query('SELECT id, full_name, email FROM admins')->fetchAll(PDO::FETCH_ASSOC);
echo "\nADMINS:\n";
foreach ($a as $row) {
    echo "ID: {$row['id']} | Name: {$row['full_name']} | Email: {$row['email']}\n";
}
