<?php
declare(strict_types=1);

require_once __DIR__ . '/shared.php';

admin_require_panel_permission('view_reports', 'You do not have permission to view activity reports.');

$activity = store_activity_feed($con, 24);

admin_page_start(
    'La Imparial | Admin Activity',
    'activity.php',
    'Activity',
    'Live action stream for cart, favorites, contact, reviews, and catalog edits.',
    'This page refreshes its feed in the background so admin can monitor recent activity without manually reloading the whole dashboard.'
);
?>

<section class="admin-content-grid">
    <article class="admin-panel">
        <div class="admin-panel__head">
            <div>
                <h3>Live Activity Feed</h3>
                <p>Recent website actions tracked in the audit log.</p>
            </div>
            <div class="admin-badge admin-badge--progress">auto refresh</div>
        </div>
        <div class="admin-list" id="admin-live-activity-feed">
            <?php if ($activity === []): ?>
                <div class="admin-list__item">
                    <strong>No activity yet.</strong>
                    <p>As users and admins interact with the site, the feed will start updating here.</p>
                </div>
            <?php else: ?>
                <?php foreach ($activity as $item): ?>
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
                            <span><?php echo e((string) ($item['actor_email'] ?: '')); ?></span>
                            <span><?php echo e((string) ($item['product_name'] ?: $item['product_slug'] ?: 'No product')); ?></span>
                            <span><?php echo e((string) $item['created_at']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </article>
</section>

<?php
admin_page_end(<<<'JS'
(function () {
    var feed = document.getElementById('admin-live-activity-feed');

    if (!feed) {
        return;
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function render(items) {
        if (!items || !items.length) {
            feed.innerHTML = '<div class="admin-list__item"><strong>No activity yet.</strong><p>As users and admins interact with the site, the feed will start updating here.</p></div>';
            return;
        }

        feed.innerHTML = items.map(function (item) {
            var badgeClass = (item.delivery_status || '') === 'sent' ? 'admin-badge--closed' : 'admin-badge--progress';
            return [
                '<div class="admin-list__item">',
                '<div class="admin-list__row">',
                '<div><strong>', escapeHtml(item.actor_name || 'System'), '</strong><p>', escapeHtml(item.subject_line || ''), '</p></div>',
                '<div class="admin-badge ', badgeClass, '">', escapeHtml(item.action_key || 'activity'), '</div>',
                '</div>',
                '<div class="admin-list__meta">',
                '<span>', escapeHtml(item.actor_email || ''), '</span>',
                '<span>', escapeHtml(item.product_name || item.product_slug || 'No product'), '</span>',
                '<span>', escapeHtml(item.created_at || ''), '</span>',
                '</div>',
                '</div>'
            ].join('');
        }).join('');
    }

    function refreshFeed() {
        fetch('./api.php?action=activity_feed', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
            .then(function (response) { return response.json(); })
            .then(function (payload) {
                if (payload && payload.success) {
                    render(payload.items || []);
                }
            })
            .catch(function () {});
    }

    window.setInterval(refreshFeed, 15000);
})();
JS);
