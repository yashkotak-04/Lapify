<?php
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}

require_once __DIR__ . '/../config/database.php';
$pdo = getPdoConnection();
$tables = ['users','laptops','wishlist','cart','orders','order_items'];
foreach ($tables as $t) {
    echo "TABLE: $t\n";
    $row = $pdo->query("SHOW CREATE TABLE `$t`")->fetch(PDO::FETCH_ASSOC);
    echo $row['Create Table'] . "\n\n";
}
