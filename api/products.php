<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/connection.php';
require_once dirname(__DIR__) . '/includes/store.php';

ensure_store_state();

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if ($method === 'GET') {
    if (!($con instanceof mysqli)) {
        store_json_response([
            'success' => true,
            'fallback' => true,
            'message' => 'Database connection unavailable. Returning fallback catalog data.',
            'products' => store_seed_all_products(),
            'brands' => store_seed_brand_catalog(),
            'categories' => store_seed_category_catalog(),
        ]);
    }

    if (function_exists('store_cms_catalog_export')) {
        store_json_response(store_cms_catalog_export($con));
    }

    store_json_response([
        'success' => true,
        'products' => store_all_products($con),
        'brands' => store_brands($con),
        'categories' => store_categories($con),
    ]);
}

if (!($con instanceof mysqli)) {
    store_json_response(['success' => false, 'message' => 'Database connection unavailable.'], 503);
}

if (!store_is_logged_in() || !store_user_has_permission($con, 'manage_products')) {
    store_json_response(['success' => false, 'message' => 'Unauthorized.'], 403);
}

$rawBody = file_get_contents('php://input');
$payload = is_string($rawBody) && trim($rawBody) !== '' ? json_decode($rawBody, true) : [];

if (!is_array($payload)) {
    store_json_response(['success' => false, 'message' => 'Invalid JSON payload.'], 400);
}

if (!empty($payload['source_url'])) {
    $result = store_import_products_from_api(
        $con,
        (string) $payload['source_url'],
        (string) ($payload['brand_override'] ?? ''),
        (int) ($payload['limit'] ?? 20)
    );
    store_json_response($result, ($result['success'] ?? false) ? 200 : 422);
}

if (function_exists('store_cms_sync_catalog_payload')) {
    $result = store_cms_sync_catalog_payload(
        $con,
        $payload,
        (string) ($payload['brand_override'] ?? ''),
        (int) ($payload['limit'] ?? 20)
    );
    store_json_response($result, ($result['success'] ?? false) ? 200 : 422);
}

store_json_response(['success' => false, 'message' => 'Catalog sync helper missing.'], 500);
