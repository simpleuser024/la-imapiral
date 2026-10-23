<?php
declare(strict_types=1);

require_once __DIR__ . '/shared.php';

admin_require_panel_permission('moderate_reviews', 'You do not have permission to moderate reviews.');

$csrfKey = 'admin_reviews_panel';
$csrfToken = store_csrf_token($csrfKey);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $token = (string) ($_POST['csrf_token'] ?? '');
    if (!store_verify_csrf_token($csrfKey, $token)) {
        store_flash('danger', 'Security verification failed. Please try again.');
        header('Location: ./reviews.php');
        exit;
    }

    if ((string) ($_POST['action'] ?? '') === 'delete_review') {
        $result = store_delete_review($con, (int) ($_POST['review_id'] ?? 0));
        store_flash($result['success'] ? 'success' : 'danger', $result['message']);
        header('Location: ./reviews.php');
        exit;
    }
}

$selectedProductSlug = trim((string) ($_GET['product'] ?? ''));
$allProducts = store_all_products($con);
$productMap = [];
foreach ($allProducts as $product) {
    $productMap[(string) $product['slug']] = $product;
}

$reviews = store_recent_reviews($con, 80);
if ($selectedProductSlug !== '') {
    $reviews = array_values(array_filter($reviews, static fn ($review) => (string) ($review['product_slug'] ?? '') === $selectedProductSlug));
}

admin_page_start(
    'La Imparial | Admin Reviews',
    'reviews.php',
    'Reviews',
    'Moderate review records and jump into the no-reload review hub.',
    'Customers can now leave reviews from product details or from a dedicated review page. This tab keeps moderation clear, searchable, and separate from user/account management.'
);
?>

<section class="admin-content-grid">
    <div class="admin-stack">
        <article class="admin-panel">
            <div class="admin-panel__head">
                <div>
                    <h3>Review Hub Shortcut</h3>
                    <p>The public review page lets users select a product first, then submit feedback without a full page reload.</p>
                </div>
            </div>
            <div class="admin-action-row">
                <a class="admin-button admin-button--primary" href="../reviews.php">Open Public Review Page</a>
                <a class="admin-button" href="../shop.php?view=products">Browse Products</a>
            </div>
        </article>

        <article class="admin-panel">
            <div class="admin-panel__head">
                <div>
                    <h3>Filter By Product</h3>
                    <p>Focus on one product when you need to moderate or inspect comments faster.</p>
                </div>
            </div>
            <form method="get" action="./reviews.php" class="admin-form-stack">
                <label>
                    <span>Select Product</span>
                    <select class="admin-input" name="product" onchange="this.form.submit()">
                        <option value="">All reviewed products</option>
                        <?php foreach ($allProducts as $product): ?>
                            <option value="<?php echo e((string) $product['slug']); ?>" <?php echo $selectedProductSlug === (string) $product['slug'] ? 'selected' : ''; ?>>
                                <?php echo e((string) $product['name']); ?> - <?php echo e((string) $product['brand_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </form>
        </article>
    </div>

    <article class="admin-panel">
        <div class="admin-panel__head">
            <div>
                <h3>Review Stream</h3>
                <p><?php echo $selectedProductSlug !== '' && isset($productMap[$selectedProductSlug]) ? 'Showing reviews for ' . e((string) $productMap[$selectedProductSlug]['name']) . '.' : 'Showing all recent review records.'; ?></p>
            </div>
        </div>
        <div class="admin-list">
            <?php if ($reviews === []): ?>
                <div class="admin-list__item">
                    <strong>No reviews found.</strong>
                    <p>Once users submit product reviews, moderation controls will appear here.</p>
                </div>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="admin-list__item">
                        <div class="admin-list__row">
                            <div class="admin-product-line">
                                <img src="../<?php echo e(ltrim((string) ($review['product_image'] ?? 'img/logo/logo.png'), './')); ?>" alt="<?php echo e((string) $review['product_name']); ?>">
                                <div>
                                    <strong><?php echo e((string) $review['product_name']); ?></strong>
                                    <p><?php echo e(store_notification_limit_text((string) $review['comment'], 160)); ?></p>
                                </div>
                            </div>
                            <div class="admin-badge admin-badge--new"><?php echo (int) $review['rating']; ?>/5</div>
                        </div>
                        <div class="admin-list__meta">
                            <span><?php echo e((string) ($review['user_name'] ?? 'User')); ?></span>
                            <span><?php echo e((string) ($review['user_email'] ?? '')); ?></span>
                            <span><?php echo e((string) $review['created_at']); ?></span>
                        </div>
                        <div class="admin-action-row" style="margin-top:12px;">
                            <a class="admin-button" href="../shop-details.php?product=<?php echo urlencode((string) $review['product_slug']); ?>">Open Product</a>
                            <form method="post" action="./reviews.php">
                                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                <input type="hidden" name="action" value="delete_review">
                                <input type="hidden" name="review_id" value="<?php echo (int) $review['id']; ?>">
                                <button class="admin-button admin-button--danger" type="submit" onclick="return confirm('Delete this review?');">Delete Review</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </article>
</section>

<?php
admin_page_end();
