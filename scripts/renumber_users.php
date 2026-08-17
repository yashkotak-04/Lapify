<?php
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}

// Danger: This script renumbers users to contiguous IDs and updates foreign keys.
// Run only after creating a backup. This script is destructive — use with caution.

require_once __DIR__ . '/../config/database.php';

$pdo = getPdoConnection();
# Disable foreign key checks to allow updating FK columns safely
$pdo->exec('SET FOREIGN_KEY_CHECKS=0');

$pdo->beginTransaction();

try {
    // Fetch current users ordered by id
    $users = $pdo->query('SELECT id FROM users ORDER BY id ASC')->fetchAll(PDO::FETCH_COLUMN);
    $mapping = [];
    $next = 1;
    foreach ($users as $oldId) {
        $mapping[$oldId] = $next++;
    }

    if (empty($mapping)) {
        echo "No users to renumber\n";
        $pdo->rollBack();
        exit(0);
    }

    // Create temporary mapping table
    $pdo->exec('CREATE TEMPORARY TABLE tmp_user_map (old_id INT PRIMARY KEY, new_id INT NOT NULL)');
    $stmtInsert = $pdo->prepare('INSERT INTO tmp_user_map (old_id, new_id) VALUES (:old, :new)');
    foreach ($mapping as $old => $new) {
        $stmtInsert->execute(['old' => $old, 'new' => $new]);
    }

    // Update FK references in dependent tables (laptops, wishlist, cart, orders)
    $refs = [
        ['table' => 'laptops', 'col' => 'user_id'],
        ['table' => 'wishlist', 'col' => 'user_id'],
        ['table' => 'cart', 'col' => 'user_id'],
        ['table' => 'orders', 'col' => 'user_id'],
    ];

    foreach ($refs as $r) {
        $sql = "UPDATE `{$r['table']}` t JOIN tmp_user_map m ON t.`{$r['col']}` = m.old_id SET t.`{$r['col']}` = m.new_id";
        $pdo->exec($sql);
    }

    // Update users PKs in safe way: create temp table, copy rows with new ids
    $pdo->exec('CREATE TABLE users_new LIKE users');
    // Ensure users_new has no AUTO_INCREMENT collision
    $pdo->exec('ALTER TABLE users_new AUTO_INCREMENT = 1');

    // Copy rows into users_new with new ids
    $cols = $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_COLUMN);
    $colsList = implode(', ', array_map(function($c){ return "`$c`"; }, $cols));

    $selectCols = [];
    foreach ($cols as $c) {
        if ($c === 'id') {
            $selectCols[] = 'm.new_id AS id';
        } else {
            $selectCols[] = 'u.`' . $c . '`';
        }
    }
    $select = implode(', ', $selectCols);

    $pdo->exec("INSERT INTO users_new ($colsList) SELECT $select FROM users u JOIN tmp_user_map m ON u.id = m.old_id ORDER BY m.new_id ASC");

    // Swap tables
    $pdo->exec('RENAME TABLE users TO users_old, users_new TO users');

    // Drop old table
    $pdo->exec('DROP TABLE users_old');

    // Clean up temp mapping
    $pdo->exec('DROP TEMPORARY TABLE IF EXISTS tmp_user_map');

    // Reset AUTO_INCREMENT
    $maxId = $pdo->query('SELECT MAX(id) FROM users')->fetchColumn();
    $nextAi = $maxId + 1;
    $pdo->exec('ALTER TABLE users AUTO_INCREMENT = ' . intval($nextAi));

    $pdo->commit();

    // Re-enable foreign key checks
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');

    echo "Renumber complete. Next AUTO_INCREMENT={$nextAi}\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Try to re-enable FK checks even on failure
    try { $pdo->exec('SET FOREIGN_KEY_CHECKS=1'); } catch (Throwable $_) {}
    echo "Error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
