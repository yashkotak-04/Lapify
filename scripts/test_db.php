<?php
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}

require_once __DIR__ . '/../config/database.php';

echo "Testing DB connection and user insert (transaction, will rollback)...\n";
try {
    echo "Connecting...\n";
    $pdo = getPdoConnection();
    echo "Connected. Ensuring schema...\n";
    ensureAuthSchema($pdo);
    echo "Schema ensured. Querying users columns...\n";
    // Show current users table columns for debugging
    $cols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll();
    echo "Current users table columns:\n";
    foreach ($cols as $c) {
        echo " - " . $c['Field'] . " (" . $c['Type'] . ")\n";
    }
    echo "Beginning transaction and inserting test user...\n";
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('INSERT INTO users (full_name, email, mobile, password, role, status) VALUES (:full_name, :email, :mobile, :password, :role, :status)');
    $stmt->execute([
        'full_name' => 'Test User',
        'email' => 'test+db-check@example.com',
        'mobile' => '9999999999',
        'password' => password_hash('TempPass123', PASSWORD_DEFAULT),
        'role' => 'user',
        'status' => 'active'
    ]);
    $pdo->rollBack();
    echo "DB insert succeeded (rolled back).\n";
} catch (Throwable $e) {
    echo "DB test failed: " . $e->getMessage() . "\n";
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

?>
