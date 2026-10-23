<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/custom-page.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $con instanceof mysqli) {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'request_code') {
        $result = store_password_reset_request($con, (string) ($_POST['email'] ?? ''));
        store_flash($result['success'] ? 'success' : 'danger', $result['message']);
        header('Location: ./forgot-password.php');
        exit;
    }

    if ($action === 'reset_password') {
        $result = store_password_reset_confirm(
            $con,
            (string) ($_POST['email'] ?? ''),
            (string) ($_POST['code'] ?? ''),
            (string) ($_POST['new_password'] ?? ''),
            (string) ($_POST['confirm_password'] ?? '')
        );
        store_flash($result['success'] ? 'success' : 'danger', $result['message']);
        header('Location: ' . ($result['success'] ? './login.php' : './forgot-password.php'));
        exit;
    }
}

$currentEmail = trim((string) ($_SESSION['user_email'] ?? ''));

render_custom_page_header('La Imparial | Password Recovery');
?>
<section class="catalog-admin">
    <div class="container">
        <div class="catalog-page__header">
            <h2>Forgot Password</h2>
            <p>Reset your password using email verification. Admin will also receive a recovery notification for visibility.</p>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="catalog-admin__panel">
                    <h4>1. Send Verification Code</h4>
                    <p>Enter the email address used for your La Imparial account. A numeric verification code will be sent to that inbox.</p>
                    <form action="./forgot-password.php" method="post" class="form-row-gap">
                        <input type="hidden" name="action" value="request_code">
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" class="form-control" name="email" value="<?php echo e($currentEmail); ?>" required>
                        </div>
                        <button type="submit" class="primary-btn">Send Code</button>
                    </form>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="catalog-admin__panel">
                    <h4>2. Verify and Set New Password</h4>
                    <p>Enter the code from your email, then choose a new password for your account.</p>
                    <form action="./forgot-password.php" method="post" class="form-row-gap">
                        <input type="hidden" name="action" value="reset_password">
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" class="form-control" name="email" value="<?php echo e($currentEmail); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Verification Code</label>
                            <input type="text" class="form-control" name="code" placeholder="6-digit code" required>
                        </div>
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" class="form-control" name="new_password" required>
                        </div>
                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <input type="password" class="form-control" name="confirm_password" required>
                        </div>
                        <button type="submit" class="primary-btn">Reset Password</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="catalog-admin__panel" style="margin-top: 30px;">
            <h4>Need Human Help?</h4>
            <p>If you still cannot access your account, contact admin support and mention the email address you are trying to recover.</p>
            <div class="cart-page__actions">
                <a class="quick-action--detail" href="./contact.php?subject=<?php echo urlencode('Password recovery help'); ?>">Contact Admin</a>
                <a class="quick-action--favorite" href="./login.php">Back to Login</a>
            </div>
        </div>
    </div>
</section>
<?php render_custom_page_footer(); ?>
