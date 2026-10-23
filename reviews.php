<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/custom-page.php';

$products = $con instanceof mysqli ? store_all_products($con) : [];

if ($products === []) {
    store_flash('warning', 'No products are available for review yet.');
    header('Location: ./shop.php?view=products');
    exit;
}

$selectedSlug = trim((string) ($_GET['product'] ?? ''));
if ($selectedSlug === '') {
    $selectedSlug = (string) ($products[0]['slug'] ?? '');
}

render_custom_page_header('La Imparial | Review Hub');
?>
<section class="catalog-admin" data-review-selector-page>
    <div class="container">
        <div class="catalog-page__header">
            <h2>Product Review Hub</h2>
            <p>Select a product first, check existing customer feedback, then add your own review from one focused page.</p>
        </div>

        <div class="row" data-review-panel>
            <div class="col-lg-5">
                <div class="catalog-admin__panel">
                    <h4>Select Product</h4>
                    <div class="form-group">
                        <label>Choose a product</label>
                        <select class="form-control" data-review-product-select>
                            <?php foreach ($products as $product): ?>
                                <option value="<?php echo e((string) $product['slug']); ?>" <?php echo $selectedSlug === (string) $product['slug'] ? 'selected' : ''; ?>>
                                    <?php echo e((string) $product['name']); ?> - <?php echo e((string) $product['brand_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="catalog-admin__panel review-preview-shell" style="padding:20px; margin-top:20px;">
                        <div data-review-product-preview></div>
                        <div class="catalog-card__meta" style="margin-top:16px;">
                            <span><strong data-review-count>0</strong> reviews</span>
                            <span><strong data-review-average>0.0</strong>/5 average</span>
                        </div>
                        <div class="cart-page__actions" style="margin-top:18px;">
                            <a class="quick-action--detail" href="./shop-details.php?product=<?php echo urlencode($selectedSlug); ?>" data-review-product-detail>Open Detail Page</a>
                            <a class="quick-action--favorite" href="./compare.php">Open Compare</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="catalog-admin__panel">
                    <h4>Customer Reviews</h4>
                    <div data-review-list>
                        <div class="admin-list__item" data-review-empty>
                            <strong>Loading reviews...</strong>
                            <p>Pick a product to see its feedback and submit a new review.</p>
                        </div>
                    </div>
                </div>

                <div class="catalog-admin__panel" style="margin-top:20px;">
                    <h4>Add Review</h4>
                    <?php if (!store_is_logged_in()): ?>
                        <p>Please login first to submit a review from the hub.</p>
                        <a class="primary-btn" href="./login.php?redirect=<?php echo urlencode('./reviews.php?product=' . $selectedSlug); ?>">Login to Review</a>
                    <?php else: ?>
                        <form action="./review-action.php" method="post" class="form-row-gap">
                            <input type="hidden" name="product" value="<?php echo e($selectedSlug); ?>">
                            <input type="hidden" name="redirect" value="<?php echo e('./reviews.php?product=' . $selectedSlug); ?>">
                            <div class="form-group">
                                <label>Rating</label>
                                <select class="form-control" name="rating" required>
                                    <option value="">Choose rating</option>
                                    <option value="5">5 - Excellent</option>
                                    <option value="4">4 - Very Good</option>
                                    <option value="3">3 - Good</option>
                                    <option value="2">2 - Average</option>
                                    <option value="1">1 - Poor</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Your Review</label>
                                <textarea class="form-control" name="comment" rows="5" placeholder="Apna honest review yahan likhen." required></textarea>
                            </div>
                            <button type="submit" class="primary-btn">Submit Review</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php render_custom_page_footer(); ?>
