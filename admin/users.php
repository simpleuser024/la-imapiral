<?php
declare(strict_types=1);

require_once __DIR__ . '/shared.php';

admin_require_panel_permission('manage_users', 'You do not have permission to manage users.');

$csrfKey = 'admin_users_panel';
$csrfToken = store_csrf_token($csrfKey);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $token = (string) ($_POST['csrf_token'] ?? '');
    if (!store_verify_csrf_token($csrfKey, $token)) {
        store_flash('danger', 'Security verification failed. Please try again.');
        header('Location: ./users.php');
        exit;
    }

    $action = trim((string) ($_POST['action'] ?? ''));

    if ($action === 'update_user') {
        $result = store_update_user_account($con, (int) ($_POST['user_id'] ?? 0), $_POST);
        store_flash($result['success'] ? 'success' : 'danger', $result['message']);
    }

    if ($action === 'create_user') {
        $result = store_create_user_account($con, $_POST);
        store_flash($result['success'] ? 'success' : 'danger', $result['message']);
    }

    if ($action === 'delete_user') {
        $result = store_delete_user_account($con, (int) ($_POST['user_id'] ?? 0));
        store_flash($result['success'] ? 'success' : 'danger', $result['message']);
    }

    if ($action === 'save_role') {
        admin_require_panel_permission('manage_roles', 'You do not have permission to manage roles.');
        $result = store_save_role($con, $_POST);
        store_flash($result['success'] ? 'success' : 'danger', $result['message']);
    }

    if ($action === 'delete_role') {
        admin_require_panel_permission('manage_roles', 'You do not have permission to manage roles.');
        $result = store_delete_role($con, (int) ($_POST['role_id'] ?? 0));
        store_flash($result['success'] ? 'success' : 'danger', $result['message']);
    }

    header('Location: ./users.php');
    exit;
}

$users = store_users($con);
$roles = store_roles($con);
$roleOptions = array_column($roles, 'name', 'slug');
$permissionsCatalog = store_permissions_catalog();

admin_page_start(
    'La Imparial | Admin Users',
    'users.php',
    'Users',
    'Manage accounts, approval roles, and access levels.',
    'Viewer, member, manager, and admin controls are handled from this page. You can update name, email, password, roles, and custom permissions without leaving the dashboard.'
);
?>

<section class="admin-content-grid">
    <article class="admin-panel">
        <div class="admin-panel__head">
            <div>
                <h3>Account Control</h3>
                <p>Edit each user directly and promote only the people you trust for product or admin access.</p>
            </div>
        </div>

        <div class="admin-user-grid">
            <?php foreach ($users as $user): ?>
                <form class="admin-user-card" method="post" action="./users.php">
                    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                    <input type="hidden" name="action" value="update_user">
                    <input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">

                    <div class="admin-avatar-line">
                        <span class="admin-avatar"><?php echo e(admin_initials((string) $user['name'])); ?></span>
                        <div>
                            <strong><?php echo e((string) $user['name']); ?></strong>
                            <p><?php echo e((string) $user['email']); ?></p>
                        </div>
                    </div>

                    <div class="admin-form-grid">
                        <label>
                            <span>Name</span>
                            <input class="admin-input" type="text" name="name" value="<?php echo e((string) $user['name']); ?>" required>
                        </label>
                        <label>
                            <span>Email</span>
                            <input class="admin-input" type="email" name="email" value="<?php echo e((string) $user['email']); ?>" required>
                        </label>
                        <label>
                            <span>Role</span>
                            <select class="admin-input" name="role">
                                <?php foreach ($roleOptions as $slug => $roleName): ?>
                                    <option value="<?php echo e((string) $slug); ?>" <?php echo (string) $user['role'] === (string) $slug ? 'selected' : ''; ?>>
                                        <?php echo e((string) $roleName); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>
                            <span>New Password</span>
                            <input class="admin-input" type="text" name="password" placeholder="Leave blank to keep">
                        </label>
                    </div>

                    <div class="admin-list__meta">
                        <span>Created: <?php echo e((string) $user['created_at']); ?></span>
                        <span>Role: <?php echo e((string) ucfirst((string) $user['role'])); ?></span>
                    </div>

                    <div class="admin-action-row">
                        <button class="admin-button admin-button--primary" type="submit">Save User</button>
                        <a class="admin-button" href="./user-report.php?user_id=<?php echo (int) $user['id']; ?>">Open Report</a>
                    </div>
                </form>
            <?php endforeach; ?>
        </div>
    </article>

    <div class="admin-stack">
        <article class="admin-panel">
            <div class="admin-panel__head">
                <div>
                    <h3>Add New User</h3>
                    <p>Create an account directly from admin with name, email, password, and role.</p>
                </div>
            </div>
            <form method="post" action="./users.php" class="admin-form-stack">
                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                <input type="hidden" name="action" value="create_user">
                <label>
                    <span>Name</span>
                    <input class="admin-input" type="text" name="name" placeholder="Team member name" required>
                </label>
                <label>
                    <span>Email</span>
                    <input class="admin-input" type="email" name="email" placeholder="member@example.com" required>
                </label>
                <label>
                    <span>Password</span>
                    <input class="admin-input" type="text" name="password" placeholder="Temporary password" required>
                </label>
                <label>
                    <span>Role</span>
                    <select class="admin-input" name="role">
                        <?php foreach ($roleOptions as $slug => $roleName): ?>
                            <option value="<?php echo e((string) $slug); ?>"><?php echo e((string) $roleName); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button class="admin-button admin-button--primary" type="submit">Create User</button>
            </form>
        </article>

        <article class="admin-panel">
            <div class="admin-panel__head">
                <div>
                    <h3>Create Or Update Role</h3>
                    <p>Build a manager-style role or create a custom access preset for your team.</p>
                </div>
            </div>
            <form method="post" action="./users.php" class="admin-form-stack">
                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                <input type="hidden" name="action" value="save_role">
                <label>
                    <span>Role Name</span>
                    <input class="admin-input" type="text" name="name" placeholder="Inventory Lead" required>
                </label>
                <label>
                    <span>Role Slug</span>
                    <input class="admin-input" type="text" name="slug" placeholder="inventory-lead">
                </label>
                <label>
                    <span>Description</span>
                    <textarea class="admin-input" name="description_text" rows="3" placeholder="Roman Urdu ya English me role purpose likhen."></textarea>
                </label>
                <div class="admin-permission-grid">
                    <?php foreach ($permissionsCatalog as $permission => $label): ?>
                        <label class="admin-permission-pill">
                            <input type="checkbox" name="permissions[]" value="<?php echo e((string) $permission); ?>">
                            <span><?php echo e((string) $label); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <button class="admin-button admin-button--primary" type="submit">Save Role</button>
            </form>
        </article>

        <article class="admin-panel">
            <div class="admin-panel__head">
                <div>
                    <h3>Current Roles</h3>
                    <p>System roles stay protected. Custom roles can be deleted once no user depends on them.</p>
                </div>
            </div>
            <div class="admin-list">
                <?php foreach ($roles as $role): ?>
                    <div class="admin-list__item">
                        <div class="admin-list__row">
                            <div>
                                <strong><?php echo e((string) $role['name']); ?></strong>
                                <p><?php echo e((string) ($role['description_text'] ?? '')); ?></p>
                            </div>
                            <div class="admin-badge <?php echo (int) ($role['is_system'] ?? 0) === 1 ? 'admin-badge--closed' : 'admin-badge--new'; ?>">
                                <?php echo (int) ($role['is_system'] ?? 0) === 1 ? 'system' : 'custom'; ?>
                            </div>
                        </div>
                        <div class="admin-list__meta">
                            <span><?php echo e((string) $role['slug']); ?></span>
                            <span><?php echo count($role['permissions'] ?? []); ?> permissions</span>
                        </div>
                        <?php if ((int) ($role['is_system'] ?? 0) !== 1): ?>
                            <form method="post" action="./users.php" style="margin-top:12px;">
                                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                <input type="hidden" name="action" value="delete_role">
                                <input type="hidden" name="role_id" value="<?php echo (int) $role['id']; ?>">
                                <button class="admin-button admin-button--danger" type="submit" onclick="return confirm('Delete this custom role?');">Delete Role</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>

        <article class="admin-panel">
            <div class="admin-panel__head">
                <div>
                    <h3>Remove Account</h3>
                    <p>Delete an account completely when you no longer need it in the system.</p>
                </div>
            </div>
            <form method="post" action="./users.php" class="admin-form-stack">
                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                <input type="hidden" name="action" value="delete_user">
                <label>
                    <span>Select User</span>
                    <select class="admin-input" name="user_id" required>
                        <option value="">Choose account</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo (int) $user['id']; ?>">
                                <?php echo e((string) $user['name']); ?> - <?php echo e((string) $user['email']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button class="admin-button admin-button--danger" type="submit" onclick="return confirm('Remove this account?');">Delete User</button>
            </form>
        </article>
    </div>
</section>

<?php
admin_page_end();
