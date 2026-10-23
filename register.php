<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/custom-page.php';

$redirect = store_safe_redirect((string) ($_REQUEST['redirect'] ?? './index.php'));

if (store_is_logged_in()) {
    header('Location: ' . $redirect);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $con instanceof mysqli) {
    $result = store_register_user($con, $_POST);
    store_flash($result['success'] ? 'success' : 'danger', $result['message']);

    if ($result['success']) {
        header('Location: ' . $redirect);
        exit;
    }
}

render_custom_page_header('La Imparial | Register');
?>
<section class="catalog-admin">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="catalog-admin__panel">
                    <div class="catalog-page__header">
                        <h2>Create Account</h2>
                        <p>Register once to add products, manage your cart, and save favorites.</p>
                    </div>
                    <form action="./register.php" method="post">
                        <input type="hidden" name="redirect" value="<?php echo e($redirect); ?>">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="cart-page__actions">
                            <button type="submit" class="quick-action--cart">Create Account</button>
                            <a class="quick-action--detail" href="./login.php?redirect=<?php echo urlencode($redirect); ?>">Back to Login</a>
                        </div>
                    </form>
                    <div style="margin-top: 18px;">
                        <?php if (store_google_enabled() && store_google_ready()): ?>
                            <a class="quick-action--favorite" href="./login.php?provider=google&amp;redirect=<?php echo urlencode($redirect); ?>">Register with Google</a>
                        <?php elseif (store_google_enabled()): ?>
                            <p class="product__item__helper">Google keys are set, but the Google API PHP Client is missing. Install Composer dependencies first.</p>
                        <?php else: ?>
                            <p class="product__item__helper">Google login is disabled. Add your Google OAuth keys in connection.php.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php render_custom_page_footer(); ?>
