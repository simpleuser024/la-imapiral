<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/custom-page.php';

$slug = trim((string) ($_GET['product'] ?? ''));

if ($slug === '' || !($con instanceof mysqli)) {
    store_flash('danger', 'Product not found.');
    header('Location: ./profile.php');
    exit;
}

store_require_login('./edit-product.php?product=' . urlencode($slug));

$product = store_product_by_slug($con, $slug);

if (!$product) {
    store_flash('danger', 'Product not found.');
    header('Location: ./profile.php');
    exit;
}

$currentUserId = store_current_user_id();
$isOwner = (int) ($product['created_by'] ?? 0) === $currentUserId;
$canManageProducts = store_user_has_permission($con, 'manage_products');
$canEditOwnProducts = store_user_has_permission($con, 'edit_own_product');

if (!$canManageProducts && !($isOwner && $canEditOwnProducts)) {
    store_flash('danger', 'You do not have permission to edit this product.');
    header('Location: ./profile.php');
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $con instanceof mysqli) {
    $result = store_update_product($con, $product, $_POST, $_FILES);
    store_flash($result['success'] ? 'success' : 'danger', $result['message']);
    header('Location: ./edit-product.php?product=' . urlencode($slug));
    exit;
}

$categories = store_categories($con);
$brands = store_brands($con);
$gallery = store_parse_gallery_images($product['gallery_images'] ?? '');
$labels = function_exists('store_product_labels') ? store_product_labels($con) : [];
$assignedLabels = function_exists('store_product_labels_for_product') ? store_product_labels_for_product($con, (int) ($product['id'] ?? 0)) : [];
$assignedLabelIds = array_map(static fn ($label) => (int) ($label['id'] ?? 0), $assignedLabels);

render_custom_page_header('La Imparial | Edit Product');
?>
<section class="catalog-admin">
    <div class="container">
        <div class="catalog-page__header">
            <h2>Edit Product</h2>
            <p>Update details, change pricing, and refresh the product gallery.</p>
        </div>

        <div class="catalog-admin__panel">
            <form action="./edit-product.php?product=<?php echo urlencode($slug); ?>" method="post" enctype="multipart/form-data" class="form-row-gap">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Product Name</label>
                            <input type="text" class="form-control" name="name" value="<?php echo e($product['name']); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Price</label>
                            <input type="number" step="0.01" class="form-control" name="price" value="<?php echo e((string) $product['price']); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Old Price</label>
                            <input type="number" step="0.01" class="form-control" name="original_price" value="<?php echo e((string) $product['original_price']); ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Document Path</label>
                            <input type="text" class="form-control" name="document_path" value="<?php echo e($product['document_path']); ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Category</label>
                            <select class="form-control" name="category_id">
                                <option value="">Select category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo (int) $category['id']; ?>" <?php echo (int) $category['id'] === (int) $product['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo e($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Brand</label>
                            <select class="form-control" name="brand_id">
                                <option value="">Select brand</option>
                                <?php foreach ($brands as $brand): ?>
                                    <option value="<?php echo (int) $brand['id']; ?>" <?php echo (int) $brand['id'] === (int) $product['brand_id'] ? 'selected' : ''; ?>>
                                        <?php echo e($brand['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Or New Category</label>
                            <input type="text" class="form-control" name="new_category">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Or New Brand</label>
                            <input type="text" class="form-control" name="new_brand">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>New Brand Logo</label>
                            <input type="file" class="form-control" name="new_brand_logo" accept="image/jpeg,image/png,image/gif,image/webp,image/avif">
                            <small class="product__item__helper">Naye brand ke sath logo bhi upload karo taake marquee aur brand page dono sahi chalein.</small>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label>New Brand Hero Title</label>
                            <input type="text" class="form-control" name="new_brand_hero_title" placeholder="Brand hero title">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label>New Brand Hero Summary</label>
                            <textarea class="form-control" name="new_brand_hero_summary" rows="3" placeholder="Brand hero summary"></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>New Brand Slider Banner</label>
                            <input type="file" class="form-control" name="new_brand_slider_banner_file" accept="image/jpeg,image/png,image/gif,image/webp,image/avif">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>New Brand Hero Poster</label>
                            <input type="file" class="form-control" name="new_brand_hero_poster_file" accept="image/jpeg,image/png,image/gif,image/webp,image/avif">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>New Brand Hero Video</label>
                            <input type="file" class="form-control" name="new_brand_hero_video_file" accept="video/mp4,video/webm,video/ogg">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>New Brand Splash Video</label>
                            <input type="file" class="form-control" name="new_brand_splash_video_file" accept="video/mp4,video/webm,video/ogg">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>New Brand Splash Logo</label>
                            <input type="file" class="form-control" name="new_brand_splash_logo_file" accept="image/jpeg,image/png,image/gif,image/webp,image/avif,image/svg+xml">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>New Brand Badges</label>
                            <input type="text" class="form-control" name="new_brand_badges" placeholder="Smart Cooling, Premium Range">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label>Short Summary</label>
                            <textarea class="form-control" name="summary" rows="3" required><?php echo e($product['summary']); ?></textarea>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label>Full Description</label>
                            <textarea class="form-control" name="description" rows="5" required><?php echo e($product['description']); ?></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Product Label (Optional)</label>
                            <input type="text" class="form-control" name="custom_label" value="<?php echo e((string) ($product['custom_label'] ?? '')); ?>" placeholder="Hot Product / Top Rated / Member Pick">
                        </div>
                    </div>
                    <?php if ($labels !== []): ?>
                        <div class="col-12">
                            <div class="form-group">
                                <label>Assign Dashboard Labels</label>
                                <div class="admin-permission-grid">
                                    <?php foreach ($labels as $label): ?>
                                        <label class="admin-permission-pill" style="margin-bottom:8px;">
                                            <input type="checkbox" name="label_ids[]" value="<?php echo (int) $label['id']; ?>" <?php echo in_array((int) $label['id'], $assignedLabelIds, true) ? 'checked' : ''; ?>>
                                            <span><?php echo e((string) $label['name']); ?><?php echo !empty($label['color_hex']) ? ' (' . e((string) $label['color_hex']) . ')' : ''; ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Replace Main Image (Optional)</label>
                            <input type="file" class="form-control" name="image" accept="image/jpeg,image/png,image/gif,image/webp,image/avif">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Add View Images (Optional)</label>
                            <input type="file" class="form-control" name="view_images[]" accept="image/jpeg,image/png,image/gif,image/webp,image/avif" multiple>
                        </div>
                    </div>
                    <?php if ($gallery !== []): ?>
                        <div class="col-12">
                            <div class="form-group">
                                <label>Remove Existing View Images</label>
                                <div class="row">
                                    <?php foreach ($gallery as $path): ?>
                                        <div class="col-md-3" style="margin-bottom: 12px;">
                                            <label style="display:block; text-align:center;">
                                                <img src="<?php echo e($path); ?>" alt="Gallery image" style="width:100%; border-radius:10px; background:#f7f9fc; padding:8px;">
                                                <div style="margin-top:8px;">
                                                    <input type="checkbox" name="remove_gallery[]" value="<?php echo e($path); ?>"> Remove
                                                </div>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="col-md-4 d-flex align-items-center">
                        <div class="form-check" style="margin-top: 28px;">
                            <input class="form-check-input" type="checkbox" value="1" name="is_featured" id="is_featured" <?php echo (int) $product['is_featured'] === 1 ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_featured">Featured Product</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="primary-btn">Update Product</button>
                        <a href="./profile.php" class="primary-btn" style="margin-left:10px; background:#101724;">Back to Profile</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>
<?php render_custom_page_footer(); ?>
