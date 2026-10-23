<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/custom-page.php';

store_require_login('./favorites.php');

$items = $con instanceof mysqli
    ? store_favorite_items($con)
    : store_seed_products_by_slugs($_SESSION['favorites'] ?? []);

render_custom_page_header('La Imparial | Favorites');
?>
<section class="catalog-admin">
    <div class="container">
        <div class="catalog-page__header">
            <h2>Favorite Products</h2>
            <p>When you favorite a product, the site also attempts to send an email notification to your configured Gmail address.</p>
        </div>

        <?php if ($items !== []): ?>
            <div class="catalog-admin__panel" data-empty-favorite-state hidden>
                <h4>No favorite products yet</h4>
                <p>Use the favorite action on any product card or product detail page and it will appear here.</p>
                <a href="./shop.php?view=products" class="primary-btn">Browse Products</a>
            </div>
            <?php foreach ($items as $item): ?>
                <article class="favorite-card" data-product-slug="<?php echo e((string) $item['slug']); ?>" data-favorite-card>
                    <img src="<?php echo e($item['image_path']); ?>" alt="<?php echo e($item['name']); ?>">
                    <div>
                        <div class="favorite-card__meta">
                            <span><?php echo e($item['brand_name']); ?></span>
                            <span><?php echo e($item['category_name']); ?></span>
                            <?php if ((int) $item['discount_percent'] > 0): ?>
                                <span><?php echo (int) $item['discount_percent']; ?>% Off</span>
                            <?php endif; ?>
                        </div>
                        <h4><a href="./shop-details.php?product=<?php echo urlencode($item['slug']); ?>"><?php echo e($item['name']); ?></a></h4>
                        <p><?php echo e($item['summary']); ?></p>
                        <div class="favorite-card__price">PKR <?php echo number_format((float) $item['price']); ?></div>
                    </div>
                    <div class="favorite-page__actions">
                        <a class="quick-action--cart" href="./store-action.php?action=add_to_cart&product=<?php echo urlencode($item['slug']); ?>&redirect=<?php echo urlencode('./favorites.php'); ?>">Add To Cart</a>
                        <a class="quick-action--favorite" href="./store-action.php?action=toggle_favorite&product=<?php echo urlencode($item['slug']); ?>&redirect=<?php echo urlencode('./favorites.php'); ?>">Remove Favorite</a>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="catalog-admin__panel" data-empty-favorite-state>
                <h4>No favorite products yet</h4>
                <p>Use the favorite action on any product card or product detail page and it will appear here.</p>
                <a href="./shop.php?view=products" class="primary-btn">Browse Products</a>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php render_custom_page_footer(); ?>
