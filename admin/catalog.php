<?php
declare(strict_types=1);

require_once __DIR__ . '/shared.php';

admin_require_panel_permission('manage_products', 'You do not have permission to manage the catalog.');

$csrfKey = 'admin_catalog_panel';
$csrfToken = store_csrf_token($csrfKey);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $token = (string) ($_POST['csrf_token'] ?? '');
    if (!store_verify_csrf_token($csrfKey, $token)) {
        store_flash('danger', 'Security verification failed. Please try again.');
        header('Location: ./catalog.php');
        exit;
    }

    $action = trim((string) ($_POST['action'] ?? ''));
    $result = ['success' => false, 'message' => 'Unknown catalog action.'];

    if ($action === 'save_brand') {
        $result = function_exists('store_save_brand_profile')
            ? store_save_brand_profile($con, $_POST, $_FILES)
            : ['success' => false, 'message' => 'Brand manager helper missing.'];
    }

    if ($action === 'delete_brand') {
        $result = function_exists('store_delete_brand_profile')
            ? store_delete_brand_profile($con, (int) ($_POST['brand_id'] ?? 0))
            : ['success' => false, 'message' => 'Brand delete helper missing.'];
    }

    if ($action === 'save_category') {
        $result = function_exists('store_save_category_profile')
            ? store_save_category_profile($con, $_POST, $_FILES)
            : ['success' => false, 'message' => 'Category manager helper missing.'];
    }

    if ($action === 'delete_category') {
        $result = function_exists('store_delete_category_profile')
            ? store_delete_category_profile($con, (int) ($_POST['category_id'] ?? 0))
            : ['success' => false, 'message' => 'Category delete helper missing.'];
    }

    if ($action === 'save_label') {
        $result = function_exists('store_save_product_label')
            ? store_save_product_label($con, $_POST)
            : ['success' => false, 'message' => 'Label helper missing.'];
    }

    if ($action === 'delete_label') {
        $result = function_exists('store_delete_product_label')
            ? store_delete_product_label($con, (int) ($_POST['label_id'] ?? 0))
            : ['success' => false, 'message' => 'Label delete helper missing.'];
    }

    if ($action === 'attach_label') {
        $result = function_exists('store_attach_product_label')
            ? store_attach_product_label($con, (int) ($_POST['product_id'] ?? 0), (int) ($_POST['label_id'] ?? 0))
            : ['success' => false, 'message' => 'Label attach helper missing.'];
    }

    if ($action === 'detach_label') {
        $result = function_exists('store_detach_product_label')
            ? store_detach_product_label($con, (int) ($_POST['product_id'] ?? 0), (int) ($_POST['label_id'] ?? 0))
            : ['success' => false, 'message' => 'Label detach helper missing.'];
    }

    if ($action === 'clear_product_labels') {
        if (function_exists('store_sync_product_labels')) {
            store_sync_product_labels($con, (int) ($_POST['product_id'] ?? 0), []);
            $result = ['success' => true, 'message' => 'Product labels cleared successfully.'];
        } else {
            $result = ['success' => false, 'message' => 'Label clear helper missing.'];
        }
    }

    if ($action === 'delete_product') {
        $result = store_delete_product($con, (int) ($_POST['product_id'] ?? 0));
    }

    if ($action === 'import_api_products') {
        $result = store_import_products_from_api(
            $con,
            (string) ($_POST['source_url'] ?? ''),
            (string) ($_POST['brand_override'] ?? ''),
            (int) ($_POST['import_limit'] ?? 20)
        );
    }

    store_flash(($result['success'] ?? false) ? 'success' : 'danger', (string) ($result['message'] ?? 'Catalog action finished.'));
    header('Location: ./catalog.php');
    exit;
}

$products = store_products_with_owner($con);
$brands = function_exists('store_brand_catalog') ? store_brand_catalog($con) : store_brands($con);
$categories = function_exists('store_category_catalog') ? store_category_catalog($con) : store_categories($con);
$labels = function_exists('store_product_labels') ? store_product_labels($con) : [];
$labelIndex = function_exists('store_product_label_index_by_product_ids')
    ? store_product_label_index_by_product_ids($con, array_map('intval', array_column($products, 'id')))
    : [];
$productsById = [];
$unlabeledProducts = [];

foreach ($products as &$product) {
    $productId = (int) ($product['id'] ?? 0);
    $product['labels'] = $labelIndex[$productId] ?? [];
    $productsById[$productId] = $product;
    if ($product['labels'] === []) {
        $unlabeledProducts[] = $product;
    }
}
unset($product);

admin_page_start(
    'La Imparial | Admin Catalog',
    'catalog.php',
    'Catalog',
    'Brands, categories, labels, media, and API sync in one dashboard.',
    'New brands can now carry their own logo, slider image, hero poster, hero video, splash video, and brand story while products stay controllable through a label-based kanban flow.'
);
?>

<section class="admin-summary-grid">
    <article class="admin-summary-card">
        <div class="admin-summary-card__eyebrow"><i class="fa fa-cubes"></i> Products</div>
        <h3><?php echo count($products); ?></h3>
        <p>Every catalog item stored in MySQL and ready for brand pages, categories, details, compare, and API export.</p>
    </article>
    <article class="admin-summary-card">
        <div class="admin-summary-card__eyebrow"><i class="fa fa-tags"></i> Brands</div>
        <h3><?php echo count($brands); ?></h3>
        <p>Dropdown-visible brands with media-rich brand-page settings and slider assets.</p>
    </article>
    <article class="admin-summary-card">
        <div class="admin-summary-card__eyebrow"><i class="fa fa-folder-open-o"></i> Categories</div>
        <h3><?php echo count($categories); ?></h3>
        <p>Categories with notes, documents, banners, and optional video content.</p>
    </article>
    <article class="admin-summary-card">
        <div class="admin-summary-card__eyebrow"><i class="fa fa-thumb-tack"></i> Labels</div>
        <h3><?php echo count($labels); ?></h3>
        <p>Reusable kanban columns so one product can live in multiple promo groups.</p>
    </article>
</section>

<section class="admin-content-grid" style="margin-top:18px;">
    <div class="admin-stack">
        <article class="admin-panel">
            <div class="admin-panel__head">
                <div>
                    <h3>Brand Studio</h3>
                    <p>Create a brand and capture the brand page media at the same time.</p>
                </div>
            </div>
            <form method="post" action="./catalog.php" enctype="multipart/form-data" class="admin-form-stack">
                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                <input type="hidden" name="action" value="save_brand">
                <div class="admin-form-grid admin-form-grid--wide">
                    <label>
                        <span>Brand Name</span>
                        <input class="admin-input" type="text" name="name" placeholder="Gree" required>
                    </label>
                    <label>
                        <span>Brand Slug</span>
                        <input class="admin-input" type="text" name="slug" placeholder="gree">
                    </label>
                    <label>
                        <span>Sort Order</span>
                        <input class="admin-input" type="number" name="sort_order" value="0">
                    </label>
                    <label>
                        <span>Brand Logo Path</span>
                        <input class="admin-input" type="text" name="logo_path" placeholder="img/brand-logos/gree.png">
                    </label>
                    <label>
                        <span>Upload Brand Logo</span>
                        <input class="admin-input" type="file" name="logo_file" accept="image/jpeg,image/png,image/gif,image/webp,image/avif,image/svg+xml">
                    </label>
                    <label>
                        <span>Visible In Dropdown</span>
                        <select class="admin-input" name="is_visible">
                            <option value="1">Yes</option>
                            <option value="">No</option>
                        </select>
                    </label>
                    <label>
                        <span>Brand Hero Title</span>
                        <input class="admin-input" type="text" name="hero_title" placeholder="Gree Smart Comfort Range">
                    </label>
                    <label>
                        <span>Featured Product Slug</span>
                        <input class="admin-input" type="text" name="featured_product_slug" placeholder="gree-smartcool-15-ac">
                    </label>
                    <label>
                        <span>Badges</span>
                        <input class="admin-input" type="text" name="badges" placeholder="Premium Cooling, Fast Delivery, Smart Home">
                    </label>
                </div>
                <label>
                    <span>Brand Hero Summary</span>
                    <textarea class="admin-input" name="hero_summary" rows="3" placeholder="Short copy for brand page hero and home showcase."></textarea>
                </label>
                <div class="admin-form-grid admin-form-grid--wide">
                    <label>
                        <span>Home Slider Banner Path</span>
                        <input class="admin-input" type="text" name="slider_banner_path" placeholder="img/slider/gree.jpg">
                    </label>
                    <label>
                        <span>Upload Home Slider Banner</span>
                        <input class="admin-input" type="file" name="slider_banner_file" accept="image/jpeg,image/png,image/gif,image/webp,image/avif">
                    </label>
                    <label>
                        <span>Brand Hero Poster Path</span>
                        <input class="admin-input" type="text" name="hero_poster_path" placeholder="img/banner/banner-1.jpg">
                    </label>
                    <label>
                        <span>Upload Brand Hero Poster</span>
                        <input class="admin-input" type="file" name="hero_poster_file" accept="image/jpeg,image/png,image/gif,image/webp,image/avif">
                    </label>
                    <label>
                        <span>Hero Video Path</span>
                        <input class="admin-input" type="text" name="hero_video_path" placeholder="Ads-Video/Gree.mp4">
                    </label>
                    <label>
                        <span>Upload Hero Video</span>
                        <input class="admin-input" type="file" name="hero_video_file" accept="video/mp4,video/webm,video/ogg">
                    </label>
                    <label>
                        <span>Splash Video Path</span>
                        <input class="admin-input" type="text" name="splash_video_path" placeholder="videos/brands/gree-intro.mp4">
                    </label>
                    <label>
                        <span>Upload Splash Video</span>
                        <input class="admin-input" type="file" name="splash_video_file" accept="video/mp4,video/webm,video/ogg">
                    </label>
                    <label>
                        <span>Splash Logo Path</span>
                        <input class="admin-input" type="text" name="splash_logo_path" placeholder="img/logo/image.png">
                    </label>
                    <label>
                        <span>Upload Splash Logo</span>
                        <input class="admin-input" type="file" name="splash_logo_file" accept="image/jpeg,image/png,image/gif,image/webp,image/avif,image/svg+xml">
                    </label>
                </div>
                <button class="admin-button admin-button--primary" type="submit">Save Brand Profile</button>
            </form>
        </article>

        <article class="admin-panel">
            <div class="admin-panel__head">
                <div>
                    <h3>API Sync</h3>
                    <p>Paste any JSON feed URL. If the feed also contains brands, categories, or labels, they sync too.</p>
                </div>
                <a class="admin-soft-link" href="../api/products.php" target="_blank" rel="noopener">Open Local Catalog API</a>
            </div>
            <form method="post" action="./catalog.php" class="admin-form-stack">
                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                <input type="hidden" name="action" value="import_api_products">
                <label>
                    <span>JSON API URL</span>
                    <input class="admin-input" type="url" name="source_url" placeholder="https://example.com/catalog.json" required>
                </label>
                <label>
                    <span>Brand Override</span>
                    <input class="admin-input" type="text" name="brand_override" placeholder="Optional fixed brand name">
                </label>
                <label>
                    <span>Import Limit</span>
                    <input class="admin-input" type="number" min="1" max="100" name="import_limit" value="20">
                </label>
                <button class="admin-button admin-button--primary" type="submit">Sync API Payload</button>
            </form>
        </article>

        <article class="admin-panel">
            <div class="admin-panel__head">
                <div>
                    <h3>Label Builder</h3>
                    <p>Create kanban labels with name, slug, and color for product grouping.</p>
                </div>
            </div>
            <form method="post" action="./catalog.php" class="admin-form-stack">
                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                <input type="hidden" name="action" value="save_label">
                <div class="admin-form-grid admin-form-grid--wide">
                    <label>
                        <span>Label Name</span>
                        <input class="admin-input" type="text" name="name" placeholder="Smart Pick" required>
                    </label>
                    <label>
                        <span>Label Slug</span>
                        <input class="admin-input" type="text" name="slug" placeholder="smart-pick">
                    </label>
                    <label>
                        <span>Color</span>
                        <input class="admin-input" type="color" name="color_hex" value="#0b1688">
                    </label>
                    <label>
                        <span>Sort Order</span>
                        <input class="admin-input" type="number" name="sort_order" value="0">
                    </label>
                </div>
                <label>
                    <span>Description</span>
                    <textarea class="admin-input" name="description_text" rows="3" placeholder="What does this label mean for the team?"></textarea>
                </label>
                <button class="admin-button admin-button--primary" type="submit">Save Label</button>
            </form>
        </article>
    </div>

    <article class="admin-panel">
        <div class="admin-panel__head">
            <div>
                <h3>Inventory Roster</h3>
                <p>Quick view of product ownership, pricing, labels, and edit routes.</p>
            </div>
            <a class="admin-soft-link" href="../product-form.php">Add product</a>
        </div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Labels</th>
                        <th>Owner</th>
                        <th>Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <div class="admin-product-line">
                                    <img src="<?php echo e(preg_match('#^https?://#i', (string) $product['image_path']) ? (string) $product['image_path'] : '../' . ltrim((string) $product['image_path'], './')); ?>" alt="<?php echo e((string) $product['name']); ?>">
                                    <div>
                                        <strong><?php echo e((string) $product['name']); ?></strong>
                                        <p><?php echo e((string) $product['brand_name']); ?> • <?php echo e((string) $product['category_name']); ?><?php echo !empty($product['custom_label']) ? ' • ' . e((string) $product['custom_label']) : ''; ?> • <?php echo (int) ($product['view_count'] ?? 0); ?> views</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="admin-label-stack">
                                    <?php if (($product['labels'] ?? []) === []): ?>
                                        <span class="admin-kanban-pill admin-kanban-pill--empty">No label</span>
                                    <?php else: ?>
                                        <?php foreach ($product['labels'] as $label): ?>
                                            <span class="admin-kanban-pill" style="--label-accent:<?php echo e((string) ($label['color_hex'] ?? '#0b1688')); ?>;"><?php echo e((string) $label['name']); ?></span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <strong><?php echo e((string) ($product['owner_name'] ?: 'System')); ?></strong>
                                <p><?php echo e((string) ($product['owner_email'] ?: '')); ?></p>
                            </td>
                            <td><?php echo e(store_format_money((float) $product['price'])); ?></td>
                            <td>
                                <div class="admin-action-row">
                                    <a class="admin-button" href="../edit-product.php?product=<?php echo urlencode((string) $product['slug']); ?>">Edit</a>
                                    <form method="post" action="./catalog.php">
                                        <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                        <input type="hidden" name="action" value="delete_product">
                                        <input type="hidden" name="product_id" value="<?php echo (int) $product['id']; ?>">
                                        <button class="admin-button admin-button--danger" type="submit" onclick="return confirm('Delete this product?');">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>

<section class="admin-content-grid" style="margin-top:18px;">
    <article class="admin-panel">
        <div class="admin-panel__head">
            <div>
                <h3>Brand Controls</h3>
                <p>Edit dropdown visibility, brand page assets, and splash/hero media for each brand.</p>
            </div>
        </div>
        <div class="admin-library-toolbar">
            <label>
                <span>Find Brand</span>
                <input class="admin-input" type="search" placeholder="Search by brand name, slug, or media path" data-admin-filter="#admin-brand-library">
            </label>
            <div class="admin-action-row">
                <button class="admin-button" type="button" data-admin-expand="#admin-brand-library">Expand All</button>
                <button class="admin-button" type="button" data-admin-collapse="#admin-brand-library">Collapse All</button>
            </div>
        </div>
        <div class="admin-brand-grid" id="admin-brand-library">
            <?php foreach ($brands as $brand): ?>
                <form method="post" action="./catalog.php" enctype="multipart/form-data" class="admin-brand-card admin-collapsible-card" data-admin-card-search="<?php echo e(strtolower((string) ($brand['name'] . ' ' . $brand['slug'] . ' ' . ($brand['logo_path'] ?? '') . ' ' . ($brand['hero_video_path'] ?? '') . ' ' . ($brand['splash_video_path'] ?? '')))); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                    <input type="hidden" name="action" value="save_brand">
                    <input type="hidden" name="brand_id" value="<?php echo (int) ($brand['id'] ?? 0); ?>">
                    <div class="admin-brand-card__head">
                        <div class="admin-product-line">
                            <img src="<?php echo e(preg_match('#^https?://#i', (string) ($brand['logo_path'] ?? '')) ? (string) $brand['logo_path'] : '../' . ltrim((string) ($brand['logo_path'] ?? 'img/logo/logo.png'), './')); ?>" alt="<?php echo e((string) $brand['name']); ?>">
                            <div>
                                <strong><?php echo e((string) $brand['name']); ?></strong>
                                <p><?php echo (int) ($brand['product_count'] ?? 0); ?> linked products</p>
                            </div>
                        </div>
                        <div class="admin-action-row">
                            <button class="admin-button admin-button--primary" type="submit">Save</button>
                        </div>
                    </div>
                    <div class="admin-form-grid admin-form-grid--wide">
                        <label>
                            <span>Name</span>
                            <input class="admin-input" type="text" name="name" value="<?php echo e((string) $brand['name']); ?>" required>
                        </label>
                        <label>
                            <span>Slug</span>
                            <input class="admin-input" type="text" name="slug" value="<?php echo e((string) $brand['slug']); ?>">
                        </label>
                        <label>
                            <span>Sort Order</span>
                            <input class="admin-input" type="number" name="sort_order" value="<?php echo (int) ($brand['sort_order'] ?? 0); ?>">
                        </label>
                        <label>
                            <span>Logo Path</span>
                            <input class="admin-input" type="text" name="logo_path" value="<?php echo e((string) ($brand['logo_path'] ?? '')); ?>">
                        </label>
                        <label>
                            <span>Upload Logo</span>
                            <input class="admin-input" type="file" name="logo_file" accept="image/jpeg,image/png,image/gif,image/webp,image/avif,image/svg+xml">
                        </label>
                        <label>
                            <span>Visible</span>
                            <select class="admin-input" name="is_visible">
                                <option value="1" <?php echo (int) ($brand['is_visible'] ?? 1) === 1 ? 'selected' : ''; ?>>Yes</option>
                                <option value="" <?php echo (int) ($brand['is_visible'] ?? 1) === 0 ? 'selected' : ''; ?>>No</option>
                            </select>
                        </label>
                        <label>
                            <span>Hero Title</span>
                            <input class="admin-input" type="text" name="hero_title" value="<?php echo e((string) ($brand['hero_title'] ?? '')); ?>">
                        </label>
                        <label>
                            <span>Featured Product Slug</span>
                            <input class="admin-input" type="text" name="featured_product_slug" value="<?php echo e((string) ($brand['featured_product_slug'] ?? '')); ?>">
                        </label>
                        <label>
                            <span>Badges</span>
                            <input class="admin-input" type="text" name="badges" value="<?php echo e(implode(', ', $brand['badges'] ?? [])); ?>">
                        </label>
                    </div>
                    <label>
                        <span>Hero Summary</span>
                        <textarea class="admin-input" name="hero_summary" rows="3"><?php echo e((string) ($brand['hero_summary'] ?? '')); ?></textarea>
                    </label>
                    <div class="admin-form-grid admin-form-grid--wide">
                        <label>
                            <span>Home Slider Banner</span>
                            <input class="admin-input" type="text" name="slider_banner_path" value="<?php echo e((string) ($brand['slider_banner_path'] ?? '')); ?>">
                        </label>
                        <label>
                            <span>Upload Slider Banner</span>
                            <input class="admin-input" type="file" name="slider_banner_file" accept="image/jpeg,image/png,image/gif,image/webp,image/avif">
                        </label>
                        <label>
                            <span>Hero Poster</span>
                            <input class="admin-input" type="text" name="hero_poster_path" value="<?php echo e((string) ($brand['hero_poster_path'] ?? '')); ?>">
                        </label>
                        <label>
                            <span>Upload Hero Poster</span>
                            <input class="admin-input" type="file" name="hero_poster_file" accept="image/jpeg,image/png,image/gif,image/webp,image/avif">
                        </label>
                        <label>
                            <span>Hero Video</span>
                            <input class="admin-input" type="text" name="hero_video_path" value="<?php echo e((string) ($brand['hero_video_path'] ?? '')); ?>">
                        </label>
                        <label>
                            <span>Upload Hero Video</span>
                            <input class="admin-input" type="file" name="hero_video_file" accept="video/mp4,video/webm,video/ogg">
                        </label>
                        <label>
                            <span>Splash Video</span>
                            <input class="admin-input" type="text" name="splash_video_path" value="<?php echo e((string) ($brand['splash_video_path'] ?? '')); ?>">
                        </label>
                        <label>
                            <span>Upload Splash Video</span>
                            <input class="admin-input" type="file" name="splash_video_file" accept="video/mp4,video/webm,video/ogg">
                        </label>
                        <label>
                            <span>Splash Logo</span>
                            <input class="admin-input" type="text" name="splash_logo_path" value="<?php echo e((string) ($brand['splash_logo_path'] ?? '')); ?>">
                        </label>
                        <label>
                            <span>Upload Splash Logo</span>
                            <input class="admin-input" type="file" name="splash_logo_file" accept="image/jpeg,image/png,image/gif,image/webp,image/avif,image/svg+xml">
                        </label>
                    </div>
                    <div class="admin-action-row">
                        <button class="admin-button admin-button--primary" type="submit">Update Brand</button>
                        <button class="admin-button admin-button--danger" type="submit" name="action" value="delete_brand" onclick="return confirm('Delete this brand?');">Delete</button>
                    </div>
                </form>
            <?php endforeach; ?>
        </div>
    </article>

    <article class="admin-panel">
        <div class="admin-panel__head">
            <div>
                <h3>Category Controls</h3>
                <p>Edit category messaging, docs, banner image, and optional video.</p>
            </div>
        </div>
        <div class="admin-library-toolbar">
            <label>
                <span>Find Category</span>
                <input class="admin-input" type="search" placeholder="Search by category name, slug, or document" data-admin-filter="#admin-category-library">
            </label>
            <div class="admin-action-row">
                <button class="admin-button" type="button" data-admin-expand="#admin-category-library">Expand All</button>
                <button class="admin-button" type="button" data-admin-collapse="#admin-category-library">Collapse All</button>
            </div>
        </div>
        <div class="admin-brand-grid" id="admin-category-library">
            <form method="post" action="./catalog.php" enctype="multipart/form-data" class="admin-brand-card admin-collapsible-card is-open" data-admin-card-search="create new category">
                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                <input type="hidden" name="action" value="save_category">
                <div class="admin-brand-card__head">
                    <div>
                        <strong>Create New Category</strong>
                        <p>Add note, document, banner image, and category hero text.</p>
                    </div>
                    <button class="admin-button admin-button--primary" type="submit">Save</button>
                </div>
                <div class="admin-form-grid admin-form-grid--wide">
                    <label>
                        <span>Name</span>
                        <input class="admin-input" type="text" name="name" placeholder="Air Purifiers" required>
                    </label>
                    <label>
                        <span>Slug</span>
                        <input class="admin-input" type="text" name="slug" placeholder="air-purifiers">
                    </label>
                    <label>
                        <span>Sort Order</span>
                        <input class="admin-input" type="number" name="sort_order" value="0">
                    </label>
                </div>
                <label>
                    <span>Category Note</span>
                    <textarea class="admin-input" name="note" rows="3" placeholder="Short front-end note for this category."></textarea>
                </label>
                <label>
                    <span>Hero Headline</span>
                    <input class="admin-input" type="text" name="hero_headline" placeholder="Kitchen essentials built for family routines.">
                </label>
                <div class="admin-form-grid admin-form-grid--wide">
                    <label>
                        <span>Document Path</span>
                        <input class="admin-input" type="text" name="document_path" placeholder="docs/sample.doc">
                    </label>
                    <label>
                        <span>Upload Document</span>
                        <input class="admin-input" type="file" name="document_file" accept=".pdf,.doc,.docx,.txt,application/pdf,application/msword">
                    </label>
                    <label>
                        <span>Banner Image Path</span>
                        <input class="admin-input" type="text" name="banner_image_path" placeholder="img/banner/home.webp">
                    </label>
                    <label>
                        <span>Upload Banner Image</span>
                        <input class="admin-input" type="file" name="banner_image_file" accept="image/jpeg,image/png,image/gif,image/webp,image/avif">
                    </label>
                    <label>
                        <span>Hero Video Path</span>
                        <input class="admin-input" type="text" name="hero_video_path" placeholder="videos/categories/kitchen.mp4">
                    </label>
                    <label>
                        <span>Upload Hero Video</span>
                        <input class="admin-input" type="file" name="hero_video_file" accept="video/mp4,video/webm,video/ogg">
                    </label>
                </div>
            </form>

            <?php foreach ($categories as $category): ?>
                <form method="post" action="./catalog.php" enctype="multipart/form-data" class="admin-brand-card admin-collapsible-card" data-admin-card-search="<?php echo e(strtolower((string) ($category['name'] . ' ' . $category['slug'] . ' ' . ($category['document_path'] ?? '') . ' ' . ($category['hero_video_path'] ?? '')))); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                    <input type="hidden" name="action" value="save_category">
                    <input type="hidden" name="category_id" value="<?php echo (int) ($category['id'] ?? 0); ?>">
                    <div class="admin-brand-card__head">
                        <div>
                            <strong><?php echo e((string) $category['name']); ?></strong>
                            <p><?php echo (int) ($category['product_count'] ?? 0); ?> linked products</p>
                        </div>
                        <button class="admin-button admin-button--primary" type="submit">Save</button>
                    </div>
                    <div class="admin-form-grid admin-form-grid--wide">
                        <label>
                            <span>Name</span>
                            <input class="admin-input" type="text" name="name" value="<?php echo e((string) $category['name']); ?>" required>
                        </label>
                        <label>
                            <span>Slug</span>
                            <input class="admin-input" type="text" name="slug" value="<?php echo e((string) $category['slug']); ?>">
                        </label>
                        <label>
                            <span>Sort Order</span>
                            <input class="admin-input" type="number" name="sort_order" value="<?php echo (int) ($category['sort_order'] ?? 0); ?>">
                        </label>
                    </div>
                    <label>
                        <span>Category Note</span>
                        <textarea class="admin-input" name="note" rows="3"><?php echo e((string) ($category['note'] ?? '')); ?></textarea>
                    </label>
                    <label>
                        <span>Hero Headline</span>
                        <input class="admin-input" type="text" name="hero_headline" value="<?php echo e((string) ($category['hero_headline'] ?? '')); ?>">
                    </label>
                    <div class="admin-form-grid admin-form-grid--wide">
                        <label>
                            <span>Document Path</span>
                            <input class="admin-input" type="text" name="document_path" value="<?php echo e((string) ($category['document_path'] ?? '')); ?>">
                        </label>
                        <label>
                            <span>Upload Document</span>
                            <input class="admin-input" type="file" name="document_file" accept=".pdf,.doc,.docx,.txt,application/pdf,application/msword">
                        </label>
                        <label>
                            <span>Banner Image Path</span>
                            <input class="admin-input" type="text" name="banner_image_path" value="<?php echo e((string) ($category['banner_image_path'] ?? '')); ?>">
                        </label>
                        <label>
                            <span>Upload Banner Image</span>
                            <input class="admin-input" type="file" name="banner_image_file" accept="image/jpeg,image/png,image/gif,image/webp,image/avif">
                        </label>
                        <label>
                            <span>Hero Video Path</span>
                            <input class="admin-input" type="text" name="hero_video_path" value="<?php echo e((string) ($category['hero_video_path'] ?? '')); ?>">
                        </label>
                        <label>
                            <span>Upload Hero Video</span>
                            <input class="admin-input" type="file" name="hero_video_file" accept="video/mp4,video/webm,video/ogg">
                        </label>
                    </div>
                    <div class="admin-action-row">
                        <button class="admin-button admin-button--primary" type="submit">Update Category</button>
                        <button class="admin-button admin-button--danger" type="submit" name="action" value="delete_category" onclick="return confirm('Delete this category?');">Delete</button>
                    </div>
                </form>
            <?php endforeach; ?>
        </div>
    </article>
</section>

<section class="admin-panel" style="margin-top:18px;">
    <div class="admin-panel__head">
        <div>
            <h3>Product Label Kanban</h3>
            <p>Drag any product card into a label column to assign it. Drop into “No Label” to clear all labels.</p>
        </div>
    </div>

    <form id="admin-kanban-form" method="post" action="./catalog.php" style="display:none;">
        <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
        <input type="hidden" name="action" value="attach_label">
        <input type="hidden" name="product_id" value="">
        <input type="hidden" name="label_id" value="">
    </form>

    <div class="admin-kanban-board">
        <div class="admin-kanban-column" data-clear-labels="1">
            <div class="admin-kanban-column__head">
                <strong>No Label</strong>
                <span><?php echo count($unlabeledProducts); ?></span>
            </div>
            <div class="admin-kanban-list">
                <?php foreach ($unlabeledProducts as $product): ?>
                    <article class="admin-kanban-card" draggable="true" data-product-id="<?php echo (int) $product['id']; ?>">
                        <div class="admin-product-line">
                            <img src="<?php echo e(preg_match('#^https?://#i', (string) $product['image_path']) ? (string) $product['image_path'] : '../' . ltrim((string) $product['image_path'], './')); ?>" alt="<?php echo e((string) $product['name']); ?>">
                            <div>
                                <strong><?php echo e((string) $product['name']); ?></strong>
                                <p><?php echo e((string) $product['brand_name']); ?> • <?php echo e((string) $product['category_name']); ?></p>
                            </div>
                        </div>
                        <div class="admin-action-row">
                            <a class="admin-button" href="../edit-product.php?product=<?php echo urlencode((string) $product['slug']); ?>">Edit</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>

        <?php foreach ($labels as $label): ?>
            <div class="admin-kanban-column" data-label-id="<?php echo (int) $label['id']; ?>">
                <div class="admin-kanban-column__head">
                    <strong><?php echo e((string) $label['name']); ?></strong>
                    <span style="background:<?php echo e((string) ($label['color_hex'] ?? '#0b1688')); ?>;"></span>
                </div>
                <div class="admin-kanban-list">
                    <?php foreach ($products as $product): ?>
                        <?php
                        $hasLabel = false;
                        foreach ($product['labels'] as $productLabel) {
                            if ((int) ($productLabel['id'] ?? 0) === (int) $label['id']) {
                                $hasLabel = true;
                                break;
                            }
                        }
                        ?>
                        <?php if ($hasLabel): ?>
                            <article class="admin-kanban-card" draggable="true" data-product-id="<?php echo (int) $product['id']; ?>">
                                <div class="admin-product-line">
                                    <img src="<?php echo e(preg_match('#^https?://#i', (string) $product['image_path']) ? (string) $product['image_path'] : '../' . ltrim((string) $product['image_path'], './')); ?>" alt="<?php echo e((string) $product['name']); ?>">
                                    <div>
                                        <strong><?php echo e((string) $product['name']); ?></strong>
                                        <p><?php echo e((string) $product['brand_name']); ?> • <?php echo e((string) $product['category_name']); ?></p>
                                    </div>
                                </div>
                                <div class="admin-label-stack">
                                    <?php foreach ($product['labels'] as $productLabel): ?>
                                        <span class="admin-kanban-pill" style="--label-accent:<?php echo e((string) ($productLabel['color_hex'] ?? '#0b1688')); ?>;"><?php echo e((string) $productLabel['name']); ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <div class="admin-action-row">
                                    <form method="post" action="./catalog.php">
                                        <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                        <input type="hidden" name="action" value="detach_label">
                                        <input type="hidden" name="product_id" value="<?php echo (int) $product['id']; ?>">
                                        <input type="hidden" name="label_id" value="<?php echo (int) $label['id']; ?>">
                                        <button class="admin-button admin-button--danger" type="submit">Remove Label</button>
                                    </form>
                                    <a class="admin-button" href="../edit-product.php?product=<?php echo urlencode((string) $product['slug']); ?>">Edit</a>
                                </div>
                            </article>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="admin-content-grid" style="margin-top:18px;">
    <article class="admin-panel">
        <div class="admin-panel__head">
            <div>
                <h3>Label Library</h3>
                <p>Edit existing labels without leaving the dashboard.</p>
            </div>
        </div>
        <div class="admin-brand-grid">
            <?php foreach ($labels as $label): ?>
                <form method="post" action="./catalog.php" class="admin-brand-card">
                    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                    <input type="hidden" name="action" value="save_label">
                    <input type="hidden" name="label_id" value="<?php echo (int) $label['id']; ?>">
                    <div class="admin-brand-card__head">
                        <div>
                            <strong><?php echo e((string) $label['name']); ?></strong>
                            <p><?php echo e((string) $label['slug']); ?></p>
                        </div>
                        <span class="admin-kanban-pill" style="--label-accent:<?php echo e((string) ($label['color_hex'] ?? '#0b1688')); ?>;"><?php echo e((string) $label['color_hex']); ?></span>
                    </div>
                    <div class="admin-form-grid admin-form-grid--wide">
                        <label>
                            <span>Name</span>
                            <input class="admin-input" type="text" name="name" value="<?php echo e((string) $label['name']); ?>">
                        </label>
                        <label>
                            <span>Slug</span>
                            <input class="admin-input" type="text" name="slug" value="<?php echo e((string) $label['slug']); ?>">
                        </label>
                        <label>
                            <span>Color</span>
                            <input class="admin-input" type="color" name="color_hex" value="<?php echo e((string) ($label['color_hex'] ?? '#0b1688')); ?>">
                        </label>
                        <label>
                            <span>Sort Order</span>
                            <input class="admin-input" type="number" name="sort_order" value="<?php echo (int) ($label['sort_order'] ?? 0); ?>">
                        </label>
                    </div>
                    <label>
                        <span>Description</span>
                        <textarea class="admin-input" name="description_text" rows="3"><?php echo e((string) ($label['description_text'] ?? '')); ?></textarea>
                    </label>
                    <div class="admin-action-row">
                        <button class="admin-button admin-button--primary" type="submit">Update Label</button>
                        <button class="admin-button admin-button--danger" type="submit" name="action" value="delete_label" onclick="return confirm('Delete this label?');">Delete</button>
                    </div>
                </form>
            <?php endforeach; ?>
        </div>
    </article>
</section>

<?php
$extraScript = <<<'JS'
document.addEventListener('DOMContentLoaded', function () {
    function setLibraryOpen(container, open) {
        if (!container) {
            return;
        }

        Array.prototype.forEach.call(container.querySelectorAll('.admin-collapsible-card'), function (card) {
            card.classList.toggle('is-open', Boolean(open));
        });
    }

    Array.prototype.forEach.call(document.querySelectorAll('.admin-collapsible-card'), function (card) {
        var head = card.querySelector('.admin-brand-card__head');

        if (!head) {
            return;
        }

        head.setAttribute('role', 'button');
        head.setAttribute('tabindex', '0');
        head.addEventListener('click', function (event) {
            if (event.target.closest('a, button, input, select, textarea, label')) {
                return;
            }

            card.classList.toggle('is-open');
        });
        head.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                card.classList.toggle('is-open');
            }
        });
    });

    Array.prototype.forEach.call(document.querySelectorAll('[data-admin-filter]'), function (input) {
        var container = document.querySelector(input.getAttribute('data-admin-filter'));

        input.addEventListener('input', function () {
            var query = input.value.trim().toLowerCase();

            if (!container) {
                return;
            }

            Array.prototype.forEach.call(container.querySelectorAll('.admin-collapsible-card'), function (card) {
                var haystack = (card.getAttribute('data-admin-card-search') || card.textContent || '').toLowerCase();
                var match = query === '' || haystack.indexOf(query) > -1;
                card.style.display = match ? '' : 'none';
                if (query !== '' && match) {
                    card.classList.add('is-open');
                }
            });
        });
    });

    Array.prototype.forEach.call(document.querySelectorAll('[data-admin-expand]'), function (button) {
        button.addEventListener('click', function () {
            setLibraryOpen(document.querySelector(button.getAttribute('data-admin-expand')), true);
        });
    });

    Array.prototype.forEach.call(document.querySelectorAll('[data-admin-collapse]'), function (button) {
        button.addEventListener('click', function () {
            setLibraryOpen(document.querySelector(button.getAttribute('data-admin-collapse')), false);
        });
    });

    var draggedProductId = '';
    var board = document.querySelector('.admin-kanban-board');
    var form = document.getElementById('admin-kanban-form');

    if (!board || !form) {
        return;
    }

    Array.prototype.forEach.call(board.querySelectorAll('.admin-kanban-card[draggable="true"]'), function (card) {
        card.addEventListener('dragstart', function () {
            draggedProductId = card.getAttribute('data-product-id') || '';
            card.classList.add('is-dragging');
        });

        card.addEventListener('dragend', function () {
            card.classList.remove('is-dragging');
        });
    });

    Array.prototype.forEach.call(board.querySelectorAll('.admin-kanban-column'), function (column) {
        column.addEventListener('dragover', function (event) {
            event.preventDefault();
            column.classList.add('is-over');
        });

        column.addEventListener('dragleave', function () {
            column.classList.remove('is-over');
        });

        column.addEventListener('drop', function (event) {
            var productField;
            var labelField;

            event.preventDefault();
            column.classList.remove('is-over');

            if (!draggedProductId) {
                return;
            }

            productField = form.querySelector('input[name="product_id"]');
            labelField = form.querySelector('input[name="label_id"]');
            productField.value = draggedProductId;

            if (column.getAttribute('data-clear-labels') === '1') {
                form.querySelector('input[name="action"]').value = 'clear_product_labels';
                labelField.value = '';
            } else {
                form.querySelector('input[name="action"]').value = 'attach_label';
                labelField.value = column.getAttribute('data-label-id') || '';
            }

            form.submit();
        });
    });
});
JS;

admin_page_end($extraScript);
?>
