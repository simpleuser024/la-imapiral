<?php
declare(strict_types=1);

require_once __DIR__ . '/catalog-seed.php';

function store_table_exists(mysqli $connection, string $table): bool
{
    $dbName = @mysqli_query($connection, 'SELECT DATABASE()');
    if (!($dbName instanceof mysqli_result)) {
        return false;
    }
    $dbRow = mysqli_fetch_row($dbName);
    $database = $dbRow[0] ?? '';
    
    if ($database === '') {
        return false;
    }
    
    $stmt = @mysqli_prepare($connection, 'SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? LIMIT 1');
    
    if (!($stmt instanceof mysqli_stmt)) {
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, 'ss', $database, $table);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $exists = $result instanceof mysqli_result && mysqli_num_rows($result) > 0;
    mysqli_stmt_close($stmt);
    
    return $exists;
}

function store_column_info(mysqli $connection, string $table, string $column): ?array
{
    $dbName = @mysqli_query($connection, 'SELECT DATABASE()');
    if (!($dbName instanceof mysqli_result)) {
        return null;
    }
    $dbRow = mysqli_fetch_row($dbName);
    $database = $dbRow[0] ?? '';
    
    if ($database === '') {
        return null;
    }
    
    $stmt = @mysqli_prepare($connection, 'SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1');

    if (!($stmt instanceof mysqli_stmt)) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 'sss', $database, $table, $column);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result instanceof mysqli_result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    return $row ?: null;
}

function store_column_exists(mysqli $connection, string $table, string $column): bool
{
    return store_column_info($connection, $table, $column) !== null;
}

function store_index_exists(mysqli $connection, string $table, string $indexName): bool
{
    $dbName = @mysqli_query($connection, 'SELECT DATABASE()');
    if (!($dbName instanceof mysqli_result)) {
        return false;
    }
    $dbRow = mysqli_fetch_row($dbName);
    $database = $dbRow[0] ?? '';
    
    if ($database === '') {
        return false;
    }
    
    $stmt = @mysqli_prepare($connection, 'SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1');

    if (!($stmt instanceof mysqli_stmt)) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, 'sss', $database, $table, $indexName);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $exists = $result instanceof mysqli_result && mysqli_num_rows($result) > 0;
    mysqli_stmt_close($stmt);

    return $exists;
}

function store_initialize(mysqli $connection): void
{
    static $initialized = false;

    if ($initialized) {
        return;
    }

    $initialized = true;
    store_bootstrap_schema($connection);
    store_migrate_schema($connection);
    store_seed_catalog($connection);
    store_seed_admin_user($connection);
    store_fix_brand_logos($connection);
    store_seed_brand_media_defaults($connection);
}

function store_bootstrap_schema(mysqli $connection): void
{
    $requiredTables = [
        'categories',
        'brands',
        'users',
        'products',
        'favorite_notifications',
        'cart_items',
        'favorites',
    ];
    $missing = false;

    foreach ($requiredTables as $table) {
        if (!store_table_exists($connection, $table)) {
            $missing = true;
            break;
        }
    }

    if (!$missing) {
        return;
    }

    $sqlFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'dbs' . DIRECTORY_SEPARATOR . 'la-imparial.sql';

    if (!is_file($sqlFile)) {
        return;
    }

    $sql = file_get_contents($sqlFile);

    if ($sql === false || trim($sql) === '') {
        return;
    }

    if (@mysqli_multi_query($connection, $sql)) {
        do {
            $result = @mysqli_store_result($connection);

            if ($result instanceof mysqli_result) {
                mysqli_free_result($result);
            }
        } while (@mysqli_more_results($connection) && @mysqli_next_result($connection));
    }
}

function store_migrate_schema(mysqli $connection): void
{
    if (!store_table_exists($connection, 'users')) {
        return;
    }

    if (!store_column_exists($connection, 'users', 'role')) {
        @mysqli_query($connection, "ALTER TABLE users ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'user'");
    }

    if (!store_column_exists($connection, 'users', 'profile_image')) {
        @mysqli_query($connection, 'ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL');
    }

    if (!store_column_exists($connection, 'users', 'password_hash')) {
        @mysqli_query($connection, 'ALTER TABLE users ADD COLUMN password_hash VARCHAR(255) DEFAULT NULL');
    }

    if (!store_column_exists($connection, 'users', 'google_id')) {
        @mysqli_query($connection, 'ALTER TABLE users ADD COLUMN google_id VARCHAR(190) DEFAULT NULL');
    }

    if (!store_column_exists($connection, 'users', 'updated_at')) {
        @mysqli_query($connection, 'ALTER TABLE users ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    }

    if (!store_column_exists($connection, 'users', 'created_at')) {
        @mysqli_query($connection, 'ALTER TABLE users ADD COLUMN created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
    }

    if (store_table_exists($connection, 'products') && !store_column_exists($connection, 'products', 'created_by')) {
        @mysqli_query($connection, 'ALTER TABLE products ADD COLUMN created_by INT UNSIGNED DEFAULT NULL');
    }

    if (store_table_exists($connection, 'products') && !store_column_exists($connection, 'products', 'gallery_images')) {
        @mysqli_query($connection, 'ALTER TABLE products ADD COLUMN gallery_images TEXT DEFAULT NULL');
    }

    if (store_table_exists($connection, 'products') && !store_column_exists($connection, 'products', 'custom_label')) {
        @mysqli_query($connection, 'ALTER TABLE products ADD COLUMN custom_label VARCHAR(120) DEFAULT NULL');
    }

    if (store_table_exists($connection, 'products') && !store_column_exists($connection, 'products', 'view_count')) {
        @mysqli_query($connection, 'ALTER TABLE products ADD COLUMN view_count INT UNSIGNED NOT NULL DEFAULT 0');
    }

    if (store_table_exists($connection, 'cart_items')) {
        if (!store_column_exists($connection, 'cart_items', 'user_id')) {
            @mysqli_query($connection, 'ALTER TABLE cart_items ADD COLUMN user_id INT UNSIGNED DEFAULT NULL');
        }

        $sessionInfo = store_column_info($connection, 'cart_items', 'session_id');
        if ($sessionInfo !== null && strtoupper((string) ($sessionInfo['Null'] ?? '')) !== 'YES') {
            @mysqli_query($connection, 'ALTER TABLE cart_items MODIFY session_id VARCHAR(190) NULL');
        }

        if (!store_index_exists($connection, 'cart_items', 'uniq_cart_user')) {
            @mysqli_query($connection, 'ALTER TABLE cart_items ADD UNIQUE KEY uniq_cart_user (user_id, product_id)');
        }

        if (!store_index_exists($connection, 'cart_items', 'idx_cart_user')) {
            @mysqli_query($connection, 'ALTER TABLE cart_items ADD KEY idx_cart_user (user_id)');
        }
    }

    if (store_table_exists($connection, 'favorites')) {
        if (!store_column_exists($connection, 'favorites', 'user_id')) {
            @mysqli_query($connection, 'ALTER TABLE favorites ADD COLUMN user_id INT UNSIGNED DEFAULT NULL');
        }

        $sessionInfo = store_column_info($connection, 'favorites', 'session_id');
        if ($sessionInfo !== null && strtoupper((string) ($sessionInfo['Null'] ?? '')) !== 'YES') {
            @mysqli_query($connection, 'ALTER TABLE favorites MODIFY session_id VARCHAR(190) NULL');
        }

        if (!store_index_exists($connection, 'favorites', 'uniq_favorite_user')) {
            @mysqli_query($connection, 'ALTER TABLE favorites ADD UNIQUE KEY uniq_favorite_user (user_id, product_id)');
        }

        if (!store_index_exists($connection, 'favorites', 'idx_favorites_user')) {
            @mysqli_query($connection, 'ALTER TABLE favorites ADD KEY idx_favorites_user (user_id)');
        }
    }

    if (!store_table_exists($connection, 'reviews')) {
        @mysqli_query(
            $connection,
            'CREATE TABLE IF NOT EXISTS reviews (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                product_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                rating TINYINT UNSIGNED NOT NULL,
                comment TEXT NOT NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_reviews_product (product_id),
                KEY idx_reviews_user (user_id),
                CONSTRAINT fk_reviews_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                CONSTRAINT fk_reviews_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    if (!store_table_exists($connection, 'activity_notifications')) {
        @mysqli_query(
            $connection,
            'CREATE TABLE IF NOT EXISTS activity_notifications (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                action_key VARCHAR(80) NOT NULL,
                audience VARCHAR(20) NOT NULL,
                actor_user_id INT UNSIGNED DEFAULT NULL,
                actor_name VARCHAR(190) DEFAULT NULL,
                actor_email VARCHAR(190) DEFAULT NULL,
                product_id INT UNSIGNED DEFAULT NULL,
                product_slug VARCHAR(190) DEFAULT NULL,
                recipient_email VARCHAR(190) NOT NULL,
                subject_line VARCHAR(255) NOT NULL,
                transport_name VARCHAR(40) DEFAULT NULL,
                delivery_status VARCHAR(40) NOT NULL,
                error_message TEXT DEFAULT NULL,
                context_json LONGTEXT DEFAULT NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_activity_notifications_action (action_key),
                KEY idx_activity_notifications_actor (actor_user_id),
                KEY idx_activity_notifications_product (product_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    if (!store_table_exists($connection, 'contact_inquiries')) {
        @mysqli_query(
            $connection,
            'CREATE TABLE IF NOT EXISTS contact_inquiries (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED DEFAULT NULL,
                product_id INT UNSIGNED DEFAULT NULL,
                full_name VARCHAR(190) NOT NULL,
                email VARCHAR(190) NOT NULL,
                phone VARCHAR(80) DEFAULT NULL,
                subject_line VARCHAR(255) NOT NULL,
                message_body TEXT NOT NULL,
                inquiry_status VARCHAR(40) NOT NULL DEFAULT "new",
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_contact_inquiries_user (user_id),
                KEY idx_contact_inquiries_product (product_id),
                KEY idx_contact_inquiries_status (inquiry_status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    if (!store_table_exists($connection, 'password_reset_requests')) {
        @mysqli_query(
            $connection,
            'CREATE TABLE IF NOT EXISTS password_reset_requests (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED DEFAULT NULL,
                email VARCHAR(190) NOT NULL,
                code_hash VARCHAR(255) NOT NULL,
                status_label VARCHAR(40) NOT NULL DEFAULT "pending",
                expires_at DATETIME NOT NULL,
                verified_at DATETIME DEFAULT NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_password_reset_requests_email (email),
                KEY idx_password_reset_requests_status (status_label)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    if (function_exists('store_extra_migrate_schema')) {
        store_extra_migrate_schema($connection);
    }
}

function store_seed_admin_user(mysqli $connection): void
{
    if (!defined('STORE_ADMIN_EMAIL') || !defined('STORE_ADMIN_PASSWORD')) {
        return;
    }

    $email = trim((string) STORE_ADMIN_EMAIL);
    $password = (string) STORE_ADMIN_PASSWORD;

    if ($email === '' || $password === '') {
        return;
    }

    $existing = store_user_by_email($connection, $email);

    if ($existing) {
        if (($existing['role'] ?? 'user') !== 'admin') {
            $stmt = @mysqli_prepare($connection, 'UPDATE users SET role = ? WHERE id = ?');
            if ($stmt instanceof mysqli_stmt) {
                $role = 'admin';
                mysqli_stmt_bind_param($stmt, 'si', $role, $existing['id']);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }
        return;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = @mysqli_prepare($connection, 'INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');

    if (!($stmt instanceof mysqli_stmt)) {
        return;
    }

    $name = 'Admin';
    $role = 'admin';
    mysqli_stmt_bind_param($stmt, 'ssss', $name, $email, $hash, $role);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function store_seed_catalog(mysqli $connection): void
{
    $seed = catalog_seed_data();

    foreach ($seed['categories'] as $slug => $category) {
        $stmt = @mysqli_prepare(
            $connection,
            'INSERT INTO categories (name, slug, note, document_path) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), note = VALUES(note), document_path = VALUES(document_path)'
        );

        if ($stmt instanceof mysqli_stmt) {
            mysqli_stmt_bind_param($stmt, 'ssss', $category['name'], $slug, $category['note'], $category['document']);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    foreach ($seed['brands'] as $brand) {
        $stmt = @mysqli_prepare(
            $connection,
            'INSERT INTO brands (name, slug, logo_path) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), logo_path = VALUES(logo_path)'
        );

        if ($stmt instanceof mysqli_stmt) {
            mysqli_stmt_bind_param($stmt, 'sss', $brand['name'], $brand['slug'], $brand['logo_path']);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    foreach ($seed['products'] as $product) {
        $categoryId = store_term_id($connection, 'categories', $product['category_slug']);
        $brandId = store_term_id($connection, 'brands', $product['brand_slug']);

        if ($categoryId <= 0 || $brandId <= 0) {
            continue;
        }

        $stmt = @mysqli_prepare(
            $connection,
            'INSERT INTO products (
                name, slug, brand_id, category_id, summary, description, document_path, image_path,
                price, original_price, discount_percent, is_featured
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                slug = VALUES(slug)'
        );

        if ($stmt instanceof mysqli_stmt) {
            mysqli_stmt_bind_param(
                $stmt,
                'ssiissssddii',
                $product['name'],
                $product['slug'],
                $brandId,
                $categoryId,
                $product['summary'],
                $product['description'],
                $product['document_path'],
                $product['image_path'],
                $product['price'],
                $product['original_price'],
                $product['discount_percent'],
                $product['is_featured']
            );
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
}

function store_term_id(mysqli $connection, string $table, string $slug): int
{
    $stmt = @mysqli_prepare($connection, "SELECT id FROM {$table} WHERE slug = ? LIMIT 1");

    if (!($stmt instanceof mysqli_stmt)) {
        return 0;
    }

    mysqli_stmt_bind_param($stmt, 's', $slug);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result instanceof mysqli_result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    return (int) ($row['id'] ?? 0);
}

function store_slug_from_text(string $name): string
{
    $slug = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $name), '-'));
    return $slug !== '' ? $slug : 'item';
}

function store_brand_logo_defaults(): array
{
    return [
        'haier' => 'img/brand-logos/haier.png',
        'dawlance' => 'img/brand-logos/Dawlance.png',
        'samsung' => 'img/brand-logos/Samsung.svg',
        'lg' => 'img/brand-logos/lg.png',
        'orient' => 'img/brand-logos/orient.png',
        'pel' => 'img/brand-logos/pel.svg',
    ];
}

function store_brand_media_defaults(): array
{
    return [
        'haier' => [
            'slider_banner_path' => 'img/slider/haier.jpg',
            'hero_poster_path' => 'img/banner/banner-1.jpg',
            'hero_video_path' => 'Ads-Video/Haier.mp4',
            'splash_video_path' => 'videos/brands/haier-intro.mp4',
            'splash_logo_path' => 'img/brand-logos/haier.png',
            'featured_product_slug' => 'haier-frontcare-8kg',
        ],
        'dawlance' => [
            'slider_banner_path' => 'img/slider/dowlance.png',
            'hero_poster_path' => 'img/banner/banner-2.jpg',
            'hero_video_path' => 'Ads-Video/Dawlance.mp4',
            'splash_video_path' => 'videos/brands/dawlance-intro.mp4',
            'splash_logo_path' => 'img/brand-logos/Dawlance.png',
            'featured_product_slug' => 'dawlance-chefmaster-30',
        ],
        'samsung' => [
            'slider_banner_path' => 'img/slider/sumsung.jfif',
            'hero_poster_path' => 'img/banner/banner-3.jpg',
            'hero_video_path' => 'Ads-Video/Sumsung.mp4',
            'splash_video_path' => 'videos/brands/samsung-intro.mp4',
            'splash_logo_path' => 'img/brand-logos/Samsung.svg',
            'featured_product_slug' => 'samsung-ecobubble-9kg',
        ],
        'lg' => [
            'slider_banner_path' => 'img/slider/lg.jfif',
            'hero_poster_path' => 'img/about/about-us.jpg',
            'hero_video_path' => 'Ads-Video/Lg.mp4',
            'splash_video_path' => 'videos/brands/lg-intro.mp4',
            'splash_logo_path' => 'img/brand-logos/lg.png',
            'featured_product_slug' => 'lg-smartwash-8kg',
        ],
        'orient' => [
            'slider_banner_path' => 'img/slider/orient.jfif',
            'hero_poster_path' => 'img/hero/hero-2.jpg',
            'hero_video_path' => 'Ads-Video/Orient.mp4',
            'splash_video_path' => 'videos/brands/orients-intro.mp4',
            'splash_logo_path' => 'img/brand-logos/orient.png',
            'featured_product_slug' => 'orient-majesty-30',
        ],
        'pel' => [
            'slider_banner_path' => 'img/slider/pel.jfif',
            'hero_poster_path' => 'img/banner/banner-1.jpg',
            'hero_video_path' => 'Ads-Video/Pel.mp4',
            'splash_video_path' => 'videos/brands/pel-intro.mp4',
            'splash_logo_path' => 'img/brand-logos/pel.svg',
            'featured_product_slug' => 'pel-familyfresh-500',
        ],
    ];
}

function store_default_brand_logo_path(string $brandSlug = ''): string
{
    $brandSlug = strtolower(trim($brandSlug));
    $defaults = store_brand_logo_defaults();

    if ($brandSlug !== '' && isset($defaults[$brandSlug])) {
        return $defaults[$brandSlug];
    }

    return 'img/logo/logo.png';
}

function store_normalize_brand_logo_path(string $brandSlug, string $logoPath): string
{
    $logoPath = trim($logoPath);
    $brandSlug = strtolower(trim($brandSlug));

    if ($logoPath !== '' && preg_match('#^https?://#i', $logoPath) === 1) {
        return $logoPath;
    }

    if ($logoPath !== '') {
        $localPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $logoPath), '.\\/ ');
        if (is_file($localPath)) {
            return str_replace('\\', '/', ltrim($logoPath, './'));
        }
    }

    return store_default_brand_logo_path($brandSlug);
}

function store_fix_brand_logos(mysqli $connection): void
{
    if (!store_table_exists($connection, 'brands')) {
        return;
    }

    foreach (store_brand_logo_defaults() as $slug => $defaultLogo) {
        $stmt = @mysqli_prepare(
            $connection,
            'SELECT logo_path FROM brands WHERE slug = ? LIMIT 1'
        );

        if (!($stmt instanceof mysqli_stmt)) {
            continue;
        }

        mysqli_stmt_bind_param($stmt, 's', $slug);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result instanceof mysqli_result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        if (!$row) {
            continue;
        }

        $normalized = store_normalize_brand_logo_path($slug, (string) ($row['logo_path'] ?? ''));
        if ($normalized === (string) ($row['logo_path'] ?? '')) {
            continue;
        }

        $update = @mysqli_prepare($connection, 'UPDATE brands SET logo_path = ? WHERE slug = ?');
        if (!($update instanceof mysqli_stmt)) {
            continue;
        }

        mysqli_stmt_bind_param($update, 'ss', $normalized, $slug);
        mysqli_stmt_execute($update);
        mysqli_stmt_close($update);
    }
}

function store_seed_brand_media_defaults(mysqli $connection): void
{
    if (!store_table_exists($connection, 'brands') || !store_column_exists($connection, 'brands', 'hero_video_path')) {
        return;
    }

    $sql = 'UPDATE brands SET
        slider_banner_path = CASE WHEN slider_banner_path IS NULL OR TRIM(slider_banner_path) = "" THEN ? ELSE slider_banner_path END,
        hero_poster_path = CASE WHEN hero_poster_path IS NULL OR TRIM(hero_poster_path) = "" THEN ? ELSE hero_poster_path END,
        hero_video_path = CASE WHEN hero_video_path IS NULL OR TRIM(hero_video_path) = "" THEN ? ELSE hero_video_path END,
        splash_video_path = CASE WHEN splash_video_path IS NULL OR TRIM(splash_video_path) = "" THEN ? ELSE splash_video_path END,
        splash_logo_path = CASE WHEN splash_logo_path IS NULL OR TRIM(splash_logo_path) = "" THEN ? ELSE splash_logo_path END,
        featured_product_slug = CASE WHEN featured_product_slug IS NULL OR TRIM(featured_product_slug) = "" THEN ? ELSE featured_product_slug END
        WHERE slug = ?';

    foreach (store_brand_media_defaults() as $slug => $defaults) {
        $stmt = @mysqli_prepare($connection, $sql);

        if (!($stmt instanceof mysqli_stmt)) {
            continue;
        }

        mysqli_stmt_bind_param(
            $stmt,
            'sssssss',
            $defaults['slider_banner_path'],
            $defaults['hero_poster_path'],
            $defaults['hero_video_path'],
            $defaults['splash_video_path'],
            $defaults['splash_logo_path'],
            $defaults['featured_product_slug'],
            $slug
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

function store_has_uploaded_file(array $file): bool
{
    return ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK
        && !empty($file['tmp_name'])
        && is_uploaded_file((string) $file['tmp_name']);
}

function ensure_store_state(): void
{
    $_SESSION['cart'] = $_SESSION['cart'] ?? [];
    $_SESSION['favorites'] = $_SESSION['favorites'] ?? [];

    if (!empty($_SESSION['store_db_loaded'])) {
        return;
    }

    global $con;

    if ($con instanceof mysqli) {
        store_initialize($con);
    }

    if ($con instanceof mysqli) {
        store_sync_session_with_db($con);
    }

    $_SESSION['store_db_loaded'] = true;
}

function store_csrf_token(string $key = 'default'): string
{
    $_SESSION['csrf_tokens'] = $_SESSION['csrf_tokens'] ?? [];

    if (!isset($_SESSION['csrf_tokens'][$key]) || !is_string($_SESSION['csrf_tokens'][$key]) || $_SESSION['csrf_tokens'][$key] === '') {
        $_SESSION['csrf_tokens'][$key] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['csrf_tokens'][$key];
}

function store_verify_csrf_token(string $key, string $token): bool
{
    $stored = (string) ($_SESSION['csrf_tokens'][$key] ?? '');
    $token = trim($token);

    if ($stored === '' || $token === '') {
        return false;
    }

    return hash_equals($stored, $token);
}

function store_flash(string $type, string $message): void
{
    $_SESSION['store_flash'] = ['type' => $type, 'message' => $message];
}

function pull_store_flash(): ?array
{
    $flash = $_SESSION['store_flash'] ?? null;
    unset($_SESSION['store_flash']);

    return is_array($flash) ? $flash : null;
}

function store_routes(): array
{
    global $con;

    $brandPages = [
        'haier' => store_app_relative_url('haier.php'),
        'dawlance' => store_app_relative_url('dawlance.php'),
        'samsung' => store_app_relative_url('samsung.php'),
        'lg' => store_app_relative_url('lg.php'),
        'orient' => store_app_relative_url('orient.php'),
        'pel' => store_app_relative_url('pel.php'),
    ];

    if ($con instanceof mysqli && function_exists('store_cms_brand_route_map')) {
        $dynamicBrandPages = store_cms_brand_route_map($con);
        if ($dynamicBrandPages !== []) {
            $brandPages = $dynamicBrandPages;
        }
    }

    return [
        'home' => store_app_relative_url('index.php'),
        'about' => store_app_relative_url('about.php'),
        'contact' => store_app_relative_url('contact.php'),
        'shop' => store_app_relative_url('shop.php'),
        'detail' => store_app_relative_url('shop-details.php'),
        'reviewsPage' => store_app_relative_url('reviews.php'),
        'compare' => store_app_relative_url('compare.php'),
        'cart' => store_app_relative_url('cart.php'),
        'favorites' => store_app_relative_url('favorites.php'),
        'productForm' => store_app_relative_url('product-form.php'),
        'action' => store_app_relative_url('store-action.php'),
        'profile' => store_app_relative_url('profile.php'),
        'admin' => store_app_relative_url('admin/'),
        'login' => store_app_relative_url('login.php'),
        'register' => store_app_relative_url('register.php'),
        'review' => store_app_relative_url('review-action.php'),
        'apiProducts' => store_app_relative_url('api/products.php'),
        'brandPages' => $brandPages,
    ];
}

function store_current_user_id(): int
{
    return (int) ($_SESSION['user_id'] ?? 0);
}

function store_is_logged_in(): bool
{
    return store_current_user_id() > 0;
}

function store_set_logged_in_user(array $user): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
    $_SESSION['user_id'] = (int) ($user['id'] ?? 0);
    $_SESSION['user_name'] = (string) ($user['name'] ?? '');
    $_SESSION['user_email'] = (string) ($user['email'] ?? '');
    $_SESSION['user_role'] = (string) ($user['role'] ?? 'viewer');
    $_SESSION['user_avatar'] = (string) ($user['profile_image'] ?? '');
    $_SESSION['user_avatar_fit'] = (string) ($user['avatar_fit'] ?? 'cover');
    $_SESSION['store_db_loaded'] = false;
}

function store_logout_user(): void
{
    unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email'], $_SESSION['user_role'], $_SESSION['user_avatar'], $_SESSION['user_avatar_fit']);
    $_SESSION['cart'] = [];
    $_SESSION['favorites'] = [];
    unset($_SESSION['google_oauth_state'], $_SESSION['login_redirect']);
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
    $_SESSION['store_db_loaded'] = false;
}

function store_is_admin(): bool
{
    return store_is_logged_in() && (string) ($_SESSION['user_role'] ?? '') === 'admin';
}

function store_require_login(string $redirect): void
{
    if (store_is_logged_in()) {
        return;
    }

    store_flash('warning', 'Please login to continue.');
    $safeRedirect = store_safe_redirect($redirect);
    $target = './login.php?redirect=' . urlencode($safeRedirect);
    header('Location: ' . $target);
    exit;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function store_all_products(mysqli $connection): array
{
    $items = [];
    $result = @mysqli_query(
        $connection,
        'SELECT
            p.id,
            p.name,
            p.slug,
            p.summary,
            p.description,
            p.document_path,
            p.image_path,
            p.gallery_images,
            p.custom_label,
            p.view_count,
            p.created_by,
            p.price,
            p.original_price,
            p.discount_percent,
            p.is_featured,
            c.slug AS category_slug,
            c.name AS category_name,
            c.note AS category_note,
            b.slug AS brand_slug,
            b.name AS brand_name,
            b.logo_path
        FROM products p
        INNER JOIN categories c ON c.id = p.category_id
        INNER JOIN brands b ON b.id = p.brand_id
        ORDER BY b.name, p.id'
    );

    if (!($result instanceof mysqli_result)) {
        return $items;
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $row['logo_path'] = store_normalize_brand_logo_path((string) ($row['brand_slug'] ?? ''), (string) ($row['logo_path'] ?? ''));
        $items[] = $row;
    }

    return $items;
}

function store_product_by_slug(mysqli $connection, string $slug): ?array
{
    $stmt = @mysqli_prepare(
        $connection,
        'SELECT
            p.id,
            p.name,
            p.slug,
            p.summary,
            p.description,
            p.document_path,
            p.image_path,
            p.gallery_images,
            p.custom_label,
            p.view_count,
            p.created_by,
            p.price,
            p.original_price,
            p.discount_percent,
            p.is_featured,
            c.slug AS category_slug,
            c.name AS category_name,
            c.note AS category_note,
            b.slug AS brand_slug,
            b.name AS brand_name,
            b.logo_path
        FROM products p
        INNER JOIN categories c ON c.id = p.category_id
        INNER JOIN brands b ON b.id = p.brand_id
        WHERE p.slug = ?
        LIMIT 1'
    );

    if (!($stmt instanceof mysqli_stmt)) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 's', $slug);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result instanceof mysqli_result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    if ($row) {
        $row['logo_path'] = store_normalize_brand_logo_path((string) ($row['brand_slug'] ?? ''), (string) ($row['logo_path'] ?? ''));
    }

    return $row ?: null;
}

function store_product_by_id(mysqli $connection, int $productId): ?array
{
    $stmt = @mysqli_prepare(
        $connection,
        'SELECT
            p.id,
            p.name,
            p.slug,
            p.summary,
            p.description,
            p.document_path,
            p.image_path,
            p.gallery_images,
            p.custom_label,
            p.view_count,
            p.created_by,
            p.price,
            p.original_price,
            p.discount_percent,
            p.is_featured,
            c.slug AS category_slug,
            c.name AS category_name,
            c.note AS category_note,
            b.slug AS brand_slug,
            b.name AS brand_name,
            b.logo_path
        FROM products p
        INNER JOIN categories c ON c.id = p.category_id
        INNER JOIN brands b ON b.id = p.brand_id
        WHERE p.id = ?
        LIMIT 1'
    );

    if (!($stmt instanceof mysqli_stmt)) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 'i', $productId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result instanceof mysqli_result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    if ($row) {
        $row['logo_path'] = store_normalize_brand_logo_path((string) ($row['brand_slug'] ?? ''), (string) ($row['logo_path'] ?? ''));
    }

    return $row ?: null;
}

function store_products_by_slugs(mysqli $connection, array $slugs): array
{
    $slugs = array_values(array_unique(array_filter(array_map('strval', $slugs))));

    if ($slugs === []) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($slugs), '?'));
    $types = str_repeat('s', count($slugs));
    $stmt = @mysqli_prepare(
        $connection,
        "SELECT
            p.id,
            p.name,
            p.slug,
            p.summary,
            p.description,
            p.document_path,
            p.image_path,
            p.gallery_images,
            p.custom_label,
            p.view_count,
            p.created_by,
            p.price,
            p.original_price,
            p.discount_percent,
            p.is_featured,
            c.slug AS category_slug,
            c.name AS category_name,
            c.note AS category_note,
            b.slug AS brand_slug,
            b.name AS brand_name,
            b.logo_path
        FROM products p
        INNER JOIN categories c ON c.id = p.category_id
        INNER JOIN brands b ON b.id = p.brand_id
        WHERE p.slug IN ($placeholders)"
    );

    if (!($stmt instanceof mysqli_stmt)) {
        return [];
    }

    mysqli_stmt_bind_param($stmt, $types, ...$slugs);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $indexed = [];

    if ($result instanceof mysqli_result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $row['logo_path'] = store_normalize_brand_logo_path((string) ($row['brand_slug'] ?? ''), (string) ($row['logo_path'] ?? ''));
            $indexed[$row['slug']] = $row;
        }
    }

    mysqli_stmt_close($stmt);

    $ordered = [];

    foreach ($slugs as $slug) {
        if (isset($indexed[$slug])) {
            $ordered[] = $indexed[$slug];
        }
    }

    return $ordered;
}

function store_categories(mysqli $connection): array
{
    $items = [];
    $result = @mysqli_query($connection, 'SELECT id, name, slug FROM categories ORDER BY name');

    if (!($result instanceof mysqli_result)) {
        return $items;
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $row['logo_path'] = store_normalize_brand_logo_path((string) ($row['slug'] ?? ''), (string) ($row['logo_path'] ?? ''));
        $items[] = $row;
    }

    return $items;
}

function store_brands(mysqli $connection): array
{
    $items = [];
    $result = @mysqli_query($connection, 'SELECT id, name, slug, logo_path FROM brands ORDER BY name');

    if (!($result instanceof mysqli_result)) {
        return $items;
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
    }

    return $items;
}

function store_default_site_media_payload(): array
{
    return [
        'siteLogo' => [
            'src' => 'img/logo/logo.png',
            'alt' => 'La Imparial',
        ],
        'splashLogo' => 'img/logo/image.png',
        'homeKitchenPromo' => [
            'src' => 'img/banner/ketchen.webp',
            'alt' => 'La Imperial kitchen highlight visual',
        ],
        'homeEssentialGallery' => [],
        'homeSmartPromo' => [
            'src' => 'img/banner/smart.webp',
            'alt' => 'Kitchen edit promotional poster',
        ],
        'brandHighlightImages' => [],
    ];
}

function store_seed_catalog_snapshot(): array
{
    static $snapshot = null;

    if (is_array($snapshot)) {
        return $snapshot;
    }

    $seed = catalog_seed_data();
    $brands = [];
    $categories = [];
    $products = [];
    $categoryMeta = [];
    $brandIndex = [];
    $categoryIndex = [];

    foreach ($seed['brands'] as $index => $brand) {
        $slug = trim((string) ($brand['slug'] ?? ''));
        if ($slug === '') {
            continue;
        }

        $item = [
            'id' => $index + 1,
            'name' => (string) ($brand['name'] ?? strtoupper($slug)),
            'slug' => $slug,
            'logo_path' => store_normalize_brand_logo_path($slug, (string) ($brand['logo_path'] ?? '')),
            'hero_title' => '',
            'hero_summary' => '',
            'badges' => [],
            'featured_product_slug' => '',
            'slider_banner_path' => '',
            'hero_poster_path' => '',
            'hero_video_path' => '',
            'splash_video_path' => '',
            'splash_logo_path' => '',
            'is_visible' => 1,
            'sort_order' => $index + 1,
        ];

        $brands[] = $item;
        $brandIndex[$slug] = $item;
    }

    $categoryOrder = 1;
    foreach ($seed['categories'] as $slug => $category) {
        $slug = (string) $slug;
        $name = (string) ($category['name'] ?? ucwords(str_replace(['-', '_'], ' ', $slug)));
        $note = (string) ($category['note'] ?? '');
        $document = (string) ($category['document'] ?? '');
        $item = [
            'id' => $categoryOrder,
            'name' => $name,
            'slug' => $slug,
            'note' => $note,
            'document_path' => $document,
            'hero_headline' => '',
            'banner_image_path' => '',
            'hero_video_path' => '',
            'sort_order' => $categoryOrder,
        ];

        $categories[] = $item;
        $categoryIndex[$slug] = $item;
        $categoryMeta[$slug] = [
            'label' => $name,
            'note' => $note,
            'document' => $document,
        ];
        $categoryOrder++;
    }

    foreach ($seed['products'] as $index => $product) {
        $brandSlug = trim((string) ($product['brand_slug'] ?? ''));
        $categorySlug = trim((string) ($product['category_slug'] ?? ''));
        if ($brandSlug === '' || $categorySlug === '') {
            continue;
        }

        $brand = $brandIndex[$brandSlug] ?? [
            'name' => (string) ($product['brand_name'] ?? strtoupper($brandSlug)),
            'logo_path' => store_normalize_brand_logo_path($brandSlug, ''),
        ];
        $category = $categoryIndex[$categorySlug] ?? [
            'name' => (string) ($product['category_name'] ?? ucwords(str_replace('-', ' ', $categorySlug))),
            'note' => '',
            'document_path' => '',
        ];

        $products[] = [
            'id' => $index + 1,
            'name' => (string) ($product['name'] ?? ''),
            'slug' => (string) ($product['slug'] ?? ''),
            'summary' => (string) ($product['summary'] ?? ''),
            'description' => (string) ($product['description'] ?? ''),
            'document_path' => (string) ($product['document_path'] ?? ($category['document_path'] ?? '')),
            'image_path' => (string) ($product['image_path'] ?? ''),
            'gallery_images' => '',
            'custom_label' => '',
            'view_count' => 0,
            'created_by' => 0,
            'price' => (float) ($product['price'] ?? 0),
            'original_price' => (float) ($product['original_price'] ?? 0),
            'discount_percent' => (int) ($product['discount_percent'] ?? 0),
            'is_featured' => (int) ($product['is_featured'] ?? 0),
            'category_slug' => $categorySlug,
            'category_name' => (string) ($category['name'] ?? ''),
            'category_note' => (string) ($category['note'] ?? ''),
            'brand_slug' => $brandSlug,
            'brand_name' => (string) ($brand['name'] ?? ''),
            'logo_path' => (string) ($brand['logo_path'] ?? store_normalize_brand_logo_path($brandSlug, '')),
        ];
    }

    $snapshot = [
        'brands' => $brands,
        'categories' => $categories,
        'category_meta' => $categoryMeta,
        'products' => $products,
    ];

    return $snapshot;
}

function store_seed_brand_catalog(): array
{
    return store_seed_catalog_snapshot()['brands'];
}

function store_seed_category_catalog(): array
{
    return store_seed_catalog_snapshot()['categories'];
}

function store_seed_category_meta_payload(): array
{
    return store_seed_catalog_snapshot()['category_meta'];
}

function store_seed_all_products(): array
{
    return store_seed_catalog_snapshot()['products'];
}

function store_seed_product_by_slug(string $slug): ?array
{
    $slug = trim($slug);
    if ($slug === '') {
        return null;
    }

    foreach (store_seed_all_products() as $product) {
        if ((string) ($product['slug'] ?? '') === $slug) {
            return $product;
        }
    }

    return null;
}

function store_seed_products_by_slugs(array $slugs): array
{
    $indexed = [];
    foreach (store_seed_all_products() as $product) {
        $indexed[(string) ($product['slug'] ?? '')] = $product;
    }

    $items = [];
    foreach ($slugs as $slug) {
        $slug = trim((string) $slug);
        if ($slug !== '' && isset($indexed[$slug])) {
            $items[] = $indexed[$slug];
        }
    }

    return $items;
}

function store_seed_cart_items(array $cart): array
{
    $items = [];
    $cart = store_normalize_cart($cart);
    $products = [];

    foreach (store_seed_products_by_slugs(array_keys($cart)) as $product) {
        $products[(string) ($product['slug'] ?? '')] = $product;
    }

    foreach ($cart as $slug => $quantity) {
        if (!isset($products[$slug])) {
            continue;
        }

        $product = $products[$slug];
        $product['quantity'] = (int) $quantity;
        $product['line_total'] = (float) $product['price'] * (int) $quantity;
        $items[] = $product;
    }

    return $items;
}

function store_seed_cart_subtotal(array $cart): float
{
    $total = 0.0;

    foreach (store_seed_cart_items($cart) as $item) {
        $total += (float) ($item['line_total'] ?? 0);
    }

    return $total;
}

function build_store_seed_client_payload(): array
{
    ensure_store_state();

    $products = store_seed_all_products();
    $brandCatalog = store_seed_brand_catalog();
    $categoryMeta = store_seed_category_meta_payload();
    $brandGroups = [];
    $productImageMap = [];
    $featured = [];
    $brandLogoMap = [];

    foreach ($brandCatalog as $brand) {
        $brandSlug = (string) ($brand['slug'] ?? '');

        if ($brandSlug === '' || ((int) ($brand['is_visible'] ?? 1) !== 1)) {
            continue;
        }

        $brandGroups[$brandSlug] = [
            'name' => (string) ($brand['name'] ?? $brandSlug),
            'slug' => $brandSlug,
            'products' => [],
            'headline' => (string) ($brand['hero_title'] ?? ''),
            'summary' => (string) ($brand['hero_summary'] ?? ''),
            'tags' => $brand['badges'] ?? [],
        ];
        $brandLogoMap[$brandSlug] = store_normalize_brand_logo_path($brandSlug, (string) ($brand['logo_path'] ?? ''));
    }

    foreach ($products as $product) {
        $brandSlug = (string) ($product['brand_slug'] ?? '');

        if (!isset($brandGroups[$brandSlug])) {
            $brandGroups[$brandSlug] = [
                'name' => (string) ($product['brand_name'] ?? $brandSlug),
                'slug' => $brandSlug,
                'products' => [],
                'headline' => '',
                'summary' => '',
                'tags' => [],
            ];
            $brandLogoMap[$brandSlug] = store_normalize_brand_logo_path($brandSlug, (string) ($product['logo_path'] ?? ''));
        }

        $brandGroups[$brandSlug]['products'][] = [
            'slug' => (string) ($product['slug'] ?? ''),
            'name' => (string) ($product['name'] ?? ''),
            'summary' => (string) ($product['summary'] ?? ''),
            'categorySlug' => (string) ($product['category_slug'] ?? ''),
            'document' => './' . ltrim((string) ($product['document_path'] ?? ''), './'),
            'image' => ltrim((string) ($product['image_path'] ?? ''), './'),
            'gallery' => store_parse_gallery_images((string) ($product['gallery_images'] ?? '')),
            'customLabel' => (string) ($product['custom_label'] ?? ''),
            'labels' => [],
            'viewCount' => (int) ($product['view_count'] ?? 0),
            'price' => (float) ($product['price'] ?? 0),
            'originalPrice' => (float) ($product['original_price'] ?? 0),
            'discountPercent' => (int) ($product['discount_percent'] ?? 0),
            'isTrending' => (int) ($product['is_featured'] ?? 0) === 1,
        ];
        $productImageMap[(string) ($product['slug'] ?? '')] = ltrim((string) ($product['image_path'] ?? ''), './');

        if ((int) ($product['is_featured'] ?? 0) === 1) {
            $featured[] = (string) ($product['slug'] ?? '');
        }
    }

    return [
        'brandGroups' => array_values($brandGroups),
        'productImageMap' => $productImageMap,
        'featuredProductSlugs' => $featured,
        'brandLogoImageMap' => $brandLogoMap,
        'categories' => $categoryMeta,
        'brandExperienceConfig' => [],
        'siteMediaConfig' => store_default_site_media_payload(),
        'labels' => [],
        'reviewMap' => [],
        'reviewSummary' => [],
        'routes' => store_routes(),
        'session' => [
            'cart' => $_SESSION['cart'],
            'favorites' => array_values($_SESSION['favorites']),
            'cartCount' => store_cart_count(),
            'favoriteCount' => count($_SESSION['favorites']),
            'user' => [
                'id' => store_current_user_id(),
                'name' => (string) ($_SESSION['user_name'] ?? ''),
                'email' => (string) ($_SESSION['user_email'] ?? ''),
                'role' => (string) ($_SESSION['user_role'] ?? ''),
                'avatar' => (string) ($_SESSION['user_avatar'] ?? ''),
                'avatarFit' => (string) ($_SESSION['user_avatar_fit'] ?? 'cover'),
            ],
        ],
    ];
}

function build_store_client_payload(mysqli $connection): array
{
    ensure_store_state();

    $products = store_all_products($connection);
    $brandCatalog = function_exists('store_brand_catalog') ? store_brand_catalog($connection) : [];
    $categoryMeta = function_exists('store_cms_category_meta_payload') ? store_cms_category_meta_payload($connection) : [];
    $brandExperienceConfig = function_exists('store_cms_brand_experience_payload') ? store_cms_brand_experience_payload($connection) : [];
    $siteMediaConfig = function_exists('store_cms_site_media_payload') ? store_cms_site_media_payload($connection) : [];
    $labelsByProduct = function_exists('store_product_label_index_by_product_ids')
        ? store_product_label_index_by_product_ids($connection, array_map('intval', array_column($products, 'id')))
        : [];
    $brandGroups = [];
    $productImageMap = [];
    $featured = [];
    $brandLogoMap = [];
    $reviewMap = [];
    $reviewSummary = [];

    foreach ($brandCatalog as $brand) {
        $brandSlug = (string) ($brand['slug'] ?? '');

        if ($brandSlug === '' || ((int) ($brand['is_visible'] ?? 1) !== 1)) {
            continue;
        }

        $brandGroups[$brandSlug] = [
            'name' => (string) ($brand['name'] ?? $brandSlug),
            'slug' => $brandSlug,
            'products' => [],
            'headline' => (string) ($brand['hero_title'] ?? ''),
            'summary' => (string) ($brand['hero_summary'] ?? ''),
            'tags' => $brand['badges'] ?? [],
        ];
        $brandLogoMap[$brandSlug] = store_normalize_brand_logo_path($brandSlug, (string) ($brand['logo_path'] ?? ''));
    }

    foreach ($products as $product) {
        $brandSlug = $product['brand_slug'];

        if (!isset($brandGroups[$brandSlug])) {
            $brandGroups[$brandSlug] = [
                'name' => $product['brand_name'],
                'slug' => $brandSlug,
                'products' => [],
                'headline' => '',
                'summary' => '',
                'tags' => [],
            ];
            $brandLogoMap[$brandSlug] = store_normalize_brand_logo_path((string) $brandSlug, (string) ($product['logo_path'] ?? ''));
        }

        $productLabels = $labelsByProduct[(int) ($product['id'] ?? 0)] ?? [];
        $productCustomLabel = (string) ($product['custom_label'] ?? '');
        if ($productCustomLabel === '' && $productLabels !== []) {
            $productCustomLabel = (string) ($productLabels[0]['name'] ?? '');
        }

        $brandGroups[$brandSlug]['products'][] = [
            'slug' => $product['slug'],
            'name' => $product['name'],
            'summary' => $product['summary'],
            'categorySlug' => $product['category_slug'],
            'document' => './' . ltrim($product['document_path'], './'),
            'image' => ltrim($product['image_path'], './'),
            'gallery' => store_parse_gallery_images($product['gallery_images'] ?? ''),
            'customLabel' => $productCustomLabel,
            'labels' => $productLabels,
            'viewCount' => (int) ($product['view_count'] ?? 0),
            'price' => (float) $product['price'],
            'originalPrice' => (float) $product['original_price'],
            'discountPercent' => (int) $product['discount_percent'],
            'isTrending' => (int) $product['is_featured'] === 1,
        ];
        $productImageMap[$product['slug']] = ltrim($product['image_path'], './');

        if ((int) $product['is_featured'] === 1) {
            $featured[] = $product['slug'];
        }
    }

    if ($products !== [] && store_table_exists($connection, 'reviews')) {
        $productIds = array_map('intval', array_column($products, 'id'));
        $reviewSummaryById = store_review_summary_by_product($connection, $productIds);
        $reviewsById = store_reviews_for_products($connection, $productIds, 5);

        foreach ($products as $product) {
            $productId = (int) $product['id'];
            $slug = $product['slug'];
            $reviewMap[$slug] = $reviewsById[$productId] ?? [];
            $summary = $reviewSummaryById[$productId] ?? ['count' => 0, 'average' => 0];
            $reviewSummary[$slug] = $summary;
        }
    }

    return [
        'brandGroups' => array_values($brandGroups),
        'productImageMap' => $productImageMap,
        'featuredProductSlugs' => $featured,
        'brandLogoImageMap' => $brandLogoMap,
        'categories' => $categoryMeta,
        'brandExperienceConfig' => $brandExperienceConfig,
        'siteMediaConfig' => $siteMediaConfig,
        'labels' => function_exists('store_product_labels') ? store_product_labels($connection) : [],
        'reviewMap' => $reviewMap,
        'reviewSummary' => $reviewSummary,
        'routes' => store_routes(),
        'session' => [
            'cart' => $_SESSION['cart'],
            'favorites' => array_values($_SESSION['favorites']),
            'cartCount' => store_cart_count(),
            'favoriteCount' => count($_SESSION['favorites']),
            'user' => [
                'id' => store_current_user_id(),
                'name' => (string) ($_SESSION['user_name'] ?? ''),
                'email' => (string) ($_SESSION['user_email'] ?? ''),
                'role' => (string) ($_SESSION['user_role'] ?? ''),
                'avatar' => (string) ($_SESSION['user_avatar'] ?? ''),
                'avatarFit' => (string) ($_SESSION['user_avatar_fit'] ?? 'cover'),
            ],
        ],
    ];
}

function store_cart_count(): int
{
    ensure_store_state();
    $total = 0;

    foreach ($_SESSION['cart'] as $quantity) {
        $total += max(0, (int) $quantity);
    }

    return $total;
}

function store_cart_items(mysqli $connection): array
{
    ensure_store_state();
    $items = store_products_by_slugs($connection, array_keys($_SESSION['cart']));

    foreach ($items as &$item) {
        $item['quantity'] = max(1, (int) ($_SESSION['cart'][$item['slug']] ?? 1));
        $item['line_total'] = $item['quantity'] * (float) $item['price'];
    }
    unset($item);

    return $items;
}

function store_cart_subtotal(mysqli $connection): float
{
    $total = 0.0;

    foreach (store_cart_items($connection) as $item) {
        $total += (float) $item['line_total'];
    }

    return $total;
}

function store_favorite_items(mysqli $connection): array
{
    ensure_store_state();

    return store_products_by_slugs($connection, $_SESSION['favorites']);
}

function store_safe_redirect(string $redirect): string
{
    $redirect = trim(str_replace('\\', '/', $redirect));

    if ($redirect === '' || preg_match('/[\x00-\x1F\x7F]/', $redirect) === 1) {
        return './index.php';
    }

    if (str_contains($redirect, '://') || str_starts_with($redirect, '//') || preg_match('#^[a-zA-Z]:/#', $redirect) === 1) {
        return './index.php';
    }

    if (str_starts_with($redirect, '/')) {
        $basePath = trim((string) parse_url(STORE_SITE_URL, PHP_URL_PATH), '/');
        $prefix = $basePath !== '' ? '/' . $basePath : '';

        if ($prefix === '' || !str_starts_with($redirect, $prefix)) {
            return './index.php';
        }

        $redirect = './' . ltrim(substr($redirect, strlen($prefix)), '/');
    }

    if (str_starts_with($redirect, '?')) {
        $redirect = './index.php' . $redirect;
    }

    if (preg_match('#(?:^|/)\.\.(?:/|$)#', $redirect) === 1) {
        return './index.php';
    }

    if (!preg_match('~^(?:\./)?[A-Za-z0-9][A-Za-z0-9/_\.-]*(?:/)?(?:\?[^\s#]*)?(?:#[^\s]*)?$~', $redirect)) {
        return './index.php';
    }

    if (!str_starts_with($redirect, './')) {
        $redirect = './' . ltrim($redirect, './');
    }

    return $redirect;
}

function store_request_wants_json(): bool
{
    if ((string) ($_REQUEST['ajax'] ?? '') === '1') {
        return true;
    }

    $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
    $requestedWith = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));

    return str_contains($accept, 'application/json') || $requestedWith === 'xmlhttprequest';
}

function store_identity_context(): array
{
    return [
        'user_id' => store_current_user_id(),
        'session_id' => session_id(),
    ];
}

function store_product_id_by_slug(mysqli $connection, string $slug): int
{
    $stmt = @mysqli_prepare($connection, 'SELECT id FROM products WHERE slug = ? LIMIT 1');

    if (!($stmt instanceof mysqli_stmt)) {
        return 0;
    }

    mysqli_stmt_bind_param($stmt, 's', $slug);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result instanceof mysqli_result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    return (int) ($row['id'] ?? 0);
}

function store_normalize_cart(array $cart): array
{
    $clean = [];

    foreach ($cart as $slug => $quantity) {
        $slug = trim((string) $slug);
        $quantity = (int) $quantity;

        if ($slug === '' || $quantity <= 0) {
            continue;
        }

        $clean[$slug] = $quantity;
    }

    ksort($clean);

    return $clean;
}

function store_normalize_slug_list(array $slugs): array
{
    $slugs = array_values(array_unique(array_filter(array_map('strval', $slugs))));
    sort($slugs);

    return $slugs;
}

function store_fetch_cart_from_db(mysqli $connection): array
{
    if (!store_table_exists($connection, 'cart_items')) {
        return [];
    }

    $identity = store_identity_context();

    if ($identity['user_id'] <= 0 && $identity['session_id'] === '') {
        return [];
    }

    $items = [];
    $sql = $identity['user_id'] > 0
        ? 'SELECT p.slug, ci.quantity FROM cart_items ci INNER JOIN products p ON p.id = ci.product_id WHERE ci.user_id = ?'
        : 'SELECT p.slug, ci.quantity FROM cart_items ci INNER JOIN products p ON p.id = ci.product_id WHERE ci.session_id = ?';
    $stmt = @mysqli_prepare($connection, $sql);

    if (!($stmt instanceof mysqli_stmt)) {
        return $items;
    }

    if ($identity['user_id'] > 0) {
        mysqli_stmt_bind_param($stmt, 'i', $identity['user_id']);
    } else {
        mysqli_stmt_bind_param($stmt, 's', $identity['session_id']);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result instanceof mysqli_result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $items[$row['slug']] = max(1, (int) $row['quantity']);
        }
    }

    mysqli_stmt_close($stmt);

    return $items;
}

function store_fetch_favorites_from_db(mysqli $connection): array
{
    if (!store_table_exists($connection, 'favorites')) {
        return [];
    }

    $identity = store_identity_context();

    if ($identity['user_id'] <= 0 && $identity['session_id'] === '') {
        return [];
    }

    $items = [];
    $sql = $identity['user_id'] > 0
        ? 'SELECT p.slug FROM favorites f INNER JOIN products p ON p.id = f.product_id WHERE f.user_id = ?'
        : 'SELECT p.slug FROM favorites f INNER JOIN products p ON p.id = f.product_id WHERE f.session_id = ?';
    $stmt = @mysqli_prepare($connection, $sql);

    if (!($stmt instanceof mysqli_stmt)) {
        return $items;
    }

    if ($identity['user_id'] > 0) {
        mysqli_stmt_bind_param($stmt, 'i', $identity['user_id']);
    } else {
        mysqli_stmt_bind_param($stmt, 's', $identity['session_id']);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result instanceof mysqli_result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $items[] = $row['slug'];
        }
    }

    mysqli_stmt_close($stmt);

    return $items;
}

function store_write_cart_to_db(mysqli $connection, array $cart): void
{
    if (!store_table_exists($connection, 'cart_items')) {
        return;
    }

    $identity = store_identity_context();

    if ($identity['user_id'] <= 0 && $identity['session_id'] === '') {
        return;
    }

    $cart = store_normalize_cart($cart);
    $deleteStmt = $identity['user_id'] > 0
        ? @mysqli_prepare($connection, 'DELETE FROM cart_items WHERE user_id = ?')
        : @mysqli_prepare($connection, 'DELETE FROM cart_items WHERE session_id = ?');

    if ($deleteStmt instanceof mysqli_stmt) {
        if ($identity['user_id'] > 0) {
            mysqli_stmt_bind_param($deleteStmt, 'i', $identity['user_id']);
        } else {
            mysqli_stmt_bind_param($deleteStmt, 's', $identity['session_id']);
        }
        mysqli_stmt_execute($deleteStmt);
        mysqli_stmt_close($deleteStmt);
    }

    if ($cart === []) {
        return;
    }

    $insertStmt = $identity['user_id'] > 0
        ? @mysqli_prepare(
            $connection,
            'INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)'
        )
        : @mysqli_prepare(
            $connection,
            'INSERT INTO cart_items (session_id, product_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)'
        );

    if (!($insertStmt instanceof mysqli_stmt)) {
        return;
    }

    foreach ($cart as $slug => $quantity) {
        $productId = store_product_id_by_slug($connection, $slug);

        if ($productId <= 0) {
            continue;
        }

        if ($identity['user_id'] > 0) {
            mysqli_stmt_bind_param($insertStmt, 'iii', $identity['user_id'], $productId, $quantity);
        } else {
            mysqli_stmt_bind_param($insertStmt, 'sii', $identity['session_id'], $productId, $quantity);
        }
        mysqli_stmt_execute($insertStmt);
    }

    mysqli_stmt_close($insertStmt);
}

function store_write_favorites_to_db(mysqli $connection, array $favorites): void
{
    if (!store_table_exists($connection, 'favorites')) {
        return;
    }

    $identity = store_identity_context();

    if ($identity['user_id'] <= 0 && $identity['session_id'] === '') {
        return;
    }

    $favorites = store_normalize_slug_list($favorites);
    $deleteStmt = $identity['user_id'] > 0
        ? @mysqli_prepare($connection, 'DELETE FROM favorites WHERE user_id = ?')
        : @mysqli_prepare($connection, 'DELETE FROM favorites WHERE session_id = ?');

    if ($deleteStmt instanceof mysqli_stmt) {
        if ($identity['user_id'] > 0) {
            mysqli_stmt_bind_param($deleteStmt, 'i', $identity['user_id']);
        } else {
            mysqli_stmt_bind_param($deleteStmt, 's', $identity['session_id']);
        }
        mysqli_stmt_execute($deleteStmt);
        mysqli_stmt_close($deleteStmt);
    }

    if ($favorites === []) {
        return;
    }

    $insertStmt = $identity['user_id'] > 0
        ? @mysqli_prepare(
            $connection,
            'INSERT INTO favorites (user_id, product_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE user_id = user_id'
        )
        : @mysqli_prepare(
            $connection,
            'INSERT INTO favorites (session_id, product_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE session_id = session_id'
        );

    if (!($insertStmt instanceof mysqli_stmt)) {
        return;
    }

    foreach ($favorites as $slug) {
        $productId = store_product_id_by_slug($connection, $slug);

        if ($productId <= 0) {
            continue;
        }

        if ($identity['user_id'] > 0) {
            mysqli_stmt_bind_param($insertStmt, 'ii', $identity['user_id'], $productId);
        } else {
            mysqli_stmt_bind_param($insertStmt, 'si', $identity['session_id'], $productId);
        }
        mysqli_stmt_execute($insertStmt);
    }

    mysqli_stmt_close($insertStmt);
}

function store_sync_session_with_db(mysqli $connection): void
{
    $identity = store_identity_context();

    if ($identity['user_id'] <= 0 && $identity['session_id'] === '') {
        return;
    }

    $dbCart = store_fetch_cart_from_db($connection);
    $dbFavorites = store_fetch_favorites_from_db($connection);
    $sessionCart = store_normalize_cart($_SESSION['cart'] ?? []);
    $sessionFavorites = store_normalize_slug_list($_SESSION['favorites'] ?? []);
    $dbCartNormalized = store_normalize_cart($dbCart);
    $dbFavoritesNormalized = store_normalize_slug_list($dbFavorites);

    $mergedCart = $sessionCart;

    foreach ($dbCartNormalized as $slug => $quantity) {
        if (!isset($mergedCart[$slug]) || $mergedCart[$slug] < $quantity) {
            $mergedCart[$slug] = $quantity;
        }
    }

    $mergedFavorites = store_normalize_slug_list(array_merge($sessionFavorites, $dbFavoritesNormalized));

    $_SESSION['cart'] = $mergedCart;
    $_SESSION['favorites'] = $mergedFavorites;

    store_write_cart_to_db($connection, $mergedCart);
    store_write_favorites_to_db($connection, $mergedFavorites);
}

function store_cart_upsert(mysqli $connection, int $productId, int $quantity): void
{
    if (!store_table_exists($connection, 'cart_items')) {
        return;
    }

    $identity = store_identity_context();

    if ($identity['user_id'] <= 0 && $identity['session_id'] === '') {
        return;
    }

    $stmt = $identity['user_id'] > 0
        ? @mysqli_prepare(
            $connection,
            'INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)'
        )
        : @mysqli_prepare(
            $connection,
            'INSERT INTO cart_items (session_id, product_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)'
        );

    if (!($stmt instanceof mysqli_stmt)) {
        return;
    }

    if ($identity['user_id'] > 0) {
        mysqli_stmt_bind_param($stmt, 'iii', $identity['user_id'], $productId, $quantity);
    } else {
        mysqli_stmt_bind_param($stmt, 'sii', $identity['session_id'], $productId, $quantity);
    }
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function store_cart_delete(mysqli $connection, int $productId): void
{
    if (!store_table_exists($connection, 'cart_items')) {
        return;
    }

    $identity = store_identity_context();

    if ($identity['user_id'] <= 0 && $identity['session_id'] === '') {
        return;
    }

    $stmt = $identity['user_id'] > 0
        ? @mysqli_prepare($connection, 'DELETE FROM cart_items WHERE user_id = ? AND product_id = ?')
        : @mysqli_prepare($connection, 'DELETE FROM cart_items WHERE session_id = ? AND product_id = ?');

    if (!($stmt instanceof mysqli_stmt)) {
        return;
    }

    if ($identity['user_id'] > 0) {
        mysqli_stmt_bind_param($stmt, 'ii', $identity['user_id'], $productId);
    } else {
        mysqli_stmt_bind_param($stmt, 'si', $identity['session_id'], $productId);
    }
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function store_cart_add(mysqli $connection, string $productSlug): array
{
    ensure_store_state();

    $product = store_product_by_slug($connection, $productSlug);
    $productId = (int) ($product['id'] ?? 0);

    if ($productId <= 0) {
        return ['success' => false, 'message' => 'Product could not be found.'];
    }

    $quantity = (int) ($_SESSION['cart'][$productSlug] ?? 0) + 1;
    $_SESSION['cart'][$productSlug] = $quantity;
    store_cart_upsert($connection, $productId, $quantity);

    $notification = store_send_action_notifications($connection, 'cart_add', [
        'product' => $product,
        'quantity' => $quantity,
    ]);

    return [
        'success' => true,
        'message' => 'Product added to cart.',
        'product' => $product,
        'quantity' => $quantity,
        'notification' => $notification,
    ];
}

function store_cart_decrease(mysqli $connection, string $productSlug): array
{
    ensure_store_state();

    if (!isset($_SESSION['cart'][$productSlug])) {
        return ['success' => false, 'message' => 'Product was not in the cart.'];
    }

    $product = store_product_by_slug($connection, $productSlug);
    $oldQuantity = max(0, (int) ($_SESSION['cart'][$productSlug] ?? 0));
    $quantity = max(0, (int) $_SESSION['cart'][$productSlug] - 1);

    if ($quantity <= 0) {
        unset($_SESSION['cart'][$productSlug]);
        $productId = (int) ($product['id'] ?? store_product_id_by_slug($connection, $productSlug));

        if ($productId > 0) {
            store_cart_delete($connection, $productId);
        }

        $notification = store_send_action_notifications($connection, 'cart_remove', [
            'product' => $product,
            'old_quantity' => $oldQuantity,
            'quantity' => 0,
            'reason' => 'Quantity reached zero after decrease.',
        ]);

        return [
            'success' => true,
            'message' => 'Product removed from cart.',
            'product' => $product,
            'quantity' => 0,
            'notification' => $notification,
        ];
    }

    $_SESSION['cart'][$productSlug] = $quantity;
    $productId = (int) ($product['id'] ?? store_product_id_by_slug($connection, $productSlug));

    if ($productId > 0) {
        store_cart_upsert($connection, $productId, $quantity);
    }

    $notification = store_send_action_notifications($connection, 'cart_decrease', [
        'product' => $product,
        'old_quantity' => $oldQuantity,
        'quantity' => $quantity,
    ]);

    return [
        'success' => true,
        'message' => 'Cart updated.',
        'product' => $product,
        'quantity' => $quantity,
        'notification' => $notification,
    ];
}

function store_cart_remove(mysqli $connection, string $productSlug): array
{
    ensure_store_state();
    $product = store_product_by_slug($connection, $productSlug);
    $oldQuantity = max(0, (int) ($_SESSION['cart'][$productSlug] ?? 0));
    unset($_SESSION['cart'][$productSlug]);

    $productId = (int) ($product['id'] ?? store_product_id_by_slug($connection, $productSlug));

    if ($productId > 0) {
        store_cart_delete($connection, $productId);
    }

    if ($productId <= 0 && $oldQuantity <= 0) {
        return ['success' => false, 'message' => 'Product could not be found in the cart.'];
    }

    $notification = store_send_action_notifications($connection, 'cart_remove', [
        'product' => $product,
        'old_quantity' => $oldQuantity,
        'quantity' => 0,
        'reason' => 'Removed directly from cart.',
    ]);

    return [
        'success' => true,
        'message' => 'Product removed from cart.',
        'product' => $product,
        'quantity' => 0,
        'notification' => $notification,
    ];
}

function store_favorite_upsert(mysqli $connection, int $productId): void
{
    if (!store_table_exists($connection, 'favorites')) {
        return;
    }

    $identity = store_identity_context();

    if ($identity['user_id'] <= 0 && $identity['session_id'] === '') {
        return;
    }

    $stmt = $identity['user_id'] > 0
        ? @mysqli_prepare(
            $connection,
            'INSERT INTO favorites (user_id, product_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE user_id = user_id'
        )
        : @mysqli_prepare(
            $connection,
            'INSERT INTO favorites (session_id, product_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE session_id = session_id'
        );

    if (!($stmt instanceof mysqli_stmt)) {
        return;
    }

    if ($identity['user_id'] > 0) {
        mysqli_stmt_bind_param($stmt, 'ii', $identity['user_id'], $productId);
    } else {
        mysqli_stmt_bind_param($stmt, 'si', $identity['session_id'], $productId);
    }
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function store_favorite_delete(mysqli $connection, int $productId): void
{
    if (!store_table_exists($connection, 'favorites')) {
        return;
    }

    $identity = store_identity_context();

    if ($identity['user_id'] <= 0 && $identity['session_id'] === '') {
        return;
    }

    $stmt = $identity['user_id'] > 0
        ? @mysqli_prepare($connection, 'DELETE FROM favorites WHERE user_id = ? AND product_id = ?')
        : @mysqli_prepare($connection, 'DELETE FROM favorites WHERE session_id = ? AND product_id = ?');

    if (!($stmt instanceof mysqli_stmt)) {
        return;
    }

    if ($identity['user_id'] > 0) {
        mysqli_stmt_bind_param($stmt, 'ii', $identity['user_id'], $productId);
    } else {
        mysqli_stmt_bind_param($stmt, 'si', $identity['session_id'], $productId);
    }
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function store_favorite_add(mysqli $connection, string $productSlug): ?array
{
    ensure_store_state();
    
    // Try to get product from database first
    $product = store_product_by_slug($connection, $productSlug);

    // Store favorite in session regardless (works for guests too)
    $favorites = array_values($_SESSION['favorites'] ?? []);

    if (!in_array($productSlug, $favorites, true)) {
        $favorites[] = $productSlug;
        $_SESSION['favorites'] = $favorites;
        
        // Only try to save to DB if product exists
        if ($product !== null) {
            store_favorite_upsert($connection, (int) $product['id']);
        }
    }

    // Return dummy product data if not found in DB (so guest favorites still work)
    return $product ?? ['id' => 0, 'slug' => $productSlug, 'name' => htmlspecialchars($productSlug)];
}

function store_favorite_remove(mysqli $connection, string $productSlug): void
{
    ensure_store_state();
    $favorites = array_values($_SESSION['favorites'] ?? []);
    $_SESSION['favorites'] = array_values(array_filter($favorites, static fn ($slug) => $slug !== $productSlug));

    $productId = store_product_id_by_slug($connection, $productSlug);

    if ($productId > 0) {
        store_favorite_delete($connection, $productId);
    }
}

function store_toggle_favorite(mysqli $connection, string $productSlug): array
{
    ensure_store_state();
    $favorites = array_values($_SESSION['favorites'] ?? []);
    $product = store_product_by_slug($connection, $productSlug);

    if (in_array($productSlug, $favorites, true)) {
        store_favorite_remove($connection, $productSlug);
        $notification = store_send_action_notifications($connection, 'favorite_remove', [
            'product' => $product,
            'favorite_total' => count($_SESSION['favorites'] ?? []),
        ]);

        return [
            'favorited' => false,
            'message' => 'Removed from favorites.',
            'product' => $product,
            'notification' => $notification,
        ];
    }

    $product = store_favorite_add($connection, $productSlug);

    if ($product === null) {
        return ['favorited' => false, 'message' => 'Favorite could not be saved.'];
    }

    $message = 'Added to favorites.';
    $notification = store_send_action_notifications($connection, 'favorite_add', [
        'product' => $product,
        'favorite_total' => count($_SESSION['favorites'] ?? []),
    ]);
    store_log_favorite($connection, $product, $message);

    return [
        'favorited' => true,
        'message' => $message,
        'product' => $product,
        'notification' => $notification,
    ];
}

function store_find_or_create_term(mysqli $connection, string $table, string $name): int
{
    $name = trim($name);

    if ($name === '') {
        return 0;
    }

    $slug = store_slug_from_text($name);
    $existingId = store_term_id($connection, $table, $slug);

    if ($table === 'brands') {
        return store_find_or_create_brand($connection, $name);
    }

    if ($existingId > 0) {
        return $existingId;
    }

    $fields = ['sql' => 'INSERT INTO categories (name, slug, note, document_path) VALUES (?, ?, ?, ?)', 'extra1' => 'Added from product form.', 'extra2' => 'docs/haier-frostcool-inverter-ac.doc'];

    $stmt = @mysqli_prepare($connection, $fields['sql']);

    if (!($stmt instanceof mysqli_stmt)) {
        return 0;
    }

    mysqli_stmt_bind_param($stmt, 'ssss', $name, $slug, $fields['extra1'], $fields['extra2']);

    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return store_term_id($connection, $table, $slug);
}

function store_find_or_create_brand(mysqli $connection, string $name, string $logoPath = ''): int
{
    $name = trim($name);

    if ($name === '') {
        return 0;
    }

    $slug = store_slug_from_text($name);
    $brandId = store_term_id($connection, 'brands', $slug);
    $normalizedLogo = store_normalize_brand_logo_path($slug, $logoPath);

    if ($brandId > 0) {
        if ($logoPath !== '') {
            $stmt = @mysqli_prepare($connection, 'UPDATE brands SET logo_path = ? WHERE id = ?');

            if ($stmt instanceof mysqli_stmt) {
                mysqli_stmt_bind_param($stmt, 'si', $normalizedLogo, $brandId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }

        return $brandId;
    }

    $stmt = @mysqli_prepare($connection, 'INSERT INTO brands (name, slug, logo_path) VALUES (?, ?, ?)');

    if (!($stmt instanceof mysqli_stmt)) {
        return 0;
    }

    mysqli_stmt_bind_param($stmt, 'sss', $name, $slug, $normalizedLogo);
    $saved = mysqli_stmt_execute($stmt);
    $brandId = $saved ? (int) mysqli_insert_id($connection) : 0;
    mysqli_stmt_close($stmt);

    return $brandId > 0 ? $brandId : store_term_id($connection, 'brands', $slug);
}

function store_upload_product_image(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Please choose a product image.'];
    }

    $tmpPath = $file['tmp_name'] ?? '';

    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        return ['success' => false, 'message' => 'Invalid uploaded image.'];
    }

    $mimeType = mime_content_type($tmpPath) ?: '';
    $allowed = store_allowed_image_types();

    if (!isset($allowed[$mimeType])) {
        return ['success' => false, 'message' => 'Only JPG, PNG, GIF, WEBP, and AVIF images are allowed.'];
    }

    return store_move_uploaded_image($tmpPath, $allowed[$mimeType]);
}

function store_allowed_image_types(): array
{
    return [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'image/avif' => 'avif',
    ];
}

function store_move_uploaded_image(string $tmpPath, string $extension, string $folder = 'products', string $prefix = 'product_'): array
{
    $folder = trim($folder, '/\\');
    $uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $folder;

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
        return ['success' => false, 'message' => 'Upload folder could not be created.'];
    }

    $fileName = uniqid($prefix, true) . '.' . $extension;
    $destination = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

    if (!move_uploaded_file($tmpPath, $destination)) {
        return ['success' => false, 'message' => 'Image upload failed.'];
    }

    return ['success' => true, 'path' => 'uploads/' . $folder . '/' . $fileName];
}

function store_upload_brand_logo(array $file, bool $required = false): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $required
            ? ['success' => false, 'message' => 'Please upload a logo for the new brand.']
            : ['success' => true, 'path' => null];
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Brand logo upload failed.'];
    }

    $tmpPath = $file['tmp_name'] ?? '';

    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        return ['success' => false, 'message' => 'Invalid brand logo upload.'];
    }

    $mimeType = mime_content_type($tmpPath) ?: '';
    $allowed = store_allowed_image_types();

    if (!isset($allowed[$mimeType])) {
        return ['success' => false, 'message' => 'Brand logo must be JPG, PNG, GIF, WEBP, or AVIF.'];
    }

    return store_move_uploaded_image($tmpPath, $allowed[$mimeType], 'brands', 'brand_');
}

function store_upload_gallery_images(array $files): array
{
    if (!isset($files['name']) || !is_array($files['name'])) {
        return ['success' => true, 'paths' => []];
    }

    $allowed = store_allowed_image_types();
    $paths = [];

    foreach ($files['name'] as $index => $name) {
        $error = $files['error'][$index] ?? UPLOAD_ERR_NO_FILE;

        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if ($error !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'One of the gallery images failed to upload.'];
        }

        $tmpPath = $files['tmp_name'][$index] ?? '';

        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            return ['success' => false, 'message' => 'Invalid gallery image upload.'];
        }

        $mimeType = mime_content_type($tmpPath) ?: '';

        if (!isset($allowed[$mimeType])) {
            return ['success' => false, 'message' => 'Only JPG, PNG, GIF, WEBP, and AVIF images are allowed.'];
        }

        $move = store_move_uploaded_image($tmpPath, $allowed[$mimeType]);

        if (!($move['success'] ?? false)) {
            return ['success' => false, 'message' => $move['message'] ?? 'Gallery image upload failed.'];
        }

        $paths[] = $move['path'];
    }

    return ['success' => true, 'paths' => $paths];
}

function store_upload_optional_product_image(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'path' => null];
    }

    return store_upload_product_image($file);
}

function store_parse_gallery_images(?string $galleryJson): array
{
    if (!$galleryJson) {
        return [];
    }

    $decoded = json_decode($galleryJson, true);

    if (!is_array($decoded)) {
        return [];
    }

    $paths = [];

    foreach ($decoded as $path) {
        $path = trim((string) $path);
        if ($path !== '') {
            $paths[] = $path;
        }
    }

    return $paths;
}

function store_encode_gallery_images(array $paths): string
{
    $clean = [];

    foreach ($paths as $path) {
        $path = trim((string) $path);
        if ($path !== '') {
            $clean[] = $path;
        }
    }

    return json_encode(array_values(array_unique($clean)), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

function store_create_product(mysqli $connection, array $post, array $files): array
{
    if (!store_user_has_permission($connection, 'create_product')) {
        return ['success' => false, 'message' => 'Admin approval is required before this account can add products.'];
    }

    $name = trim($post['name'] ?? '');
    $summary = trim($post['summary'] ?? '');
    $description = trim($post['description'] ?? '');
    $price = (float) ($post['price'] ?? 0);
    $originalPrice = trim($post['original_price'] ?? '') === '' ? $price : (float) $post['original_price'];
    $categoryId = (int) ($post['category_id'] ?? 0);
    $brandId = (int) ($post['brand_id'] ?? 0);
    $newCategory = trim($post['new_category'] ?? '');
    $newBrand = trim($post['new_brand'] ?? '');
    $documentPath = trim($post['document_path'] ?? '');
    $customLabel = trim((string) ($post['custom_label'] ?? ''));
    $selectedLabelIds = function_exists('store_cms_extract_label_ids')
        ? store_cms_extract_label_ids($connection, $post['label_ids'] ?? [])
        : [];

    if ($name === '' || $summary === '' || $description === '' || $price <= 0) {
        return ['success' => false, 'message' => 'Please fill in all required product fields.'];
    }

    if ($customLabel === '' && $selectedLabelIds !== [] && function_exists('store_product_label_by_id')) {
        $firstLabel = store_product_label_by_id($connection, (int) $selectedLabelIds[0]);
        $customLabel = (string) ($firstLabel['name'] ?? '');
    }

    if ($newCategory !== '') {
        $categoryId = store_find_or_create_term($connection, 'categories', $newCategory);
    }

    if ($newBrand !== '') {
        $newBrandSlug = store_slug_from_text($newBrand);
        $existingBrandId = store_term_id($connection, 'brands', $newBrandSlug);
        $brandLogoUpload = store_upload_brand_logo($files['new_brand_logo'] ?? [], $existingBrandId <= 0);

        if (($brandLogoUpload['success'] ?? false) !== true) {
            return ['success' => false, 'message' => $brandLogoUpload['message'] ?? 'Brand logo is required.'];
        }

        $brandId = store_find_or_create_brand($connection, $newBrand, (string) ($brandLogoUpload['path'] ?? ''));

        if ($brandId > 0 && function_exists('store_save_brand_profile')) {
            store_save_brand_profile(
                $connection,
                [
                    'brand_id' => $brandId,
                    'name' => $newBrand,
                    'logo_path' => (string) ($brandLogoUpload['path'] ?? ''),
                    'hero_title' => (string) ($post['new_brand_hero_title'] ?? ''),
                    'hero_summary' => (string) ($post['new_brand_hero_summary'] ?? ''),
                    'slider_banner_path' => (string) ($post['new_brand_slider_banner_path'] ?? ''),
                    'hero_poster_path' => (string) ($post['new_brand_hero_poster_path'] ?? ''),
                    'hero_video_path' => (string) ($post['new_brand_hero_video_path'] ?? ''),
                    'splash_video_path' => (string) ($post['new_brand_splash_video_path'] ?? ''),
                    'splash_logo_path' => (string) ($post['new_brand_splash_logo_path'] ?? ''),
                    'featured_product_slug' => '',
                    'badges' => (string) ($post['new_brand_badges'] ?? ''),
                    'is_visible' => '1',
                ],
                [
                    'logo_file' => $files['new_brand_logo'] ?? [],
                    'slider_banner_file' => $files['new_brand_slider_banner_file'] ?? [],
                    'hero_poster_file' => $files['new_brand_hero_poster_file'] ?? [],
                    'hero_video_file' => $files['new_brand_hero_video_file'] ?? [],
                    'splash_video_file' => $files['new_brand_splash_video_file'] ?? [],
                    'splash_logo_file' => $files['new_brand_splash_logo_file'] ?? [],
                ]
            );
        }
    }

    if ($categoryId <= 0 || $brandId <= 0) {
        return ['success' => false, 'message' => 'Please select or create a category and brand.'];
    }

    $upload = store_upload_product_image($files['image'] ?? []);

    if (($upload['success'] ?? false) !== true) {
        return ['success' => false, 'message' => $upload['message'] ?? 'Image upload failed.'];
    }

    $galleryUpload = store_upload_gallery_images($files['view_images'] ?? []);

    if (($galleryUpload['success'] ?? false) !== true) {
        return ['success' => false, 'message' => $galleryUpload['message'] ?? 'Gallery images could not be uploaded.'];
    }

    $galleryJson = store_encode_gallery_images($galleryUpload['paths'] ?? []);

    $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $name) ?? '', '-'));
    $discountPercent = $originalPrice > $price && $originalPrice > 0 ? (int) round((1 - ($price / $originalPrice)) * 100) : 0;
    $isFeatured = isset($post['is_featured']) ? 1 : 0;

    if ($documentPath === '') {
        $documentPath = 'docs/haier-frostcool-inverter-ac.doc';
    }

    $hasCreatorColumn = store_column_exists($connection, 'products', 'created_by');
    $creatorId = store_current_user_id();
    $hasGalleryColumn = store_column_exists($connection, 'products', 'gallery_images');
    $sql = $hasCreatorColumn || $hasGalleryColumn
        ? 'INSERT INTO products (
            name, slug, brand_id, category_id, summary, description, document_path, image_path,
            price, original_price, discount_percent, is_featured, custom_label' .
            ($hasGalleryColumn ? ', gallery_images' : '') .
            ($hasCreatorColumn ? ', created_by' : '') .
        ') VALUES (' .
            '?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?' .
            ($hasGalleryColumn ? ', ?' : '') .
            ($hasCreatorColumn ? ', ?' : '') .
        ')'
        : 'INSERT INTO products (
            name, slug, brand_id, category_id, summary, description, document_path, image_path,
            price, original_price, discount_percent, is_featured, custom_label
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

    $stmt = @mysqli_prepare($connection, $sql);

    if (!($stmt instanceof mysqli_stmt)) {
        return ['success' => false, 'message' => 'Product could not be prepared for saving.'];
    }

    $types = 'ssiissssddiis';
    $values = [
        $name,
        $slug,
        $brandId,
        $categoryId,
        $summary,
        $description,
        $documentPath,
        $upload['path'],
        $price,
        $originalPrice,
        $discountPercent,
        $isFeatured,
        $customLabel,
    ];

    if ($hasGalleryColumn) {
        $types .= 's';
        $values[] = $galleryJson;
    }

    if ($hasCreatorColumn) {
        $types .= 'i';
        $values[] = $creatorId;
    }

    mysqli_stmt_bind_param($stmt, $types, ...$values);

    $saved = mysqli_stmt_execute($stmt);
    $productId = $saved ? (int) mysqli_insert_id($connection) : 0;
    mysqli_stmt_close($stmt);

    if (!$saved) {
        return ['success' => false, 'message' => 'Product could not be saved. Try a different product name.'];
    }

    $product = $productId > 0 ? store_product_by_id($connection, $productId) : store_product_by_slug($connection, $slug);
    if ($product && function_exists('store_sync_product_labels')) {
        store_sync_product_labels($connection, (int) ($product['id'] ?? 0), $selectedLabelIds);
    }
    $notification = store_send_action_notifications($connection, 'product_create', [
        'product' => $product,
    ]);

    return [
        'success' => true,
        'message' => 'Product added successfully.',
        'product' => $product,
        'notification' => $notification,
    ];
}

function store_update_product(mysqli $connection, array $product, array $post, array $files): array
{
    $currentUserId = store_current_user_id();
    $isOwner = (int) ($product['created_by'] ?? 0) === $currentUserId && $currentUserId > 0;
    $canManage = store_user_has_permission($connection, 'manage_products');
    $canEditOwn = store_user_has_permission($connection, 'edit_own_product');

    if (!$canManage && !($isOwner && $canEditOwn)) {
        return ['success' => false, 'message' => 'You do not have permission to edit this product.'];
    }

    $before = $product;
    $name = trim($post['name'] ?? '');
    $summary = trim($post['summary'] ?? '');
    $description = trim($post['description'] ?? '');
    $price = (float) ($post['price'] ?? 0);
    $originalPrice = trim($post['original_price'] ?? '') === '' ? $price : (float) $post['original_price'];
    $categoryId = (int) ($post['category_id'] ?? 0);
    $brandId = (int) ($post['brand_id'] ?? 0);
    $newCategory = trim($post['new_category'] ?? '');
    $newBrand = trim($post['new_brand'] ?? '');
    $documentPath = trim($post['document_path'] ?? '');
    $customLabel = trim((string) ($post['custom_label'] ?? ''));
    $selectedLabelIds = function_exists('store_cms_extract_label_ids')
        ? store_cms_extract_label_ids($connection, $post['label_ids'] ?? [])
        : [];

    if ($name === '' || $summary === '' || $description === '' || $price <= 0) {
        return ['success' => false, 'message' => 'Please fill in all required product fields.'];
    }

    if ($customLabel === '' && $selectedLabelIds !== [] && function_exists('store_product_label_by_id')) {
        $firstLabel = store_product_label_by_id($connection, (int) $selectedLabelIds[0]);
        $customLabel = (string) ($firstLabel['name'] ?? '');
    }

    if ($newCategory !== '') {
        $categoryId = store_find_or_create_term($connection, 'categories', $newCategory);
    }

    if ($newBrand !== '') {
        $newBrandSlug = store_slug_from_text($newBrand);
        $existingBrandId = store_term_id($connection, 'brands', $newBrandSlug);
        $brandLogoUpload = store_upload_brand_logo($files['new_brand_logo'] ?? [], $existingBrandId <= 0);

        if (($brandLogoUpload['success'] ?? false) !== true) {
            return ['success' => false, 'message' => $brandLogoUpload['message'] ?? 'Brand logo is required for the new brand.'];
        }

        $brandId = store_find_or_create_brand($connection, $newBrand, (string) ($brandLogoUpload['path'] ?? ''));

        if ($brandId > 0 && function_exists('store_save_brand_profile')) {
            store_save_brand_profile(
                $connection,
                [
                    'brand_id' => $brandId,
                    'name' => $newBrand,
                    'logo_path' => (string) ($brandLogoUpload['path'] ?? ''),
                    'hero_title' => (string) ($post['new_brand_hero_title'] ?? ''),
                    'hero_summary' => (string) ($post['new_brand_hero_summary'] ?? ''),
                    'slider_banner_path' => (string) ($post['new_brand_slider_banner_path'] ?? ''),
                    'hero_poster_path' => (string) ($post['new_brand_hero_poster_path'] ?? ''),
                    'hero_video_path' => (string) ($post['new_brand_hero_video_path'] ?? ''),
                    'splash_video_path' => (string) ($post['new_brand_splash_video_path'] ?? ''),
                    'splash_logo_path' => (string) ($post['new_brand_splash_logo_path'] ?? ''),
                    'featured_product_slug' => (string) ($product['slug'] ?? ''),
                    'badges' => (string) ($post['new_brand_badges'] ?? ''),
                    'is_visible' => '1',
                ],
                [
                    'logo_file' => $files['new_brand_logo'] ?? [],
                    'slider_banner_file' => $files['new_brand_slider_banner_file'] ?? [],
                    'hero_poster_file' => $files['new_brand_hero_poster_file'] ?? [],
                    'hero_video_file' => $files['new_brand_hero_video_file'] ?? [],
                    'splash_video_file' => $files['new_brand_splash_video_file'] ?? [],
                    'splash_logo_file' => $files['new_brand_splash_logo_file'] ?? [],
                ]
            );
        }
    }

    if ($categoryId <= 0 || $brandId <= 0) {
        return ['success' => false, 'message' => 'Please select or create a category and brand.'];
    }

    $upload = store_upload_optional_product_image($files['image'] ?? []);

    if (($upload['success'] ?? false) !== true) {
        return ['success' => false, 'message' => $upload['message'] ?? 'Image upload failed.'];
    }

    $galleryUpload = store_upload_gallery_images($files['view_images'] ?? []);

    if (($galleryUpload['success'] ?? false) !== true) {
        return ['success' => false, 'message' => $galleryUpload['message'] ?? 'Gallery images could not be uploaded.'];
    }

    $existingGallery = store_parse_gallery_images($product['gallery_images'] ?? '');
    $removeGallery = array_values(array_filter(array_map('strval', $post['remove_gallery'] ?? [])));
    $remainingGallery = array_values(array_filter($existingGallery, static function ($path) use ($removeGallery) {
        return !in_array($path, $removeGallery, true);
    }));
    $mergedGallery = array_merge($remainingGallery, $galleryUpload['paths'] ?? []);
    $galleryJson = store_encode_gallery_images($mergedGallery);
    $discountPercent = $originalPrice > $price && $originalPrice > 0 ? (int) round((1 - ($price / $originalPrice)) * 100) : 0;
    $isFeatured = isset($post['is_featured']) ? 1 : 0;
    $imagePath = $upload['path'] ?: (string) ($product['image_path'] ?? '');

    if ($documentPath === '') {
        $documentPath = (string) ($product['document_path'] ?? 'docs/haier-frostcool-inverter-ac.doc');
    }

    $hasGalleryColumn = store_column_exists($connection, 'products', 'gallery_images');
    $sql = 'UPDATE products SET name = ?, brand_id = ?, category_id = ?, summary = ?, description = ?, document_path = ?, image_path = ?, price = ?, original_price = ?, discount_percent = ?, is_featured = ?, custom_label = ?' .
        ($hasGalleryColumn ? ', gallery_images = ?' : '') .
        ' WHERE id = ?';

    $stmt = @mysqli_prepare($connection, $sql);

    if (!($stmt instanceof mysqli_stmt)) {
        return ['success' => false, 'message' => 'Product could not be updated.'];
    }

    if ($hasGalleryColumn) {
        mysqli_stmt_bind_param(
            $stmt,
            'siissssddiissi',
            $name,
            $brandId,
            $categoryId,
            $summary,
            $description,
            $documentPath,
            $imagePath,
            $price,
            $originalPrice,
            $discountPercent,
            $isFeatured,
            $customLabel,
            $galleryJson,
            $product['id']
        );
    } else {
        mysqli_stmt_bind_param(
            $stmt,
            'siissssddiii',
            $name,
            $brandId,
            $categoryId,
            $summary,
            $description,
            $documentPath,
            $imagePath,
            $price,
            $originalPrice,
            $discountPercent,
            $isFeatured,
            $customLabel,
            $product['id']
        );
    }

    $saved = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if (!$saved) {
        return ['success' => false, 'message' => 'Product could not be updated.'];
    }

    $updatedProduct = store_product_by_id($connection, (int) $product['id']);
    if ($updatedProduct && function_exists('store_sync_product_labels')) {
        store_sync_product_labels($connection, (int) ($updatedProduct['id'] ?? 0), $selectedLabelIds);
    }
    $notification = store_send_action_notifications($connection, 'product_update', [
        'product' => $updatedProduct,
        'before' => $before,
        'changes' => store_product_change_summary($before, $updatedProduct ?? $before),
    ]);

    return [
        'success' => true,
        'message' => 'Product updated successfully.',
        'product' => $updatedProduct,
        'notification' => $notification,
    ];
}

function store_user_by_email(mysqli $connection, string $email): ?array
{
    $stmt = @mysqli_prepare($connection, 'SELECT id, name, email, password_hash, google_id, role, profile_image, avatar_fit FROM users WHERE email = ? LIMIT 1');

    if (!($stmt instanceof mysqli_stmt)) {
        $stmt = @mysqli_prepare($connection, 'SELECT id, name, email, password_hash, google_id, avatar_fit FROM users WHERE email = ? LIMIT 1');
    }

    if (!($stmt instanceof mysqli_stmt)) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result instanceof mysqli_result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    if (!$row) {
        return null;
    }

    $row['role'] = $row['role'] ?? 'viewer';
    $row['profile_image'] = $row['profile_image'] ?? null;
    $row['avatar_fit'] = $row['avatar_fit'] ?? 'cover';

    return $row;
}

function store_user_by_google_id(mysqli $connection, string $googleId): ?array
{
    $stmt = @mysqli_prepare($connection, 'SELECT id, name, email, password_hash, google_id, role, profile_image, avatar_fit FROM users WHERE google_id = ? LIMIT 1');

    if (!($stmt instanceof mysqli_stmt)) {
        $stmt = @mysqli_prepare($connection, 'SELECT id, name, email, password_hash, google_id, avatar_fit FROM users WHERE google_id = ? LIMIT 1');
    }

    if (!($stmt instanceof mysqli_stmt)) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 's', $googleId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result instanceof mysqli_result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    if (!$row) {
        return null;
    }

    $row['role'] = $row['role'] ?? 'viewer';
    $row['profile_image'] = $row['profile_image'] ?? null;
    $row['avatar_fit'] = $row['avatar_fit'] ?? 'cover';

    return $row;
}

function store_user_by_id(mysqli $connection, int $userId): ?array
{
    $stmt = @mysqli_prepare($connection, 'SELECT id, name, email, password_hash, google_id, role, profile_image, avatar_fit FROM users WHERE id = ? LIMIT 1');

    if (!($stmt instanceof mysqli_stmt)) {
        $stmt = @mysqli_prepare($connection, 'SELECT id, name, email, password_hash, google_id, avatar_fit FROM users WHERE id = ? LIMIT 1');
    }

    if (!($stmt instanceof mysqli_stmt)) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result instanceof mysqli_result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    if (!$row) {
        return null;
    }

    $row['role'] = $row['role'] ?? 'viewer';
    $row['profile_image'] = $row['profile_image'] ?? null;
    $row['avatar_fit'] = $row['avatar_fit'] ?? 'cover';

    return $row;
}

function store_register_user(mysqli $connection, array $data): array
{
    store_initialize($connection);

    $name = trim((string) ($data['name'] ?? ''));
    $email = trim((string) ($data['email'] ?? ''));
    $password = (string) ($data['password'] ?? '');

    if ($name === '' || $email === '' || $password === '') {
        return ['success' => false, 'message' => 'Please fill in all registration fields.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Please enter a valid email address.'];
    }

    if (store_user_by_email($connection, $email) !== null) {
        return ['success' => false, 'message' => 'This email is already registered.'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = @mysqli_prepare($connection, 'INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');

    if (!($stmt instanceof mysqli_stmt)) {
        store_migrate_schema($connection);
        $stmt = @mysqli_prepare($connection, 'INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
    }

    if (!($stmt instanceof mysqli_stmt)) {
        return ['success' => false, 'message' => 'Account could not be created.'];
    }

    mysqli_stmt_bind_param($stmt, 'sss', $name, $email, $hash);
    $saved = mysqli_stmt_execute($stmt);
    $stmtErrno = $saved ? 0 : (int) mysqli_stmt_errno($stmt);
    $userId = $saved ? (int) mysqli_insert_id($connection) : 0;
    mysqli_stmt_close($stmt);

    if (!$saved || $userId <= 0) {
        if ($stmtErrno === 1062) {
            return ['success' => false, 'message' => 'This email is already registered.'];
        }
        return ['success' => false, 'message' => 'Account could not be created.'];
    }

    $user = store_user_by_id($connection, $userId);
    if ($user) {
        store_set_logged_in_user($user);
        store_sync_session_with_db($connection);
    }

    return ['success' => true, 'message' => 'Account created successfully.'];
}

function store_login_user(mysqli $connection, array $data): array
{
    store_initialize($connection);

    $email = trim((string) ($data['email'] ?? ''));
    $password = (string) ($data['password'] ?? '');

    if ($email === '' || $password === '') {
        return ['success' => false, 'message' => 'Please enter your email and password.'];
    }

    $user = store_user_by_email($connection, $email);

    if (!$user) {
        if (defined('STORE_ADMIN_EMAIL') && defined('STORE_ADMIN_PASSWORD') && $email === STORE_ADMIN_EMAIL && $password === STORE_ADMIN_PASSWORD) {
            store_seed_admin_user($connection);
            $user = store_user_by_email($connection, $email);
        }
    }

    if (!$user || !isset($user['password_hash']) || !password_verify($password, (string) $user['password_hash'])) {
        return ['success' => false, 'message' => 'Login failed. Check your credentials.'];
    }

    store_set_logged_in_user($user);
    store_sync_session_with_db($connection);

    return ['success' => true, 'message' => 'Welcome back!'];
}

function store_google_enabled(): bool
{
    return defined('STORE_GOOGLE_CLIENT_ID')
        && defined('STORE_GOOGLE_CLIENT_SECRET')
        && defined('STORE_GOOGLE_REDIRECT')
        && STORE_GOOGLE_CLIENT_ID !== ''
        && STORE_GOOGLE_CLIENT_SECRET !== ''
        && STORE_GOOGLE_REDIRECT !== '';
}

function store_google_ready(): bool
{
    return store_google_enabled() && function_exists('store_google_client_available') && store_google_client_available();
}

function store_google_oauth_url(string $redirect): string
{
    $state = bin2hex(random_bytes(16));
    $_SESSION['google_oauth_state'] = $state;
    $_SESSION['login_redirect'] = $redirect;

    $params = [
        'client_id' => STORE_GOOGLE_CLIENT_ID,
        'redirect_uri' => STORE_GOOGLE_REDIRECT,
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'access_type' => 'online',
        'prompt' => 'select_account',
        'state' => $state,
    ];

    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
}

function store_http_post_form(string $url, array $data): ?array
{
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($data),
            'ignore_errors' => true,
            'timeout' => 15,
        ],
    ]);

    $body = @file_get_contents($url, false, $context);

    if ($body === false) {
        return null;
    }

    return json_decode($body, true);
}

function store_http_get_json(string $url, array $headers = []): ?array
{
    $headerLines = '';
    foreach ($headers as $header) {
        $headerLines .= $header . "\r\n";
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => $headerLines,
            'ignore_errors' => true,
            'timeout' => 15,
        ],
    ]);

    $body = @file_get_contents($url, false, $context);

    if ($body === false) {
        return null;
    }

    return json_decode($body, true);
}

function store_google_exchange_code(string $code): ?array
{
    return store_http_post_form('https://oauth2.googleapis.com/token', [
        'code' => $code,
        'client_id' => STORE_GOOGLE_CLIENT_ID,
        'client_secret' => STORE_GOOGLE_CLIENT_SECRET,
        'redirect_uri' => STORE_GOOGLE_REDIRECT,
        'grant_type' => 'authorization_code',
    ]);
}

function store_google_fetch_profile(string $accessToken): ?array
{
    return store_http_get_json('https://openidconnect.googleapis.com/v1/userinfo', [
        'Authorization: Bearer ' . $accessToken,
    ]);
}

function store_login_with_google(mysqli $connection, array $profile): array
{
    $googleId = trim((string) ($profile['sub'] ?? ''));
    $email = trim((string) ($profile['email'] ?? ''));
    $name = trim((string) ($profile['name'] ?? ''));

    if ($googleId === '' || $email === '') {
        return ['success' => false, 'message' => 'Google login failed. Profile data missing.'];
    }

    $user = store_user_by_google_id($connection, $googleId);

    if (!$user) {
        $user = store_user_by_email($connection, $email);

        if ($user) {
            $stmt = @mysqli_prepare($connection, 'UPDATE users SET google_id = ? WHERE id = ?');
            if ($stmt instanceof mysqli_stmt) {
                mysqli_stmt_bind_param($stmt, 'si', $googleId, $user['id']);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        } else {
            $stmt = @mysqli_prepare($connection, 'INSERT INTO users (name, email, google_id) VALUES (?, ?, ?)');
            if ($stmt instanceof mysqli_stmt) {
                mysqli_stmt_bind_param($stmt, 'sss', $name !== '' ? $name : 'Google User', $email, $googleId);
                mysqli_stmt_execute($stmt);
                $userId = (int) mysqli_insert_id($connection);
                mysqli_stmt_close($stmt);
                $user = $userId > 0 ? store_user_by_id($connection, $userId) : null;
            }
        }
    }

    if (!$user) {
        return ['success' => false, 'message' => 'Google login failed. User could not be saved.'];
    }

    store_set_logged_in_user($user);
    store_sync_session_with_db($connection);

    return ['success' => true, 'message' => 'Google login successful.'];
}

function store_current_user(mysqli $connection): ?array
{
    $userId = store_current_user_id();

    if ($userId <= 0) {
        return null;
    }

    return store_user_by_id($connection, $userId);
}

function store_users(mysqli $connection): array
{
    $items = [];
    $result = @mysqli_query($connection, 'SELECT id, name, email, role, profile_image, avatar_fit, created_at FROM users ORDER BY created_at DESC');

    if (!($result instanceof mysqli_result)) {
        return $items;
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
    }

    return $items;
}

function store_products_by_user(mysqli $connection, int $userId): array
{
    $items = [];
    $stmt = @mysqli_prepare(
        $connection,
        'SELECT
            p.id,
            p.name,
            p.slug,
            p.summary,
            p.image_path,
            p.custom_label,
            p.view_count,
            p.price,
            p.created_at,
            c.name AS category_name,
            b.name AS brand_name
        FROM products p
        INNER JOIN categories c ON c.id = p.category_id
        INNER JOIN brands b ON b.id = p.brand_id
        WHERE p.created_by = ?
        ORDER BY p.created_at DESC'
    );

    if (!($stmt instanceof mysqli_stmt)) {
        return $items;
    }

    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result instanceof mysqli_result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $items[] = $row;
        }
    }

    mysqli_stmt_close($stmt);

    return $items;
}

function store_review_summary_by_product(mysqli $connection, array $productIds): array
{
    $summary = [];
    $productIds = array_values(array_filter(array_map('intval', $productIds)));

    if ($productIds === [] || !store_table_exists($connection, 'reviews')) {
        return $summary;
    }

    $idList = implode(',', $productIds);
    $result = @mysqli_query(
        $connection,
        'SELECT product_id, COUNT(*) AS total, AVG(rating) AS avg_rating
         FROM reviews
         WHERE product_id IN (' . $idList . ')
         GROUP BY product_id'
    );

    if (!($result instanceof mysqli_result)) {
        return $summary;
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $productId = (int) ($row['product_id'] ?? 0);
        if ($productId <= 0) {
            continue;
        }
        $summary[$productId] = [
            'count' => (int) ($row['total'] ?? 0),
            'average' => round((float) ($row['avg_rating'] ?? 0), 1),
        ];
    }

    return $summary;
}

function store_reviews_for_products(mysqli $connection, array $productIds, int $limitPerProduct = 5): array
{
    $reviews = [];
    $productIds = array_values(array_filter(array_map('intval', $productIds)));

    if ($productIds === [] || !store_table_exists($connection, 'reviews')) {
        return $reviews;
    }

    $idList = implode(',', $productIds);
    $result = @mysqli_query(
        $connection,
        'SELECT r.product_id, r.rating, r.comment, r.created_at, u.name AS user_name, u.profile_image AS user_avatar
         FROM reviews r
         LEFT JOIN users u ON u.id = r.user_id
         WHERE r.product_id IN (' . $idList . ')
         ORDER BY r.created_at DESC'
    );

    if (!($result instanceof mysqli_result)) {
        return $reviews;
    }

    $counts = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $productId = (int) ($row['product_id'] ?? 0);
        if ($productId <= 0) {
            continue;
        }

        $counts[$productId] = $counts[$productId] ?? 0;
        if ($counts[$productId] >= $limitPerProduct) {
            continue;
        }

        $reviews[$productId] = $reviews[$productId] ?? [];
        $reviews[$productId][] = [
            'rating' => (int) ($row['rating'] ?? 0),
            'comment' => (string) ($row['comment'] ?? ''),
            'createdAt' => (string) ($row['created_at'] ?? ''),
            'userName' => (string) ($row['user_name'] ?? 'Guest'),
            'userAvatar' => (string) ($row['user_avatar'] ?? ''),
        ];
        $counts[$productId] += 1;
    }

    return $reviews;
}

function store_create_review(mysqli $connection, string $productSlug, int $userId, int $rating, string $comment): array
{
    $productSlug = trim($productSlug);
    $comment = trim($comment);

    if ($productSlug === '' || $userId <= 0) {
        return ['success' => false, 'message' => 'Please login before adding a review.'];
    }

    if ($rating < 1 || $rating > 5) {
        return ['success' => false, 'message' => 'Please choose a rating from 1 to 5.'];
    }

    if ($comment === '') {
        return ['success' => false, 'message' => 'Please add a short review message.'];
    }

    store_migrate_schema($connection);

    if (!store_table_exists($connection, 'reviews')) {
        return ['success' => false, 'message' => 'Reviews are not available yet.'];
    }

    $productId = store_product_id_by_slug($connection, $productSlug);

    if ($productId <= 0) {
        return ['success' => false, 'message' => 'Product could not be found for review.'];
    }

    $stmt = @mysqli_prepare($connection, 'INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)');

    if (!($stmt instanceof mysqli_stmt)) {
        return ['success' => false, 'message' => 'Review could not be saved.'];
    }

    mysqli_stmt_bind_param($stmt, 'iiis', $productId, $userId, $rating, $comment);
    $saved = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if (!$saved) {
        return ['success' => false, 'message' => 'Review could not be saved.'];
    }

    $product = store_product_by_id($connection, $productId);
    $notification = store_send_action_notifications($connection, 'review_create', [
        'product' => $product,
        'rating' => $rating,
        'comment' => $comment,
    ]);

    return [
        'success' => true,
        'message' => 'Review submitted successfully.',
        'product' => $product,
        'notification' => $notification,
    ];
}

function store_products_with_owner(mysqli $connection): array
{
    $items = [];
    $result = @mysqli_query(
        $connection,
        'SELECT
            p.id,
            p.name,
            p.slug,
            p.summary,
            p.image_path,
            p.custom_label,
            p.view_count,
            p.price,
            p.created_at,
            c.name AS category_name,
            b.name AS brand_name,
            u.name AS owner_name,
            u.email AS owner_email
        FROM products p
        INNER JOIN categories c ON c.id = p.category_id
        INNER JOIN brands b ON b.id = p.brand_id
        LEFT JOIN users u ON u.id = p.created_by
        ORDER BY p.created_at DESC'
    );

    if (!($result instanceof mysqli_result)) {
        return $items;
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
    }

    return $items;
}

function store_record_product_view(mysqli $connection, int $productId): int
{
    if ($productId <= 0 || !store_column_exists($connection, 'products', 'view_count')) {
        return 0;
    }

    $_SESSION['product_views_seen'] = $_SESSION['product_views_seen'] ?? [];
    $seen = array_values(array_filter(array_map('intval', $_SESSION['product_views_seen'])));

    if (!in_array($productId, $seen, true)) {
        $stmt = @mysqli_prepare($connection, 'UPDATE products SET view_count = view_count + 1 WHERE id = ?');

        if ($stmt instanceof mysqli_stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $productId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        $seen[] = $productId;
        $_SESSION['product_views_seen'] = $seen;
    }

    $product = store_product_by_id($connection, $productId);
    return (int) ($product['view_count'] ?? 0);
}

function store_import_products_from_api(mysqli $connection, string $sourceUrl, string $brandOverride = '', int $limit = 20): array
{
    $sourceUrl = trim($sourceUrl);
    $brandOverride = trim($brandOverride);
    $limit = max(1, min(100, $limit));

    if ($sourceUrl === '' || preg_match('#^https?://#i', $sourceUrl) !== 1) {
        return ['success' => false, 'message' => 'Please provide a valid product API URL.'];
    }

    $payload = store_http_get_json($sourceUrl);

    if (!is_array($payload)) {
        return ['success' => false, 'message' => 'API response could not be read.'];
    }

    if (function_exists('store_cms_sync_catalog_payload')) {
        $items = [];

        if (isset($payload['products']) && is_array($payload['products'])) {
            $items = array_slice($payload['products'], 0, $limit);
            $payload['products'] = $items;
        } elseif (isset($payload['items']) && is_array($payload['items'])) {
            $items = array_slice($payload['items'], 0, $limit);
            $payload['items'] = $items;
        } elseif (array_values($payload) === $payload) {
            $items = array_slice($payload, 0, $limit);
            $payload = ['products' => $items];
        }

        if ($items === [] && empty($payload['brands']) && empty($payload['categories']) && empty($payload['labels'])) {
            return ['success' => false, 'message' => 'API response did not include a usable catalog payload.'];
        }

        return store_cms_sync_catalog_payload($connection, $payload, $brandOverride, $limit);
    }

    return ['success' => false, 'message' => 'Catalog sync helper is not available.'];
}

function store_upload_profile_image(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'path' => null];
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Profile image upload failed.'];
    }

    $tmpPath = $file['tmp_name'] ?? '';

    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        return ['success' => false, 'message' => 'Invalid profile image upload.'];
    }

    $allowed = store_allowed_image_types();
    $mimeType = mime_content_type($tmpPath) ?: '';

    if (!isset($allowed[$mimeType])) {
        return ['success' => false, 'message' => 'Only JPG, PNG, GIF, WEBP, and AVIF images are allowed.'];
    }

    $uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'profiles';

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
        return ['success' => false, 'message' => 'Profile upload folder could not be created.'];
    }

    $fileName = uniqid('profile_', true) . '.' . $allowed[$mimeType];
    $destination = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

    if (!move_uploaded_file($tmpPath, $destination)) {
        return ['success' => false, 'message' => 'Profile image upload failed.'];
    }

    return ['success' => true, 'path' => 'uploads/profiles/' . $fileName];
}

function store_update_profile(mysqli $connection, int $userId, array $post, array $files): array
{
    $name = trim((string) ($post['name'] ?? ''));
    $email = trim((string) ($post['email'] ?? ''));
    $avatarFit = trim((string) ($post['avatar_fit'] ?? 'cover'));
    $avatarFit = in_array($avatarFit, ['cover', 'contain'], true) ? $avatarFit : 'cover';

    if ($name === '' || $email === '') {
        return ['success' => false, 'message' => 'Name and email are required.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Please enter a valid email address.'];
    }

    $existing = store_user_by_email($connection, $email);

    if ($existing && (int) $existing['id'] !== $userId) {
        return ['success' => false, 'message' => 'This email is already in use.'];
    }

    $avatarUpload = store_upload_profile_image($files['profile_image'] ?? []);

    if (($avatarUpload['success'] ?? false) !== true) {
        return ['success' => false, 'message' => $avatarUpload['message'] ?? 'Profile image upload failed.'];
    }

    $avatarPath = $avatarUpload['path'] ?? null;
    $stmt = $avatarPath
        ? @mysqli_prepare($connection, 'UPDATE users SET name = ?, email = ?, profile_image = ?, avatar_fit = ? WHERE id = ?')
        : @mysqli_prepare($connection, 'UPDATE users SET name = ?, email = ?, avatar_fit = ? WHERE id = ?');

    if (!($stmt instanceof mysqli_stmt)) {
        return ['success' => false, 'message' => 'Profile could not be updated.'];
    }

    if ($avatarPath) {
        mysqli_stmt_bind_param($stmt, 'ssssi', $name, $email, $avatarPath, $avatarFit, $userId);
    } else {
        mysqli_stmt_bind_param($stmt, 'sssi', $name, $email, $avatarFit, $userId);
    }

    $saved = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if (!$saved) {
        return ['success' => false, 'message' => 'Profile could not be updated.'];
    }

    $user = store_user_by_id($connection, $userId);
    if ($user) {
        store_set_logged_in_user($user);
    }

    return ['success' => true, 'message' => 'Profile updated successfully.'];
}

function store_update_password(mysqli $connection, int $userId, array $post): array
{
    $current = (string) ($post['current_password'] ?? '');
    $newPassword = (string) ($post['new_password'] ?? '');
    $confirm = (string) ($post['confirm_password'] ?? '');

    if ($newPassword === '' || $confirm === '') {
        return ['success' => false, 'message' => 'Please enter and confirm a new password.'];
    }

    if ($newPassword !== $confirm) {
        return ['success' => false, 'message' => 'Password confirmation does not match.'];
    }

    $user = store_user_by_id($connection, $userId);

    if (!$user) {
        return ['success' => false, 'message' => 'User account not found.'];
    }

    if (!empty($user['password_hash']) && !password_verify($current, (string) $user['password_hash'])) {
        return ['success' => false, 'message' => 'Current password is incorrect.'];
    }

    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = @mysqli_prepare($connection, 'UPDATE users SET password_hash = ? WHERE id = ?');

    if (!($stmt instanceof mysqli_stmt)) {
        return ['success' => false, 'message' => 'Password could not be updated.'];
    }

    mysqli_stmt_bind_param($stmt, 'si', $hash, $userId);
    $saved = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $saved
        ? ['success' => true, 'message' => 'Password updated successfully.']
        : ['success' => false, 'message' => 'Password could not be updated.'];
}

function store_generate_verification_code(int $length = 6): string
{
    $length = max(4, min(8, $length));
    $code = '';

    for ($i = 0; $i < $length; $i++) {
        $code .= (string) random_int(0, 9);
    }

    return $code;
}

function store_password_reset_request(mysqli $connection, string $email): array
{
    store_migrate_schema($connection);

    $email = trim($email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Please enter a valid email address.'];
    }

    $user = store_user_by_email($connection, $email);
    if (!$user) {
        return ['success' => true, 'message' => 'If this email exists in our system, a verification code has been sent.'];
    }

    if (!store_table_exists($connection, 'password_reset_requests')) {
        return ['success' => false, 'message' => 'Password recovery is not available yet.'];
    }

    $expireAt = date('Y-m-d H:i:s', time() + (20 * 60));
    $verificationCode = store_generate_verification_code();
    $codeHash = password_hash($verificationCode, PASSWORD_DEFAULT);
    @mysqli_query(
        $connection,
        "UPDATE password_reset_requests SET status_label = 'expired' WHERE email = '" . mysqli_real_escape_string($connection, $email) . "' AND status_label = 'pending'"
    );
    $stmt = @mysqli_prepare(
        $connection,
        'INSERT INTO password_reset_requests (user_id, email, code_hash, status_label, expires_at) VALUES (?, ?, ?, ?, ?)'
    );

    if (!($stmt instanceof mysqli_stmt)) {
        return ['success' => false, 'message' => 'Password recovery request could not be saved.'];
    }

    $status = 'pending';
    $userId = (int) ($user['id'] ?? 0);
    mysqli_stmt_bind_param($stmt, 'issss', $userId, $email, $codeHash, $status, $expireAt);
    $saved = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if (!$saved) {
        return ['success' => false, 'message' => 'Password recovery request could not be saved.'];
    }

    store_send_action_notifications($connection, 'password_reset_code', [
        'headline' => 'Password reset verification',
        'summary' => 'Use the verification code below to reset your La Imparial account password.',
        'reset_email' => $email,
        'verification_code' => $verificationCode,
        'expires_at' => $expireAt,
        'actor_override' => [
            'name' => (string) ($user['name'] ?? 'User'),
            'email' => $email,
        ],
    ]);

    return ['success' => true, 'message' => 'Verification code sent. Check your email and then set a new password.'];
}

function store_password_reset_confirm(mysqli $connection, string $email, string $code, string $newPassword, string $confirmPassword): array
{
    store_migrate_schema($connection);

    $email = trim($email);
    $code = trim($code);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Please enter a valid email address.'];
    }

    if ($code === '') {
        return ['success' => false, 'message' => 'Please enter the verification code sent to your email.'];
    }

    if ($newPassword === '' || $confirmPassword === '') {
        return ['success' => false, 'message' => 'Please enter and confirm your new password.'];
    }

    if ($newPassword !== $confirmPassword) {
        return ['success' => false, 'message' => 'Password confirmation does not match.'];
    }

    $user = store_user_by_email($connection, $email);
    if (!$user) {
        return ['success' => false, 'message' => 'Account could not be verified.'];
    }

    $stmt = @mysqli_prepare(
        $connection,
        "SELECT id, code_hash, expires_at FROM password_reset_requests WHERE email = ? AND status_label = 'pending' ORDER BY created_at DESC LIMIT 1"
    );

    if (!($stmt instanceof mysqli_stmt)) {
        return ['success' => false, 'message' => 'Password reset verification could not be loaded.'];
    }

    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $request = $result instanceof mysqli_result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    if (!$request) {
        return ['success' => false, 'message' => 'No active verification request found. Please request a new code.'];
    }

    $expiresAt = strtotime((string) ($request['expires_at'] ?? ''));
    if ($expiresAt <= time()) {
        @mysqli_query($connection, 'UPDATE password_reset_requests SET status_label = "expired" WHERE id = ' . (int) $request['id']);
        return ['success' => false, 'message' => 'Verification code expired. Please request a new code.'];
    }

    if (!password_verify($code, (string) ($request['code_hash'] ?? ''))) {
        return ['success' => false, 'message' => 'Verification code is incorrect.'];
    }

    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $updateUser = @mysqli_prepare($connection, 'UPDATE users SET password_hash = ? WHERE id = ?');

    if (!($updateUser instanceof mysqli_stmt)) {
        return ['success' => false, 'message' => 'Password could not be updated.'];
    }

    mysqli_stmt_bind_param($updateUser, 'si', $hash, $user['id']);
    $saved = mysqli_stmt_execute($updateUser);
    mysqli_stmt_close($updateUser);

    if (!$saved) {
        return ['success' => false, 'message' => 'Password could not be updated.'];
    }

    @mysqli_query(
        $connection,
        'UPDATE password_reset_requests SET status_label = "used", verified_at = NOW() WHERE id = ' . (int) $request['id']
    );

    store_send_action_notifications($connection, 'password_reset_success', [
        'headline' => 'Password reset completed',
        'summary' => 'Your La Imparial account password was reset successfully.',
        'reset_email' => $email,
        'actor_override' => [
            'name' => (string) ($user['name'] ?? 'User'),
            'email' => $email,
        ],
    ]);

    return ['success' => true, 'message' => 'Password reset successfully. You can now login with your new password.'];
}

function store_create_contact_inquiry(mysqli $connection, array $data): array
{
    store_migrate_schema($connection);

    $name = trim((string) ($data['name'] ?? ($_SESSION['user_name'] ?? '')));
    $email = trim((string) ($data['email'] ?? ($_SESSION['user_email'] ?? '')));
    $phone = trim((string) ($data['phone'] ?? ''));
    $subject = trim((string) ($data['subject'] ?? ''));
    $message = trim((string) ($data['message'] ?? ''));
    $productSlug = trim((string) ($data['product'] ?? ''));

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $subject === '' || $message === '') {
        return ['success' => false, 'message' => 'Please complete name, email, subject, and message.'];
    }

    if (!store_table_exists($connection, 'contact_inquiries')) {
        return ['success' => false, 'message' => 'Contact support is not available yet.'];
    }

    $product = $productSlug !== '' ? store_product_by_slug($connection, $productSlug) : null;
    $productId = (int) ($product['id'] ?? 0);
    $userId = store_current_user_id();
    $status = 'new';
    $stmt = @mysqli_prepare(
        $connection,
        'INSERT INTO contact_inquiries (user_id, product_id, full_name, email, phone, subject_line, message_body, inquiry_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );

    if (!($stmt instanceof mysqli_stmt)) {
        return ['success' => false, 'message' => 'Inquiry could not be saved.'];
    }

    mysqli_stmt_bind_param($stmt, 'iissssss', $userId, $productId, $name, $email, $phone, $subject, $message, $status);
    $saved = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if (!$saved) {
        return ['success' => false, 'message' => 'Inquiry could not be sent.'];
    }

    store_send_action_notifications($connection, 'contact_inquiry', [
        'product' => $product,
        'headline' => $subject,
        'summary' => $message,
        'contact_subject' => $subject,
        'message' => $message,
        'phone' => $phone,
        'actor_override' => [
            'name' => $name,
            'email' => $email,
        ],
    ]);

    return ['success' => true, 'message' => 'Inquiry sent successfully. Admin will receive it right away.'];
}

function store_contact_inquiries(mysqli $connection, int $limit = 50): array
{
    $items = [];
    $limit = max(1, min(200, $limit));

    if (!store_table_exists($connection, 'contact_inquiries')) {
        return $items;
    }

    $result = @mysqli_query(
        $connection,
        'SELECT
            ci.id,
            ci.full_name,
            ci.email,
            ci.phone,
            ci.subject_line,
            ci.message_body,
            ci.inquiry_status,
            ci.created_at,
            p.slug AS product_slug,
            p.name AS product_name,
            u.name AS user_name
        FROM contact_inquiries ci
        LEFT JOIN products p ON p.id = ci.product_id
        LEFT JOIN users u ON u.id = ci.user_id
        ORDER BY ci.created_at DESC
        LIMIT ' . $limit
    );

    if (!($result instanceof mysqli_result)) {
        return $items;
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
    }

    return $items;
}

function store_update_contact_inquiry_status(mysqli $connection, int $inquiryId, string $status): bool
{
    $allowed = ['new', 'in_progress', 'closed'];
    $status = in_array($status, $allowed, true) ? $status : 'new';
    $stmt = @mysqli_prepare($connection, 'UPDATE contact_inquiries SET inquiry_status = ? WHERE id = ?');

    if (!($stmt instanceof mysqli_stmt)) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, 'si', $status, $inquiryId);
    $saved = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $saved;
}

function store_recent_reviews(mysqli $connection, int $limit = 20): array
{
    $items = [];
    $limit = max(1, min(100, $limit));

    if (!store_table_exists($connection, 'reviews')) {
        return $items;
    }

    $result = @mysqli_query(
        $connection,
        'SELECT
            r.id,
            r.rating,
            r.comment,
            r.created_at,
            p.slug AS product_slug,
            p.name AS product_name,
            p.image_path AS product_image,
            u.name AS user_name,
            u.email AS user_email
        FROM reviews r
        INNER JOIN products p ON p.id = r.product_id
        LEFT JOIN users u ON u.id = r.user_id
        ORDER BY r.created_at DESC
        LIMIT ' . $limit
    );

    if (!($result instanceof mysqli_result)) {
        return $items;
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
    }

    return $items;
}

function store_update_user_role(mysqli $connection, int $userId, string $role): bool
{
    $role = trim($role);
    $role = store_role_by_slug($connection, $role) ? $role : 'viewer';
    $stmt = @mysqli_prepare($connection, 'UPDATE users SET role = ? WHERE id = ?');

    if (!($stmt instanceof mysqli_stmt)) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, 'si', $role, $userId);
    $saved = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $saved;
}

function store_delete_review(mysqli $connection, int $reviewId): array
{
    if ($reviewId <= 0) {
        return ['success' => false, 'message' => 'Review could not be removed.'];
    }

    $stmt = @mysqli_prepare($connection, 'DELETE FROM reviews WHERE id = ?');

    if (!($stmt instanceof mysqli_stmt)) {
        return ['success' => false, 'message' => 'Review could not be removed.'];
    }

    mysqli_stmt_bind_param($stmt, 'i', $reviewId);
    $saved = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return [
        'success' => $saved,
        'message' => $saved ? 'Review removed successfully.' : 'Review could not be removed.',
    ];
}

function store_delete_product(mysqli $connection, int $productId): array
{
    $product = store_product_by_id($connection, $productId);

    if (!$product) {
        return ['success' => false, 'message' => 'Product could not be found.'];
    }

    $currentUserId = store_current_user_id();
    $isOwner = (int) ($product['created_by'] ?? 0) === $currentUserId && $currentUserId > 0;
    $canManage = store_user_has_permission($connection, 'manage_products');

    if (!$canManage && !$isOwner) {
        return ['success' => false, 'message' => 'You do not have permission to remove this product.'];
    }

    $stmt = @mysqli_prepare($connection, 'DELETE FROM products WHERE id = ?');

    if (!($stmt instanceof mysqli_stmt)) {
        return ['success' => false, 'message' => 'Product could not be removed.'];
    }

    mysqli_stmt_bind_param($stmt, 'i', $productId);
    $saved = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if (!$saved) {
        return ['success' => false, 'message' => 'Product could not be removed.'];
    }

    $notification = store_send_action_notifications($connection, 'product_delete', [
        'product' => $product,
    ]);

    return [
        'success' => true,
        'message' => 'Product removed.',
        'product' => $product,
        'notification' => $notification,
    ];
}

function store_notification_limit_text(string $value, int $limit = 160): string
{
    $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');

    if ($limit < 4 || strlen($value) <= $limit) {
        return $value;
    }

    return rtrim(substr($value, 0, $limit - 3)) . '...';
}

function store_format_money(float $amount): string
{
    return 'PKR ' . number_format($amount, 2);
}

function store_product_change_summary(?array $before, ?array $after): array
{
    if (!is_array($before) || !is_array($after)) {
        return [];
    }

    $fields = [
        'name' => 'Name',
        'summary' => 'Summary',
        'description' => 'Description',
        'price' => 'Price',
        'original_price' => 'Old price',
        'category_name' => 'Category',
        'brand_name' => 'Brand',
        'image_path' => 'Image path',
        'document_path' => 'Document path',
        'custom_label' => 'Product label',
        'discount_percent' => 'Discount',
        'is_featured' => 'Featured',
    ];
    $changes = [];

    foreach ($fields as $key => $label) {
        $oldValue = $before[$key] ?? '';
        $newValue = $after[$key] ?? '';

        if (in_array($key, ['price', 'original_price'], true)) {
            $oldValue = store_format_money((float) $oldValue);
            $newValue = store_format_money((float) $newValue);
        } elseif ($key === 'discount_percent') {
            $oldValue = (string) ((int) $oldValue) . '%';
            $newValue = (string) ((int) $newValue) . '%';
        } elseif ($key === 'is_featured') {
            $oldValue = (int) $oldValue === 1 ? 'Yes' : 'No';
            $newValue = (int) $newValue === 1 ? 'Yes' : 'No';
        } else {
            $oldValue = store_notification_limit_text((string) $oldValue, 120);
            $newValue = store_notification_limit_text((string) $newValue, 120);
        }

        if ($oldValue !== $newValue) {
            $changes[] = $label . ': ' . ($oldValue !== '' ? $oldValue : '(empty)') . ' -> ' . ($newValue !== '' ? $newValue : '(empty)');
        }
    }

    return $changes;
}

function store_notification_actor(mysqli $connection): array
{
    $user = store_current_user($connection);

    return [
        'id' => (int) ($user['id'] ?? store_current_user_id()),
        'name' => trim((string) ($user['name'] ?? ($_SESSION['user_name'] ?? 'Guest User'))),
        'email' => trim((string) ($user['email'] ?? ($_SESSION['user_email'] ?? ''))),
        'role' => trim((string) ($user['role'] ?? ($_SESSION['user_role'] ?? 'guest'))),
        'session_id' => session_id(),
        'is_logged_in' => store_is_logged_in(),
    ];
}

function store_notification_admin_email(): string
{
    $adminEmail = trim((string) (defined('STORE_ADMIN_EMAIL') ? STORE_ADMIN_EMAIL : ''));
    if (filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        return $adminEmail;
    }

    $notifyEmail = trim((string) (defined('STORE_NOTIFY_EMAIL') ? STORE_NOTIFY_EMAIL : ''));
    return filter_var($notifyEmail, FILTER_VALIDATE_EMAIL) ? $notifyEmail : '';
}

function store_site_url(): string
{
    $configured = trim((string) (defined('STORE_SITE_URL') ? STORE_SITE_URL : ''));

    if ($configured !== '') {
        return rtrim($configured, '/');
    }

    $scheme = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') ? 'https' : 'http';
    $host = trim((string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $base = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/')));
    $base = $base === '/' || $base === '.' ? '' : rtrim($base, '/');

    return $scheme . '://' . $host . $base;
}

function store_site_path_prefix(): string
{
    $path = trim((string) parse_url(store_site_url(), PHP_URL_PATH), '/');

    return $path !== '' ? '/' . $path : '';
}

function store_app_relative_url(string $path = ''): string
{
    $path = trim(str_replace('\\', '/', $path));

    if ($path === '' || $path === '/') {
        $prefix = store_site_path_prefix();
        return $prefix !== '' ? $prefix . '/' : '/';
    }

    if (preg_match('#^https?://#i', $path) === 1) {
        return $path;
    }

    $prefix = store_site_path_prefix();

    if (str_starts_with($path, '/')) {
        if ($prefix !== '' && str_starts_with($path, $prefix . '/')) {
            return $path;
        }

        return ($prefix !== '' ? $prefix : '') . $path;
    }

    return ($prefix !== '' ? $prefix : '') . '/' . ltrim($path, './');
}

function store_absolute_url(string $path): string
{
    $path = trim($path);

    if ($path === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $path) === 1) {
        return $path;
    }

    return rtrim(store_site_url(), '/') . '/' . ltrim($path, './');
}

function store_asset_url(string $path): string
{
    $path = ltrim(str_replace('\\', '/', trim($path)), './');
    $url = store_app_relative_url($path);

    if ($path === '') {
        return $url;
    }

    $absolutePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);

    if (!is_file($absolutePath)) {
        return $url;
    }

    $version = (string) @filemtime($absolutePath);

    if ($version === '' || $version === '0') {
        return $url;
    }

    return $url . (str_contains($url, '?') ? '&' : '?') . 'v=' . rawurlencode($version);
}

function store_local_path_from_relative(string $path): ?string
{
    $relativePath = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), '.\\/ ');

    if ($relativePath === '') {
        return null;
    }

    $absolutePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . $relativePath;

    return is_file($absolutePath) ? $absolutePath : null;
}

function store_notification_inline_image(?array $product): ?array
{
    if (!is_array($product)) {
        return null;
    }

    $imagePath = trim((string) ($product['image_path'] ?? ''));
    $absolutePath = store_local_path_from_relative($imagePath);

    if ($absolutePath === null || !is_readable($absolutePath)) {
        return null;
    }

    $fileSize = @filesize($absolutePath);
    if ($fileSize === false || $fileSize > 2500000) {
        return null;
    }

    $mimeType = mime_content_type($absolutePath) ?: 'application/octet-stream';
    if (!str_starts_with($mimeType, 'image/')) {
        return null;
    }

    $content = @file_get_contents($absolutePath);
    if ($content === false) {
        return null;
    }

    return [
        'cid' => 'product-image-' . uniqid('', true),
        'mime' => $mimeType,
        'filename' => basename($absolutePath),
        'content' => $content,
    ];
}

function store_notification_request_snapshot(): array
{
    return [
        'time' => date('Y-m-d H:i:s'),
        'method' => trim((string) ($_SERVER['REQUEST_METHOD'] ?? 'CLI')),
        'uri' => trim((string) ($_SERVER['REQUEST_URI'] ?? '')),
        'ip' => trim((string) ($_SERVER['REMOTE_ADDR'] ?? '')),
        'user_agent' => store_notification_limit_text((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 180),
    ];
}

function store_notification_meta(string $actionKey): array
{
    $map = [
        'cart_add' => [
            'badge' => 'Cart Updated',
            'accent' => '#0f766e',
            'user_title' => 'Product added to your cart',
            'user_intro' => 'This email confirms that your account added a product to the cart.',
            'admin_title' => 'A user added a product to the cart',
            'admin_intro' => 'A storefront user updated their cart.',
            'admin_verb' => 'added to cart',
        ],
        'cart_decrease' => [
            'badge' => 'Cart Updated',
            'accent' => '#0d9488',
            'user_title' => 'Cart quantity reduced',
            'user_intro' => 'This email confirms that your cart quantity was reduced.',
            'admin_title' => 'A user reduced cart quantity',
            'admin_intro' => 'A storefront user reduced quantity for a cart item.',
            'admin_verb' => 'reduced cart quantity for',
        ],
        'cart_remove' => [
            'badge' => 'Cart Updated',
            'accent' => '#dc2626',
            'user_title' => 'Product removed from your cart',
            'user_intro' => 'This email confirms that a product was removed from your cart.',
            'admin_title' => 'A user removed a product from the cart',
            'admin_intro' => 'A storefront user removed a product from the cart.',
            'admin_verb' => 'removed from cart',
        ],
        'favorite_add' => [
            'badge' => 'Favorites Updated',
            'accent' => '#be185d',
            'user_title' => 'Product added to your favorites',
            'user_intro' => 'This email confirms that a product was saved in your favorites.',
            'admin_title' => 'A user favorited a product',
            'admin_intro' => 'A storefront user added a product to favorites.',
            'admin_verb' => 'favorited',
        ],
        'favorite_remove' => [
            'badge' => 'Favorites Updated',
            'accent' => '#9d174d',
            'user_title' => 'Product removed from your favorites',
            'user_intro' => 'This email confirms that a product was removed from your favorites.',
            'admin_title' => 'A user removed a favorite',
            'admin_intro' => 'A storefront user removed a product from favorites.',
            'admin_verb' => 'removed from favorites',
        ],
        'product_create' => [
            'badge' => 'Catalog Activity',
            'accent' => '#2563eb',
            'user_title' => 'Product added successfully',
            'user_intro' => 'Your account added a new product to the catalog.',
            'admin_title' => 'A user added a new product',
            'admin_intro' => 'A catalog change was made by a signed-in user.',
            'admin_verb' => 'created',
        ],
        'product_update' => [
            'badge' => 'Catalog Activity',
            'accent' => '#1d4ed8',
            'user_title' => 'Product updated successfully',
            'user_intro' => 'Your account updated a product in the catalog.',
            'admin_title' => 'A user updated a product',
            'admin_intro' => 'A catalog product was edited by a signed-in user.',
            'admin_verb' => 'updated',
        ],
        'product_delete' => [
            'badge' => 'Catalog Activity',
            'accent' => '#7c3aed',
            'user_title' => 'Product removed from the catalog',
            'user_intro' => 'Your account removed a product from the catalog.',
            'admin_title' => 'A user deleted a product',
            'admin_intro' => 'A catalog product was deleted by a signed-in user.',
            'admin_verb' => 'deleted',
        ],
        'review_create' => [
            'badge' => 'Review Submitted',
            'accent' => '#ea580c',
            'user_title' => 'Review submitted successfully',
            'user_intro' => 'This email confirms that your review was submitted.',
            'admin_title' => 'A user submitted a review',
            'admin_intro' => 'A new product review was submitted by a signed-in user.',
            'admin_verb' => 'reviewed',
        ],
        'contact_inquiry' => [
            'badge' => 'Support Message',
            'accent' => '#0f766e',
            'user_title' => 'Your inquiry was sent',
            'user_intro' => 'This email confirms that your support inquiry was submitted.',
            'admin_title' => 'A new contact inquiry was received',
            'admin_intro' => 'A customer or signed-in user sent a support inquiry.',
            'admin_verb' => 'submitted support inquiry for',
        ],
        'password_reset_code' => [
            'badge' => 'Security Verification',
            'accent' => '#d97706',
            'user_title' => 'Your verification code is ready',
            'user_intro' => 'Use the verification code below to reset your password.',
            'admin_title' => 'A password reset verification was requested',
            'admin_intro' => 'A user requested a password reset verification code.',
            'admin_verb' => 'requested password reset for',
        ],
        'password_reset_success' => [
            'badge' => 'Security Update',
            'accent' => '#16a34a',
            'user_title' => 'Password reset completed',
            'user_intro' => 'This email confirms that the account password was updated.',
            'admin_title' => 'A user completed password reset',
            'admin_intro' => 'A password reset was completed successfully.',
            'admin_verb' => 'completed password reset for',
        ],
    ];

    return $map[$actionKey] ?? [
        'badge' => 'Store Activity',
        'accent' => '#334155',
        'user_title' => 'Store action completed',
        'user_intro' => 'Your account completed a store action.',
        'admin_title' => 'A store action was completed',
        'admin_intro' => 'A store action was completed by a signed-in user.',
        'admin_verb' => 'updated',
    ];
}

function store_notification_subject(string $audience, array $meta, array $actor, ?array $product): string
{
    $productName = trim((string) ($product['name'] ?? 'Store activity'));

    if ($audience === 'admin') {
        return 'La Imparial Admin: ' . $actor['name'] . ' ' . $meta['admin_verb'] . ' ' . $productName;
    }

    return 'La Imparial: ' . $meta['user_title'] . ' - ' . $productName;
}

function store_notification_action_link(string $audience, string $actionKey, ?array $product): array
{
    if ($audience === 'admin') {
        return [
            'label' => 'Open admin panel',
            'url' => store_absolute_url('admin/'),
        ];
    }

    if ($actionKey === 'password_reset_code' || $actionKey === 'password_reset_success') {
        return [
            'label' => 'Open password recovery',
            'url' => store_absolute_url('forgot-password.php'),
        ];
    }

    if ($actionKey === 'contact_inquiry') {
        return [
            'label' => 'Open contact page',
            'url' => store_absolute_url('contact.php'),
        ];
    }

    if ($actionKey === 'product_delete') {
        return [
            'label' => 'Open profile',
            'url' => store_absolute_url('profile.php'),
        ];
    }

    if (is_array($product) && !empty($product['slug'])) {
        return [
            'label' => 'View product',
            'url' => store_absolute_url('shop-details.php?product=' . rawurlencode((string) $product['slug'])),
        ];
    }

    return [
        'label' => 'Open store',
        'url' => store_absolute_url('index.php'),
    ];
}

function store_notification_rows(string $audience, string $actionKey, array $actor, ?array $product, array $context): array
{
    $rows = [];

    if (is_array($product)) {
        $rows['Product'] = trim((string) ($product['name'] ?? ''));
        $rows['Slug'] = trim((string) ($product['slug'] ?? ''));
        $rows['Brand'] = trim((string) ($product['brand_name'] ?? ''));
        $rows['Category'] = trim((string) ($product['category_name'] ?? ''));
        $rows['Price'] = isset($product['price']) ? store_format_money((float) $product['price']) : '';
        $rows['Product summary'] = store_notification_limit_text((string) ($product['summary'] ?? ''), 140);
    }

    if (isset($context['quantity'])) {
        $rows['Current quantity'] = (string) ((int) $context['quantity']);
    }

    if (isset($context['old_quantity'])) {
        $rows['Previous quantity'] = (string) ((int) $context['old_quantity']);
    }

    if (isset($context['favorite_total'])) {
        $rows['Favorite total'] = (string) ((int) $context['favorite_total']);
    }

    if (isset($context['rating'])) {
        $rows['Rating'] = (string) ((int) $context['rating']) . ' / 5';
    }

    if (!empty($context['comment'])) {
        $rows['Review'] = store_notification_limit_text((string) $context['comment'], 220);
    }

    if (!empty($context['reason'])) {
        $rows['Reason'] = store_notification_limit_text((string) $context['reason'], 180);
    }

    if (!empty($context['changes']) && is_array($context['changes'])) {
        $rows['Changed fields'] = implode("\n", array_slice(array_values($context['changes']), 0, 8));
    }

    if (!empty($context['contact_subject'])) {
        $rows['Inquiry subject'] = store_notification_limit_text((string) $context['contact_subject'], 160);
    }

    if (!empty($context['message'])) {
        $rows['Message'] = store_notification_limit_text((string) $context['message'], 260);
    }

    if (!empty($context['phone'])) {
        $rows['Phone'] = trim((string) $context['phone']);
    }

    if (!empty($context['verification_code'])) {
        $rows['Verification code'] = trim((string) $context['verification_code']);
    }

    if (!empty($context['expires_at'])) {
        $rows['Expires at'] = trim((string) $context['expires_at']);
    }

    if (!empty($context['reset_email'])) {
        $rows['Reset email'] = trim((string) $context['reset_email']);
    }

    if ($audience === 'user') {
        $rows['Account email'] = trim((string) ($actor['email'] ?? ''));
    } else {
        $rows['User name'] = trim((string) ($actor['name'] ?? ''));
        $rows['User email'] = trim((string) ($actor['email'] ?? ''));
        $rows['User role'] = trim((string) ($actor['role'] ?? ''));
        $rows['User id'] = (string) ((int) ($actor['id'] ?? 0));
        $rows['Session id'] = trim((string) ($actor['session_id'] ?? ''));
        $request = $context['request'] ?? [];
        $rows['Request time'] = trim((string) ($request['time'] ?? ''));
        $rows['Request method'] = trim((string) ($request['method'] ?? ''));
        $rows['Request uri'] = trim((string) ($request['uri'] ?? ''));
        $rows['IP address'] = trim((string) ($request['ip'] ?? ''));
        $rows['Browser'] = trim((string) ($request['user_agent'] ?? ''));
    }

    return array_filter($rows, static fn ($value) => trim((string) $value) !== '');
}

function store_notification_rows_html(array $rows): string
{
    $html = '';

    foreach ($rows as $label => $value) {
        $formattedValue = nl2br(e((string) $value), false);
        $html .= '<tr>'
            . '<td style="padding:10px 12px;border-bottom:1px solid #e2e8f0;font-size:13px;font-weight:700;color:#0f172a;width:160px;vertical-align:top;">' . e((string) $label) . '</td>'
            . '<td style="padding:10px 12px;border-bottom:1px solid #e2e8f0;font-size:13px;color:#334155;vertical-align:top;">' . $formattedValue . '</td>'
            . '</tr>';
    }

    return $html;
}

function store_notification_rows_text(array $rows): string
{
    $lines = [];

    foreach ($rows as $label => $value) {
        $cleanValue = trim(preg_replace('/\s+/', ' ', str_replace(["\r", "\n"], ' | ', (string) $value)) ?? '');
        $lines[] = $label . ': ' . $cleanValue;
    }

    return implode("\n", $lines);
}

function store_mime_encoded_header(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    return '=?UTF-8?B?' . base64_encode($value) . '?=';
}

function store_mime_address(string $email, string $name = ''): string
{
    $email = trim($email);
    $name = trim($name);

    if ($name === '') {
        return $email;
    }

    return store_mime_encoded_header($name) . ' <' . $email . '>';
}

function store_build_mime_message(string $toEmail, string $toName, string $subject, string $html, string $text, array $inlineAttachments = []): array
{
    $fromEmail = trim((string) (defined('STORE_MAIL_FROM') ? STORE_MAIL_FROM : ''));
    $fromName = trim((string) (defined('STORE_MAIL_FROM_NAME') ? STORE_MAIL_FROM_NAME : 'La Imparial'));
    $outerBoundary = 'mix_' . bin2hex(random_bytes(8));
    $altBoundary = 'alt_' . bin2hex(random_bytes(8));
    $headers = [
        'Date: ' . date('r'),
        'From: ' . store_mime_address($fromEmail, $fromName),
        'To: ' . store_mime_address($toEmail, $toName),
        'Reply-To: ' . store_mime_address($fromEmail, $fromName),
        'Message-ID: <' . uniqid('laimparial_', true) . '@' . preg_replace('/[^a-z0-9.\-]/i', '', (string) parse_url(store_site_url(), PHP_URL_HOST)) . '>',
        'Subject: ' . store_mime_encoded_header($subject),
        'MIME-Version: 1.0',
        'Content-Type: multipart/related; boundary="' . $outerBoundary . '"',
    ];

    $body = '--' . $outerBoundary . "\r\n"
        . 'Content-Type: multipart/alternative; boundary="' . $altBoundary . '"' . "\r\n\r\n"
        . '--' . $altBoundary . "\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n"
        . "Content-Transfer-Encoding: base64\r\n\r\n"
        . chunk_split(base64_encode($text))
        . '--' . $altBoundary . "\r\n"
        . "Content-Type: text/html; charset=UTF-8\r\n"
        . "Content-Transfer-Encoding: base64\r\n\r\n"
        . chunk_split(base64_encode($html))
        . '--' . $altBoundary . "--\r\n";

    foreach ($inlineAttachments as $attachment) {
        if (!is_array($attachment) || !isset($attachment['cid'], $attachment['mime'], $attachment['filename'], $attachment['content'])) {
            continue;
        }

        $body .= '--' . $outerBoundary . "\r\n"
            . 'Content-Type: ' . $attachment['mime'] . '; name="' . addslashes((string) $attachment['filename']) . '"' . "\r\n"
            . "Content-Transfer-Encoding: base64\r\n"
            . 'Content-ID: <' . $attachment['cid'] . '>' . "\r\n"
            . 'Content-Disposition: inline; filename="' . addslashes((string) $attachment['filename']) . '"' . "\r\n\r\n"
            . chunk_split(base64_encode((string) $attachment['content']))
            . "\r\n";
    }

    $body .= '--' . $outerBoundary . "--\r\n";

    return [
        'smtp_message' => implode("\r\n", $headers) . "\r\n\r\n" . $body,
        'mail_headers' => implode("\r\n", [
            $headers[0],
            $headers[1],
            $headers[3],
            $headers[4],
            $headers[6],
            $headers[7],
        ]),
        'mail_body' => $body,
    ];
}

function store_smtp_read($socket): array
{
    $response = '';

    while (is_resource($socket) && ($line = fgets($socket, 515)) !== false) {
        $response .= $line;

        if (strlen($line) < 4 || $line[3] === ' ') {
            break;
        }
    }

    return [
        'code' => (int) substr($response, 0, 3),
        'message' => trim($response),
    ];
}

function store_smtp_command($socket, string $command, array $expectedCodes): array
{
    if (!is_resource($socket)) {
        return ['success' => false, 'message' => 'SMTP socket is not available.'];
    }

    if (@fwrite($socket, $command . "\r\n") === false) {
        return ['success' => false, 'message' => 'SMTP command could not be written.'];
    }

    $response = store_smtp_read($socket);

    return [
        'success' => in_array($response['code'], $expectedCodes, true),
        'message' => $response['message'],
    ];
}

function store_send_via_smtp(string $toEmail, string $rawMessage): array
{
    $host = trim((string) (defined('STORE_SMTP_HOST') ? STORE_SMTP_HOST : ''));
    $port = (int) (defined('STORE_SMTP_PORT') ? STORE_SMTP_PORT : 0);
    $username = trim((string) (defined('STORE_SMTP_USERNAME') ? STORE_SMTP_USERNAME : ''));
    $password = (string) (defined('STORE_SMTP_PASSWORD') ? STORE_SMTP_PASSWORD : '');
    $encryption = strtolower(trim((string) (defined('STORE_SMTP_ENCRYPTION') ? STORE_SMTP_ENCRYPTION : 'ssl')));

    if ($host === '' || $port <= 0 || $username === '' || $password === '') {
        return ['success' => false, 'transport' => 'smtp', 'error' => 'SMTP credentials are not configured.'];
    }

    $remoteHost = $encryption === 'ssl' ? 'ssl://' . $host : $host;
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ],
    ]);
    $socket = @stream_socket_client($remoteHost . ':' . $port, $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $context);

    if (!is_resource($socket)) {
        return ['success' => false, 'transport' => 'smtp', 'error' => trim($errstr) !== '' ? $errstr : ('SMTP connection failed with code ' . $errno . '.')];
    }

    stream_set_timeout($socket, 20);
    $welcome = store_smtp_read($socket);
    if ($welcome['code'] !== 220) {
        fclose($socket);
        return ['success' => false, 'transport' => 'smtp', 'error' => $welcome['message']];
    }

    $commands = [
        ['EHLO localhost', [250]],
        ['AUTH LOGIN', [334]],
        [base64_encode($username), [334]],
        [base64_encode($password), [235]],
        ['MAIL FROM:<' . $username . '>', [250]],
        ['RCPT TO:<' . $toEmail . '>', [250, 251]],
        ['DATA', [354]],
    ];

    foreach ($commands as [$command, $expected]) {
        $result = store_smtp_command($socket, $command, $expected);
        if (!$result['success']) {
            @fwrite($socket, "QUIT\r\n");
            fclose($socket);
            return ['success' => false, 'transport' => 'smtp', 'error' => $result['message']];
        }
    }

    $safeMessage = preg_replace('/(?m)^\./', '..', $rawMessage) ?? $rawMessage;
    $sent = @fwrite($socket, $safeMessage . "\r\n.\r\n");
    if ($sent === false) {
        @fwrite($socket, "QUIT\r\n");
        fclose($socket);
        return ['success' => false, 'transport' => 'smtp', 'error' => 'SMTP message body could not be sent.'];
    }

    $queued = store_smtp_read($socket);
    @fwrite($socket, "QUIT\r\n");
    fclose($socket);

    if (!in_array($queued['code'], [250], true)) {
        return ['success' => false, 'transport' => 'smtp', 'error' => $queued['message']];
    }

    return ['success' => true, 'transport' => 'smtp', 'error' => ''];
}

function store_send_html_email(string $toEmail, string $toName, string $subject, string $html, string $text, array $inlineAttachments = []): array
{
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'transport' => 'validation', 'error' => 'Recipient email is invalid.'];
    }

    $payload = store_build_mime_message($toEmail, $toName, $subject, $html, $text, $inlineAttachments);
    $smtpResult = store_send_via_smtp($toEmail, $payload['smtp_message']);

    if ($smtpResult['success']) {
        return $smtpResult;
    }

    $mailSent = @mail($toEmail, $subject, $payload['mail_body'], $payload['mail_headers']);

    return [
        'success' => $mailSent,
        'transport' => $mailSent ? 'mail' : $smtpResult['transport'],
        'error' => $mailSent ? '' : $smtpResult['error'],
    ];
}

function store_build_notification_email(string $audience, string $actionKey, array $actor, ?array $product, array $context): array
{
    $meta = store_notification_meta($actionKey);
    $subject = store_notification_subject($audience, $meta, $actor, $product);
    $rows = store_notification_rows($audience, $actionKey, $actor, $product, $context);
    $cta = store_notification_action_link($audience, $actionKey, $product);
    $inlineImage = store_notification_inline_image($product);
    $imageSrc = '';

    if (is_array($inlineImage)) {
        $imageSrc = 'cid:' . $inlineImage['cid'];
    } elseif (is_array($product)) {
        $imageSrc = store_absolute_url((string) ($product['image_path'] ?? ''));
    }

    $productName = trim((string) ($context['headline'] ?? ($product['name'] ?? 'Store activity')));
    $intro = $audience === 'admin' ? $meta['admin_intro'] : $meta['user_intro'];
    $greetingName = trim((string) ($actor['name'] ?? 'Customer'));
    $greeting = $audience === 'admin' ? 'Hello Admin,' : 'Hello ' . e($greetingName !== '' ? $greetingName : 'Customer') . ',';
    $summaryText = !empty($context['summary'])
        ? store_notification_limit_text((string) $context['summary'], 180)
        : (is_array($product) && !empty($product['summary'])
        ? store_notification_limit_text((string) $product['summary'], 180)
        : $intro);
    $imageBlock = $imageSrc !== ''
        ? '<div style="margin:0 0 18px;"><img src="' . e($imageSrc) . '" alt="' . e($productName) . '" style="display:block;width:100%;max-width:320px;height:220px;object-fit:contain;background:#f8fafc;border:1px solid #e2e8f0;border-radius:16px;padding:16px;margin:0 auto;"></div>'
        : '';

    $html = '<!doctype html><html><body style="margin:0;padding:24px;background:#e2e8f0;font-family:Arial,sans-serif;color:#0f172a;">'
        . '<table role="presentation" style="width:100%;border-collapse:collapse;"><tr><td align="center">'
        . '<table role="presentation" style="width:100%;max-width:720px;border-collapse:collapse;background:#ffffff;border-radius:24px;overflow:hidden;box-shadow:0 20px 45px rgba(15,23,42,0.12);">'
        . '<tr><td style="padding:24px 28px;background:linear-gradient(135deg,' . e($meta['accent']) . ' 0%,#0f172a 100%);color:#ffffff;">'
        . '<div style="display:inline-block;padding:7px 12px;border-radius:999px;background:rgba(255,255,255,0.15);font-size:12px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;">' . e($meta['badge']) . '</div>'
        . '<h1 style="margin:16px 0 10px;font-size:28px;line-height:1.2;">' . e($audience === 'admin' ? $meta['admin_title'] : $meta['user_title']) . '</h1>'
        . '<p style="margin:0;font-size:14px;line-height:1.7;color:rgba(255,255,255,0.92);">' . e($intro) . '</p>'
        . '</td></tr>'
        . '<tr><td style="padding:28px;">'
        . '<p style="margin:0 0 14px;font-size:15px;color:#334155;">' . $greeting . '</p>'
        . '<div style="background:#f8fafc;border:1px solid #dbeafe;border-radius:20px;padding:20px;">'
        . $imageBlock
        . '<h2 style="margin:0 0 8px;font-size:22px;color:#0f172a;">' . e($productName) . '</h2>'
        . '<p style="margin:0 0 18px;font-size:14px;line-height:1.7;color:#475569;">' . e($summaryText) . '</p>'
        . '<table role="presentation" style="width:100%;border-collapse:collapse;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;">'
        . store_notification_rows_html($rows)
        . '</table>'
        . '<div style="padding-top:18px;"><a href="' . e($cta['url']) . '" style="display:inline-block;padding:12px 20px;border-radius:999px;background:' . e($meta['accent']) . ';color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;">' . e($cta['label']) . '</a></div>'
        . '</div>'
        . '<p style="margin:18px 0 0;font-size:12px;line-height:1.7;color:#64748b;">This notification was generated automatically by La Imparial activity tracking.</p>'
        . '</td></tr>'
        . '</table>'
        . '</td></tr></table>'
        . '</body></html>';

    $text = ($audience === 'admin' ? $meta['admin_title'] : $meta['user_title']) . "\n\n"
        . $intro . "\n\n"
        . store_notification_rows_text($rows) . "\n\n"
        . 'Open: ' . $cta['url'];

    return [
        'subject' => $subject,
        'html' => $html,
        'text' => $text,
        'attachments' => $inlineImage ? [$inlineImage] : [],
    ];
}

function store_log_activity_notification(mysqli $connection, string $actionKey, string $audience, array $actor, ?array $product, string $recipientEmail, string $subject, array $delivery, array $context): void
{
    if (!store_table_exists($connection, 'activity_notifications')) {
        return;
    }

    $contextJson = json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $transport = trim((string) ($delivery['transport'] ?? ''));
    $status = ($delivery['success'] ?? false) ? 'sent' : 'failed';
    $error = trim((string) ($delivery['error'] ?? ''));
    $productId = (int) ($product['id'] ?? 0);
    $productSlug = trim((string) ($product['slug'] ?? ''));
    $stmt = @mysqli_prepare(
        $connection,
        'INSERT INTO activity_notifications (
            action_key, audience, actor_user_id, actor_name, actor_email, product_id, product_slug,
            recipient_email, subject_line, transport_name, delivery_status, error_message, context_json
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    if (!($stmt instanceof mysqli_stmt)) {
        return;
    }

    $actorId = (int) ($actor['id'] ?? 0);
    $actorName = trim((string) ($actor['name'] ?? ''));
    $actorEmail = trim((string) ($actor['email'] ?? ''));
    mysqli_stmt_bind_param(
        $stmt,
        'ssississsssss',
        $actionKey,
        $audience,
        $actorId,
        $actorName,
        $actorEmail,
        $productId,
        $productSlug,
        $recipientEmail,
        $subject,
        $transport,
        $status,
        $error,
        $contextJson
    );
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function store_send_action_notifications(mysqli $connection, string $actionKey, array $context = []): array
{
    store_migrate_schema($connection);

    $actor = store_notification_actor($connection);
    if (!empty($context['actor_override']) && is_array($context['actor_override'])) {
        $actor = array_merge($actor, array_filter($context['actor_override'], static fn ($value) => $value !== null));
    }
    $product = isset($context['product']) && is_array($context['product']) ? $context['product'] : null;

    if ($product === null && isset($context['product_id'])) {
        $product = store_product_by_id($connection, (int) $context['product_id']);
    }

    if ($product === null && isset($context['product_slug'])) {
        $product = store_product_by_slug($connection, (string) $context['product_slug']);
    }

    if ($product === null && (!empty($context['headline']) || !empty($context['summary']))) {
        $product = [
            'name' => (string) ($context['headline'] ?? 'Store activity'),
            'summary' => (string) ($context['summary'] ?? ''),
            'slug' => '',
            'brand_name' => '',
            'category_name' => '',
            'image_path' => '',
        ];
    }

    $context['request'] = store_notification_request_snapshot();
    $results = [
        'user' => null,
        'admin' => null,
    ];

    $userEmail = trim((string) ($actor['email'] ?? ''));
    if (filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
        $email = store_build_notification_email('user', $actionKey, $actor, $product, $context);
        $delivery = store_send_html_email($userEmail, (string) ($actor['name'] ?? 'Customer'), $email['subject'], $email['html'], $email['text'], $email['attachments']);
        store_log_activity_notification($connection, $actionKey, 'user', $actor, $product, $userEmail, $email['subject'], $delivery, $context);
        $results['user'] = $delivery;
    }

    $adminEmail = store_notification_admin_email();
    if ($adminEmail !== '') {
        $email = store_build_notification_email('admin', $actionKey, $actor, $product, $context);
        $delivery = store_send_html_email($adminEmail, 'Admin', $email['subject'], $email['html'], $email['text'], $email['attachments']);
        store_log_activity_notification($connection, $actionKey, 'admin', $actor, $product, $adminEmail, $email['subject'], $delivery, $context);
        $results['admin'] = $delivery;
    }

    return $results;
}

function store_send_favorite_email(array $product): string
{
    $recipient = store_notification_admin_email();

    if ($recipient === '') {
        return 'Favorite saved.';
    }

    $actor = [
        'id' => 0,
        'name' => 'Guest User',
        'email' => '',
        'role' => 'guest',
        'session_id' => session_id(),
        'is_logged_in' => false,
    ];
    $context = [
        'product' => $product,
        'request' => store_notification_request_snapshot(),
    ];
    $email = store_build_notification_email('admin', 'favorite_add', $actor, $product, $context);
    $delivery = store_send_html_email($recipient, 'Admin', $email['subject'], $email['html'], $email['text'], $email['attachments']);

    return ($delivery['success'] ?? false)
        ? 'Favorite saved and email notification sent.'
        : 'Favorite saved, but email could not be sent from this server.';
}

function store_log_favorite(mysqli $connection, array $product, string $status): void
{
    $sessionId = session_id();
    $notifyEmail = STORE_NOTIFY_EMAIL;
    $productId = (int) $product['id'];
    $stmt = @mysqli_prepare(
        $connection,
        'INSERT INTO favorite_notifications (product_id, session_id, notify_email, status_message) VALUES (?, ?, ?, ?)'
    );

    if (!($stmt instanceof mysqli_stmt)) {
        return;
    }

    mysqli_stmt_bind_param($stmt, 'isss', $productId, $sessionId, $notifyEmail, $status);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

require_once __DIR__ . '/store-cms.php';
require_once __DIR__ . '/store-admin.php';

if (isset($con) && $con instanceof mysqli) {
    store_initialize($con);
}
