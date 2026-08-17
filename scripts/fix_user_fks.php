<?php
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}

require_once __DIR__ . '/../config/database.php';
$pdo = getPdoConnection();
try {
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

    $actions = [
        // table => [dropConstraintName, column]
        'laptops' => ['laptops_ibfk_1', 'user_id'],
        'wishlist' => ['wishlist_ibfk_1', 'user_id'],
        'cart' => ['cart_ibfk_1', 'user_id'],
        'orders' => ['orders_ibfk_1', 'user_id'],
    ];

    foreach ($actions as $table => $info) {
        $constraint = $info[0];
        $column = $info[1];

        // Try drop foreign key if exists
        try {
            $pdo->exec("ALTER TABLE `$table` DROP FOREIGN KEY `$constraint`");
        } catch (Throwable $e) {
            // ignore
        }

        // Add foreign key referencing users(id)
        try {
            $pdo->exec("ALTER TABLE `$table` ADD CONSTRAINT `$constraint` FOREIGN KEY (`$column`) REFERENCES `users`(`id`) ON DELETE CASCADE");
        } catch (Throwable $e) {
            echo "Failed to add FK $constraint on $table($column): " . $e->getMessage() . "\n";
        }
    }

    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    echo "FK fix complete\n";
} catch (Throwable $e) {
    try { $pdo->exec('SET FOREIGN_KEY_CHECKS=1'); } catch (Throwable $_) {}
    echo 'Error: ' . $e->getMessage() . "\n";
    exit(1);
}
