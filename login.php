<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/custom-page.php';

$redirect = store_safe_redirect((string) ($_REQUEST['redirect'] ?? './index.php'));
$provider = trim((string) ($_GET['provider'] ?? ''));

if ($provider === 'google') {
    if (!store_google_enabled()) {
        store_flash('danger', 'Google login is not configured yet. Add your Client ID and Client Secret in connection.php first.');
        header('Location: ./login.php?redirect=' . urlencode($redirect));
        exit;
    }

    // Roman Urdu: Composer se Google API PHP Client install honi chahiye.
    if (!store_google_ready()) {
        store_flash('danger', 'Google API PHP Client missing. Run Composer install for google/apiclient first.');
        header('Location: ./login.php?redirect=' . urlencode($redirect));
        exit;
    }

    // Roman Urdu: Yahan se user ko Google consent screen par bhej rahe hain.
    $googleUrl = store_google_auth_url_client($redirect);

    if ($googleUrl === null) {
        store_flash('danger', 'Google login URL could not be created.');
        header('Location: ./login.php?redirect=' . urlencode($redirect));
        exit;
    }

    header('Location: ' . $googleUrl);
    exit;
}

if (store_is_logged_in()) {
    render_custom_page_header('La Imparial | Login');
    ?>
    <section class="catalog-admin">
        <div class="container">
            <div class="catalog-admin__panel">
                <div class="catalog-page__header">
                    <h2>You are already logged in</h2>
                    <p>Continue shopping or logout to switch accounts.</p>
                </div>
                <div class="cart-page__actions">
                    <a class="quick-action--cart" href="<?php echo e($redirect); ?>">Continue</a>
                    <a class="quick-action--favorite" href="./logout.php">Logout</a>
                </div>
            </div>
        </div>
    </section>
    <?php
    render_custom_page_footer();
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $con instanceof mysqli) {
    $result = store_login_user($con, $_POST);
    store_flash($result['success'] ? 'success' : 'danger', $result['message']);

    if ($result['success']) {
        header('Location: ' . $redirect);
        exit;
    }
}

render_custom_page_header('La Imparial | Login');
?>
<section class="catalog-admin">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="catalog-admin__panel">
                    <div class="catalog-page__header">
                        <h2>Login</h2>
                        <p>Sign in to add products, manage your cart, and save favorites.</p>
                    </div>
                    <form action="./login.php" method="post">
                        <input type="hidden" name="redirect" value="<?php echo e($redirect); ?>">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="cart-page__actions">
                            <button type="submit" class="quick-action--cart">Login</button>
                            <a class="quick-action--detail" href="./register.php?redirect=<?php echo urlencode($redirect); ?>">Create Account</a>
                        </div>
                    </form>
                    <div style="margin-top: 14px;">
                        <a class="product__item__helper" href="./forgot-password.php">Forgot password? Use verification recovery</a>
                    </div>
                    <div style="margin-top: 18px;">
                        <?php if (store_google_enabled() && store_google_ready()): ?>
                            <a class="quick-action--favorite" href="./login.php?provider=google&amp;redirect=<?php echo urlencode($redirect); ?>">Login with Google</a>
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
