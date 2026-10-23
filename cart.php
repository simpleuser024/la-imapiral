<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/custom-page.php';

store_require_login('./cart.php');

$items = $con instanceof mysqli
    ? store_cart_items($con)
    : store_seed_cart_items($_SESSION['cart'] ?? []);
$subtotal = $con instanceof mysqli
    ? store_cart_subtotal($con)
    : store_seed_cart_subtotal($_SESSION['cart'] ?? []);

render_custom_page_header('La Imparial | Cart');
?>
<section class="catalog-admin">
    <div class="container">
        <div class="catalog-page__header">
            <h2>Shopping Cart</h2>
            <p>Review your selected products, update quantity, or remove them from the cart.</p>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="catalog-admin__panel" data-empty-cart-state <?php echo $items !== [] ? 'hidden' : ''; ?>>
                    <h4>Your cart is empty</h4>
                    <p>Add any product from the store and it will appear here.</p>
                    <a href="./shop.php?view=products" class="primary-btn">Browse Products</a>
                </div>
                <?php if ($items !== []): ?>
                    <?php foreach ($items as $item): ?>
                        <article class="catalog-card" data-product-slug="<?php echo e((string) $item['slug']); ?>">
                            <img src="<?php echo e($item['image_path']); ?>" alt="<?php echo e($item['name']); ?>">
                            <div>
                                <div class="catalog-card__meta">
                                    <span><?php echo e($item['brand_name']); ?></span>
                                    <span><?php echo e($item['category_name']); ?></span>
                                    <span>Qty: <strong data-cart-item-qty><?php echo (int) $item['quantity']; ?></strong></span>
                                </div>
                                <h4><a href="./shop-details.php?product=<?php echo urlencode($item['slug']); ?>"><?php echo e($item['name']); ?></a></h4>
                                <p><?php echo e($item['summary']); ?></p>
                                <div class="catalog-card__price" data-cart-line-total>PKR <?php echo number_format((float) $item['line_total']); ?></div>
                            </div>
                            <div class="cart-page__actions">
                                <a class="quick-action--cart" href="./store-action.php?action=add_to_cart&product=<?php echo urlencode($item['slug']); ?>&redirect=<?php echo urlencode('./cart.php'); ?>">+</a>
                                <a class="quick-action--detail" href="./store-action.php?action=decrease_cart&product=<?php echo urlencode($item['slug']); ?>&redirect=<?php echo urlencode('./cart.php'); ?>">-</a>
                                <a class="quick-action--favorite" href="./store-action.php?action=remove_from_cart&product=<?php echo urlencode($item['slug']); ?>&redirect=<?php echo urlencode('./cart.php'); ?>">Remove</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="col-lg-4">
                <div class="catalog-summary">
                    <h4>Cart Summary</h4>
                    <ul>
                        <li><span>Total Items</span><strong data-cart-total-items><?php echo store_cart_count(); ?></strong></li>
                        <li><span>Subtotal</span><strong data-cart-subtotal>PKR <?php echo number_format($subtotal); ?></strong></li>
                        <li><span>Favorites</span><strong><?php echo count($_SESSION['favorites'] ?? []); ?></strong></li>
                    </ul>
                    <a href="./shop.php?view=products" class="primary-btn" style="margin-top: 18px;">Continue Shopping</a>
                </div>
            </div>
        </div>
    </div>
</section>
<?php render_custom_page_footer(); ?>
