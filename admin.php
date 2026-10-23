<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/custom-page.php';

store_require_login('./admin.php');

if (!store_is_admin()) {
    store_flash('danger', 'Admin access required.');
    header('Location: ./profile.php');
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $con instanceof mysqli) {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'update_role') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $role = (string) ($_POST['role'] ?? 'user');
        if ($userId === store_current_user_id() && $role !== 'admin') {
            store_flash('danger', 'You cannot remove your own admin access.');
        } else {
            $updated = store_update_user_role($con, $userId, $role);
            store_flash($updated ? 'success' : 'danger', $updated ? 'User role updated.' : 'Role update failed.');
        }
        header('Location: ./admin.php');
        exit;
    }

    if ($action === 'delete_product') {
        $productId = (int) ($_POST['product_id'] ?? 0);
        $result = store_delete_product($con, $productId);
        store_flash($result['success'] ? 'success' : 'danger', $result['message']);
        header('Location: ./admin.php');
        exit;
    }
}

$users = $con instanceof mysqli ? store_users($con) : [];
$products = $con instanceof mysqli ? store_products_with_owner($con) : [];

render_custom_page_header('La Imparial | Admin');
?>
<section class="catalog-admin">
    <div class="container">
        <div class="catalog-page__header">
            <h2>Admin Control Center</h2>
            <p>Manage users, review products, and keep the catalogue healthy.</p>
        </div>

        <div class="catalog-admin__panel" style="margin-bottom: 24px;">
            <h4>Professional Dashboard</h4>
            <p>Open the new folder-based admin area for messages, reviews, user activity, and broader website control.</p>
            <a class="primary-btn" href="./admin/">Open Pro Admin Dashboard</a>
        </div>

        <div class="catalog-admin__panel">
            <h4>Users</h4>
            <?php if ($users === []): ?>
                <p>No users found.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Update</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo e($user['name']); ?></td>
                                    <td><?php echo e($user['email']); ?></td>
                                    <td><?php echo e($user['role']); ?></td>
                                    <td>
                                        <form action="./admin.php" method="post" style="display:flex; gap:10px; align-items:center;">
                                            <input type="hidden" name="action" value="update_role">
                                            <input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">
                                            <select class="form-control" name="role" style="max-width:160px;">
                                                <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                            </select>
                                            <button type="submit" class="primary-btn">Save</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="catalog-admin__panel" style="margin-top: 30px;">
            <h4>All Products</h4>
            <?php if ($products === []): ?>
                <p>No products found.</p>
            <?php else: ?>
                <?php foreach ($products as $item): ?>
                    <article class="catalog-card">
                        <img src="<?php echo e($item['image_path']); ?>" alt="<?php echo e($item['name']); ?>">
                        <div>
                            <div class="catalog-card__meta">
                                <span><?php echo e($item['brand_name']); ?></span>
                                <span><?php echo e($item['category_name']); ?></span>
                                <span><?php echo e($item['owner_name'] ?: 'System'); ?></span>
                            </div>
                            <h4><?php echo e($item['name']); ?></h4>
                            <p><?php echo e($item['summary']); ?></p>
                            <div class="catalog-card__price">PKR <?php echo number_format((float) $item['price']); ?></div>
                        </div>
                        <div class="favorite-page__actions">
                            <a class="quick-action--detail" href="./edit-product.php?product=<?php echo urlencode($item['slug']); ?>">Edit</a>
                            <form action="./admin.php" method="post">
                                <input type="hidden" name="action" value="delete_product">
                                <input type="hidden" name="product_id" value="<?php echo (int) $item['id']; ?>">
                                <button type="submit" class="quick-action--favorite" onclick="return confirm('Delete this product?');">Delete</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php render_custom_page_footer(); ?>
