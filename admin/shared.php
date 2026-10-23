<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/connection.php';
require_once dirname(__DIR__) . '/includes/store.php';

ensure_store_state();

$adminRedirect = './admin/' . basename((string) ($_SERVER['PHP_SELF'] ?? 'index.php'));

if (!store_is_logged_in()) {
    store_flash('warning', 'Please login to open the admin dashboard.');
    header('Location: ../login.php?redirect=' . urlencode($adminRedirect));
    exit;
}

if (!($con instanceof mysqli)) {
    store_flash('danger', 'Database connection is unavailable. Start MySQL in XAMPP to use the admin dashboard.');
    header('Location: ../index.php');
    exit;
}

if (!store_is_admin() && !store_user_has_permission($con, 'view_admin_dashboard')) {
    store_flash('danger', 'You do not have permission to open the admin dashboard.');
    header('Location: ../profile.php');
    exit;
}

function admin_count_rows(mysqli $connection, string $table, string $where = '1=1'): int
{
    if (!store_table_exists($connection, $table)) {
        return 0;
    }

    $result = @mysqli_query($connection, 'SELECT COUNT(*) AS total FROM ' . $table . ' WHERE ' . $where);
    if (!($result instanceof mysqli_result)) {
        return 0;
    }

    $row = mysqli_fetch_assoc($result);
    return (int) ($row['total'] ?? 0);
}

function admin_percent(int $part, int $whole): int
{
    if ($whole <= 0) {
        return 0;
    }

    return max(0, min(100, (int) round(($part / $whole) * 100)));
}

function admin_initials(string $name): string
{
    $parts = array_values(array_filter(preg_split('/\s+/', trim($name)) ?: []));
    if ($parts === []) {
        return 'AD';
    }

    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
    }

    return $initials !== '' ? $initials : 'AD';
}

function admin_nav_items(): array
{
    return [
        ['slug' => 'index.php', 'icon' => 'fa-dashboard', 'label' => 'Overview', 'permission' => 'view_admin_dashboard'],
        ['slug' => 'users.php', 'icon' => 'fa-users', 'label' => 'Users', 'permission' => 'manage_users'],
        ['slug' => 'catalog.php', 'icon' => 'fa-cubes', 'label' => 'Catalog', 'permission' => 'manage_products'],
        ['slug' => 'reviews.php', 'icon' => 'fa-star-o', 'label' => 'Reviews', 'permission' => 'moderate_reviews'],
        ['slug' => 'messages.php', 'icon' => 'fa-comments-o', 'label' => 'Messages', 'permission' => 'send_messages'],
        ['slug' => 'customize.php', 'icon' => 'fa-paint-brush', 'label' => 'Customize', 'permission' => 'manage_settings'],
        ['slug' => 'activity.php', 'icon' => 'fa-bolt', 'label' => 'Activity', 'permission' => 'view_reports'],
    ];
}

function admin_allowed_nav_items(mysqli $connection): array
{
    $items = [];
    foreach (admin_nav_items() as $item) {
        if (store_is_admin() || store_user_has_permission($connection, (string) $item['permission'])) {
            $items[] = $item;
        }
    }

    return $items;
}

function admin_require_panel_permission(string $permission, string $message = 'Permission required for this admin page.'): void
{
    global $con;

    if ($con instanceof mysqli && (store_is_admin() || store_user_has_permission($con, $permission))) {
        return;
    }

    store_flash('danger', $message);
    header('Location: ./index.php');
    exit;
}

function admin_flash_markup(): string
{
    $flash = pull_store_flash();
    if (!is_array($flash)) {
        return '';
    }

    return '<div class="admin-flash admin-flash--' . e((string) ($flash['type'] ?? 'success')) . '">' . e((string) ($flash['message'] ?? '')) . '</div>';
}

function admin_page_start(string $pageTitle, string $activeSlug, string $eyebrow, string $headline, string $summary): void
{
    global $con;

    $adminUser = $con instanceof mysqli ? store_current_user($con) : null;
    $siteTitle = $con instanceof mysqli ? store_setting($con, 'site_title', 'La Imparial Appliances') : 'La Imparial Appliances';
    $siteTagline = $con instanceof mysqli ? store_setting($con, 'site_tagline', 'Premium appliances and admin controls.') : 'Premium appliances and admin controls.';
    $themeCss = $con instanceof mysqli ? store_theme_css_variables($con) : '';
    $navItems = $con instanceof mysqli ? admin_allowed_nav_items($con) : [];
    $canCreateProduct = $con instanceof mysqli && store_user_has_permission($con, 'create_product');
    $canManageSettings = $con instanceof mysqli && store_user_has_permission($con, 'manage_settings');
    $responsiveCssHref = store_asset_url('css/responsive-enhancements.css');
    $themeToggleScript = store_asset_url('js/theme-toggle.js');
    $colorMode = $con instanceof mysqli ? store_setting($con, 'color_mode', 'light') : 'light';
    $colorModeJs = json_encode($colorMode, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle); ?></title>
    <script>(function(){var mode=<?php echo $colorModeJs; ?>;try{mode=window.localStorage.getItem('la_imparial_theme')||mode;}catch(error){}document.documentElement.setAttribute('data-theme',mode||'light');}());</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="../css/font-awesome.min.css" type="text/css">
    <link rel="stylesheet" href="../css/admin-dashboard.css" type="text/css">
    <link rel="stylesheet" href="<?php echo e($responsiveCssHref); ?>" type="text/css">
    <?php if ($themeCss !== ''): ?>
        <style><?php echo $themeCss; ?></style>
    <?php endif; ?>
</head>
<body class="admin-dashboard-page">
    <div class="admin-app">
        <aside class="admin-sidebar">
            <a class="admin-sidebar__brand" href="../index.php">
                <img src="../img/logo/logo.png" alt="<?php echo e($siteTitle); ?>">
                <div>
                    <h1><?php echo e($siteTitle); ?></h1>
                    <p><?php echo e($siteTagline); ?></p>
                </div>
            </a>

            <p class="admin-sidebar__section-label">Navigation</p>
            <nav class="admin-sidebar__nav">
                <?php foreach ($navItems as $item): ?>
                    <a href="./<?php echo e((string) $item['slug']); ?>" class="<?php echo $activeSlug === (string) $item['slug'] ? 'is-active' : ''; ?>">
                        <i class="fa <?php echo e((string) $item['icon']); ?>"></i>
                        <span><?php echo e((string) $item['label']); ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <p class="admin-sidebar__section-label" style="margin-top:24px;">Shortcuts</p>
            <nav class="admin-sidebar__nav">
                <a href="../index.php"><i class="fa fa-home"></i> Website</a>
                <a href="../profile.php"><i class="fa fa-user-o"></i> Profile</a>
                <?php if ($canManageSettings): ?><a href="../website-editor.php"><i class="fa fa-paint-brush"></i> Website Editor</a><?php endif; ?>
                <a href="../contact.php"><i class="fa fa-envelope-o"></i> Support</a>
                <a href="../reviews.php"><i class="fa fa-pencil-square-o"></i> Review Hub</a>
            </nav>

            <div class="admin-sidebar__card">
                <h2>Secure Admin Session</h2>
                <p>Dashboard tabs stay separated so admins and managers can work faster without everything being pushed into one crowded page.</p>
                <ul>
                    <li><span>Signed in</span><strong><?php echo e((string) ($adminUser['name'] ?? 'Admin')); ?></strong></li>
                    <li><span>Email</span><strong><?php echo e((string) ($adminUser['email'] ?? '')); ?></strong></li>
                    <li><span>Role</span><strong><?php echo e(ucfirst((string) ($adminUser['role'] ?? 'admin'))); ?></strong></li>
                </ul>
            </div>
        </aside>

        <main class="admin-main">
            <?php echo admin_flash_markup(); ?>

            <div class="admin-topbar">
                <div class="admin-topbar__eyebrow"><i class="fa fa-superpowers"></i> <?php echo e($eyebrow); ?></div>
                <div class="admin-topbar__actions">
                    <a class="admin-chip-link" href="../index.php"><i class="fa fa-globe"></i> Open Website</a>
                    <a class="admin-chip-link" href="../profile.php"><i class="fa fa-user-o"></i> Profile</a>
                    <?php if ($canCreateProduct): ?>
                        <a class="admin-chip-link admin-chip-link--primary" href="../product-form.php"><i class="fa fa-plus"></i> Add Product</a>
                    <?php endif; ?>
                    <a class="admin-chip-link" href="../logout.php"><i class="fa fa-sign-out"></i> Logout</a>
                </div>
            </div>

            <section class="admin-hero admin-hero--page">
                <div class="admin-hero__copy">
                    <div class="admin-topbar__eyebrow"><i class="fa fa-compass"></i> <?php echo e($eyebrow); ?></div>
                    <h1><?php echo e($headline); ?></h1>
                    <p><?php echo e($summary); ?></p>
                </div>
                <div class="admin-hero__visual admin-hero__visual--page">
                    <div class="admin-visual__label"><i class="fa fa-shield"></i> Clear Tabs</div>
                    <h2 class="admin-visual__title">Dedicated pages for users, products, reviews, settings, and live activity.</h2>
                    <p class="admin-visual__text">This keeps the admin area easier on the eyes, cleaner on mobile, and more practical when the store grows.</p>
                </div>
            </section>
<?php
}

function admin_page_end(string $extraScript = ''): void
{
    ?>
        </main>
    </div>
    <script src="../js/jquery-3.3.1.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="<?php echo e(store_asset_url('js/theme-toggle.js')); ?>"></script>
    <?php if ($extraScript !== ''): ?>
        <script><?php echo $extraScript; ?></script>
    <?php endif; ?>
</body>
</html>
<?php
}
