<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/connection.php';
require_once dirname(__DIR__) . '/includes/store.php';

ensure_store_state();

if (!store_is_logged_in()) {
    store_flash('warning', 'Please login to open the user dashboard.');
    header('Location: ../login.php?redirect=' . urlencode('./user/'));
    exit;
}

$user = $con instanceof mysqli ? store_current_user($con) : null;

if (!$user) {
    store_flash('danger', 'User account could not be loaded.');
    header('Location: ../index.php');
    exit;
}

$products = $con instanceof mysqli ? store_products_by_user($con, (int) $user['id']) : [];
$favorites = $con instanceof mysqli ? store_favorite_items($con) : [];
$cartItems = $con instanceof mysqli ? store_cart_items($con) : [];
$inquiries = $con instanceof mysqli ? array_values(array_filter(store_contact_inquiries($con, 50), static fn ($row) => strtolower((string) $row['email']) === strtolower((string) $user['email']))) : [];
$messages = $con instanceof mysqli ? store_messages_for_user($con, (int) $user['id'], (string) ($user['role'] ?? 'viewer')) : [];
$cartCount = store_cart_count();
$favoriteCount = count($_SESSION['favorites'] ?? []);
$canCreateProduct = $con instanceof mysqli ? store_user_has_permission($con, 'create_product', $user) : false;
$canManageProducts = $con instanceof mysqli ? store_user_has_permission($con, 'manage_products', $user) : false;
$canManageSettings = $con instanceof mysqli ? store_user_has_permission($con, 'manage_settings', $user) : false;
$canViewAdmin = $con instanceof mysqli ? store_user_has_permission($con, 'view_admin_dashboard', $user) : false;
$flash = pull_store_flash();
$colorMode = $con instanceof mysqli ? store_setting($con, 'color_mode', 'light') : 'light';
$colorModeJs = json_encode($colorMode, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>La Imparial | User Dashboard</title>
    <script>(function(){var mode=<?php echo $colorModeJs; ?>;try{mode=window.localStorage.getItem('la_imparial_theme')||mode;}catch(error){}document.documentElement.setAttribute('data-theme',mode||'light');}());</script>
    <link rel="stylesheet" href="../css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="../css/font-awesome.min.css" type="text/css">
    <link rel="stylesheet" href="<?php echo e(store_asset_url('css/responsive-enhancements.css')); ?>" type="text/css">
    <style>
        :root {
            --bg: #f4f8fb;
            --card: #ffffff;
            --ink: #102033;
            --muted: #5c6b7d;
            --line: #d9e4ef;
            --accent: #1d4ed8;
            --accent-2: #0f766e;
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: linear-gradient(180deg, #fbfdff 0%, #edf3fa 100%); color: var(--ink); font-family: "Segoe UI", Arial, sans-serif; }
        a { color: inherit; }
        .shell { max-width: 1320px; margin: 0 auto; padding: 28px 18px 42px; }
        .topbar { display:flex; justify-content:space-between; gap:18px; align-items:center; margin-bottom:24px; }
        .brand { display:flex; align-items:center; gap:14px; }
        .brand img { width:54px; height:54px; object-fit:contain; padding:8px; border-radius:16px; background:#fff; box-shadow:0 10px 28px rgba(16,32,51,0.08); }
        .brand h1 { margin:0; font-size:30px; }
        .brand p { margin:4px 0 0; color:var(--muted); }
        .top-actions { display:flex; flex-wrap:wrap; gap:10px; }
        .chip-link { display:inline-flex; gap:8px; align-items:center; padding:12px 16px; background:#fff; border:1px solid var(--line); border-radius:999px; text-decoration:none; font-weight:600; }
        .hero { background:linear-gradient(135deg, #102033 0%, #1d4ed8 58%, #0f766e 100%); color:#fff; border-radius:30px; padding:28px; box-shadow:0 24px 60px rgba(16,32,51,0.18); }
        .hero p { color:rgba(255,255,255,0.84); max-width:860px; }
        .stats { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin-top:24px; }
        .stat { padding:18px; border-radius:22px; background:rgba(255,255,255,0.13); border:1px solid rgba(255,255,255,0.14); }
        .stat strong { display:block; font-size:34px; line-height:1; margin-bottom:10px; }
        .grid { display:grid; grid-template-columns:1fr 1fr; gap:18px; margin-top:22px; }
        .panel { background:var(--card); border:1px solid var(--line); border-radius:24px; padding:22px; box-shadow:0 18px 40px rgba(16,32,51,0.08); }
        .panel h2 { margin:0 0 8px; font-size:22px; }
        .panel > p { margin:0 0 18px; color:var(--muted); }
        .quick-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:14px; }
        .quick-card { border:1px solid var(--line); border-radius:20px; padding:18px; background:#f8fbff; }
        .quick-card h3 { font-size:16px; margin:0 0 10px; }
        .quick-card p { color:var(--muted); margin:0 0 14px; min-height:44px; }
        .quick-card a { display:inline-block; text-decoration:none; padding:10px 14px; border-radius:999px; background:var(--accent); color:#fff; font-weight:700; }
        .list-card { display:flex; gap:14px; padding:14px 0; border-bottom:1px solid var(--line); }
        .list-card img { width:82px; height:82px; object-fit:contain; background:#f8fafc; border-radius:16px; padding:10px; border:1px solid var(--line); }
        .list-card h4 { margin:0 0 6px; font-size:18px; }
        .list-card p { margin:0 0 8px; color:var(--muted); }
        .list-card .meta { display:flex; flex-wrap:wrap; gap:10px; color:var(--muted); font-size:13px; }
        .actions { display:flex; flex-wrap:wrap; gap:10px; margin-top:10px; }
        .button { display:inline-block; padding:10px 14px; border-radius:999px; text-decoration:none; font-weight:700; background:var(--accent-2); color:#fff; }
        .button.secondary { background:#e8f0ff; color:#1d4ed8; }
        .flash { margin-bottom:18px; padding:14px 16px; border-radius:16px; border:1px solid var(--line); background:#fff; }
        .flash--success { border-color:#86efac; background:#f0fdf4; }
        .flash--danger { border-color:#fdba74; background:#fff7ed; }
        .flash--warning { border-color:#fde68a; background:#fffbeb; }
        .permission-strip { display:grid; grid-template-columns:repeat(auto-fit, minmax(190px, 1fr)); gap:12px; margin-top:18px; }
        .permission-pill { border:1px solid rgba(255,255,255,0.16); border-radius:18px; padding:14px; background:rgba(255,255,255,0.12); }
        .permission-pill strong { display:block; font-size:14px; }
        .permission-pill span { color:rgba(255,255,255,0.78); font-size:12px; }
        @media (max-width: 991px) {
            .grid { grid-template-columns:1fr; }
            .topbar { flex-direction:column; align-items:flex-start; }
        }
    </style>
</head>
<body>
    <div class="shell">
        <?php if (is_array($flash)): ?>
            <div class="flash flash--<?php echo e($flash['type']); ?>"><?php echo e($flash['message']); ?></div>
        <?php endif; ?>

        <div class="topbar">
            <div class="brand">
                <img src="../img/logo/logo.png" alt="La Imparial">
                <div>
                    <h1>User Dashboard</h1>
                    <p>Your profile, products, cart, favorites, support, and recovery tools in one place.</p>
                </div>
            </div>
            <div class="top-actions">
                <a class="chip-link" href="../index.php"><i class="fa fa-home"></i> Website</a>
                <a class="chip-link" href="../profile.php"><i class="fa fa-user"></i> Profile</a>
                <?php if ($canViewAdmin): ?><a class="chip-link" href="../admin/"><i class="fa fa-th-large"></i> Admin</a><?php endif; ?>
                <a class="chip-link" href="../contact.php"><i class="fa fa-envelope"></i> Contact Admin</a>
                <a class="chip-link" href="../logout.php"><i class="fa fa-sign-out"></i> Logout</a>
            </div>
        </div>

        <section class="hero">
            <h2 style="margin:0 0 8px; font-size:34px;">Welcome, <?php echo e($user['name']); ?></h2>
            <p>This dedicated user folder gives you a clean dashboard for account actions, product management, favorites, cart, reviews, password recovery, and direct admin support.</p>
            <div class="stats">
                <div class="stat"><strong><?php echo count($products); ?></strong><span>My Products</span></div>
                <div class="stat"><strong><?php echo $favoriteCount; ?></strong><span>Favorites</span></div>
                <div class="stat"><strong><?php echo $cartCount; ?></strong><span>Cart Items</span></div>
                <div class="stat"><strong><?php echo count($inquiries); ?></strong><span>My Support Tickets</span></div>
            </div>
            <div class="permission-strip">
                <div class="permission-pill"><strong><?php echo $canCreateProduct ? 'Product creator' : 'Viewer mode'; ?></strong><span><?php echo $canCreateProduct ? 'You can add and edit your approved products.' : 'Ask admin for product creation permission.'; ?></span></div>
                <div class="permission-pill"><strong><?php echo $canManageProducts ? 'Catalog manager' : 'Catalog locked'; ?></strong><span><?php echo $canManageProducts ? 'Brand, category, labels, and kanban controls are available.' : 'Brand and category controls need admin approval.'; ?></span></div>
                <div class="permission-pill"><strong><?php echo $canManageSettings ? 'Website editor' : 'Editor locked'; ?></strong><span><?php echo $canManageSettings ? 'You can customize templates and visual settings.' : 'Theme customization needs settings permission.'; ?></span></div>
            </div>
        </section>

        <div class="grid">
            <div class="panel">
                <h2>Quick Actions</h2>
                <p>Jump into the main user flows without searching across the site.</p>
                <div class="quick-grid">
                    <div class="quick-card">
                        <h3>Manage Profile</h3>
                        <p>Update your name, email, password, and avatar from the main profile page.</p>
                        <a href="../profile.php">Open Profile</a>
                    </div>
                    <div class="quick-card">
                        <h3>Add Product</h3>
                        <p><?php echo $canCreateProduct ? 'Create a new product with images, category, brand, and pricing details.' : 'Your account is currently in viewer mode. Ask admin to upgrade your role before adding products.'; ?></p>
                        <a href="<?php echo $canCreateProduct ? '../product-form.php' : '../contact.php?subject=' . urlencode('Need product creation access'); ?>"><?php echo $canCreateProduct ? 'Add Product' : 'Request Access'; ?></a>
                    </div>
                    <div class="quick-card">
                        <h3>Catalog Studio</h3>
                        <p><?php echo $canManageProducts ? 'Open brand groups, category groups, labels, API sync, and product kanban.' : 'Request catalog permission to manage brands, categories, and label boards.'; ?></p>
                        <a href="<?php echo $canManageProducts ? '../admin/catalog.php' : '../contact.php?subject=' . urlencode('Need catalog management access'); ?>"><?php echo $canManageProducts ? 'Open Catalog' : 'Request Catalog Access'; ?></a>
                    </div>
                    <div class="quick-card">
                        <h3>Website Templates</h3>
                        <p><?php echo $canManageSettings ? 'Apply ready templates, dark/light defaults, SEO text, and visual settings.' : 'Request editor permission before changing website templates.'; ?></p>
                        <a href="<?php echo $canManageSettings ? '../website-editor.php' : '../contact.php?subject=' . urlencode('Need website editor access'); ?>"><?php echo $canManageSettings ? 'Open Editor' : 'Request Editor Access'; ?></a>
                    </div>
                    <div class="quick-card">
                        <h3>Browse Store</h3>
                        <p>Open live products and separate product detail pages with reviews.</p>
                        <a href="../shop.php?view=products">Browse Products</a>
                    </div>
                    <div class="quick-card">
                        <h3>Password Recovery</h3>
                        <p>Use email verification code recovery if you ever forget your password.</p>
                        <a href="../forgot-password.php">Recover Password</a>
                    </div>
                    <div class="quick-card">
                        <h3>Favorites and Cart</h3>
                        <p>See everything you saved or prepared for purchase from the live site.</p>
                        <a href="../cart.php">Open Cart</a>
                    </div>
                    <div class="quick-card">
                        <h3>Contact Admin</h3>
                        <p>Send a real support inquiry that reaches the admin dashboard and inbox.</p>
                        <a href="../contact.php?subject=<?php echo urlencode('User support request'); ?>">Open Support</a>
                    </div>
                </div>
            </div>

            <div class="panel">
                <h2>Dashboard Inbox</h2>
                <p>Announcements sent by admin or your assigned role appear here.</p>
                <?php if ($messages === []): ?>
                    <p style="margin:0 0 14px;">No dashboard messages yet.</p>
                <?php else: ?>
                    <?php foreach (array_slice($messages, 0, 5) as $message): ?>
                        <div class="list-card">
                            <div>
                                <h4><?php echo e((string) $message['title']); ?></h4>
                                <p><?php echo e(store_notification_limit_text((string) $message['message_body'], 120)); ?></p>
                                <div class="meta">
                                    <span><?php echo e((string) ($message['sender_name'] ?: 'Admin')); ?></span>
                                    <span><?php echo e((string) $message['created_at']); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="panel">
                <h2>My Support Status</h2>
                <p>Your latest contact inquiries and their current admin handling status.</p>
                <?php if ($inquiries === []): ?>
                    <p style="margin:0;">No inquiries yet. If you need help, open the real contact page and message admin directly.</p>
                <?php else: ?>
                    <?php foreach (array_slice($inquiries, 0, 6) as $inquiry): ?>
                        <div class="list-card">
                            <div>
                                <h4><?php echo e($inquiry['subject_line']); ?></h4>
                                <p><?php echo e(store_notification_limit_text((string) $inquiry['message_body'], 120)); ?></p>
                                <div class="meta">
                                    <span>Status: <?php echo e(str_replace('_', ' ', (string) $inquiry['inquiry_status'])); ?></span>
                                    <span><?php echo e((string) $inquiry['created_at']); ?></span>
                                    <?php if (!empty($inquiry['product_name'])): ?><span>Product: <?php echo e($inquiry['product_name']); ?></span><?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid">
            <div class="panel">
                <h2>My Products</h2>
                <p>Products you added to the catalog.</p>
                <?php if ($products === []): ?>
                    <p style="margin:0 0 12px;"><?php echo $canCreateProduct ? 'No products yet. Start by uploading your first item.' : 'You are still on viewer access, so product creation is locked until admin upgrades your role.'; ?></p>
                    <a class="button" href="<?php echo $canCreateProduct ? '../product-form.php' : '../contact.php?subject=' . urlencode('Need product creation access'); ?>"><?php echo $canCreateProduct ? 'Add My First Product' : 'Request Product Access'; ?></a>
                <?php else: ?>
                    <?php foreach (array_slice($products, 0, 4) as $product): ?>
                        <div class="list-card">
                            <img src="../<?php echo e(ltrim((string) $product['image_path'], './')); ?>" alt="<?php echo e($product['name']); ?>">
                            <div>
                                <h4><?php echo e($product['name']); ?></h4>
                                <p><?php echo e($product['summary']); ?></p>
                                <div class="meta">
                                    <span><?php echo e($product['brand_name']); ?></span>
                                    <span><?php echo e($product['category_name']); ?></span>
                                    <span><?php echo e(store_format_money((float) $product['price'])); ?></span>
                                </div>
                                <div class="actions">
                                    <a class="button" href="../edit-product.php?product=<?php echo urlencode((string) $product['slug']); ?>">Edit</a>
                                    <a class="button secondary" href="../shop-details.php?product=<?php echo urlencode((string) $product['slug']); ?>">View Detail</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="panel">
                <h2>Favorites and Cart</h2>
                <p>Your saved products and active cart flow.</p>
                <?php foreach (array_slice($favorites, 0, 2) as $item): ?>
                    <div class="list-card">
                        <img src="../<?php echo e(ltrim((string) $item['image_path'], './')); ?>" alt="<?php echo e($item['name']); ?>">
                        <div>
                            <h4><?php echo e($item['name']); ?></h4>
                            <p><?php echo e($item['summary']); ?></p>
                            <div class="meta">
                                <span><?php echo e($item['brand_name']); ?></span>
                                <span><?php echo e(store_format_money((float) $item['price'])); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php foreach (array_slice($cartItems, 0, 2) as $item): ?>
                    <div class="list-card">
                        <img src="../<?php echo e(ltrim((string) $item['image_path'], './')); ?>" alt="<?php echo e($item['name']); ?>">
                        <div>
                            <h4><?php echo e($item['name']); ?></h4>
                            <p>Quantity: <?php echo (int) $item['quantity']; ?> | Line Total: <?php echo e(store_format_money((float) $item['line_total'])); ?></p>
                            <div class="meta">
                                <span><?php echo e($item['brand_name']); ?></span>
                                <span><?php echo e($item['category_name']); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="actions">
                    <a class="button" href="../favorites.php">Open Favorites</a>
                    <a class="button secondary" href="../cart.php">Open Cart</a>
                </div>
            </div>
        </div>
    </div>
    <script src="<?php echo e(store_asset_url('js/theme-toggle.js')); ?>"></script>
</body>
</html>
