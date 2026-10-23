<?php
declare(strict_types=1);

require_once __DIR__ . '/shared.php';

$overview = [
    'products' => admin_count_rows($con, 'products'),
    'users' => admin_count_rows($con, 'users'),
    'reviews' => admin_count_rows($con, 'reviews'),
    'inquiries' => admin_count_rows($con, 'contact_inquiries'),
    'notifications' => admin_count_rows($con, 'activity_notifications'),
];

$recentInquiries = store_contact_inquiries($con, 6);
$recentReviews = store_recent_reviews($con, 6);
$recentActivity = store_activity_feed($con, 8);
$messages = store_messages_admin_feed($con, 6);
$roles = store_roles($con);
$adminCount = count(array_filter(store_users($con), static fn ($user) => (string) ($user['role'] ?? '') === 'admin'));
$managerCount = count(array_filter(store_users($con), static fn ($user) => (string) ($user['role'] ?? '') === 'manager'));
$pipelineOpen = admin_count_rows($con, 'contact_inquiries', "inquiry_status IN ('new', 'in_progress')");

admin_page_start(
    'La Imparial | Admin Overview',
    'index.php',
    'Overview',
    'Operations, users, reviews, and activity in one cleaner dashboard.',
    'Website shortcuts, admin-only navigation, support monitoring, and action history now live inside a structured overview instead of one crowded page.'
);
?>

<section class="admin-summary-grid">
    <article class="admin-summary-card">
        <div class="admin-summary-card__eyebrow"><i class="fa fa-cubes"></i> Products</div>
        <h3><?php echo $overview['products']; ?></h3>
        <p>Live catalog items currently stored in MySQL and available across the website.</p>
    </article>
    <article class="admin-summary-card">
        <div class="admin-summary-card__eyebrow"><i class="fa fa-users"></i> Accounts</div>
        <h3><?php echo $overview['users']; ?></h3>
        <p><?php echo $adminCount; ?> admins and <?php echo $managerCount; ?> managers currently have elevated access.</p>
    </article>
    <article class="admin-summary-card">
        <div class="admin-summary-card__eyebrow"><i class="fa fa-comments-o"></i> Reviews</div>
        <h3><?php echo $overview['reviews']; ?></h3>
        <p>Customer review records are available in both product detail flow and the new review hub.</p>
    </article>
    <article class="admin-summary-card">
        <div class="admin-summary-card__eyebrow"><i class="fa fa-bolt"></i> Activity</div>
        <h3><?php echo $overview['notifications']; ?></h3>
        <p>Action logs saved for cart, favorites, contact, review, and catalog changes.</p>
    </article>
</section>

<section class="admin-content-grid" style="margin-top:18px;">
    <article class="admin-panel">
        <div class="admin-panel__head">
            <div>
                <h3>Fast Admin Routes</h3>
                <p>Each major control area now has its own page so the dashboard stays clearer on desktop and mobile.</p>
            </div>
        </div>
        <div class="admin-mini-grid">
            <a class="admin-mini-card admin-soft-link-card" href="./users.php">
                <span>Users</span>
                <strong>Edit users, email, password, roles, and custom role permissions.</strong>
            </a>
            <a class="admin-mini-card admin-soft-link-card" href="./catalog.php">
                <span>Catalog</span>
                <strong>Manage products, categories, brands, and inventory ownership.</strong>
            </a>
            <a class="admin-mini-card admin-soft-link-card" href="./reviews.php">
                <span>Reviews</span>
                <strong>Moderate product reviews and jump into the review hub quickly.</strong>
            </a>
            <a class="admin-mini-card admin-soft-link-card" href="./customize.php">
                <span>Customize</span>
                <strong>Save site theme colors, SEO text, and Google login setup details.</strong>
            </a>
        </div>
    </article>

    <div class="admin-stack">
        <article class="admin-panel">
            <div class="admin-panel__head">
                <div>
                    <h3>Support Pipeline</h3>
                    <p><?php echo $pipelineOpen; ?> inquiries still need admin attention.</p>
                </div>
                <a class="admin-soft-link" href="./activity.php">Open activity</a>
            </div>
            <div class="admin-list">
                <?php if ($recentInquiries === []): ?>
                    <div class="admin-list__item">
                        <strong>No support inquiries yet.</strong>
                        <p>When customers submit the contact form, records will show up here.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recentInquiries as $inquiry): ?>
                        <div class="admin-list__item">
                            <div class="admin-list__row">
                                <div>
                                    <strong><?php echo e((string) $inquiry['subject_line']); ?></strong>
                                    <p><?php echo e((string) $inquiry['full_name']); ?> • <?php echo e((string) $inquiry['email']); ?></p>
                                </div>
                                <div class="admin-badge <?php echo (string) ($inquiry['inquiry_status'] ?? 'new') === 'closed' ? 'admin-badge--closed' : 'admin-badge--progress'; ?>">
                                    <?php echo e(str_replace('_', ' ', (string) ($inquiry['inquiry_status'] ?? 'new'))); ?>
                                </div>
                            </div>
                            <div class="admin-list__meta">
                                <span><?php echo e(store_notification_limit_text((string) $inquiry['message_body'], 110)); ?></span>
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
                    <h3>Inbox Broadcasts</h3>
                    <p>Admin-to-user and role-targeted messages sent from the dashboard.</p>
                </div>
                <a class="admin-soft-link" href="./messages.php">Send message</a>
            </div>
            <div class="admin-list">
                <?php if ($messages === []): ?>
                    <div class="admin-list__item">
                        <strong>No dashboard messages yet.</strong>
                        <p>Use the Messages tab to broadcast updates to all users, one role, or one account.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="admin-list__item">
                            <strong><?php echo e((string) $message['title']); ?></strong>
                            <p><?php echo e(store_notification_limit_text((string) $message['message_body'], 100)); ?></p>
                            <div class="admin-list__meta">
                                <span>Target: <?php echo e((string) $message['target_type']); ?><?php echo !empty($message['target_value']) ? ' / ' . e((string) $message['target_value']) : ''; ?></span>
                                <span><?php echo e((string) $message['created_at']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </article>
    </div>
</section>

<section class="admin-content-grid" style="margin-top:18px;">
    <article class="admin-panel">
        <div class="admin-panel__head">
            <div>
                <h3>Recent Reviews</h3>
                <p>Latest customer feedback coming from the live website review flow.</p>
            </div>
            <a class="admin-soft-link" href="./reviews.php">Open reviews</a>
        </div>
        <div class="admin-list">
            <?php if ($recentReviews === []): ?>
                <div class="admin-list__item">
                    <strong>No reviews yet.</strong>
                    <p>The review hub and product detail pages will start filling this list as users submit feedback.</p>
                </div>
            <?php else: ?>
                <?php foreach ($recentReviews as $review): ?>
                    <div class="admin-list__item">
                        <div class="admin-list__row">
                            <div class="admin-product-line">
                                <img src="../<?php echo e(ltrim((string) ($review['product_image'] ?? 'img/logo/logo.png'), './')); ?>" alt="<?php echo e((string) $review['product_name']); ?>">
                                <div>
                                    <strong><?php echo e((string) $review['product_name']); ?></strong>
                                    <p><?php echo e(store_notification_limit_text((string) $review['comment'], 115)); ?></p>
                                </div>
                            </div>
                            <div class="admin-badge admin-badge--new"><?php echo (int) $review['rating']; ?>/5</div>
                        </div>
                        <div class="admin-list__meta">
                            <span><?php echo e((string) ($review['user_name'] ?? 'User')); ?></span>
                            <span><?php echo e((string) ($review['user_email'] ?? '')); ?></span>
                            <span><?php echo e((string) $review['created_at']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </article>

    <article class="admin-panel">
        <div class="admin-panel__head">
            <div>
                <h3>Live Activity Feed</h3>
                <p>Recent tracked actions from users and admins across the website.</p>
            </div>
            <a class="admin-soft-link" href="./activity.php">Full stream</a>
        </div>
        <div class="admin-list" data-activity-feed>
            <?php if ($recentActivity === []): ?>
                <div class="admin-list__item">
                    <strong>No activity yet.</strong>
                    <p>Tracked cart, favorites, contact, and catalog actions will appear here.</p>
                </div>
            <?php else: ?>
                <?php foreach ($recentActivity as $item): ?>
                    <div class="admin-list__item">
                        <div class="admin-list__row">
                            <div>
                                <strong><?php echo e((string) ($item['actor_name'] ?: 'System')); ?></strong>
                                <p><?php echo e((string) $item['subject_line']); ?></p>
                            </div>
                            <div class="admin-badge <?php echo (string) ($item['delivery_status'] ?? '') === 'sent' ? 'admin-badge--closed' : 'admin-badge--progress'; ?>">
                                <?php echo e((string) ($item['action_key'] ?? 'activity')); ?>
                            </div>
                        </div>
                        <div class="admin-list__meta">
                            <span><?php echo e((string) ($item['product_name'] ?: $item['product_slug'] ?: 'No product')); ?></span>
                            <span><?php echo e((string) $item['created_at']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </article>
</section>

<section class="admin-summary-grid" style="margin-top:18px;">
    <?php foreach ($roles as $role): ?>
        <article class="admin-summary-card">
            <div class="admin-summary-card__eyebrow"><i class="fa fa-key"></i> <?php echo e((string) $role['name']); ?></div>
            <h3><?php echo count($role['permissions'] ?? []); ?></h3>
            <p><?php echo e((string) ($role['description_text'] ?? 'Role permissions.')); ?></p>
        </article>
    <?php endforeach; ?>
</section>

<?php
admin_page_end();
