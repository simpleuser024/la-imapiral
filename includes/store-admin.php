<?php
declare(strict_types=1);

function store_extra_migrate_schema(mysqli $connection): void
{
    if (store_table_exists($connection, 'users')) {
        if (!store_column_exists($connection, 'users', 'picture')) {
            @mysqli_query($connection, 'ALTER TABLE users ADD COLUMN picture VARCHAR(255) DEFAULT NULL');
        }

        if (!store_column_exists($connection, 'users', 'avatar_fit')) {
            @mysqli_query($connection, 'ALTER TABLE users ADD COLUMN avatar_fit VARCHAR(20) NOT NULL DEFAULT "cover"');
        }

        @mysqli_query($connection, 'ALTER TABLE users MODIFY role VARCHAR(50) NOT NULL DEFAULT "viewer"');
    }

    if (!store_table_exists($connection, 'roles')) {
        @mysqli_query(
            $connection,
            'CREATE TABLE IF NOT EXISTS roles (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(120) NOT NULL,
                slug VARCHAR(120) NOT NULL UNIQUE,
                description_text TEXT DEFAULT NULL,
                permissions_json LONGTEXT DEFAULT NULL,
                is_system TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    if (!store_table_exists($connection, 'site_settings')) {
        @mysqli_query(
            $connection,
            'CREATE TABLE IF NOT EXISTS site_settings (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(120) NOT NULL UNIQUE,
                setting_value LONGTEXT DEFAULT NULL,
                updated_by INT UNSIGNED DEFAULT NULL,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    if (!store_table_exists($connection, 'user_messages')) {
        @mysqli_query(
            $connection,
            'CREATE TABLE IF NOT EXISTS user_messages (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                sender_user_id INT UNSIGNED DEFAULT NULL,
                target_type VARCHAR(20) NOT NULL,
                target_value VARCHAR(190) DEFAULT NULL,
                title VARCHAR(190) NOT NULL,
                message_body TEXT NOT NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_user_messages_target (target_type, target_value)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    store_seed_roles($connection);
    store_seed_site_settings($connection);

    if (function_exists('store_cms_migrate_schema')) {
        store_cms_migrate_schema($connection);
    }
}

function store_permissions_catalog(): array
{
    return [
        'view_admin_dashboard' => 'Open admin dashboard',
        'manage_users' => 'Manage users',
        'manage_roles' => 'Manage roles',
        'manage_products' => 'Manage any product',
        'create_product' => 'Create product',
        'edit_own_product' => 'Edit own product',
        'manage_settings' => 'Manage site settings',
        'manage_support' => 'Manage support inquiries',
        'moderate_reviews' => 'Review moderation access',
        'send_messages' => 'Send internal messages',
        'view_reports' => 'View user reports',
    ];
}

function store_slugify(string $value): string
{
    $value = strtolower(trim($value));
    $value = (string) preg_replace('/[^a-z0-9]+/i', '-', $value);
    return trim($value, '-');
}

function store_default_role_definitions(): array
{
    return [
        'viewer' => [
            'name' => 'Viewer',
            'description' => 'Sirf browse, cart, favorite, contact, aur review ke liye basic role.',
            'permissions' => [],
            'system' => 1,
        ],
        'user' => [
            'name' => 'Member',
            'description' => 'Trusted member jo apne products add aur edit kar sakta hai.',
            'permissions' => ['create_product', 'edit_own_product'],
            'system' => 1,
        ],
        'manager' => [
            'name' => 'Manager',
            'description' => 'Operational role jo products, support, reviews, aur reports dekh sakta hai.',
            'permissions' => ['view_admin_dashboard', 'manage_products', 'create_product', 'edit_own_product', 'manage_support', 'moderate_reviews', 'view_reports'],
            'system' => 1,
        ],
        'admin' => [
            'name' => 'Admin',
            'description' => 'Full control role with all dashboard permissions.',
            'permissions' => array_keys(store_permissions_catalog()),
            'system' => 1,
        ],
    ];
}

function store_seed_roles(mysqli $connection): void
{
    if (!store_table_exists($connection, 'roles')) {
        return;
    }

    foreach (store_default_role_definitions() as $slug => $role) {
        $stmt = @mysqli_prepare(
            $connection,
            'INSERT INTO roles (name, slug, description_text, permissions_json, is_system)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                description_text = VALUES(description_text),
                permissions_json = VALUES(permissions_json),
                is_system = VALUES(is_system)'
        );

        if (!($stmt instanceof mysqli_stmt)) {
            continue;
        }

        $json = json_encode(array_values($role['permissions']), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $isSystem = (int) ($role['system'] ?? 0);
        mysqli_stmt_bind_param($stmt, 'ssssi', $role['name'], $slug, $role['description'], $json, $isSystem);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    @mysqli_query($connection, "UPDATE users SET role = 'viewer' WHERE role IS NULL OR role = ''");
}

function store_normalize_permissions($permissions): array
{
    if (is_string($permissions)) {
        $decoded = json_decode($permissions, true);
        $permissions = is_array($decoded) ? $decoded : [];
    }

    if (!is_array($permissions)) {
        return [];
    }

    $clean = [];

    foreach ($permissions as $permission) {
        $permission = trim((string) $permission);
        if ($permission !== '') {
            $clean[] = $permission;
        }
    }

    return array_values(array_unique($clean));
}

function store_roles(mysqli $connection): array
{
    $items = [];

    if (!store_table_exists($connection, 'roles')) {
        return $items;
    }

    $result = @mysqli_query($connection, 'SELECT id, name, slug, description_text, permissions_json, is_system, created_at, updated_at FROM roles ORDER BY is_system DESC, name ASC');

    if (!($result instanceof mysqli_result)) {
        return $items;
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $row['permissions'] = store_normalize_permissions($row['permissions_json'] ?? '[]');
        $items[] = $row;
    }

    return $items;
}

function store_role_by_slug(mysqli $connection, string $slug): ?array
{
    $stmt = @mysqli_prepare($connection, 'SELECT id, name, slug, description_text, permissions_json, is_system FROM roles WHERE slug = ? LIMIT 1');

    if (!($stmt instanceof mysqli_stmt)) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 's', $slug);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $role = $result instanceof mysqli_result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    if (!$role) {
        return null;
    }

    $role['permissions'] = store_normalize_permissions($role['permissions_json'] ?? '[]');

    return $role;
}

function store_current_role_slug(): string
{
    $role = trim((string) ($_SESSION['user_role'] ?? 'viewer'));
    return $role !== '' ? $role : 'viewer';
}

function store_role_permissions(mysqli $connection, string $roleSlug): array
{
    $roleSlug = trim($roleSlug);
    if ($roleSlug === '') {
        return [];
    }

    $stored = store_role_by_slug($connection, $roleSlug);
    if ($stored) {
        return $stored['permissions'];
    }

    $defaults = store_default_role_definitions();
    return isset($defaults[$roleSlug]) ? $defaults[$roleSlug]['permissions'] : [];
}

function store_user_has_permission(mysqli $connection, string $permission, ?array $user = null): bool
{
    $permission = trim($permission);

    if ($permission === '') {
        return false;
    }

    $roleSlug = trim((string) (($user['role'] ?? null) ?: store_current_role_slug()));

    if ($roleSlug === 'admin') {
        return true;
    }

    return in_array($permission, store_role_permissions($connection, $roleSlug), true);
}

function store_require_permission(mysqli $connection, string $permission, string $redirect, string $message = 'Permission required.'): void
{
    if ($con = $connection) {
        if (store_user_has_permission($con, $permission)) {
            return;
        }
    }

    store_flash('danger', $message);
    header('Location: ' . $redirect);
    exit;
}

function store_update_user_account(mysqli $connection, int $userId, array $data): array
{
    $name = trim((string) ($data['name'] ?? ''));
    $email = trim((string) ($data['email'] ?? ''));
    $role = trim((string) ($data['role'] ?? 'viewer'));
    $password = (string) ($data['password'] ?? '');
    $role = store_role_by_slug($connection, $role) ? $role : 'viewer';

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Valid name and email are required.'];
    }

    $existing = store_user_by_email($connection, $email);
    if ($existing && (int) ($existing['id'] ?? 0) !== $userId) {
        return ['success' => false, 'message' => 'This email is already in use by another account.'];
    }

    $stmt = $password !== ''
        ? @mysqli_prepare($connection, 'UPDATE users SET name = ?, email = ?, role = ?, password_hash = ? WHERE id = ?')
        : @mysqli_prepare($connection, 'UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?');

    if (!($stmt instanceof mysqli_stmt)) {
        return ['success' => false, 'message' => 'User could not be updated.'];
    }

    if ($password !== '') {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        mysqli_stmt_bind_param($stmt, 'ssssi', $name, $email, $role, $hash, $userId);
    } else {
        mysqli_stmt_bind_param($stmt, 'sssi', $name, $email, $role, $userId);
    }

    $saved = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if (!$saved) {
        return ['success' => false, 'message' => 'User could not be updated.'];
    }

    if ($userId === store_current_user_id()) {
        $fresh = store_user_by_id($connection, $userId);
        if ($fresh) {
            store_set_logged_in_user($fresh);
        }
    }

    return ['success' => true, 'message' => 'User updated successfully.'];
}

function store_create_user_account(mysqli $connection, array $data): array
{
    $name = trim((string) ($data['name'] ?? ''));
    $email = trim((string) ($data['email'] ?? ''));
    $password = (string) ($data['password'] ?? '');
    $role = trim((string) ($data['role'] ?? 'viewer'));
    $role = store_role_by_slug($connection, $role) ? $role : 'viewer';

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        return ['success' => false, 'message' => 'Name, email, password, and role are required.'];
    }

    if (store_user_by_email($connection, $email)) {
        return ['success' => false, 'message' => 'This email is already in use.'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = @mysqli_prepare($connection, 'INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');

    if (!($stmt instanceof mysqli_stmt)) {
        return ['success' => false, 'message' => 'User could not be created.'];
    }

    mysqli_stmt_bind_param($stmt, 'ssss', $name, $email, $hash, $role);
    $saved = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return [
        'success' => $saved,
        'message' => $saved ? 'User created successfully.' : 'User could not be created.',
    ];
}

function store_delete_user_account(mysqli $connection, int $userId): array
{
    if ($userId <= 0) {
        return ['success' => false, 'message' => 'User could not be removed.'];
    }

    if ($userId === store_current_user_id()) {
        return ['success' => false, 'message' => 'You cannot remove your own active account.'];
    }

    $stmt = @mysqli_prepare($connection, 'DELETE FROM users WHERE id = ?');

    if (!($stmt instanceof mysqli_stmt)) {
        return ['success' => false, 'message' => 'User could not be removed.'];
    }

    mysqli_stmt_bind_param($stmt, 'i', $userId);
    $saved = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return [
        'success' => $saved,
        'message' => $saved ? 'User removed successfully.' : 'User could not be removed.',
    ];
}

function store_save_role(mysqli $connection, array $data): array
{
    $roleId = (int) ($data['role_id'] ?? 0);
    $name = trim((string) ($data['name'] ?? ''));
    $slug = store_slugify((string) ($data['slug'] ?? $name));
    $description = trim((string) ($data['description_text'] ?? ''));
    $permissions = store_normalize_permissions($data['permissions'] ?? []);

    if ($name === '' || $slug === '') {
        return ['success' => false, 'message' => 'Role name and slug are required.'];
    }

    $existing = store_role_by_slug($connection, $slug);
    if ($existing && (int) ($existing['id'] ?? 0) !== $roleId) {
        return ['success' => false, 'message' => 'This role slug already exists.'];
    }

    if ($roleId > 0) {
        $current = null;
        foreach (store_roles($connection) as $role) {
            if ((int) ($role['id'] ?? 0) === $roleId) {
                $current = $role;
                break;
            }
        }

        if (!$current) {
            return ['success' => false, 'message' => 'Role could not be found.'];
        }

        if ((int) ($current['is_system'] ?? 0) === 1 && $slug !== (string) ($current['slug'] ?? '')) {
            return ['success' => false, 'message' => 'System role slug cannot be changed.'];
        }

        $stmt = @mysqli_prepare(
            $connection,
            'UPDATE roles
             SET name = ?, slug = ?, description_text = ?, permissions_json = ?
             WHERE id = ?'
        );

        if (!($stmt instanceof mysqli_stmt)) {
            return ['success' => false, 'message' => 'Role could not be updated.'];
        }

        $json = json_encode($permissions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        mysqli_stmt_bind_param($stmt, 'ssssi', $name, $slug, $description, $json, $roleId);
        $saved = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return [
            'success' => $saved,
            'message' => $saved ? 'Role updated successfully.' : 'Role could not be updated.',
        ];
    }

    $stmt = @mysqli_prepare(
        $connection,
        'INSERT INTO roles (name, slug, description_text, permissions_json, is_system)
         VALUES (?, ?, ?, ?, 0)'
    );

    if (!($stmt instanceof mysqli_stmt)) {
        return ['success' => false, 'message' => 'Role could not be created.'];
    }

    $json = json_encode($permissions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    mysqli_stmt_bind_param($stmt, 'ssss', $name, $slug, $description, $json);
    $saved = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return [
        'success' => $saved,
        'message' => $saved ? 'Role created successfully.' : 'Role could not be created.',
    ];
}

function store_delete_role(mysqli $connection, int $roleId): array
{
    if ($roleId <= 0) {
        return ['success' => false, 'message' => 'Role could not be removed.'];
    }

    $target = null;
    foreach (store_roles($connection) as $role) {
        if ((int) ($role['id'] ?? 0) === $roleId) {
            $target = $role;
            break;
        }
    }

    if (!$target) {
        return ['success' => false, 'message' => 'Role could not be found.'];
    }

    if ((int) ($target['is_system'] ?? 0) === 1) {
        return ['success' => false, 'message' => 'System roles cannot be removed.'];
    }

    $slug = (string) ($target['slug'] ?? '');
    if ($slug !== '') {
        $stmt = @mysqli_prepare($connection, 'SELECT COUNT(*) AS total FROM users WHERE role = ?');
        if ($stmt instanceof mysqli_stmt) {
            mysqli_stmt_bind_param($stmt, 's', $slug);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = $result instanceof mysqli_result ? mysqli_fetch_assoc($result) : null;
            mysqli_stmt_close($stmt);
            if ((int) ($row['total'] ?? 0) > 0) {
                return ['success' => false, 'message' => 'Move users out of this role before deleting it.'];
            }
        }
    }

    $stmt = @mysqli_prepare($connection, 'DELETE FROM roles WHERE id = ?');

    if (!($stmt instanceof mysqli_stmt)) {
        return ['success' => false, 'message' => 'Role could not be removed.'];
    }

    mysqli_stmt_bind_param($stmt, 'i', $roleId);
    $saved = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return [
        'success' => $saved,
        'message' => $saved ? 'Role removed successfully.' : 'Role could not be removed.',
    ];
}

function store_user_report(mysqli $connection, int $userId): array
{
    $user = store_user_by_id($connection, $userId);

    if (!$user) {
        return [];
    }

    $products = store_products_by_user($connection, $userId);
    $favorites = [];
    $cartItems = [];
    $inquiries = [];
    $reviews = [];
    $activity = [];

    if (store_table_exists($connection, 'favorites')) {
        $result = @mysqli_query(
            $connection,
            'SELECT p.name, p.slug, p.image_path, p.price
             FROM favorites f
             INNER JOIN products p ON p.id = f.product_id
             WHERE f.user_id = ' . $userId . '
             ORDER BY f.created_at DESC'
        );
        if ($result instanceof mysqli_result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $favorites[] = $row;
            }
        }
    }

    if (store_table_exists($connection, 'cart_items')) {
        $result = @mysqli_query(
            $connection,
            'SELECT p.name, p.slug, p.image_path, c.quantity, p.price
             FROM cart_items c
             INNER JOIN products p ON p.id = c.product_id
             WHERE c.user_id = ' . $userId . '
             ORDER BY c.updated_at DESC'
        );
        if ($result instanceof mysqli_result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $cartItems[] = $row;
            }
        }
    }

    if (store_table_exists($connection, 'contact_inquiries')) {
        $result = @mysqli_query($connection, 'SELECT * FROM contact_inquiries WHERE user_id = ' . $userId . ' ORDER BY created_at DESC LIMIT 20');
        if ($result instanceof mysqli_result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $inquiries[] = $row;
            }
        }
    }

    if (store_table_exists($connection, 'reviews')) {
        $result = @mysqli_query(
            $connection,
            'SELECT r.rating, r.comment, r.created_at, p.name AS product_name, p.slug AS product_slug
             FROM reviews r
             INNER JOIN products p ON p.id = r.product_id
             WHERE r.user_id = ' . $userId . '
             ORDER BY r.created_at DESC
             LIMIT 20'
        );
        if ($result instanceof mysqli_result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $reviews[] = $row;
            }
        }
    }

    if (store_table_exists($connection, 'activity_notifications')) {
        $result = @mysqli_query(
            $connection,
            'SELECT action_key, audience, subject_line, delivery_status, created_at
             FROM activity_notifications
             WHERE actor_user_id = ' . $userId . '
             ORDER BY created_at DESC
             LIMIT 30'
        );
        if ($result instanceof mysqli_result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $activity[] = $row;
            }
        }
    }

    return [
        'user' => $user,
        'products' => $products,
        'favorites' => $favorites,
        'cart' => $cartItems,
        'inquiries' => $inquiries,
        'reviews' => $reviews,
        'activity' => $activity,
    ];
}

function store_seed_site_settings(mysqli $connection): void
{
    if (!store_table_exists($connection, 'site_settings')) {
        return;
    }

    $defaults = [
        'site_title' => 'La Imparial Appliances',
        'site_tagline' => 'Premium appliances, clean comparisons, and faster support.',
        'theme_primary' => '#0b1688',
        'theme_secondary' => '#5561b8',
        'theme_accent' => '#0f766e',
        'color_mode' => 'light',
        'theme_template' => 'ocean',
        'surface_style' => 'soft',
        'card_radius' => '18px',
        'button_radius' => '999px',
        'section_gap' => '90px',
        'card_button_bg' => '#0b1688',
        'card_button_text' => '#ffffff',
        'link_hover_color' => '#0b1688',
        'label_slider_bg' => '#0b1688',
        'label_slider_text' => '#ffffff',
        'announcement_text' => 'Discover premium appliances with smart compare, support, and product detail tools.',
        'support_email' => STORE_ADMIN_EMAIL,
        'seo_default_description' => 'La Imparial Appliances offers premium ACs, refrigerators, microwaves, washers, reviews, compare tools, and direct support.',
        'seo_default_keywords' => 'La Imparial, appliances, AC, refrigerator, microwave, washing machine, compare products, Pakistan',
        'seo_og_image' => 'img/logo/logo.png',
        'seo_robots' => 'index,follow',
        'seo_schema_type' => 'Store',
        'seo_business_phone' => '',
        'seo_business_address' => '',
        'custom_template_css_url' => '',
        'custom_css' => '',
    ];

    if (function_exists('store_cms_site_setting_defaults')) {
        $defaults = array_merge($defaults, store_cms_site_setting_defaults());
    }

    foreach ($defaults as $key => $value) {
        $stmt = @mysqli_prepare(
            $connection,
            'INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = setting_value'
        );
        if (!($stmt instanceof mysqli_stmt)) {
            continue;
        }
        mysqli_stmt_bind_param($stmt, 'ss', $key, $value);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

function store_settings(mysqli $connection): array
{
    $items = [];

    if (!store_table_exists($connection, 'site_settings')) {
        return $items;
    }

    $result = @mysqli_query($connection, 'SELECT setting_key, setting_value FROM site_settings');

    if (!($result instanceof mysqli_result)) {
        return $items;
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $items[(string) $row['setting_key']] = (string) ($row['setting_value'] ?? '');
    }

    return $items;
}

function store_setting(mysqli $connection, string $key, string $default = ''): string
{
    $settings = store_settings($connection);
    return array_key_exists($key, $settings) ? (string) $settings[$key] : $default;
}

function store_save_settings(mysqli $connection, array $settings): array
{
    $allowed = [
        'site_title',
        'site_tagline',
        'theme_primary',
        'theme_secondary',
        'theme_accent',
        'color_mode',
        'theme_template',
        'surface_style',
        'card_radius',
        'button_radius',
        'section_gap',
        'card_button_bg',
        'card_button_text',
        'link_hover_color',
        'label_slider_bg',
        'label_slider_text',
        'announcement_text',
        'support_email',
        'seo_default_description',
        'seo_default_keywords',
        'seo_og_image',
        'seo_robots',
        'seo_schema_type',
        'seo_business_phone',
        'seo_business_address',
        'custom_template_css_url',
        'custom_css',
    ];

    if (function_exists('store_cms_site_setting_keys')) {
        $allowed = array_values(array_unique(array_merge($allowed, store_cms_site_setting_keys())));
    }

    foreach ($allowed as $key) {
        if (!array_key_exists($key, $settings)) {
            continue;
        }

        $value = trim((string) $settings[$key]);
        $stmt = @mysqli_prepare(
            $connection,
            'INSERT INTO site_settings (setting_key, setting_value, updated_by) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)'
        );
        if (!($stmt instanceof mysqli_stmt)) {
            return ['success' => false, 'message' => 'Settings could not be saved.'];
        }

        $updatedBy = store_current_user_id();
        mysqli_stmt_bind_param($stmt, 'ssi', $key, $value, $updatedBy);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    return ['success' => true, 'message' => 'Settings saved successfully.'];
}

function store_theme_css_variables(mysqli $connection): string
{
    $primary = store_setting($connection, 'theme_primary', '#0b1688');
    $secondary = store_setting($connection, 'theme_secondary', '#5561b8');
    $accent = store_setting($connection, 'theme_accent', '#0f766e');
    $colorMode = store_setting($connection, 'color_mode', 'light');
    $template = store_setting($connection, 'theme_template', 'ocean');
    $surfaceStyle = store_setting($connection, 'surface_style', 'soft');
    $cardRadius = store_setting($connection, 'card_radius', '18px');
    $buttonRadius = store_setting($connection, 'button_radius', '999px');
    $sectionGap = store_setting($connection, 'section_gap', '90px');
    $cardButtonBg = store_setting($connection, 'card_button_bg', $primary);
    $cardButtonText = store_setting($connection, 'card_button_text', '#ffffff');
    $linkHover = store_setting($connection, 'link_hover_color', $primary);
    $labelSliderBg = store_setting($connection, 'label_slider_bg', $primary);
    $labelSliderText = store_setting($connection, 'label_slider_text', '#ffffff');
    $customCss = str_ireplace('</style', '<\\/style', store_setting($connection, 'custom_css', ''));
    $pageBg = '#f7f9fd';
    $surfaceBg = '#ffffff';
    $headerGradient = 'linear-gradient(135deg, rgba(11,22,136,0.08), rgba(15,118,110,0.06))';
    $shadow = '0 16px 40px rgba(16, 23, 36, 0.08)';

    if ($template === 'sunset') {
        $pageBg = '#fff7f2';
        $surfaceBg = '#ffffff';
        $headerGradient = 'linear-gradient(135deg, rgba(234,88,12,0.14), rgba(219,39,119,0.1))';
    } elseif ($template === 'emerald') {
        $pageBg = '#f3fbf8';
        $surfaceBg = '#ffffff';
        $headerGradient = 'linear-gradient(135deg, rgba(5,150,105,0.14), rgba(14,165,233,0.08))';
    } elseif ($template === 'graphite') {
        $pageBg = '#f5f6f8';
        $surfaceBg = '#ffffff';
        $headerGradient = 'linear-gradient(135deg, rgba(31,41,55,0.12), rgba(120,113,108,0.08))';
    }

    if ($surfaceStyle === 'glass') {
        $surfaceBg = 'rgba(255,255,255,0.78)';
        $shadow = '0 20px 45px rgba(15, 23, 42, 0.12)';
    } elseif ($surfaceStyle === 'bold') {
        $surfaceBg = '#ffffff';
        $shadow = '0 24px 60px rgba(11, 22, 136, 0.14)';
    }

    $css = ':root{'
        . '--la-theme-primary:' . e($primary) . ';'
        . '--la-theme-secondary:' . e($secondary) . ';'
        . '--la-theme-accent:' . e($accent) . ';'
        . '--la-card-radius:' . e($cardRadius) . ';'
        . '--la-button-radius:' . e($buttonRadius) . ';'
        . '--la-section-space:' . e($sectionGap) . ';'
        . '--la-card-button-bg:' . e($cardButtonBg) . ';'
        . '--la-card-button-text:' . e($cardButtonText) . ';'
        . '--la-link-hover-color:' . e($linkHover) . ';'
        . '--la-label-slider-bg:' . e($labelSliderBg) . ';'
        . '--la-label-slider-text:' . e($labelSliderText) . ';'
        . '--la-page-bg:' . e($pageBg) . ';'
        . '--la-surface-bg:' . e($surfaceBg) . ';'
        . '--la-surface-shadow:' . e($shadow) . ';'
        . '--la-header-gradient:' . e($headerGradient) . ';'
        . '--la-color-mode:' . e($colorMode) . ';'
        . '}';

    if ($customCss !== '') {
        $css .= $customCss;
    }

    return $css;
}

function store_public_editor_config(mysqli $connection): array
{
    $keys = [
        'site_title',
        'site_tagline',
        'announcement_text',
        'color_mode',
        'theme_template',
        'surface_style',
        'theme_primary',
        'theme_secondary',
        'theme_accent',
        'card_radius',
        'button_radius',
        'section_gap',
        'card_button_bg',
        'card_button_text',
        'link_hover_color',
        'label_slider_bg',
        'label_slider_text',
        'custom_template_css_url',
        'custom_css',
    ];

    if (function_exists('store_cms_public_editor_keys')) {
        $keys = array_values(array_unique(array_merge($keys, store_cms_public_editor_keys())));
    }

    $settings = [];
    foreach ($keys as $key) {
        $settings[$key] = store_setting($connection, $key, '');
    }

    return [
        'canManageSettings' => store_user_has_permission($connection, 'manage_settings'),
        'saveUrl' => store_absolute_url('website-editor.php'),
        'fallbackUrl' => store_absolute_url('website-editor.php'),
        'csrfToken' => store_csrf_token('website_editor_panel'),
        'currentPath' => basename((string) ($_SERVER['PHP_SELF'] ?? 'index.php')),
        'settings' => $settings,
        'palettes' => [
            'ocean' => ['primary' => '#0b1688', 'secondary' => '#5561b8', 'accent' => '#0f766e', 'button' => '#0b1688', 'sliderBg' => '#0b1688', 'sliderText' => '#ffffff'],
            'sunset' => ['primary' => '#c2410c', 'secondary' => '#f97316', 'accent' => '#db2777', 'button' => '#c2410c', 'sliderBg' => '#7c2d12', 'sliderText' => '#ffffff'],
            'emerald' => ['primary' => '#047857', 'secondary' => '#10b981', 'accent' => '#0ea5e9', 'button' => '#047857', 'sliderBg' => '#065f46', 'sliderText' => '#ffffff'],
            'graphite' => ['primary' => '#111827', 'secondary' => '#4b5563', 'accent' => '#0ea5e9', 'button' => '#111827', 'sliderBg' => '#1f2937', 'sliderText' => '#ffffff'],
        ],
    ];
}

function store_activity_feed(mysqli $connection, int $limit = 40): array
{
    $items = [];

    if (!store_table_exists($connection, 'activity_notifications')) {
        return $items;
    }

    $result = @mysqli_query(
        $connection,
        'SELECT
            n.id,
            n.action_key,
            n.audience,
            n.actor_user_id,
            n.actor_name,
            n.actor_email,
            n.product_id,
            n.product_slug,
            n.subject_line,
            n.delivery_status,
            n.created_at,
            p.name AS product_name
         FROM activity_notifications n
         LEFT JOIN products p ON p.id = n.product_id
         ORDER BY n.created_at DESC
         LIMIT ' . max(1, min(200, $limit))
    );

    if (!($result instanceof mysqli_result)) {
        return $items;
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
    }

    return $items;
}

function store_create_message(mysqli $connection, array $data): array
{
    $title = trim((string) ($data['title'] ?? ''));
    $message = trim((string) ($data['message_body'] ?? ''));
    $targetType = trim((string) ($data['target_type'] ?? 'all'));
    $targetValue = trim((string) ($data['target_value'] ?? ''));

    if ($title === '' || $message === '') {
        return ['success' => false, 'message' => 'Message title and body are required.'];
    }

    if (!in_array($targetType, ['all', 'role', 'user'], true)) {
        $targetType = 'all';
    }

    if ($targetType === 'role' && $targetValue === '') {
        return ['success' => false, 'message' => 'Please choose a role target.'];
    }

    if ($targetType === 'user' && $targetValue === '') {
        return ['success' => false, 'message' => 'Please choose a user target.'];
    }

    $stmt = @mysqli_prepare(
        $connection,
        'INSERT INTO user_messages (sender_user_id, target_type, target_value, title, message_body) VALUES (?, ?, ?, ?, ?)'
    );

    if (!($stmt instanceof mysqli_stmt)) {
        return ['success' => false, 'message' => 'Message could not be sent.'];
    }

    $senderId = store_current_user_id();
    mysqli_stmt_bind_param($stmt, 'issss', $senderId, $targetType, $targetValue, $title, $message);
    $saved = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return [
        'success' => $saved,
        'message' => $saved ? 'Message sent successfully.' : 'Message could not be sent.',
    ];
}

function store_messages_for_user(mysqli $connection, int $userId, string $roleSlug): array
{
    $items = [];

    if (!store_table_exists($connection, 'user_messages')) {
        return $items;
    }

    $safeRole = mysqli_real_escape_string($connection, $roleSlug);
    $result = @mysqli_query(
        $connection,
        'SELECT m.id, m.target_type, m.target_value, m.title, m.message_body, m.created_at, u.name AS sender_name
         FROM user_messages m
         LEFT JOIN users u ON u.id = m.sender_user_id
         WHERE m.target_type = "all"
            OR (m.target_type = "role" AND m.target_value = "' . $safeRole . '")
            OR (m.target_type = "user" AND m.target_value = "' . (int) $userId . '")
         ORDER BY m.created_at DESC
         LIMIT 30'
    );

    if (!($result instanceof mysqli_result)) {
        return $items;
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
    }

    return $items;
}

function store_messages_admin_feed(mysqli $connection, int $limit = 40): array
{
    $items = [];
    if (!store_table_exists($connection, 'user_messages')) {
        return $items;
    }

    $result = @mysqli_query(
        $connection,
        'SELECT m.id, m.target_type, m.target_value, m.title, m.message_body, m.created_at, u.name AS sender_name
         FROM user_messages m
         LEFT JOIN users u ON u.id = m.sender_user_id
         ORDER BY m.created_at DESC
         LIMIT ' . max(1, min(200, $limit))
    );

    if (!($result instanceof mysqli_result)) {
        return $items;
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
    }

    return $items;
}

function store_brand_create(mysqli $connection, string $name, string $logoPath = ''): array
{
    if (function_exists('store_save_brand_profile')) {
        return store_save_brand_profile($connection, [
            'name' => $name,
            'logo_path' => $logoPath,
            'is_visible' => '1',
        ]);
    }

    $name = trim($name);

    if ($name === '') {
        return ['success' => false, 'message' => 'Brand name is required.'];
    }

    $brandId = store_find_or_create_brand($connection, $name, $logoPath);

    return [
        'success' => $brandId > 0,
        'message' => $brandId > 0 ? 'Brand saved successfully.' : 'Brand could not be saved.',
    ];
}

function store_category_create(mysqli $connection, string $name, string $note = '', string $documentPath = ''): array
{
    if (function_exists('store_save_category_profile')) {
        return store_save_category_profile($connection, [
            'name' => $name,
            'note' => $note,
            'document_path' => $documentPath,
        ]);
    }

    $name = trim($name);

    if ($name === '') {
        return ['success' => false, 'message' => 'Category name is required.'];
    }

    $slug = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $name), '-'));
    $note = $note !== '' ? $note : $name . ' category';
    $documentPath = $documentPath !== '' ? $documentPath : 'docs/haier-frostcool-inverter-ac.doc';
    $stmt = @mysqli_prepare(
        $connection,
        'INSERT INTO categories (name, slug, note, document_path) VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE name = VALUES(name), note = VALUES(note), document_path = VALUES(document_path)'
    );

    if (!($stmt instanceof mysqli_stmt)) {
        return ['success' => false, 'message' => 'Category could not be saved.'];
    }

    mysqli_stmt_bind_param($stmt, 'ssss', $name, $slug, $note, $documentPath);
    $saved = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return ['success' => $saved, 'message' => $saved ? 'Category saved successfully.' : 'Category could not be saved.'];
}

function store_json_response(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function store_project_folder_name(): string
{
    return basename(dirname(__DIR__));
}

function store_google_console_urls(): array
{
    $folder = store_project_folder_name();
    return [
        'project_folder' => $folder,
        'origin' => 'http://localhost/' . $folder,
        'redirect' => 'http://localhost/' . $folder . '/callback.php',
    ];
}

function store_google_vendor_autoload_path(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
}

function store_google_client_available(): bool
{
    $autoload = store_google_vendor_autoload_path();
    if (!is_file($autoload)) {
        return false;
    }

    require_once $autoload;

    return class_exists('Google\Client');
}

function store_google_client_instance()
{
    if (!store_google_client_available()) {
        return null;
    }

    $client = new Google\Client();
    $client->setClientId((string) STORE_GOOGLE_CLIENT_ID);
    $client->setClientSecret((string) STORE_GOOGLE_CLIENT_SECRET);
    $client->setRedirectUri((string) STORE_GOOGLE_REDIRECT);
    $client->addScope('openid');
    $client->addScope('email');
    $client->addScope('profile');
    $client->setAccessType('offline');
    $client->setIncludeGrantedScopes(true);
    $client->setPrompt('select_account');

    return $client;
}

function store_google_auth_url_client(string $redirectAfterLogin): ?string
{
    $client = store_google_client_instance();

    if (!$client) {
        return null;
    }

    // Roman Urdu: login ke baad user ko isi page par wapas bhejna hai.
    $_SESSION['login_redirect'] = store_safe_redirect($redirectAfterLogin);
    $_SESSION['google_oauth_state'] = bin2hex(random_bytes(16));
    $client->setState((string) $_SESSION['google_oauth_state']);

    return $client->createAuthUrl();
}

function store_google_sync_picture(mysqli $connection, int $userId, string $picture): void
{
    $picture = trim($picture);

    if ($userId <= 0 || $picture === '') {
        return;
    }

    $query = store_column_exists($connection, 'users', 'picture')
        ? 'UPDATE users SET profile_image = ?, picture = ? WHERE id = ?'
        : 'UPDATE users SET profile_image = ? WHERE id = ?';
    $stmt = @mysqli_prepare($connection, $query);

    if (!($stmt instanceof mysqli_stmt)) {
        return;
    }

    if (store_column_exists($connection, 'users', 'picture')) {
        mysqli_stmt_bind_param($stmt, 'ssi', $picture, $picture, $userId);
    } else {
        mysqli_stmt_bind_param($stmt, 'si', $picture, $userId);
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function store_google_callback_login(mysqli $connection, string $code): array
{
    $client = store_google_client_instance();

    if (!$client) {
        return ['success' => false, 'message' => 'Google API PHP Client is not installed yet.'];
    }

    // Roman Urdu: Google se access token hasil kar rahe hain.
    $token = $client->fetchAccessTokenWithAuthCode($code);

    if (!is_array($token) || isset($token['error'])) {
        return ['success' => false, 'message' => 'Google login failed: invalid token or redirect mismatch.'];
    }

    $client->setAccessToken($token);
    $oauth = new Google\Service\Oauth2($client);
    $googleUser = $oauth->userinfo->get();

    $profile = [
        'sub' => trim((string) ($googleUser->id ?? '')),
        'email' => trim((string) ($googleUser->email ?? '')),
        'name' => trim((string) ($googleUser->name ?? '')),
        'picture' => trim((string) ($googleUser->picture ?? '')),
    ];

    if ($profile['sub'] === '' || $profile['email'] === '') {
        return ['success' => false, 'message' => 'Google login failed: profile data missing.'];
    }

    $result = store_login_with_google($connection, $profile);

    if (($result['success'] ?? false) === true) {
        $user = store_user_by_email($connection, $profile['email']);
        if ($user) {
            store_google_sync_picture($connection, (int) $user['id'], $profile['picture']);
            $fresh = store_user_by_id($connection, (int) $user['id']);
            if ($fresh) {
                store_set_logged_in_user($fresh);
            }
        }
    }

    return $result;
}
