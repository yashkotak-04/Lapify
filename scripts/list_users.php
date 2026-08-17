<?php
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}

require_once __DIR__ . '/../config/database.php';
$pdo = getPdoConnection();
$rows = $pdo->query('SELECT id, email FROM users ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo $r['id'] . ' - ' . $r['email'] . "\n";
}
