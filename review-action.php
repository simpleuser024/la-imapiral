<?php
declare(strict_types=1);

require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/includes/store.php';

ensure_store_state();

$redirect = store_safe_redirect((string) ($_REQUEST['redirect'] ?? './shop.php?view=products'));
$wantsJson = store_request_wants_json();
$loginRedirect = store_absolute_url('login.php?redirect=' . urlencode($redirect));

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !($con instanceof mysqli)) {
    if ($wantsJson) {
        store_json_response(['success' => false, 'message' => 'Invalid review request.'], 400);
    }

    header('Location: ' . $redirect);
    exit;
}

if (!store_is_logged_in()) {
    if ($wantsJson) {
        store_json_response([
            'success' => false,
            'message' => 'Please login before posting a review.',
            'requiresLogin' => true,
            'redirect' => $loginRedirect,
        ], 401);
    }

    store_require_login($redirect);
}

$productSlug = trim((string) ($_POST['product'] ?? ''));
$rating = (int) ($_POST['rating'] ?? 0);
$comment = trim((string) ($_POST['comment'] ?? ''));

$result = store_create_review($con, $productSlug, store_current_user_id(), $rating, $comment);
store_flash($result['success'] ? 'success' : 'danger', $result['message']);

if ($wantsJson) {
    $product = store_product_by_slug($con, $productSlug);
    $user = store_current_user($con);
    $summary = ['count' => 0, 'average' => 0];

    if ($product) {
        $reviewSummary = store_review_summary_by_product($con, [(int) $product['id']]);
        $summary = $reviewSummary[(int) $product['id']] ?? $summary;
    }

    store_json_response([
        'success' => (bool) ($result['success'] ?? false),
        'message' => (string) ($result['message'] ?? ''),
        'product' => [
            'slug' => $productSlug,
            'name' => (string) ($product['name'] ?? ''),
        ],
        'summary' => $summary,
        'review' => [
            'rating' => $rating,
            'comment' => $comment,
            'createdAt' => date('Y-m-d H:i:s'),
            'userName' => (string) ($user['name'] ?? 'User'),
            'userAvatar' => (string) ($user['profile_image'] ?? ''),
        ],
    ], ($result['success'] ?? false) ? 200 : 422);
}

header('Location: ' . $redirect);
exit;
