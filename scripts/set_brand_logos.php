<?php
require_once __DIR__ . '/../config/database.php';
try {
    $pdo = getPdoConnection();
    // add column if missing
    $pdo->exec("ALTER TABLE `brands` ADD COLUMN IF NOT EXISTS `logo_path` VARCHAR(255) DEFAULT ''");
} catch (Throwable $e) {
    // Fallback for MySQL versions that don't support IF NOT EXISTS on ALTER
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM `brands`")->fetchAll(PDO::FETCH_ASSOC);
        $has = false;
        foreach ($cols as $c) { if ($c['Field'] === 'logo_path') { $has = true; break; } }
        if (!$has) {
            $pdo->exec("ALTER TABLE `brands` ADD COLUMN `logo_path` VARCHAR(255) DEFAULT ''");
        }
    } catch (Throwable $e2) {
        echo "Could not ensure column: " . $e2->getMessage() . PHP_EOL;
        exit(1);
    }
}
$map = [
    'acer' => 'uploads/brands/acer.svg',
    'apple' => 'uploads/brands/apple.svg',
    'asus' => 'uploads/brands/asus.svg',
    'dell' => 'uploads/brands/dell.svg',
    'hp' => 'uploads/brands/hp.svg',
    'lenovo' => 'uploads/brands/lenovo.svg',
    'msi' => 'uploads/brands/msi.svg',
];
foreach ($map as $slug => $path) {
    // Match by brand_name (case-insensitive)
    $stmt = $pdo->prepare("UPDATE brands SET logo_path = :path WHERE LOWER(brand_name) = :name");
    $stmt->execute(['path' => $path, 'name' => $slug]);
    // Also try names containing the slug (e.g., 'HP' vs 'hp')
    $stmt2 = $pdo->prepare("UPDATE brands SET logo_path = :path WHERE LOWER(brand_name) LIKE :like");
    $stmt2->execute(['path' => $path, 'like' => '%' . $slug . '%']);
}
// Report results
$rows = $pdo->query("SELECT id, brand_name, logo_path FROM brands")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo $r['id'] . "\t" . $r['brand_name'] . "\t" . $r['logo_path'] . PHP_EOL;
}
