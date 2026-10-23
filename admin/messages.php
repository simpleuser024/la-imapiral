<?php
declare(strict_types=1);

require_once __DIR__ . '/shared.php';

admin_require_panel_permission('send_messages', 'You do not have permission to send dashboard messages.');

$csrfKey = 'admin_messages_panel';
$csrfToken = store_csrf_token($csrfKey);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $token = (string) ($_POST['csrf_token'] ?? '');
    if (!store_verify_csrf_token($csrfKey, $token)) {
        store_flash('danger', 'Security verification failed. Please try again.');
        header('Location: ./messages.php');
        exit;
    }

    if ((string) ($_POST['action'] ?? '') === 'send_message') {
        $data = $_POST;
        $targetType = (string) ($_POST['target_type'] ?? 'all');
        if ($targetType === 'role') {
            $data['target_value'] = (string) ($_POST['role_target'] ?? '');
        } elseif ($targetType === 'user') {
            $data['target_value'] = (string) ($_POST['user_target'] ?? '');
        } else {
            $data['target_value'] = '';
        }

        $result = store_create_message($con, $data);
        store_flash($result['success'] ? 'success' : 'danger', $result['message']);
        header('Location: ./messages.php');
        exit;
    }
}

$roles = store_roles($con);
$users = store_users($con);
$messages = store_messages_admin_feed($con, 40);

admin_page_start(
    'La Imparial | Admin Messages',
    'messages.php',
    'Messages',
    'Send one dashboard message to all users, one role, or one account.',
    'This works like a lightweight internal chat channel: admins can broadcast announcements, permission updates, or support follow-ups directly into user dashboards.'
);
?>

<section class="admin-content-grid">
    <article class="admin-panel">
        <div class="admin-panel__head">
            <div>
                <h3>Compose Message</h3>
                <p>Choose whether the message should go to everyone, one role, or a specific user.</p>
            </div>
        </div>
        <form method="post" action="./messages.php" class="admin-form-stack">
            <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
            <input type="hidden" name="action" value="send_message">
            <label>
                <span>Message Title</span>
                <input class="admin-input" type="text" name="title" placeholder="New store update" required>
            </label>
            <label>
                <span>Target Type</span>
                <select class="admin-input" name="target_type" id="message-target-type">
                    <option value="all">All users</option>
                    <option value="role">One role</option>
                    <option value="user">One user</option>
                </select>
            </label>
            <label>
                <span>Role Target</span>
                <select class="admin-input" name="role_target">
                    <option value="">Choose role</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?php echo e((string) $role['slug']); ?>"><?php echo e((string) $role['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>User Target</span>
                <select class="admin-input" name="user_target">
                    <option value="">Choose user</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo (int) $user['id']; ?>"><?php echo e((string) $user['name']); ?> - <?php echo e((string) $user['email']); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Message Body</span>
                <textarea class="admin-input" name="message_body" rows="6" placeholder="Roman Urdu ya English me clear instruction ya update likhen." required></textarea>
            </label>
            <button class="admin-button admin-button--primary" type="submit">Send Dashboard Message</button>
        </form>
    </article>

    <article class="admin-panel">
        <div class="admin-panel__head">
            <div>
                <h3>Recent Messages</h3>
                <p>Everything sent from the admin message center appears in this feed.</p>
            </div>
        </div>
        <div class="admin-list">
            <?php if ($messages === []): ?>
                <div class="admin-list__item">
                    <strong>No messages yet.</strong>
                    <p>Send the first dashboard message and users will see it in their dashboard inbox.</p>
                </div>
            <?php else: ?>
                <?php foreach ($messages as $message): ?>
                    <div class="admin-list__item">
                        <div class="admin-list__row">
                            <div>
                                <strong><?php echo e((string) $message['title']); ?></strong>
                                <p><?php echo e(store_notification_limit_text((string) $message['message_body'], 160)); ?></p>
                            </div>
                            <div class="admin-badge admin-badge--new"><?php echo e((string) $message['target_type']); ?></div>
                        </div>
                        <div class="admin-list__meta">
                            <span>Target: <?php echo e((string) ($message['target_value'] ?: 'all')); ?></span>
                            <span>Sender: <?php echo e((string) ($message['sender_name'] ?: 'Admin')); ?></span>
                            <span><?php echo e((string) $message['created_at']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </article>
</section>

<?php
admin_page_end();
