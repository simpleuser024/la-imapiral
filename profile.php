<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/custom-page.php';

store_require_login('./profile.php');

$user = $con instanceof mysqli ? store_current_user($con) : null;

if (!$user) {
    store_flash('danger', 'User profile could not be loaded.');
    header('Location: ./index.php');
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $con instanceof mysqli) {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'update_profile') {
        $result = store_update_profile($con, (int) $user['id'], $_POST, $_FILES);
        store_flash($result['success'] ? 'success' : 'danger', $result['message']);
        header('Location: ./profile.php');
        exit;
    }

    if ($action === 'update_password') {
        $result = store_update_password($con, (int) $user['id'], $_POST);
        store_flash($result['success'] ? 'success' : 'danger', $result['message']);
        header('Location: ./profile.php');
        exit;
    }
}

$user = $con instanceof mysqli ? store_current_user($con) : $user;
$userProducts = $con instanceof mysqli ? store_products_by_user($con, (int) $user['id']) : [];
$favoriteItems = $con instanceof mysqli ? store_favorite_items($con) : [];
$cartItems = $con instanceof mysqli ? store_cart_items($con) : [];
$reviewProductIds = array_map('intval', array_column($userProducts, 'id'));
$reviewSummaryById = $con instanceof mysqli ? store_review_summary_by_product($con, $reviewProductIds) : [];
$reviewListById = $con instanceof mysqli ? store_reviews_for_products($con, $reviewProductIds, 3) : [];

function render_review_stars(int $rating): string
{
    $rating = max(0, min(5, $rating));
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        $class = $i <= $rating ? 'fa-star' : 'fa-star-o';
        $stars .= '<i class="fa ' . $class . '" aria-hidden="true"></i>';
    }
    return $stars;
}

render_custom_page_header('La Imparial | Profile');
?>
<section class="catalog-admin">
    <div class="container">
        <div class="catalog-page__header">
            <h2>My Profile</h2>
            <p>Manage your account, update your photo, and review the products you have added.</p>
        </div>

        <div class="catalog-admin__panel">
            <div class="profile-card">
                <div class="profile-avatar">
                    <img src="<?php echo e($user['profile_image'] ?: 'img/logo/logo.png'); ?>" alt="Profile" style="object-fit: <?php echo e((string) ($user['avatar_fit'] ?? 'cover')); ?>;">
                </div>
                <div class="profile-meta">
                    <h3><?php echo e($user['name']); ?></h3>
                    <p><?php echo e($user['email']); ?></p>
                    <span><?php echo e($user['role']); ?></span>
                    <?php if (store_is_admin()): ?>
                        <div style="margin-top: 12px;">
                            <a class="quick-action--detail" href="./admin.php">Open Admin Panel</a>
                        </div>
                    <?php endif; ?>
                    <div style="margin-top: 12px;">
                        <a class="quick-action--favorite" href="./logout.php">Logout</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="catalog-admin__panel" style="margin-top: 20px;">
            <h4>Account Portals</h4>
            <div class="cart-page__actions">
                <a class="quick-action--cart" href="./user/">Open User Dashboard</a>
                <?php if (store_is_admin()): ?>
                    <a class="quick-action--detail" href="./admin/">Open Pro Admin Dashboard</a>
                <?php endif; ?>
                <?php if ($con instanceof mysqli && store_user_has_permission($con, 'manage_settings')): ?>
                    <a class="quick-action--detail" href="./website-editor.php">Website Editor</a>
                <?php endif; ?>
                <a class="quick-action--favorite" href="./forgot-password.php">Password Recovery</a>
                <a class="quick-action--detail" href="./contact.php?subject=<?php echo urlencode('Profile verification support'); ?>">Contact Admin</a>
            </div>
        </div>

        <div class="row" style="margin-top: 30px;">
            <div class="col-lg-6">
                <div class="catalog-admin__panel">
                    <h4>Update Profile</h4>
                    <form action="./profile.php" method="post" enctype="multipart/form-data" class="form-row-gap">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" class="form-control" name="name" value="<?php echo e($user['name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" class="form-control" name="email" value="<?php echo e($user['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Profile Picture (Optional)</label>
                            <input type="file" class="form-control" name="profile_image" accept="image/jpeg,image/png,image/gif,image/webp,image/avif">
                        </div>
                        <div class="form-group">
                            <label>Profile Image Fit</label>
                            <select class="form-control" name="avatar_fit">
                                <option value="cover" <?php echo (string) ($user['avatar_fit'] ?? 'cover') === 'cover' ? 'selected' : ''; ?>>Cover / Crop Fill</option>
                                <option value="contain" <?php echo (string) ($user['avatar_fit'] ?? 'cover') === 'contain' ? 'selected' : ''; ?>>Contain / Full Fit</option>
                            </select>
                        </div>
                        <button type="submit" class="primary-btn">Save Profile</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="catalog-admin__panel">
                    <h4>Update Password</h4>
                    <form action="./profile.php" method="post" class="form-row-gap">
                        <input type="hidden" name="action" value="update_password">
                        <div class="form-group">
                            <label>Current Password</label>
                            <input type="password" class="form-control" name="current_password">
                        </div>
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" class="form-control" name="new_password" required>
                        </div>
                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <input type="password" class="form-control" name="confirm_password" required>
                        </div>
                        <button type="submit" class="primary-btn">Update Password</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="catalog-page__header" style="margin-top: 40px;">
            <h3>My Products</h3>
            <p>Your uploaded products appear here. Edit details or add new products anytime.</p>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <?php if ($userProducts === []): ?>
                    <div class="catalog-admin__panel">
                        <p>No products yet. Use the add product form to upload your first item.</p>
                        <a class="primary-btn" href="./product-form.php">Add Product</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($userProducts as $item): ?>
                        <article class="catalog-card">
                            <img src="<?php echo e($item['image_path']); ?>" alt="<?php echo e($item['name']); ?>">
                            <div>
                                <div class="catalog-card__meta">
                                    <span><?php echo e($item['brand_name']); ?></span>
                                    <span><?php echo e($item['category_name']); ?></span>
                                </div>
                                <h4><?php echo e($item['name']); ?></h4>
                                <p><?php echo e($item['summary']); ?></p>
                                <div class="catalog-card__price">PKR <?php echo number_format((float) $item['price']); ?></div>
                            </div>
                            <div class="favorite-page__actions">
                                <a class="quick-action--detail" href="./edit-product.php?product=<?php echo urlencode($item['slug']); ?>">Edit</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="catalog-page__header" style="margin-top: 40px;">
            <h3>Reviews on Your Products</h3>
            <p>See what customers are saying about the products you added.</p>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <?php
                $hasReviews = false;
                foreach ($userProducts as $product) {
                    if (!empty($reviewListById[$product['id']] ?? [])) {
                        $hasReviews = true;
                        break;
                    }
                }
                ?>
                <?php if (!$hasReviews): ?>
                    <div class="catalog-admin__panel">
                        <p>No reviews yet. Share your products to start getting feedback.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($userProducts as $product): ?>
                        <?php $reviews = $reviewListById[$product['id']] ?? []; ?>
                        <?php if ($reviews === []): ?>
                            <?php continue; ?>
                        <?php endif; ?>
                        <?php $summary = $reviewSummaryById[$product['id']] ?? ['count' => 0, 'average' => 0]; ?>
                        <div class="catalog-admin__panel" style="margin-bottom: 20px;">
                            <h4><?php echo e($product['name']); ?></h4>
                            <div class="catalog-card__meta" style="margin-top: 12px;">
                                <span>Average <?php echo number_format((float) $summary['average'], 1); ?></span>
                                <span><?php echo (int) $summary['count']; ?> reviews</span>
                            </div>
                            <div style="margin-top: 14px;">
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
                                                <?php echo render_review_stars((int) $review['rating']); ?>
                                            </div>
                                            <p><?php echo e($review['comment']); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="catalog-page__header" style="margin-top: 40px;">
            <h3>My Favorites</h3>
            <p>Quick access to the products you saved.</p>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <?php if ($favoriteItems === []): ?>
                    <div class="catalog-admin__panel">
                        <p>No favorites yet. Browse products and tap the favorite button to save them.</p>
                        <a class="primary-btn" href="./shop.php?view=products">Browse Products</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($favoriteItems as $item): ?>
                        <article class="favorite-card">
                            <img src="<?php echo e($item['image_path']); ?>" alt="<?php echo e($item['name']); ?>">
                            <div>
                                <div class="favorite-card__meta">
                                    <span><?php echo e($item['brand_name']); ?></span>
                                    <span><?php echo e($item['category_name']); ?></span>
                                </div>
                                <h4><?php echo e($item['name']); ?></h4>
                                <p><?php echo e($item['summary']); ?></p>
                                <div class="favorite-card__price">PKR <?php echo number_format((float) $item['price']); ?></div>
                            </div>
                            <div class="favorite-page__actions">
                                <a class="quick-action--detail" href="./shop-details.php?product=<?php echo urlencode($item['slug']); ?>">View</a>
                                <a class="quick-action--favorite" href="./store-action.php?action=toggle_favorite&product=<?php echo urlencode($item['slug']); ?>&redirect=<?php echo urlencode('./profile.php'); ?>">Remove</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="catalog-page__header" style="margin-top: 40px;">
            <h3>My Cart</h3>
            <p>Everything you recently added to the cart.</p>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <?php if ($cartItems === []): ?>
                    <div class="catalog-admin__panel">
                        <p>Your cart is empty. Add products to see them here.</p>
                        <a class="primary-btn" href="./shop.php?view=products">Browse Products</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($cartItems as $item): ?>
                        <article class="catalog-card">
                            <img src="<?php echo e($item['image_path']); ?>" alt="<?php echo e($item['name']); ?>">
                            <div>
                                <div class="catalog-card__meta">
                                    <span><?php echo e($item['brand_name']); ?></span>
                                    <span><?php echo e($item['category_name']); ?></span>
                                </div>
                                <h4><?php echo e($item['name']); ?></h4>
                                <p>Quantity: <?php echo (int) $item['quantity']; ?></p>
                                <div class="catalog-card__price">PKR <?php echo number_format((float) $item['line_total']); ?></div>
                            </div>
                            <div class="cart-page__actions">
                                <a class="quick-action--detail" href="./shop-details.php?product=<?php echo urlencode($item['slug']); ?>">View</a>
                                <a class="quick-action--cart" href="./cart.php">Open Cart</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php render_custom_page_footer(); ?>
