<?php
// config/database.php - Database bootstrap for Lapify

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'lapify');

mysqli_report(MYSQLI_REPORT_OFF);

$conn = null;

function connectWithMysqli() {
    global $conn;
    if ($conn !== null) {
        return $conn;
    }

    $conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$conn) {
        $conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS);
        if ($conn) {
            mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            mysqli_select_db($conn, DB_NAME);
        }
    }

    if ($conn) {
        mysqli_set_charset($conn, 'utf8mb4');
    }

    return $conn;
}

function getDbConnection() {
    return connectWithMysqli();
}

function getPdoConnection() {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    try {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';charset=utf8mb4';
            $fallback = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $fallback->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $inner) {
            error_log('Database connection failed: ' . $inner->getMessage());
            throw $inner;
        }
    }

    return $pdo;
}

function ensureTableColumn(PDO $pdo, string $table, string $column, string $definition): void {
    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $safeColumn = preg_replace('/[^a-zA-Z0-9_]/', '', $column);

    if ($safeTable === '' || $safeColumn === '') {
        return;
    }

    $stmt = $pdo->query("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = '{$safeTable}' AND column_name = '{$safeColumn}' LIMIT 1");
    if ($stmt && $stmt->fetch()) {
        return;
    }

    $pdo->exec("ALTER TABLE `{$safeTable}` ADD COLUMN `{$safeColumn}` {$definition}");
}

function columnExists(PDO $pdo, string $table, string $column): bool {
    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $safeColumn = preg_replace('/[^a-zA-Z0-9_]/', '', $column);

    if ($safeTable === '' || $safeColumn === '') {
        return false;
    }

    $stmt = $pdo->query("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = '{$safeTable}' AND column_name = '{$safeColumn}' LIMIT 1");
    return $stmt ? (bool) $stmt->fetch() : false;
}

function ensureColumnRename(PDO $pdo, string $table, string $oldColumn, string $newColumn, string $definition): void {
    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $safeOld = preg_replace('/[^a-zA-Z0-9_]/', '', $oldColumn);
    $safeNew = preg_replace('/[^a-zA-Z0-9_]/', '', $newColumn);

    if ($safeTable === '' || $safeOld === '' || $safeNew === '') {
        return;
    }

    if (columnExists($pdo, $table, $newColumn)) {
        return;
    }

    if (columnExists($pdo, $table, $oldColumn)) {
        try {
            $pdo->exec("ALTER TABLE `{$safeTable}` CHANGE COLUMN `{$safeOld}` `{$safeNew}` {$definition}");
        } catch (Throwable $e) {
            error_log('Column rename failed: ' . $e->getMessage());
        }
        return;
    }

    ensureTableColumn($pdo, $table, $newColumn, $definition);
}

/**
 * Seed the brand_models table with the 2024–2026 model lineup for all 7 brands.
 * Only runs if the table is empty, so it's safe to call on every page load.
 */
function seedBrandModelsIfEmpty(PDO $pdo): void {
    try {
        $count = (int) $pdo->query("SELECT COUNT(*) FROM brand_models")->fetchColumn();
        if ($count > 0) {
            return;
        }

        $models = [
            // Apple (brand_id = 1)
            [1, 'MacBook Air 13" M3', 2024], [1, 'MacBook Air 15" M3', 2024],
            [1, 'MacBook Pro 14" M3 Pro', 2024], [1, 'MacBook Pro 16" M3 Max', 2024],
            [1, 'MacBook Air 13" M4', 2025], [1, 'MacBook Air 15" M4', 2025],
            [1, 'MacBook Pro 14" M4 Pro', 2025], [1, 'MacBook Pro 16" M4 Max', 2025],
            [1, 'MacBook Air 13" M5', 2026], [1, 'MacBook Pro 14" M5 Pro', 2026],
            // Dell (brand_id = 2)
            [2, 'Dell XPS 13 9340', 2024], [2, 'Dell XPS 14 9440', 2024],
            [2, 'Dell XPS 16 9640', 2024], [2, 'Dell Inspiron 14 5440', 2024],
            [2, 'Dell Latitude 7450', 2024], [2, 'Dell XPS 13 9350', 2025],
            [2, 'Dell XPS 16 9650', 2025], [2, 'Dell Inspiron 15 5545', 2025],
            [2, 'Dell Precision 5680', 2025], [2, 'Dell XPS 13 9360', 2026],
            [2, 'Dell Inspiron 16 7640', 2026],
            // HP (brand_id = 3)
            [3, 'HP Spectre x360 14', 2024], [3, 'HP Spectre x360 16', 2024],
            [3, 'HP Envy x360 15', 2024], [3, 'HP Pavilion 15', 2024],
            [3, 'HP Omen 16', 2024], [3, 'HP Spectre x360 14 2025', 2025],
            [3, 'HP Envy 16', 2025], [3, 'HP Pavilion Plus 14', 2025],
            [3, 'HP Omen Transcend 16', 2025], [3, 'HP Spectre Fold 17', 2026],
            [3, 'HP Envy x360 14', 2026],
            // Lenovo (brand_id = 4)
            [4, 'Lenovo ThinkPad X1 Carbon Gen 12', 2024], [4, 'Lenovo ThinkPad T14s Gen 5', 2024],
            [4, 'Lenovo Yoga 9i 14', 2024], [4, 'Lenovo Legion 5 Pro 16', 2024],
            [4, 'Lenovo IdeaPad Slim 5', 2024], [4, 'Lenovo ThinkPad X1 Carbon Gen 13', 2025],
            [4, 'Lenovo Yoga Slim 7x', 2025], [4, 'Lenovo Legion 7i 16', 2025],
            [4, 'Lenovo ThinkBook 14 Gen 7', 2025], [4, 'Lenovo ThinkPad X1 Nano Gen 4', 2026],
            [4, 'Lenovo Yoga Pro 9i', 2026],
            // Asus (brand_id = 5)
            [5, 'Asus ROG Zephyrus G14', 2024], [5, 'Asus ROG Zephyrus G16', 2024],
            [5, 'Asus Zenbook 14 OLED', 2024], [5, 'Asus Vivobook 16', 2024],
            [5, 'Asus TUF Gaming A16', 2024], [5, 'Asus ROG Strix Scar 18', 2025],
            [5, 'Asus Zenbook S 16', 2025], [5, 'Asus Vivobook S 15', 2025],
            [5, 'Asus ROG Flow Z13', 2025], [5, 'Asus ROG Zephyrus G14 2026', 2026],
            [5, 'Asus Zenbook Duo 14', 2026],
            // Acer (brand_id = 6)
            [6, 'Acer Swift Go 14', 2024], [6, 'Acer Swift X 14', 2024],
            [6, 'Acer Aspire 5', 2024], [6, 'Acer Predator Helios 16', 2024],
            [6, 'Acer Nitro V 15', 2024], [6, 'Acer Swift Go 14 2025', 2025],
            [6, 'Acer Aspire Vero 16', 2025], [6, 'Acer Predator Helios Neo 16', 2025],
            [6, 'Acer Nitro 16', 2025], [6, 'Acer Swift Edge 16', 2026],
            [6, 'Acer Predator Triton 14', 2026],
            // MSI (brand_id = 7)
            [7, 'MSI Stealth 14 Studio', 2024], [7, 'MSI Stealth 16 Studio', 2024],
            [7, 'MSI Raider GE68 HX', 2024], [7, 'MSI Katana 15', 2024],
            [7, 'MSI Prestige 16 AI', 2024], [7, 'MSI Stealth 18 AI Studio', 2025],
            [7, 'MSI Raider 18 HX', 2025], [7, 'MSI Titan 18 HX', 2025],
            [7, 'MSI Cyborg 15', 2025], [7, 'MSI Stealth 16 AI Studio', 2026],
            [7, 'MSI Creator Z16 HX', 2026],
        ];

        $stmt = $pdo->prepare("INSERT INTO brand_models (brand_id, model_name, year) VALUES (?, ?, ?)");
        $pdo->beginTransaction();
        foreach ($models as $m) {
            $stmt->execute([$m[0], $m[1], $m[2]]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('brand_models auto-seed failed: ' . $e->getMessage());
    }
}

function seedLaptopsIfEmpty(PDO $pdo): void {
    try {
        // 1. Ensure brands 1..7 exist and are active
        $pdo->exec("INSERT INTO brands (id, brand_name, status) VALUES
            (1, 'Apple', 'active'), (2, 'Dell', 'active'), (3, 'HP', 'active'),
            (4, 'Lenovo', 'active'), (5, 'Asus', 'active'), (6, 'Acer', 'active'), (7, 'MSI', 'active')
            ON DUPLICATE KEY UPDATE brand_name=VALUES(brand_name), status='active'");

        // 2. Ensure brand models exist
        if (function_exists('seedBrandModelsIfEmpty')) {
            seedBrandModelsIfEmpty($pdo);
        }

        // 3. Ensure test users exist
        $userPass = password_hash('password123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO users (id, full_name, email, phone, password, role, status) VALUES 
            (2, 'Alex Johnson', 'alex@example.com', '+1 (555) 234-5678', '{$userPass}', 'user', 'active'),
            (3, 'Sarah Connor', 'sarah@example.com', '+1 (555) 876-5432', '{$userPass}', 'user', 'active')
            ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), password=VALUES(password)");

        // 4. Auto-approve any pending laptops so catalog displays immediately
        $pdo->exec("UPDATE laptops SET status = 'approved', approval_status = 'approved' WHERE status = 'pending' OR approval_status = 'pending'");

        $count = (int) $pdo->query("SELECT COUNT(*) FROM laptops")->fetchColumn();
        if ($count === 0) {
            $sqlPath = __DIR__ . '/../schema.sql';
            if (file_exists($sqlPath)) {
                $sql = file_get_contents($sqlPath);
                if (preg_match('/INSERT INTO `laptops`.*?;/s', $sql, $matches)) {
                    $insertSql = str_replace("'pending', 'pending'", "'approved', 'approved'", $matches[0]);
                    $pdo->exec($insertSql);
                }
            }
        }
    } catch (Throwable $e) {
        error_log('laptops auto-seed failed: ' . $e->getMessage());
    }
}

define('SCHEMA_VERSION', 9);


/**
 * Read the current applied schema version from the meta table.
 * Used so the heavy schema bootstrap only runs once (or on an upgrade),
 * not on every single page load — this was the main localhost slowdown.
 */
function getSchemaVersion(PDO $pdo): int {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS schema_meta (meta_key VARCHAR(50) PRIMARY KEY, meta_value VARCHAR(255) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $stmt = $pdo->query("SELECT meta_value FROM schema_meta WHERE meta_key = 'schema_version' LIMIT 1");
        $row = $stmt->fetch();
        return $row ? (int)$row['meta_value'] : 0;
    } catch (Throwable $e) {
        return 0;
    }
}

function ensureAuthSchema(PDO $pdo): void {
    // Fast-path: schema already fully migrated → skip all CREATE/ALTER/seed
    // queries entirely. This is what makes page loads fast on localhost.
    if (getSchemaVersion($pdo) >= SCHEMA_VERSION) {
        return;
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(100) NOT NULL DEFAULT '',
            email VARCHAR(150) NOT NULL UNIQUE,
            phone VARCHAR(20) DEFAULT NULL,
            password VARCHAR(255) NOT NULL,
            gender VARCHAR(20) DEFAULT NULL,
            dob DATE DEFAULT NULL,
            profile_image VARCHAR(255) DEFAULT NULL,
            role VARCHAR(25) NOT NULL DEFAULT 'user',
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            remember_token VARCHAR(255) DEFAULT NULL,
            remember_expiry DATETIME DEFAULT NULL,
            reset_token VARCHAR(255) DEFAULT NULL,
            reset_expires DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) DEFAULT NULL UNIQUE,
            full_name VARCHAR(100) NOT NULL DEFAULT '',
            email VARCHAR(150) NOT NULL UNIQUE,
            phone VARCHAR(20) DEFAULT NULL,
            password VARCHAR(255) NOT NULL,
            profile_image VARCHAR(255) DEFAULT NULL,
            secret_key VARCHAR(255) NOT NULL DEFAULT '',
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS brands (
            id INT AUTO_INCREMENT PRIMARY KEY,
            brand_name VARCHAR(100) NOT NULL UNIQUE,
            status ENUM('active','inactive') DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS brand_models (
            id INT AUTO_INCREMENT PRIMARY KEY,
            brand_id INT NOT NULL,
            model_name VARCHAR(150) NOT NULL,
            year YEAR DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_brand_model (brand_id, model_name),
            FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS laptops (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `user_id` INT NOT NULL,
          `brand_id` INT NOT NULL,
          `type` ENUM('New','Old') NOT NULL,
          `condition_type` ENUM('new','old') NOT NULL DEFAULT 'old',
          `model` VARCHAR(100) NOT NULL,
          `processor` VARCHAR(100) DEFAULT NULL,
          `ram` VARCHAR(20) DEFAULT NULL,
          `storage` VARCHAR(20) DEFAULT NULL,
          `condition` VARCHAR(50) DEFAULT NULL,
          `price` DECIMAL(10,2) NOT NULL,
          `description` TEXT DEFAULT NULL,
          `image` VARCHAR(255) DEFAULT NULL,
          `quantity` INT NOT NULL DEFAULT 5,
          `stock_quantity` INT NOT NULL DEFAULT 1,
          `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
          `approval_status` VARCHAR(20) NOT NULL DEFAULT 'pending',
          `rejection_reason` TEXT DEFAULT NULL,
          `reviewed_by` INT DEFAULT NULL,
          `reviewed_at` DATETIME DEFAULT NULL,
          `approved_by` INT DEFAULT NULL,
          `approved_at` DATETIME DEFAULT NULL,
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
          FOREIGN KEY (`brand_id`) REFERENCES `brands`(`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS wishlist (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            laptop_id INT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_wish (user_id, laptop_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (laptop_id) REFERENCES laptops(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS cart (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            laptop_id INT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_cart_item (user_id, laptop_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (laptop_id) REFERENCES laptops(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_number VARCHAR(30) NOT NULL UNIQUE,
            user_id INT NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL,
            status ENUM('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            laptop_id INT NOT NULL,
            brand_name VARCHAR(100) NOT NULL,
            model VARCHAR(100) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    ensureColumnRename($pdo, 'users', 'mobile', 'phone', "VARCHAR(20) DEFAULT NULL");
    ensureColumnRename($pdo, 'users', 'profile', 'profile_image', "VARCHAR(255) DEFAULT NULL");
    ensureTableColumn($pdo, 'users', 'phone', "VARCHAR(20) DEFAULT NULL");
    ensureTableColumn($pdo, 'users', 'profile_image', "VARCHAR(255) DEFAULT NULL");
    ensureTableColumn($pdo, 'users', 'gender', "VARCHAR(20) DEFAULT NULL");
    ensureTableColumn($pdo, 'users', 'dob', "DATE DEFAULT NULL");
    ensureTableColumn($pdo, 'users', 'role', "VARCHAR(25) NOT NULL DEFAULT 'user'");
    ensureTableColumn($pdo, 'users', 'status', "VARCHAR(20) NOT NULL DEFAULT 'active'");
    ensureTableColumn($pdo, 'users', 'remember_token', "VARCHAR(255) DEFAULT NULL");
    ensureTableColumn($pdo, 'users', 'remember_expiry', "DATETIME DEFAULT NULL");
    ensureTableColumn($pdo, 'users', 'reset_token', "VARCHAR(255) DEFAULT NULL");
    ensureTableColumn($pdo, 'users', 'reset_expires', "DATETIME DEFAULT NULL");

    ensureTableColumn($pdo, 'admins', 'reset_token', "VARCHAR(255) DEFAULT NULL");
    ensureTableColumn($pdo, 'admins', 'reset_expires', "DATETIME DEFAULT NULL");

    ensureTableColumn($pdo, 'laptops', 'condition_type', "ENUM('new','old') NOT NULL DEFAULT 'old'");
    ensureTableColumn($pdo, 'laptops', 'quantity', "INT NOT NULL DEFAULT 5");
    ensureTableColumn($pdo, 'laptops', 'stock_quantity', "INT NOT NULL DEFAULT 1");
    ensureTableColumn($pdo, 'laptops', 'approval_status', "VARCHAR(20) NOT NULL DEFAULT 'pending'");
    ensureTableColumn($pdo, 'laptops', 'approved_by', "INT DEFAULT NULL");
    ensureTableColumn($pdo, 'laptops', 'approved_at', "DATETIME DEFAULT NULL");
    ensureTableColumn($pdo, 'laptops', 'rejection_reason', "TEXT DEFAULT NULL");
    ensureTableColumn($pdo, 'laptops', 'reviewed_by', "INT DEFAULT NULL");
    ensureTableColumn($pdo, 'laptops', 'reviewed_at', "DATETIME DEFAULT NULL");

    try {
        $pdo->exec("ALTER TABLE laptops MODIFY COLUMN status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'");
    } catch (Throwable $e) {
        error_log('Laptop approval status migration failed: ' . $e->getMessage());
    }

    try {
        $pdo->exec("UPDATE laptops SET status = CASE WHEN status IN ('Available','Sold','Inactive') THEN 'approved' WHEN status IS NULL OR status = '' THEN 'pending' ELSE status END, approval_status = CASE WHEN approval_status IS NULL OR approval_status = '' THEN status ELSE approval_status END");
    } catch (Throwable $e) {
        error_log('Laptop approval default migration failed: ' . $e->getMessage());
    }

    // Backfill condition_type from existing type column (for databases created before condition_type existed)
    try {
        $pdo->exec("UPDATE laptops SET condition_type = CASE WHEN type = 'New' THEN 'new' ELSE 'old' END WHERE condition_type IS NULL OR condition_type = ''");
    } catch (Throwable $e) {
        error_log('Laptop condition_type backfill failed: ' . $e->getMessage());
    }

    // Auto-seed brand_models if the table is empty (so users who imported
    // database.sql don't need to manually import schema.sql).
    seedBrandModelsIfEmpty($pdo);
    seedLaptopsIfEmpty($pdo);

    // ----- Lapify Multi-Step Checkout & Auto Order Tracking migration -----
    // orders: add placed_at, status_updated_at, shipping_method, and the
    // time-driven status enum (placed / confirmed / shipped / delivered).
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            user_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            payment_method VARCHAR(50) NOT NULL DEFAULT 'cod',
            payment_status VARCHAR(25) NOT NULL DEFAULT 'pending',
            transaction_id VARCHAR(100) DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    try {
        $pdo->exec("ALTER TABLE orders MODIFY COLUMN status ENUM('placed','confirmed','shipped','delivered','cancelled') NOT NULL DEFAULT 'placed'");
    } catch (Throwable $e) {
        error_log('orders.status migration to time-driven enum failed: ' . $e->getMessage());
    }

    ensureTableColumn($pdo, 'orders', 'placed_at', "DATETIME DEFAULT NULL");
    ensureTableColumn($pdo, 'orders', 'status_updated_at', "DATETIME DEFAULT NULL");
    ensureTableColumn($pdo, 'orders', 'shipping_method', "ENUM('standard','express') NOT NULL DEFAULT 'standard'");
    ensureTableColumn($pdo, 'orders', 'shipping_address', "VARCHAR(255) DEFAULT NULL");
    ensureTableColumn($pdo, 'orders', 'promo_code', "VARCHAR(50) DEFAULT NULL");
    ensureTableColumn($pdo, 'orders', 'discount_amount', "DECIMAL(10,2) NOT NULL DEFAULT 0.00");

    // Backfill newly created columns for legacy rows so the tracking UI works on old data.
    try {
        $pdo->exec("UPDATE orders SET placed_at = COALESCE(placed_at, created_at), status_updated_at = COALESCE(status_updated_at, created_at), shipping_method = COALESCE(shipping_method, 'standard')");
    } catch (Throwable $e) {
        error_log('orders tracking backfill failed: ' . $e->getMessage());
    }
    try {
        $pdo->exec("UPDATE orders SET status = 'placed' WHERE status = 'pending' OR status = 'processing'");
    } catch (Throwable $e) {
        error_log('orders status normalization failed: ' . $e->getMessage());
    }
    try {
        $pdo->exec("ALTER TABLE orders MODIFY COLUMN status ENUM('placed','confirmed','shipped','delivered','cancelled') NOT NULL DEFAULT 'placed'");
    } catch (Throwable $e) {
        error_log('orders status enum finalize failed: ' . $e->getMessage());
    }

    try {
        $pdo->exec("UPDATE laptops SET stock_quantity = COALESCE(stock_quantity, quantity, 1) WHERE stock_quantity IS NULL OR stock_quantity < 1");
    } catch (Throwable $e) {
        error_log('Laptop stock quantity migration failed: ' . $e->getMessage());
    }

    try {
        $pdo->exec("UPDATE laptops SET approval_status = 'approved' WHERE approval_status IS NULL OR approval_status = ''");
    } catch (Throwable $e) {
        error_log('Approval status migration failed: ' . $e->getMessage());
    }

    ensureTableColumn($pdo, 'admins', 'username', "VARCHAR(100) DEFAULT NULL");
    ensureTableColumn($pdo, 'admins', 'phone', "VARCHAR(20) DEFAULT NULL");
    ensureTableColumn($pdo, 'admins', 'profile_image', "VARCHAR(255) DEFAULT NULL");
    ensureTableColumn($pdo, 'admins', 'secret_key', "VARCHAR(255) NOT NULL DEFAULT ''");
    ensureTableColumn($pdo, 'admins', 'status', "VARCHAR(20) NOT NULL DEFAULT 'active'");
    ensureTableColumn($pdo, 'admins', 'reset_token', "VARCHAR(255) DEFAULT NULL");
    ensureTableColumn($pdo, 'admins', 'reset_expires', "DATETIME DEFAULT NULL");

    $adminPasswordHash = password_hash('Admin@1234', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO admins (id, username, full_name, email, password, secret_key) VALUES (1, 'admin', 'System Administrator', 'admin@lapify.com', :password, '') ON DUPLICATE KEY UPDATE password = VALUES(password), secret_key = ''");
    if ($stmt) {
        $stmt->execute(['password' => $adminPasswordHash]);
    }

    try {
        $pdo->exec("DELETE FROM users WHERE LOWER(email) = 'admin@lapify.com' OR LOWER(COALESCE(role, '')) = 'admin'");
    } catch (Throwable $e) {
        error_log('Admin cleanup failed: ' . $e->getMessage());
    }

    // Mark the schema as fully migrated so the heavy bootstrap above
    // never runs again on subsequent page loads.
    try {
        $pdo->exec("INSERT INTO schema_meta (meta_key, meta_value) VALUES ('schema_version', '" . (int)SCHEMA_VERSION . "') ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value)");
    } catch (Throwable $e) {
        error_log('Schema version write failed: ' . $e->getMessage());
    }
}

try {
    $pdo = getPdoConnection();
    ensureAuthSchema($pdo);
} catch (Throwable $e) {
    error_log('Database bootstrap failed: ' . $e->getMessage());
}

connectWithMysqli();
