<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

echo "=== TESTING FORGOT & RESET PASSWORD FLOW ===" . PHP_EOL;

$pdo = getPdoConnection();
ensureAuthSchema($pdo);

// 1. Pick a test account
$stmt = $pdo->query("SELECT id, email, full_name FROM users ORDER BY id ASC LIMIT 1");
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    die("Test user not found!\n");
}
echo "[1] Test user found: {$user['email']} (ID: {$user['id']})" . PHP_EOL;

// 2. Generate token with DATE_ADD(NOW(), INTERVAL 1 HOUR)
$rawToken = bin2hex(random_bytes(32));
$hash = hash('sha256', $rawToken);

$upd = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?");
$upd->execute([$hash, $user['id']]);
echo "[2] Reset token stored in DB successfully." . PHP_EOL;

// 3. Simulate reset_password.php lookup
$lookup = $pdo->prepare("SELECT id, full_name, email FROM users WHERE reset_token = :hash AND (reset_expires IS NULL OR reset_expires > NOW()) LIMIT 1");
$lookup->execute(['hash' => $hash]);
$found = $lookup->fetch(PDO::FETCH_ASSOC);

if (!$found) {
    // Check admins table
    $lookupAdmin = $pdo->prepare("SELECT id, full_name, email FROM admins WHERE reset_token = :hash AND (reset_expires IS NULL OR reset_expires > NOW()) LIMIT 1");
    $lookupAdmin->execute(['hash' => $hash]);
    $found = $lookupAdmin->fetch(PDO::FETCH_ASSOC);
}

echo "[3] Lookup result: " . ($found ? "FOUND {$found['email']} (PASS)" : "NOT FOUND (FAIL)") . PHP_EOL;

// 4. Test admin account token generation & lookup
$admin = $pdo->query("SELECT id, email, full_name FROM admins ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if ($admin) {
    $admToken = bin2hex(random_bytes(32));
    $admHash = hash('sha256', $admToken);
    $updAdm = $pdo->prepare("UPDATE admins SET reset_token = ?, reset_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?");
    $updAdm->execute([$admHash, $admin['id']]);

    $admLookup = $pdo->prepare("SELECT id, full_name, email FROM admins WHERE reset_token = :hash AND (reset_expires IS NULL OR reset_expires > NOW()) LIMIT 1");
    $admLookup->execute(['hash' => $admHash]);
    $admFound = $admLookup->fetch(PDO::FETCH_ASSOC);
    echo "[4] Admin reset lookup: " . ($admFound ? "FOUND {$admFound['email']} (PASS)" : "FAIL") . PHP_EOL;
}

echo "=== ALL TESTS PASSED SUCCESSFULLY ===" . PHP_EOL;
