<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/custom-page.php';

$redirect = store_safe_redirect((string) ($_SESSION['login_redirect'] ?? './index.php'));
$state = trim((string) ($_GET['state'] ?? ''));
$expectedState = trim((string) ($_SESSION['google_oauth_state'] ?? ''));
$code = trim((string) ($_GET['code'] ?? ''));
$oauthError = trim((string) ($_GET['error'] ?? ''));

if ($oauthError !== '') {
    unset($_SESSION['google_oauth_state'], $_SESSION['login_redirect']);
    store_flash('danger', 'Google login failed: ' . $oauthError . '.');
    header('Location: ./login.php?redirect=' . urlencode($redirect));
    exit;
}

// Roman Urdu: state verify karna CSRF safety ke liye zaroori hai.
if ($state === '' || $expectedState === '' || !hash_equals($expectedState, $state)) {
    unset($_SESSION['google_oauth_state'], $_SESSION['login_redirect']);
    store_flash('danger', 'Google login failed: invalid state or redirect mismatch.');
    header('Location: ./login.php?redirect=' . urlencode($redirect));
    exit;
}

if (!($con instanceof mysqli) || $code === '') {
    unset($_SESSION['google_oauth_state'], $_SESSION['login_redirect']);
    store_flash('danger', 'Google login failed: authorization code missing.');
    header('Location: ./login.php?redirect=' . urlencode($redirect));
    exit;
}

// Roman Urdu: Google API PHP Client ke zariye token exchange aur profile fetch kar rahe hain.
$result = store_google_callback_login($con, $code);

unset($_SESSION['google_oauth_state'], $_SESSION['login_redirect']);
store_flash($result['success'] ? 'success' : 'danger', (string) ($result['message'] ?? 'Google login completed.'));

header('Location: ' . $redirect);
exit;
