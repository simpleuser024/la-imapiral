<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/custom-page.php';

store_require_login('./product-form.php');

if (!($con instanceof mysqli) || !store_user_has_permission($con, 'create_product')) {
    store_flash('warning', 'Admin permission is required before this account can add products.');
    header('Location: ./profile.php');
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $con instanceof mysqli) {
    $result = store_create_product($con, $_POST, $_FILES);
    store_flash($result['success'] ? 'success' : 'danger', $result['message']);
    header('Location: ./product-form.php');
    exit;
}

$categories = $con instanceof mysqli ? store_categories($con) : [];
$brands = $con instanceof mysqli ? store_brands($con) : [];
$labels = $con instanceof mysqli && function_exists('store_product_labels') ? store_product_labels($con) : [];

render_custom_page_header('La Imparial | Add Product');
?>
<section class="catalog-admin">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <div class="catalog-admin__panel">
                    <div class="catalog-page__header">
                        <h2>Add Product Form</h2>
                        <p>Add a new product, assign it to a brand and category, and store it directly in the database.</p>
                    </div>

                    <form action="./product-form.php" method="post" enctype="multipart/form-data" class="form-row-gap">
                        <div class="catalog-template-panel">
                            <div>
                                <h5>Product Quick Templates</h5>
                                <p>Start with a ready product structure, then adjust details before saving.</p>
                            </div>
                            <div class="catalog-template-actions">
                                <button type="button" class="quick-action--detail" data-product-preset="ac">AC</button>
                                <button type="button" class="quick-action--detail" data-product-preset="refrigerator">Refrigerator</button>
                                <button type="button" class="quick-action--detail" data-product-preset="washer">Washer</button>
                                <button type="button" class="quick-action--detail" data-product-preset="kitchen">Kitchen</button>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Product Name</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Price</label>
                                    <input type="number" step="0.01" class="form-control" name="price" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Old Price</label>
                                    <input type="number" step="0.01" class="form-control" name="original_price">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Document Path</label>
                                    <input type="text" class="form-control" name="document_path" placeholder="docs/haier-frostcool-inverter-ac.doc">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Category</label>
                                    <select class="form-control" name="category_id">
                                        <option value="">Select category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo (int) $category['id']; ?>"><?php echo e($category['name']); ?></option>
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
                                            <option value="<?php echo (int) $brand['id']; ?>"><?php echo e($brand['name']); ?></option>
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
                                    <small class="product__item__helper">Agar naya brand add kar rahe ho to us ka logo bhi upload karo.</small>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label>New Brand Hero Title</label>
                                    <input type="text" class="form-control" name="new_brand_hero_title" placeholder="Gree premium cooling collection">
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label>New Brand Hero Summary</label>
                                    <textarea class="form-control" name="new_brand_hero_summary" rows="3" placeholder="Brand page hero text, splash feeling, aur home slider copy."></textarea>
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
                                    <input type="text" class="form-control" name="new_brand_badges" placeholder="Smart Cooling, Premium Range, Fast Delivery">
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label>Short Summary</label>
                                    <textarea class="form-control" name="summary" rows="3" required></textarea>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label>Full Description</label>
                                    <textarea class="form-control" name="description" rows="5" required></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Product Label (Optional)</label>
                                    <input type="text" class="form-control" name="custom_label" placeholder="Hot Product / Top Rated / Member Pick">
                                </div>
                            </div>
                            <?php if ($labels !== []): ?>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>Assign Dashboard Labels</label>
                                        <div class="admin-permission-grid">
                                            <?php foreach ($labels as $label): ?>
                                                <label class="admin-permission-pill" style="margin-bottom:8px;">
                                                    <input type="checkbox" name="label_ids[]" value="<?php echo (int) $label['id']; ?>">
                                                    <span><?php echo e((string) $label['name']); ?><?php echo !empty($label['color_hex']) ? ' (' . e((string) $label['color_hex']) . ')' : ''; ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label>Product Image</label>
                                    <input type="file" class="form-control" name="image" accept="image/jpeg,image/png,image/gif,image/webp,image/avif" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>View Images (Optional)</label>
                                    <input type="file" class="form-control" name="view_images[]" accept="image/jpeg,image/png,image/gif,image/webp,image/avif" multiple>
                                </div>
                            </div>
                            <div class="col-md-4 d-flex align-items-center">
                                <div class="form-check" style="margin-top: 28px;">
                                    <input class="form-check-input" type="checkbox" value="1" name="is_featured" id="is_featured">
                                    <label class="form-check-label" for="is_featured">Featured Product</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="primary-btn">Save Product</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="catalog-admin__helper">
                    <h4>What This Form Does</h4>
                    <ul>
                        <li>Saves new products in the MySQL database.</li>
                        <li>Adds products into the site’s category and brand flow.</li>
                        <li>Uploads the new product image into this project.</li>
                        <li>Makes the product available on shop, detail, compare, favorite, and cart pages.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var presets = {
        ac: {
            summary: 'Fast inverter cooling, quiet airflow, and energy-aware comfort for daily room use.',
            description: 'This air conditioner is built for reliable cooling, controlled airflow, and practical home comfort. Add exact tonnage, warranty, energy rating, and installation notes before publishing.',
            custom_label: 'Cooling Pick'
        },
        refrigerator: {
            summary: 'Balanced cooling, family storage, and organized shelves for daily grocery freshness.',
            description: 'This refrigerator focuses on stable temperature control, usable shelf space, and practical storage for family routines. Add capacity, energy details, warranty, and special cooling features before publishing.',
            custom_label: 'Fresh Storage'
        },
        washer: {
            summary: 'Family laundry support with practical wash cycles and easy everyday controls.',
            description: 'This washing product is prepared for daily fabric care, balanced capacity, and straightforward operation. Add drum size, wash programs, warranty, and motor details before publishing.',
            custom_label: 'Laundry Care'
        },
        kitchen: {
            summary: 'Compact kitchen support for fast heating, cooking, reheating, and family meal prep.',
            description: 'This kitchen appliance is ready for everyday cooking workflows with easy controls and practical placement. Add capacity, presets, warranty, and safety notes before publishing.',
            custom_label: 'Kitchen Pick'
        }
    };

    function setField(name, value) {
        var field = document.querySelector('[name="' + name + '"]');
        if (field && !field.value) {
            field.value = value;
        }
    }

    Array.prototype.forEach.call(document.querySelectorAll('[data-product-preset]'), function (button) {
        button.addEventListener('click', function () {
            var preset = presets[button.getAttribute('data-product-preset')] || {};
            Object.keys(preset).forEach(function (name) {
                setField(name, preset[name]);
            });
        });
    });
});
</script>
<?php render_custom_page_footer(); ?>
