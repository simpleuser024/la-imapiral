<?php
declare(strict_types=1);

require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/includes/store.php';

ensure_store_state();

$action = trim((string) ($_REQUEST['action'] ?? ''));
$productSlug = trim((string) ($_REQUEST['product'] ?? ''));
$redirect = store_safe_redirect((string) ($_REQUEST['redirect'] ?? './index.php'));
$wantsJson = store_request_wants_json();
$loginRedirect = store_absolute_url('login.php?redirect=' . urlencode($redirect));
$databaseOnline = $con instanceof mysqli;

if (in_array($action, ['add_to_cart', 'decrease_cart', 'remove_from_cart'], true) && !store_is_logged_in()) {
    if ($wantsJson) {
        store_json_response([
            'success' => false,
            'message' => 'Please login to continue.',
            'requiresLogin' => true,
            'redirect' => $loginRedirect,
        ], 401);
    }

    store_require_login($redirect);
}

if ($action === '' || $productSlug === '') {
    if ($wantsJson) {
        store_json_response([
            'success' => false,
            'message' => 'Action or product could not be resolved.',
        ], 400);
    }

    header('Location: ' . $redirect);
    exit;
}

if ($databaseOnline) {
    switch ($action) {
        case 'add_to_cart':
            $result = store_cart_add($con, $productSlug);
            $flashType = $result['success'] ? 'success' : 'danger';
            break;

        case 'decrease_cart':
            $result = store_cart_decrease($con, $productSlug);
            $flashType = $result['success'] ? 'success' : 'warning';
            break;

        case 'remove_from_cart':
            $result = store_cart_remove($con, $productSlug);
            $flashType = $result['success'] ? 'warning' : 'danger';
            break;

        case 'toggle_favorite':
            $result = store_toggle_favorite($con, $productSlug);
            $flashType = ($result['favorited'] ?? false) ? 'success' : 'warning';
            break;

        case 'record_view':
            $product = store_product_by_slug($con, $productSlug);
            if ($product) {
                $count = store_record_product_view($con, (int) $product['id']);
                $result = ['success' => true, 'message' => 'View recorded.', 'viewCount' => $count];
                $flashType = 'success';
            } else {
                $result = ['success' => false, 'message' => 'Product could not be found.', 'viewCount' => 0];
                $flashType = 'warning';
            }
            break;

        default:
            $result = ['success' => false, 'message' => 'Unknown action.'];
            $flashType = 'danger';
            break;
    }
} else {
    $product = store_seed_product_by_slug($productSlug);

    switch ($action) {
        case 'add_to_cart':
            if (!$product) {
                $result = ['success' => false, 'message' => 'Product could not be found.'];
                $flashType = 'danger';
                break;
            }
            $_SESSION['cart'][$productSlug] = max(0, (int) ($_SESSION['cart'][$productSlug] ?? 0)) + 1;
            $result = ['success' => true, 'message' => 'Product added to cart.', 'product' => $product];
            $flashType = 'success';
            break;

        case 'decrease_cart':
            if (!isset($_SESSION['cart'][$productSlug])) {
                $result = ['success' => false, 'message' => 'Product was not in the cart.'];
                $flashType = 'warning';
                break;
            }
            $quantity = max(0, (int) ($_SESSION['cart'][$productSlug] ?? 0) - 1);
            if ($quantity > 0) {
                $_SESSION['cart'][$productSlug] = $quantity;
                $result = ['success' => true, 'message' => 'Cart updated.', 'product' => $product];
            } else {
                unset($_SESSION['cart'][$productSlug]);
                $result = ['success' => true, 'message' => 'Product removed from cart.', 'product' => $product];
            }
            $flashType = 'success';
            break;

        case 'remove_from_cart':
            if (!isset($_SESSION['cart'][$productSlug])) {
                $result = ['success' => false, 'message' => 'Product could not be found in the cart.'];
                $flashType = 'danger';
                break;
            }
            unset($_SESSION['cart'][$productSlug]);
            $result = ['success' => true, 'message' => 'Product removed from cart.', 'product' => $product];
            $flashType = 'warning';
            break;

        case 'toggle_favorite':
            if (!$product) {
                $result = ['success' => false, 'message' => 'Product could not be found.', 'favorited' => false];
                $flashType = 'danger';
                break;
            }
            $favorites = array_values($_SESSION['favorites'] ?? []);
            if (in_array($productSlug, $favorites, true)) {
                $_SESSION['favorites'] = array_values(array_filter($favorites, static fn ($slug): bool => $slug !== $productSlug));
                $result = ['success' => true, 'message' => 'Product removed from favorites.', 'favorited' => false, 'product' => $product];
                $flashType = 'warning';
            } else {
                $favorites[] = $productSlug;
                $_SESSION['favorites'] = array_values(array_unique($favorites));
                $result = ['success' => true, 'message' => 'Product added to favorites.', 'favorited' => true, 'product' => $product];
                $flashType = 'success';
            }
            break;

        case 'record_view':
            $result = [
                'success' => (bool) $product,
                'message' => $product ? 'View recorded.' : 'Product could not be found.',
                'viewCount' => (int) ($product['view_count'] ?? 0),
            ];
            $flashType = $product ? 'success' : 'warning';
            break;

        default:
            $result = ['success' => false, 'message' => 'Unknown action.'];
            $flashType = 'danger';
            break;
    }
}

if ($action !== 'record_view') {
    store_flash($flashType, (string) ($result['message'] ?? 'Action completed.'));
}

if ($wantsJson) {
    $product = isset($product) ? $product : ($databaseOnline ? store_product_by_slug($con, $productSlug) : store_seed_product_by_slug($productSlug));
    $quantity = max(0, (int) ($_SESSION['cart'][$productSlug] ?? 0));
    $price = (float) ($product['price'] ?? 0);

    store_json_response([
        'success' => (bool) ($result['success'] ?? array_key_exists('favorited', $result)),
        'message' => (string) ($result['message'] ?? 'Action completed.'),
        'action' => $action,
        'product' => [
            'slug' => $productSlug,
            'name' => (string) ($product['name'] ?? ($result['product']['name'] ?? 'Product')),
            'image' => (string) ($product['image_path'] ?? ''),
            'price' => $price,
            'viewCount' => (int) ($result['viewCount'] ?? ($product['view_count'] ?? 0)),
        ],
        'state' => [
            'cartCount' => store_cart_count(),
            'favoriteCount' => count($_SESSION['favorites'] ?? []),
            'productQuantity' => $quantity,
            'favorited' => in_array($productSlug, $_SESSION['favorites'] ?? [], true),
            'cartSubtotal' => $databaseOnline ? store_cart_subtotal($con) : store_seed_cart_subtotal($_SESSION['cart'] ?? []),
            'lineTotal' => $quantity * $price,
            'viewCount' => (int) ($result['viewCount'] ?? ($product['view_count'] ?? 0)),
        ],
    ]);
}

header('Location: ' . $redirect);
exit;
