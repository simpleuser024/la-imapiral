<?php
declare(strict_types=1);

require_once __DIR__ . '/shared.php';

admin_require_panel_permission('view_reports', 'You do not have permission to view user reports.');

$userId = (int) ($_GET['user_id'] ?? 0);
$report = $userId > 0 ? store_user_report($con, $userId) : [];
$user = $report['user'] ?? null;

if (!$user) {
    store_flash('danger', 'User report could not be loaded.');
    header('Location: ./users.php');
    exit;
}

admin_page_start(
    'La Imparial | User Report',
    'users.php',
    'User Report',
    'Detailed activity snapshot for one selected account.',
    'This report combines favorites, cart items, inquiries, product ownership, reviews, and activity logs so admin can inspect one user without jumping between pages.'
);
?>

<section class="admin-summary-grid">
    <article class="admin-summary-card">
        <div class="admin-summary-card__eyebrow"><i class="fa fa-user-o"></i> Account</div>
        <h3><?php echo e((string) $user['name']); ?></h3>
        <p><?php echo e((string) $user['email']); ?> • <?php echo e((string) ucfirst((string) $user['role'])); ?></p>
    </article>
    <article class="admin-summary-card">
        <div class="admin-summary-card__eyebrow"><i class="fa fa-cubes"></i> Products</div>
        <h3><?php echo count($report['products'] ?? []); ?></h3>
        <p>Catalog items owned by this user.</p>
    </article>
    <article class="admin-summary-card">
        <div class="admin-summary-card__eyebrow"><i class="fa fa-heart-o"></i> Favorites</div>
        <h3><?php echo count($report['favorites'] ?? []); ?></h3>
        <p>Saved products connected to this account.</p>
    </article>
    <article class="admin-summary-card">
        <div class="admin-summary-card__eyebrow"><i class="fa fa-shopping-cart"></i> Cart</div>
        <h3><?php echo count($report['cart'] ?? []); ?></h3>
        <p>Current cart rows stored for this user.</p>
    </article>
</section>

<section class="admin-content-grid" style="margin-top:18px;">
    <article class="admin-panel">
        <div class="admin-panel__head">
            <div>
                <h3>Owned Products</h3>
                <p>Products created by this user account.</p>
            </div>
        </div>
        <div class="admin-list">
            <?php if (($report['products'] ?? []) === []): ?>
                <div class="admin-list__item"><strong>No owned products.</strong><p>This user has not added any product yet.</p></div>
            <?php else: ?>
                <?php foreach ($report['products'] as $product): ?>
                    <div class="admin-list__item">
                        <strong><?php echo e((string) $product['name']); ?></strong>
                        <p><?php echo e((string) $product['summary']); ?></p>
                        <div class="admin-list__meta">
                            <span><?php echo e((string) $product['brand_name']); ?></span>
                            <span><?php echo e((string) $product['category_name']); ?></span>
                            <span><?php echo e(store_format_money((float) $product['price'])); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </article>

    <div class="admin-stack">
        <article class="admin-panel">
            <div class="admin-panel__head">
                <div>
                    <h3>Favorites</h3>
                    <p>Favorite history tied to this user account.</p>
                </div>
            </div>
            <div class="admin-list">
                <?php if (($report['favorites'] ?? []) === []): ?>
                    <div class="admin-list__item"><strong>No favorites.</strong><p>This user has not saved any product yet.</p></div>
                <?php else: ?>
                    <?php foreach (array_slice($report['favorites'], 0, 8) as $favorite): ?>
                        <div class="admin-list__item">
                            <strong><?php echo e((string) $favorite['name']); ?></strong>
                            <div class="admin-list__meta">
                                <span><?php echo e((string) $favorite['slug']); ?></span>
                                <span><?php echo e(store_format_money((float) $favorite['price'])); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </article>

        <article class="admin-panel">
            <div class="admin-panel__head">
                <div>
                    <h3>Cart + Reviews</h3>
                    <p>Live purchase intent and product feedback from this user.</p>
                </div>
            </div>
            <div class="admin-list">
                <?php foreach (array_slice($report['cart'] ?? [], 0, 4) as $cartItem): ?>
                    <div class="admin-list__item">
                        <strong><?php echo e((string) $cartItem['name']); ?></strong>
                        <div class="admin-list__meta">
                            <span>Qty <?php echo (int) $cartItem['quantity']; ?></span>
                            <span><?php echo e(store_format_money((float) $cartItem['price'])); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php foreach (array_slice($report['reviews'] ?? [], 0, 4) as $review): ?>
                    <div class="admin-list__item">
                        <strong><?php echo e((string) $review['product_name']); ?></strong>
                        <p><?php echo e(store_notification_limit_text((string) $review['comment'], 110)); ?></p>
                        <div class="admin-list__meta">
                            <span><?php echo (int) $review['rating']; ?>/5</span>
                            <span><?php echo e((string) $review['created_at']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>
    </div>
</section>

<section class="admin-content-grid" style="margin-top:18px;">
    <article class="admin-panel">
        <div class="admin-panel__head">
            <div>
                <h3>Support History</h3>
                <p>Inquiries and their current admin handling status.</p>
            </div>
        </div>
        <div class="admin-list">
            <?php if (($report['inquiries'] ?? []) === []): ?>
                <div class="admin-list__item"><strong>No inquiries.</strong><p>This user has not sent any support request yet.</p></div>
            <?php else: ?>
                <?php foreach ($report['inquiries'] as $inquiry): ?>
                    <div class="admin-list__item">
                        <strong><?php echo e((string) $inquiry['subject_line']); ?></strong>
                        <p><?php echo e(store_notification_limit_text((string) $inquiry['message_body'], 130)); ?></p>
                        <div class="admin-list__meta">
                            <span><?php echo e((string) str_replace('_', ' ', (string) $inquiry['inquiry_status'])); ?></span>
                            <span><?php echo e((string) $inquiry['created_at']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </article>

    <article class="admin-panel">
        <div class="admin-panel__head">
            <div>
                <h3>Activity Log</h3>
                <p>Tracked actions performed by this user across the website.</p>
            </div>
        </div>
        <div class="admin-list">
            <?php if (($report['activity'] ?? []) === []): ?>
                <div class="admin-list__item"><strong>No tracked actions.</strong><p>Once this user interacts with the site, logs will show up here.</p></div>
            <?php else: ?>
                <?php foreach ($report['activity'] as $activity): ?>
                    <div class="admin-list__item">
                        <strong><?php echo e((string) $activity['subject_line']); ?></strong>
                        <div class="admin-list__meta">
                            <span><?php echo e((string) $activity['action_key']); ?></span>
                            <span><?php echo e((string) $activity['delivery_status']); ?></span>
                            <span><?php echo e((string) $activity['created_at']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </article>
</section>

<?php
admin_page_end();
