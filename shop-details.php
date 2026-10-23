<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/custom-page.php';

$slug = trim((string) ($_GET['product'] ?? ''));
$databaseOnline = $con instanceof mysqli;

if ($slug === '') {
    store_flash('warning', 'Please choose a product first.');
    header('Location: ./shop.php?view=products');
    exit;
}

$product = $databaseOnline ? store_product_by_slug($con, $slug) : store_seed_product_by_slug($slug);

if (!$product) {
    store_flash('danger', 'Product not found.');
    header('Location: ./shop.php?view=products');
    exit;
}

$gallery = store_parse_gallery_images($product['gallery_images'] ?? '');
$reviewSummary = ['count' => 0, 'average' => 0];
$reviews = [];
$allProducts = $databaseOnline ? store_all_products($con) : store_seed_all_products();
$relatedProducts = [];

if ($databaseOnline) {
    $product['view_count'] = store_record_product_view($con, (int) $product['id']);
    $reviewSummaryIndex = store_review_summary_by_product($con, [(int) $product['id']]);
    $reviewSummary = $reviewSummaryIndex[(int) $product['id']] ?? ['count' => 0, 'average' => 0];
    $reviewsByProduct = store_reviews_for_products($con, [(int) $product['id']], 20);
    $reviews = $reviewsByProduct[(int) $product['id']] ?? [];
}

foreach ($allProducts as $item) {
    if ($item['slug'] === $product['slug']) {
        continue;
    }
    if ($item['brand_slug'] === $product['brand_slug'] || $item['category_slug'] === $product['category_slug']) {
        $relatedProducts[] = $item;
    }
    if (count($relatedProducts) >= 4) {
        break;
    }
}

function shop_detail_stars(int $rating): string
{
    $rating = max(0, min(5, $rating));
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        $html .= '<i class="fa ' . ($i <= $rating ? 'fa-star' : 'fa-star-o') . '"></i>';
    }
    return $html;
}

$productSeoDescription = trim((string) ($product['summary'] ?? ''));
if ($productSeoDescription === '') {
    $productSeoDescription = 'Open ' . (string) $product['name'] . ' details, price, reviews, gallery, and related appliances at La Imparial.';
}

render_custom_page_header('La Imparial | ' . $product['name'], [
    'description' => $productSeoDescription,
    'keywords' => implode(', ', array_filter([
        (string) ($product['name'] ?? ''),
        (string) ($product['brand_name'] ?? ''),
        (string) ($product['category_name'] ?? ''),
        'La Imparial',
        'appliance price',
        'product reviews',
    ])),
    'image' => (string) ($product['image_path'] ?? 'img/logo/logo.png'),
    'type' => 'product',
    'canonical' => store_absolute_url('shop-details.php?product=' . urlencode((string) $product['slug'])),
]);
?>
<section class="catalog-admin">
    <div class="container">
        <div class="catalog-page__header">
            <h2><?php echo e($product['name']); ?></h2>
            <p><?php echo e($product['brand_name']); ?> / <?php echo e($product['category_name']); ?> / <?php echo e($product['slug']); ?></p>
        </div>

        <div class="catalog-admin__panel">
            <div class="row align-items-start">
                <div class="col-lg-6">
                    <div style="background:#f8fafc; border:1px solid #e5e7eb; border-radius:24px; padding:24px; text-align:center;">
                        <img src="<?php echo e($product['image_path']); ?>" alt="<?php echo e($product['name']); ?>" style="width:100%; max-height:420px; object-fit:contain;">
                    </div>
                    <?php if ($gallery !== []): ?>
                        <div style="margin-top:18px; margin-bottom:12px;">
                            <strong>Product Views / Gallery</strong>
                        </div>
                        <div class="row" style="margin-top: 18px;">
                            <?php foreach ($gallery as $image): ?>
                                <div class="col-6 col-md-4" style="margin-bottom: 16px;">
                                    <div style="background:#f8fafc; border:1px solid #e5e7eb; border-radius:18px; padding:14px; text-align:center;">
                                        <img src="<?php echo e($image); ?>" alt="<?php echo e($product['name']); ?>" style="width:100%; height:120px; object-fit:contain;">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-lg-6">
                    <div class="catalog-card__meta">
                        <span><?php echo e($product['brand_name']); ?></span>
                        <span><?php echo e($product['category_name']); ?></span>
                        <span><?php echo (int) ($product['view_count'] ?? 0); ?> views</span>
                        <span><span data-review-count><?php echo (int) $reviewSummary['count']; ?></span> reviews</span>
                    </div>
                    <h3 style="margin: 18px 0 10px;"><?php echo e($product['name']); ?></h3>
                    <div style="font-size: 20px; color: #f59e0b; margin-bottom: 14px;">
                        <?php echo shop_detail_stars((int) round((float) $reviewSummary['average'])); ?>
                        <span style="font-size: 14px; color: #475569; margin-left: 8px;"><span data-review-average><?php echo number_format((float) $reviewSummary['average'], 1); ?></span>/5</span>
                    </div>
                    <div class="detail-view-strip">
                        <span><i class="fa fa-eye" aria-hidden="true"></i> <strong data-detail-view-count><?php echo (int) ($product['view_count'] ?? 0); ?></strong> views</span>
                        <span><i class="fa fa-comments-o" aria-hidden="true"></i> <span data-review-count><?php echo (int) $reviewSummary['count']; ?></span> reviews</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:14px; flex-wrap:wrap; margin-bottom:18px;">
                        <div class="catalog-card__price"><?php echo e(store_format_money((float) $product['price'])); ?></div>
                        <?php if ((float) $product['original_price'] > (float) $product['price']): ?>
                            <div style="font-size: 18px; color: #94a3b8; text-decoration: line-through;"><?php echo e(store_format_money((float) $product['original_price'])); ?></div>
                            <span style="padding:6px 12px; border-radius:999px; background:#fee2e2; color:#b91c1c; font-weight:700;"><?php echo (int) $product['discount_percent']; ?>% off</span>
                        <?php endif; ?>
                    </div>
                    <p><?php echo e($product['summary']); ?></p>
                    <p style="margin-top:16px;"><?php echo e($product['description']); ?></p>

                    <div style="display:grid; gap:12px; margin:24px 0;">
                        <div style="padding:14px 16px; border:1px solid #e5e7eb; border-radius:16px; background:#fff;">
                            <strong style="display:block; margin-bottom:6px;">Product Views</strong>
                            <span><span data-detail-view-count><?php echo (int) ($product['view_count'] ?? 0); ?></span> visitors have opened this detail page.</span>
                        </div>
                        <div style="padding:14px 16px; border:1px solid #e5e7eb; border-radius:16px; background:#fff;">
                            <strong style="display:block; margin-bottom:6px;">Document</strong>
                            <a class="quick-action--detail" href="./<?php echo e(ltrim($product['document_path'], './')); ?>">Open Product Document</a>
                        </div>
                        <div style="padding:14px 16px; border:1px solid #e5e7eb; border-radius:16px; background:#fff;">
                            <strong style="display:block; margin-bottom:6px;">Need help with this product?</strong>
                            <a class="quick-action--favorite" href="./contact.php?product=<?php echo urlencode($product['slug']); ?>&subject=<?php echo urlencode('Support for ' . $product['name']); ?>">Contact Admin About This Product</a>
                        </div>
                    </div>

                    <div class="cart-page__actions">
                        <a class="quick-action--cart" href="./store-action.php?action=add_to_cart&product=<?php echo urlencode($product['slug']); ?>&redirect=<?php echo urlencode('./shop-details.php?product=' . $product['slug']); ?>">Add to Cart</a>
                        <a class="quick-action--favorite" href="./store-action.php?action=toggle_favorite&product=<?php echo urlencode($product['slug']); ?>&redirect=<?php echo urlencode('./shop-details.php?product=' . $product['slug']); ?>">Toggle Favorite</a>
                        <a class="quick-action--detail" href="./compare.php">Open Compare</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="catalog-page__header" style="margin-top: 40px;">
            <h3>Customer Reviews</h3>
            <p><?php echo $databaseOnline ? 'Real review records from the database appear here for this product.' : 'Database is offline, so review history and new review submission are temporarily unavailable.'; ?></p>
        </div>

        <div class="row" data-review-panel>
            <div class="col-lg-7">
                <?php if ($reviews === []): ?>
                    <div class="catalog-admin__panel" data-review-list>
                        <p data-review-empty>No reviews yet. Be the first to share your experience.</p>
                    </div>
                <?php else: ?>
                    <div class="catalog-admin__panel" data-review-list>
                        <?php foreach ($reviews as $review): ?>
                            <div class="detail-review-card">
                                <div class="detail-review-card__avatar">
                                    <img src="<?php echo e($review['userAvatar'] ?: 'img/logo/logo.png'); ?>" alt="Reviewer">
                                </div>
                                <div class="detail-review-card__body">
                                    <div class="detail-review-card__meta">
                                        <strong><?php echo e($review['userName'] ?: 'User'); ?></strong>
                                        <span><?php echo e($review['createdAt']); ?></span>
                                    </div>
                                    <div class="detail-review-card__stars">
                                        <?php echo shop_detail_stars((int) $review['rating']); ?>
                                    </div>
                                    <p><?php echo e($review['comment']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-5">
                <div class="catalog-admin__panel">
                    <h4>Add Review</h4>
                    <?php if (!$databaseOnline): ?>
                        <p>Database is offline right now, so review submission is paused until MySQL is available again.</p>
                    <?php elseif (!store_is_logged_in()): ?>
                        <p>Please login to submit a review for this product.</p>
                        <a class="primary-btn" href="./login.php?redirect=<?php echo urlencode('./shop-details.php?product=' . $product['slug']); ?>">Login to Review</a>
                    <?php else: ?>
                        <form action="./review-action.php" method="post" class="form-row-gap">
                            <input type="hidden" name="product" value="<?php echo e($product['slug']); ?>">
                            <input type="hidden" name="redirect" value="<?php echo e('./shop-details.php?product=' . $product['slug']); ?>">
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
                                <label>Review</label>
                                <textarea class="form-control" name="comment" rows="5" placeholder="Share your experience with this product" required></textarea>
                            </div>
                            <button type="submit" class="primary-btn">Submit Review</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($relatedProducts !== []): ?>
            <div class="catalog-page__header" style="margin-top: 40px;">
                <h3>Related Products</h3>
                <p>More products from the same brand or category, with quick compare and save actions.</p>
            </div>
            <div class="row">
                <?php foreach ($relatedProducts as $item): ?>
                    <div class="col-lg-3 col-md-6">
                        <article class="product__item product__item--catalog<?php echo (float) $item['original_price'] > (float) $item['price'] ? ' sale' : ''; ?>" data-product-slug="<?php echo e((string) $item['slug']); ?>">
                            <div class="product__item__pic">
                                <img src="<?php echo e($item['image_path']); ?>" alt="<?php echo e($item['name']); ?>" class="product__item__image">
                                <div class="product__item__badges">
                                    <span class="label"><?php echo e((string) $item['brand_name']); ?></span>
                                    <span class="product__item__deal"><?php echo e((string) ($item['custom_label'] ?: ((float) $item['original_price'] > (float) $item['price'] ? ((int) $item['discount_percent'] . '% Off') : 'Featured'))); ?></span>
                                </div>
                                <div class="product__item__float-actions">
                                    <a href="./shop-details.php?product=<?php echo urlencode((string) $item['slug']); ?>" class="product__item__float-link" title="Open Details" aria-label="Open Details"><i class="fa fa-search"></i><span>Details</span></a>
                                    <a href="./store-action.php?action=toggle_favorite&product=<?php echo urlencode((string) $item['slug']); ?>&redirect=<?php echo urlencode('./shop-details.php?product=' . (string) $item['slug']); ?>" class="product__item__float-link product__item__float-link--favorite" title="Add Favorite" aria-label="Add Favorite"><i class="fa fa-heart-o"></i><span>Favorite</span></a>
                                    <a href="./store-action.php?action=add_to_cart&product=<?php echo urlencode((string) $item['slug']); ?>&redirect=<?php echo urlencode('./shop-details.php?product=' . (string) $item['slug']); ?>" class="product__item__float-link" title="Add to Cart" aria-label="Add to Cart"><i class="fa fa-shopping-cart"></i><span>Add Cart</span></a>
                                    <a href="./compare.php?left=<?php echo urlencode((string) $product['slug']); ?>&right=<?php echo urlencode((string) $item['slug']); ?>" class="product__item__float-link" title="Compare Product" aria-label="Compare Product"><i class="fa fa-exchange"></i><span>Compare</span></a>
                                </div>
                            </div>
                            <div class="product__item__text">
                                <div class="catalog-card__meta">
                                    <span><?php echo e($item['brand_name']); ?></span>
                                    <span><?php echo e($item['category_name']); ?></span>
                                    <span><?php echo (int) ($item['view_count'] ?? 0); ?> views</span>
                                </div>
                                <h6><a href="./shop-details.php?product=<?php echo urlencode((string) $item['slug']); ?>" class="product__item__title-link"><?php echo e($item['name']); ?></a></h6>
                                <p class="product__summary"><?php echo e($item['summary']); ?></p>
                                <div class="product__item__helper-row">
                                    <span class="product__item__helper"><?php echo e((string) $item['brand_name']); ?></span>
                                    <span class="product__item__helper product__item__helper--views"><i class="fa fa-eye" aria-hidden="true"></i> <?php echo (int) ($item['view_count'] ?? 0); ?> views</span>
                                </div>
                                <div class="product__item__price-row">
                                    <h5><?php echo e(store_format_money((float) $item['price'])); ?></h5>
                                    <?php if ((float) $item['original_price'] > (float) $item['price']): ?>
                                        <span class="product__item__old-price"><?php echo e(store_format_money((float) $item['original_price'])); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php render_custom_page_footer(); ?>
