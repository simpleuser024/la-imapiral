<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/render.php';

$brandSlug = trim((string) ($_GET['brand'] ?? ''));

if ($brandSlug === '') {
    header('Location: ./index.php#brand-lineup');
    exit;
}

$brandExists = false;

if (isset($con) && $con instanceof mysqli) {
    $brandList = function_exists('store_brand_catalog') ? store_brand_catalog($con) : store_brands($con);
} else {
    $brandList = function_exists('store_seed_brand_catalog') ? store_seed_brand_catalog() : [];
}

foreach ($brandList as $brand) {
    if ((string) ($brand['slug'] ?? '') === $brandSlug && (int) ($brand['is_visible'] ?? 1) === 1) {
        $brandExists = true;
        break;
    }
}

if (!$brandExists) {
    header('Location: ./index.php#brand-lineup');
    exit;
}

render_catalog_template('haier.html', ['brandSlug' => $brandSlug]);
