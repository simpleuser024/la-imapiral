<?php
declare(strict_types=1);

function store_cms_add_column_if_missing(mysqli $connection, string $table, string $column, string $definition): void
{
    if (!store_table_exists($connection, $table) || store_column_exists($connection, $table, $column)) {
        return;
    }

    @mysqli_query($connection, 'ALTER TABLE ' . $table . ' ADD COLUMN ' . $column . ' ' . $definition);
}

function store_cms_add_index_if_missing(mysqli $connection, string $table, string $indexName, string $definition): void
{
    if (!store_table_exists($connection, $table) || store_index_exists($connection, $table, $indexName)) {
        return;
    }

    @mysqli_query($connection, 'ALTER TABLE ' . $table . ' ADD ' . $definition);
}

function store_cms_migrate_schema(mysqli $connection): void
{
    store_cms_add_column_if_missing($connection, 'brands', 'hero_title', 'VARCHAR(255) DEFAULT NULL');
    store_cms_add_column_if_missing($connection, 'brands', 'hero_summary', 'TEXT DEFAULT NULL');
    store_cms_add_column_if_missing($connection, 'brands', 'slider_banner_path', 'VARCHAR(255) DEFAULT NULL');
    store_cms_add_column_if_missing($connection, 'brands', 'hero_poster_path', 'VARCHAR(255) DEFAULT NULL');
    store_cms_add_column_if_missing($connection, 'brands', 'hero_video_path', 'VARCHAR(255) DEFAULT NULL');
    store_cms_add_column_if_missing($connection, 'brands', 'splash_video_path', 'VARCHAR(255) DEFAULT NULL');
    store_cms_add_column_if_missing($connection, 'brands', 'splash_logo_path', 'VARCHAR(255) DEFAULT NULL');
    store_cms_add_column_if_missing($connection, 'brands', 'featured_product_slug', 'VARCHAR(255) DEFAULT NULL');
    store_cms_add_column_if_missing($connection, 'brands', 'badge_json', 'LONGTEXT DEFAULT NULL');
    store_cms_add_column_if_missing($connection, 'brands', 'is_visible', 'TINYINT(1) NOT NULL DEFAULT 1');
    store_cms_add_column_if_missing($connection, 'brands', 'sort_order', 'INT NOT NULL DEFAULT 0');

    store_cms_add_column_if_missing($connection, 'categories', 'hero_headline', 'VARCHAR(255) DEFAULT NULL');
    store_cms_add_column_if_missing($connection, 'categories', 'banner_image_path', 'VARCHAR(255) DEFAULT NULL');
    store_cms_add_column_if_missing($connection, 'categories', 'hero_video_path', 'VARCHAR(255) DEFAULT NULL');
    store_cms_add_column_if_missing($connection, 'categories', 'sort_order', 'INT NOT NULL DEFAULT 0');

    if (!store_table_exists($connection, 'product_labels')) {
        @mysqli_query(
            $connection,
            'CREATE TABLE IF NOT EXISTS product_labels (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(120) NOT NULL,
                slug VARCHAR(140) NOT NULL UNIQUE,
                color_hex VARCHAR(20) NOT NULL DEFAULT "#0b1688",
                description_text TEXT DEFAULT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    if (!store_table_exists($connection, 'product_label_assignments')) {
        @mysqli_query(
            $connection,
            'CREATE TABLE IF NOT EXISTS product_label_assignments (
                product_id INT UNSIGNED NOT NULL,
                label_id INT UNSIGNED NOT NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (product_id, label_id),
                CONSTRAINT fk_product_label_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                CONSTRAINT fk_product_label_label FOREIGN KEY (label_id) REFERENCES product_labels(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    store_cms_seed_labels($connection);
}

function store_cms_site_setting_defaults(): array
{
    return [
        'site_logo_path' => 'img/logo/logo.png',
        'site_logo_alt' => 'La Imparial',
        'site_splash_logo' => 'img/logo/image.png',
        'home_kitchen_promo' => 'img/banner/ketchen.webp',
        'home_kitchen_promo_alt' => 'La Imperial kitchen highlight visual',
        'home_smart_promo' => 'img/banner/smart.webp',
        'home_smart_promo_alt' => 'Kitchen edit promotional poster',
        'home_gallery_images_json' => json_encode([
            ['src' => 'img/banner/home.webp', 'alt' => 'La Imperial appliances in a home setting'],
            ['src' => 'img/banner/banner-2.jpg', 'alt' => 'Cooling and comfort interior highlight'],
            ['src' => 'img/banner/banner-3.jpg', 'alt' => 'Modern appliance showcase detail'],
            ['src' => 'img/hero/hero-1.jpg', 'alt' => 'Premium appliance lifestyle scene'],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    ];
}

function store_cms_site_setting_keys(): array
{
    return array_keys(store_cms_site_setting_defaults());
}

function store_cms_public_editor_keys(): array
{
    return [
        'site_logo_path',
        'site_logo_alt',
        'site_splash_logo',
        'home_kitchen_promo',
        'home_kitchen_promo_alt',
        'home_smart_promo',
        'home_smart_promo_alt',
        'home_gallery_images_json',
    ];
}

function store_cms_allowed_document_types(): array
{
    return [
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'text/plain' => 'txt',
    ];
}

function store_cms_allowed_video_types(): array
{
    return [
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'video/ogg' => 'ogv',
        'application/octet-stream' => 'mp4',
    ];
}

function store_cms_move_uploaded_asset(string $tmpPath, string $extension, string $folder, string $prefix): array
{
    $targetDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $folder;

    if (!is_dir($targetDir) && !@mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
        return ['success' => false, 'message' => 'Upload folder could not be created.'];
    }

    $filename = $prefix . uniqid('', true) . '.' . $extension;
    $destination = $targetDir . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($tmpPath, $destination)) {
        return ['success' => false, 'message' => 'Uploaded file could not be moved.'];
    }

    return ['success' => true, 'path' => 'uploads/' . $folder . '/' . $filename];
}

function store_cms_upload_asset(array $file, array $allowedMimeTypes, string $folder, string $prefix, bool $required = false, string $missingMessage = 'Please choose a file.'): array
{
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($error === UPLOAD_ERR_NO_FILE) {
        return $required ? ['success' => false, 'message' => $missingMessage] : ['success' => true, 'path' => ''];
    }

    if ($error !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Uploaded file is invalid.'];
    }

    $tmpPath = (string) ($file['tmp_name'] ?? '');

    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        return ['success' => false, 'message' => 'Uploaded file could not be verified.'];
    }

    $mimeType = mime_content_type($tmpPath) ?: '';

    if (!isset($allowedMimeTypes[$mimeType])) {
        return ['success' => false, 'message' => 'File type is not allowed for this field.'];
    }

    return store_cms_move_uploaded_asset($tmpPath, $allowedMimeTypes[$mimeType], $folder, $prefix);
}

function store_cms_upload_image_asset(array $file, bool $required = false, string $folder = 'brand-assets', string $prefix = 'asset_'): array
{
    return store_cms_upload_asset($file, store_allowed_image_types(), $folder, $prefix, $required, 'Please choose an image.');
}

function store_cms_upload_video_asset(array $file, bool $required = false, string $folder = 'brand-videos', string $prefix = 'video_'): array
{
    return store_cms_upload_asset($file, store_cms_allowed_video_types(), $folder, $prefix, $required, 'Please choose a video.');
}

function store_cms_upload_document_asset(array $file, bool $required = false, string $folder = 'category-docs', string $prefix = 'document_'): array
{
    return store_cms_upload_asset($file, store_cms_allowed_document_types(), $folder, $prefix, $required, 'Please choose a document.');
}

function store_cms_upload_or_keep(string $manualPath, array $file, callable $uploadHandler): array
{
    $manualPath = trim($manualPath);
    $upload = $uploadHandler($file);

    if (($upload['success'] ?? false) !== true) {
        return $upload;
    }

    $uploadedPath = trim((string) ($upload['path'] ?? ''));
    return [
        'success' => true,
        'path' => $uploadedPath !== '' ? $uploadedPath : $manualPath,
    ];
}

function store_cms_safe_json_list($value): array
{
    if (is_string($value)) {
        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            $value = $decoded;
        }
    }

    if (!is_array($value)) {
        return [];
    }

    $items = [];

    foreach ($value as $item) {
        if (is_array($item)) {
            $src = trim((string) ($item['src'] ?? ''));
            if ($src === '') {
                continue;
            }

            $items[] = [
                'src' => $src,
                'alt' => trim((string) ($item['alt'] ?? 'La Imparial showcase media')),
            ];
            continue;
        }

        $item = trim((string) $item);
        if ($item !== '') {
            $items[] = ['src' => $item, 'alt' => 'La Imparial showcase media'];
        }
    }

    return $items;
}

function store_cms_comma_list(string $value): array
{
    $parts = preg_split('/[,\\n]+/', $value) ?: [];
    $clean = [];

    foreach ($parts as $part) {
        $part = trim($part);
        if ($part !== '') {
            $clean[] = $part;
        }
    }

    return array_values(array_unique($clean));
}

function store_cms_seed_labels(mysqli $connection): void
{
    if (!store_table_exists($connection, 'product_labels')) {
        return;
    }

    $defaults = [
        ['name' => 'Featured', 'slug' => 'featured', 'color_hex' => '#0b1688', 'description_text' => 'Homepage ya premium showcase label.', 'sort_order' => 10],
        ['name' => 'New Arrival', 'slug' => 'new-arrival', 'color_hex' => '#0f766e', 'description_text' => 'Fresh stock ya latest addition.', 'sort_order' => 20],
        ['name' => 'Hot Deal', 'slug' => 'hot-deal', 'color_hex' => '#dc2626', 'description_text' => 'Discount aur promo products.', 'sort_order' => 30],
    ];

    foreach ($defaults as $label) {
        $stmt = @mysqli_prepare(
            $connection,
            'INSERT INTO product_labels (name, slug, color_hex, description_text, sort_order)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                color_hex = VALUES(color_hex),
                description_text = VALUES(description_text),
                sort_order = VALUES(sort_order)'
        );

        if (!($stmt instanceof mysqli_stmt)) {
            continue;
        }

        mysqli_stmt_bind_param(
            $stmt,
            'ssssi',
            $label['name'],
            $label['slug'],
            $label['color_hex'],
            $label['description_text'],
            $label['sort_order']
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

function store_product_labels(mysqli $connection): array
{
    $items = [];

    if (!store_table_exists($connection, 'product_labels')) {
        return $items;
    }

    $result = @mysqli_query($connection, 'SELECT id, name, slug, color_hex, description_text, sort_order FROM product_labels ORDER BY sort_order ASC, name ASC');

    if (!($result instanceof mysqli_result)) {
        return $items;
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
    }

    return $items;
}

function store_product_label_by_id(mysqli $connection, int $labelId): ?array
{
    $stmt = @mysqli_prepare($connection, 'SELECT id, name, slug, color_hex, description_text, sort_order FROM product_labels WHERE id = ? LIMIT 1');

    if (!($stmt instanceof mysqli_stmt)) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 'i', $labelId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result instanceof mysqli_result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    return $row ?: null;
}

function store_cms_product_label_id(mysqli $connection, string $slug): int
{
    $stmt = @mysqli_prepare($connection, 'SELECT id FROM product_labels WHERE slug = ? LIMIT 1');

    if (!($stmt instanceof mysqli_stmt)) {
        return 0;
    }

    mysqli_stmt_bind_param($stmt, 's', $slug);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result instanceof mysqli_result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    return (int) ($row['id'] ?? 0);
}

function store_save_product_label(mysqli $connection, array $data): array
{
    $labelId = (int) ($data['label_id'] ?? 0);
    $name = trim((string) ($data['name'] ?? ''));
    $slug = trim((string) ($data['slug'] ?? ''));
    $color = trim((string) ($data['color_hex'] ?? '#0b1688'));
    $description = trim((string) ($data['description_text'] ?? ''));
    $sortOrder = (int) ($data['sort_order'] ?? 0);

    if ($name === '') {
        return ['success' => false, 'message' => 'Label name is required.'];
    }

    $slug = $slug !== '' ? store_slugify($slug) : store_slugify($name);
    if ($slug === '') {
        $slug = 'label';
    }

    if (!preg_match('/^#?[0-9a-f]{6}$/i', $color)) {
        $color = '#0b1688';
    }
    if ($color[0] !== '#') {
        $color = '#' . $color;
    }

    $existingId = store_cms_product_label_id($connection, $slug);
    if ($existingId > 0 && $existingId !== $labelId) {
        return ['success' => false, 'message' => 'Another label already uses this slug.'];
    }

    if ($labelId > 0) {
        $stmt = @mysqli_prepare(
            $connection,
            'UPDATE product_labels SET name = ?, slug = ?, color_hex = ?, description_text = ?, sort_order = ? WHERE id = ?'
        );

        if (!($stmt instanceof mysqli_stmt)) {
            return ['success' => false, 'message' => 'Label could not be updated.'];
        }

        mysqli_stmt_bind_param($stmt, 'ssssii', $name, $slug, $color, $description, $sortOrder, $labelId);
        $saved = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return ['success' => $saved, 'message' => $saved ? 'Label updated successfully.' : 'Label could not be updated.'];
    }

    $stmt = @mysqli_prepare(
        $connection,
        'INSERT INTO product_labels (name, slug, color_hex, description_text, sort_order) VALUES (?, ?, ?, ?, ?)'
    );

    if (!($stmt instanceof mysqli_stmt)) {
        return ['success' => false, 'message' => 'Label could not be saved.'];
    }

    mysqli_stmt_bind_param($stmt, 'ssssi', $name, $slug, $color, $description, $sortOrder);
    $saved = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return ['success' => $saved, 'message' => $saved ? 'Label saved successfully.' : 'Label could not be saved.'];
}

function store_delete_product_label(mysqli $connection, int $labelId): array
{
    if ($labelId <= 0) {
        return ['success' => false, 'message' => 'Label could not be found.'];
    }

    $stmt = @mysqli_prepare($connection, 'DELETE FROM product_labels WHERE id = ?');

    if (!($stmt instanceof mysqli_stmt)) {
        return ['success' => false, 'message' => 'Label could not be deleted.'];
    }

    mysqli_stmt_bind_param($stmt, 'i', $labelId);
    $saved = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return ['success' => $saved, 'message' => $saved ? 'Label deleted successfully.' : 'Label could not be deleted.'];
}

function store_product_label_index_by_product_ids(mysqli $connection, array $productIds): array
{
    $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds))));
    $index = [];

    if ($productIds === [] || !store_table_exists($connection, 'product_label_assignments')) {
        return $index;
    }

    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $types = str_repeat('i', count($productIds));
    $stmt = @mysqli_prepare(
        $connection,
        'SELECT a.product_id, l.id, l.name, l.slug, l.color_hex, l.description_text
         FROM product_label_assignments a
         INNER JOIN product_labels l ON l.id = a.label_id
         WHERE a.product_id IN (' . $placeholders . ')
         ORDER BY l.sort_order ASC, l.name ASC'
    );

    if (!($stmt instanceof mysqli_stmt)) {
        return $index;
    }

    mysqli_stmt_bind_param($stmt, $types, ...$productIds);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result instanceof mysqli_result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $productId = (int) ($row['product_id'] ?? 0);
            unset($row['product_id']);
            $index[$productId][] = $row;
        }
    }

    mysqli_stmt_close($stmt);
    return $index;
}

function store_product_labels_for_product(mysqli $connection, int $productId): array
{
    $index = store_product_label_index_by_product_ids($connection, [$productId]);
    return $index[$productId] ?? [];
}

function store_sync_product_labels(mysqli $connection, int $productId, array $labelIds): void
{
    if ($productId <= 0 || !store_table_exists($connection, 'product_label_assignments')) {
        return;
    }

    $labelIds = array_values(array_unique(array_filter(array_map('intval', $labelIds))));

    @mysqli_query($connection, 'DELETE FROM product_label_assignments WHERE product_id = ' . $productId);

    if ($labelIds === []) {
        return;
    }

    $stmt = @mysqli_prepare($connection, 'INSERT INTO product_label_assignments (product_id, label_id) VALUES (?, ?)');

    if (!($stmt instanceof mysqli_stmt)) {
        return;
    }

    foreach ($labelIds as $labelId) {
        mysqli_stmt_bind_param($stmt, 'ii', $productId, $labelId);
        mysqli_stmt_execute($stmt);
    }

    mysqli_stmt_close($stmt);
}

function store_attach_product_label(mysqli $connection, int $productId, int $labelId): array
{
    if ($productId <= 0 || $labelId <= 0) {
        return ['success' => false, 'message' => 'Product ya label missing hai.'];
    }

    $stmt = @mysqli_prepare(
        $connection,
        'INSERT INTO product_label_assignments (product_id, label_id) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE created_at = created_at'
    );

    if (!($stmt instanceof mysqli_stmt)) {
        return ['success' => false, 'message' => 'Label assign nahin ho saka.'];
    }

    mysqli_stmt_bind_param($stmt, 'ii', $productId, $labelId);
    $saved = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return ['success' => $saved, 'message' => $saved ? 'Label assign ho gaya.' : 'Label assign nahin ho saka.'];
}

function store_detach_product_label(mysqli $connection, int $productId, int $labelId): array
{
    if ($productId <= 0 || $labelId <= 0) {
        return ['success' => false, 'message' => 'Product ya label missing hai.'];
    }

    $stmt = @mysqli_prepare($connection, 'DELETE FROM product_label_assignments WHERE product_id = ? AND label_id = ?');

    if (!($stmt instanceof mysqli_stmt)) {
        return ['success' => false, 'message' => 'Label remove nahin ho saka.'];
    }

    mysqli_stmt_bind_param($stmt, 'ii', $productId, $labelId);
    $saved = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return ['success' => $saved, 'message' => $saved ? 'Label remove ho gaya.' : 'Label remove nahin ho saka.'];
}

function store_cms_extract_label_ids(mysqli $connection, $rawLabels): array
{
    if (is_string($rawLabels)) {
        $decoded = json_decode($rawLabels, true);
        if (is_array($decoded)) {
            $rawLabels = $decoded;
        } else {
            $rawLabels = store_cms_comma_list($rawLabels);
        }
    }

    if (!is_array($rawLabels)) {
        return [];
    }

    $labelIds = [];

    foreach ($rawLabels as $item) {
        if (is_array($item)) {
            if (!empty($item['id'])) {
                $labelIds[] = (int) $item['id'];
                continue;
            }

            $result = store_save_product_label($connection, [
                'label_id' => 0,
                'name' => (string) ($item['name'] ?? ''),
                'slug' => (string) ($item['slug'] ?? ''),
                'color_hex' => (string) ($item['color'] ?? ($item['color_hex'] ?? '#0b1688')),
                'description_text' => (string) ($item['description'] ?? ($item['description_text'] ?? '')),
                'sort_order' => (int) ($item['sort_order'] ?? 0),
            ]);

            if (($result['success'] ?? false) === true) {
                $slug = store_slugify((string) ($item['slug'] ?? ($item['name'] ?? '')));
                $labelIds[] = store_cms_product_label_id($connection, $slug);
            }
            continue;
        }

        $value = trim((string) $item);
        if ($value === '') {
            continue;
        }

        if (ctype_digit($value)) {
            $labelIds[] = (int) $value;
            continue;
        }

        $slug = store_slugify($value);
        $existingId = store_cms_product_label_id($connection, $slug);

        if ($existingId <= 0) {
            store_save_product_label($connection, ['name' => $value, 'slug' => $slug]);
            $existingId = store_cms_product_label_id($connection, $slug);
        }

        if ($existingId > 0) {
            $labelIds[] = $existingId;
        }
    }

    return array_values(array_unique(array_filter(array_map('intval', $labelIds))));
}

function store_brand_catalog(mysqli $connection): array
{
    $items = [];
    $result = @mysqli_query(
        $connection,
        'SELECT
            b.id,
            b.name,
            b.slug,
            b.logo_path,
            b.hero_title,
            b.hero_summary,
            b.slider_banner_path,
            b.hero_poster_path,
            b.hero_video_path,
            b.splash_video_path,
            b.splash_logo_path,
            b.featured_product_slug,
            b.badge_json,
            b.is_visible,
            b.sort_order,
            COUNT(p.id) AS product_count
        FROM brands b
        LEFT JOIN products p ON p.brand_id = b.id
        GROUP BY
            b.id, b.name, b.slug, b.logo_path, b.hero_title, b.hero_summary, b.slider_banner_path,
            b.hero_poster_path, b.hero_video_path, b.splash_video_path, b.splash_logo_path,
            b.featured_product_slug, b.badge_json, b.is_visible, b.sort_order
        ORDER BY b.sort_order ASC, b.name ASC'
    );

    if (!($result instanceof mysqli_result)) {
        return $items;
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $row['logo_path'] = store_normalize_brand_logo_path((string) ($row['slug'] ?? ''), (string) ($row['logo_path'] ?? ''));
        $decodedBadges = json_decode((string) ($row['badge_json'] ?? ''), true);
        $row['badges'] = is_array($decodedBadges)
            ? array_values(array_filter(array_map('strval', $decodedBadges)))
            : store_cms_comma_list((string) ($row['badge_json'] ?? ''));
        $items[] = $row;
    }

    return $items;
}

function store_brand_by_id(mysqli $connection, int $brandId): ?array
{
    foreach (store_brand_catalog($connection) as $brand) {
        if ((int) ($brand['id'] ?? 0) === $brandId) {
            return $brand;
        }
    }

    return null;
}

function store_category_catalog(mysqli $connection): array
{
    $items = [];
    $result = @mysqli_query(
        $connection,
        'SELECT
            c.id,
            c.name,
            c.slug,
            c.note,
            c.document_path,
            c.hero_headline,
            c.banner_image_path,
            c.hero_video_path,
            c.sort_order,
            COUNT(p.id) AS product_count
        FROM categories c
        LEFT JOIN products p ON p.category_id = c.id
        GROUP BY
            c.id, c.name, c.slug, c.note, c.document_path, c.hero_headline, c.banner_image_path, c.hero_video_path, c.sort_order
        ORDER BY c.sort_order ASC, c.name ASC'
    );

    if (!($result instanceof mysqli_result)) {
        return $items;
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
    }

    return $items;
}

function store_category_by_id(mysqli $connection, int $categoryId): ?array
{
    foreach (store_category_catalog($connection) as $category) {
        if ((int) ($category['id'] ?? 0) === $categoryId) {
            return $category;
        }
    }

    return null;
}

function store_save_brand_profile(mysqli $connection, array $post, array $files = []): array
{
    $brandId = (int) ($post['brand_id'] ?? 0);
    $name = trim((string) ($post['name'] ?? ''));
    $slug = trim((string) ($post['slug'] ?? ''));
    $existingBrand = $brandId > 0 ? store_brand_by_id($connection, $brandId) : null;

    if ($name === '') {
        return ['success' => false, 'message' => 'Brand name is required.'];
    }

    $slug = $slug !== '' ? store_slugify($slug) : store_slugify($name);
    $existingBrandId = store_term_id($connection, 'brands', $slug);
    if ($existingBrandId > 0 && $existingBrandId !== $brandId) {
        $brandId = $existingBrandId;
        $existingBrand = store_brand_by_id($connection, $brandId);
    }

    $logoUpload = store_upload_brand_logo($files['logo_file'] ?? [], false);
    if (($logoUpload['success'] ?? false) !== true) {
        return ['success' => false, 'message' => $logoUpload['message'] ?? 'Brand logo upload failed.'];
    }

    $sliderUpload = store_cms_upload_image_asset($files['slider_banner_file'] ?? [], false, 'brand-assets', 'slider_');
    $posterUpload = store_cms_upload_image_asset($files['hero_poster_file'] ?? [], false, 'brand-assets', 'poster_');
    $heroVideoUpload = store_cms_upload_video_asset($files['hero_video_file'] ?? [], false, 'brand-videos', 'hero_');
    $splashVideoUpload = store_cms_upload_video_asset($files['splash_video_file'] ?? [], false, 'brand-videos', 'splash_');
    $splashLogoUpload = store_cms_upload_image_asset($files['splash_logo_file'] ?? [], false, 'brand-assets', 'splash_logo_');

    foreach ([$sliderUpload, $posterUpload, $heroVideoUpload, $splashVideoUpload, $splashLogoUpload] as $upload) {
        if (($upload['success'] ?? false) !== true) {
            return ['success' => false, 'message' => (string) ($upload['message'] ?? 'Brand asset upload failed.')];
        }
    }

    $logoPath = trim((string) ($post['logo_path'] ?? ''));
    if (!empty($logoUpload['path'])) {
        $logoPath = (string) $logoUpload['path'];
    }
    $logoPath = store_normalize_brand_logo_path($slug, $logoPath);

    $sliderBannerPath = trim((string) ($post['slider_banner_path'] ?? ''));
    $heroPosterPath = trim((string) ($post['hero_poster_path'] ?? ''));
    $heroVideoPath = trim((string) ($post['hero_video_path'] ?? ''));
    $splashVideoPath = trim((string) ($post['splash_video_path'] ?? ''));
    $splashLogoPath = trim((string) ($post['splash_logo_path'] ?? ''));

    if (!empty($sliderUpload['path'])) {
        $sliderBannerPath = (string) $sliderUpload['path'];
    }
    if (!empty($posterUpload['path'])) {
        $heroPosterPath = (string) $posterUpload['path'];
    }
    if (!empty($heroVideoUpload['path'])) {
        $heroVideoPath = (string) $heroVideoUpload['path'];
    }
    if (!empty($splashVideoUpload['path'])) {
        $splashVideoPath = (string) $splashVideoUpload['path'];
    }
    if (!empty($splashLogoUpload['path'])) {
        $splashLogoPath = (string) $splashLogoUpload['path'];
    }

    $heroTitle = trim((string) ($post['hero_title'] ?? ''));
    $heroSummary = trim((string) ($post['hero_summary'] ?? ''));
    $featuredProductSlug = trim((string) ($post['featured_product_slug'] ?? ''));
    $badges = store_cms_comma_list((string) ($post['badges'] ?? ''));
    $sortOrder = (int) ($post['sort_order'] ?? 0);
    $isVisible = trim((string) ($post['is_visible'] ?? '1')) !== '' ? 1 : 0;

    if ($existingBrand) {
        $logoPath = $logoPath !== '' ? $logoPath : (string) ($existingBrand['logo_path'] ?? '');
        $sliderBannerPath = $sliderBannerPath !== '' ? $sliderBannerPath : (string) ($existingBrand['slider_banner_path'] ?? '');
        $heroPosterPath = $heroPosterPath !== '' ? $heroPosterPath : (string) ($existingBrand['hero_poster_path'] ?? '');
        $heroVideoPath = $heroVideoPath !== '' ? $heroVideoPath : (string) ($existingBrand['hero_video_path'] ?? '');
        $splashVideoPath = $splashVideoPath !== '' ? $splashVideoPath : (string) ($existingBrand['splash_video_path'] ?? '');
        $splashLogoPath = $splashLogoPath !== '' ? $splashLogoPath : (string) ($existingBrand['splash_logo_path'] ?? '');
        $heroTitle = $heroTitle !== '' ? $heroTitle : (string) ($existingBrand['hero_title'] ?? '');
        $heroSummary = $heroSummary !== '' ? $heroSummary : (string) ($existingBrand['hero_summary'] ?? '');
        $featuredProductSlug = $featuredProductSlug !== '' ? $featuredProductSlug : (string) ($existingBrand['featured_product_slug'] ?? '');
        $badges = $badges !== [] ? $badges : ($existingBrand['badges'] ?? []);
    }

    $badgeJson = json_encode($badges, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    if ($brandId > 0) {
        $stmt = @mysqli_prepare(
            $connection,
            'UPDATE brands
             SET name = ?, slug = ?, logo_path = ?, hero_title = ?, hero_summary = ?, slider_banner_path = ?, hero_poster_path = ?, hero_video_path = ?, splash_video_path = ?, splash_logo_path = ?, featured_product_slug = ?, badge_json = ?, is_visible = ?, sort_order = ?
             WHERE id = ?'
        );

        if (!($stmt instanceof mysqli_stmt)) {
            return ['success' => false, 'message' => 'Brand could not be updated.'];
        }

        mysqli_stmt_bind_param(
            $stmt,
            'ssssssssssssiii',
            $name,
            $slug,
            $logoPath,
            $heroTitle,
            $heroSummary,
            $sliderBannerPath,
            $heroPosterPath,
            $heroVideoPath,
            $splashVideoPath,
            $splashLogoPath,
            $featuredProductSlug,
            $badgeJson,
            $isVisible,
            $sortOrder,
            $brandId
        );
        $saved = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return ['success' => $saved, 'message' => $saved ? 'Brand updated successfully.' : 'Brand could not be updated.'];
    }

    $stmt = @mysqli_prepare(
        $connection,
        'INSERT INTO brands (
            name, slug, logo_path, hero_title, hero_summary, slider_banner_path, hero_poster_path,
            hero_video_path, splash_video_path, splash_logo_path, featured_product_slug, badge_json, is_visible, sort_order
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    if (!($stmt instanceof mysqli_stmt)) {
        return ['success' => false, 'message' => 'Brand could not be saved.'];
    }

    mysqli_stmt_bind_param(
        $stmt,
        'ssssssssssssii',
        $name,
        $slug,
        $logoPath,
        $heroTitle,
        $heroSummary,
        $sliderBannerPath,
        $heroPosterPath,
        $heroVideoPath,
        $splashVideoPath,
        $splashLogoPath,
        $featuredProductSlug,
        $badgeJson,
        $isVisible,
        $sortOrder
    );
    $saved = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return ['success' => $saved, 'message' => $saved ? 'Brand saved successfully.' : 'Brand could not be saved.'];
}

function store_save_category_profile(mysqli $connection, array $post, array $files = []): array
{
    $categoryId = (int) ($post['category_id'] ?? 0);
    $name = trim((string) ($post['name'] ?? ''));
    $slug = trim((string) ($post['slug'] ?? ''));
    $existingCategory = $categoryId > 0 ? store_category_by_id($connection, $categoryId) : null;

    if ($name === '') {
        return ['success' => false, 'message' => 'Category name is required.'];
    }

    $slug = $slug !== '' ? store_slugify($slug) : store_slugify($name);
    $existingCategoryId = store_term_id($connection, 'categories', $slug);
    if ($existingCategoryId > 0 && $existingCategoryId !== $categoryId) {
        $categoryId = $existingCategoryId;
        $existingCategory = store_category_by_id($connection, $categoryId);
    }
    $documentUpload = store_cms_upload_document_asset($files['document_file'] ?? [], false);
    $bannerUpload = store_cms_upload_image_asset($files['banner_image_file'] ?? [], false, 'category-assets', 'banner_');
    $videoUpload = store_cms_upload_video_asset($files['hero_video_file'] ?? [], false, 'category-videos', 'category_');

    foreach ([$documentUpload, $bannerUpload, $videoUpload] as $upload) {
        if (($upload['success'] ?? false) !== true) {
            return ['success' => false, 'message' => (string) ($upload['message'] ?? 'Category asset upload failed.')];
        }
    }

    $note = trim((string) ($post['note'] ?? ''));
    $documentPath = trim((string) ($post['document_path'] ?? ''));
    $heroHeadline = trim((string) ($post['hero_headline'] ?? ''));
    $bannerImagePath = trim((string) ($post['banner_image_path'] ?? ''));
    $heroVideoPath = trim((string) ($post['hero_video_path'] ?? ''));
    $sortOrder = (int) ($post['sort_order'] ?? 0);

    if (!empty($documentUpload['path'])) {
        $documentPath = (string) $documentUpload['path'];
    }
    if (!empty($bannerUpload['path'])) {
        $bannerImagePath = (string) $bannerUpload['path'];
    }
    if (!empty($videoUpload['path'])) {
        $heroVideoPath = (string) $videoUpload['path'];
    }

    if ($existingCategory) {
        $note = $note !== '' ? $note : (string) ($existingCategory['note'] ?? '');
        $documentPath = $documentPath !== '' ? $documentPath : (string) ($existingCategory['document_path'] ?? '');
        $heroHeadline = $heroHeadline !== '' ? $heroHeadline : (string) ($existingCategory['hero_headline'] ?? '');
        $bannerImagePath = $bannerImagePath !== '' ? $bannerImagePath : (string) ($existingCategory['banner_image_path'] ?? '');
        $heroVideoPath = $heroVideoPath !== '' ? $heroVideoPath : (string) ($existingCategory['hero_video_path'] ?? '');
    }

    $note = $note !== '' ? $note : $name . ' category';
    $documentPath = $documentPath !== '' ? $documentPath : 'docs/haier-frostcool-inverter-ac.doc';

    if ($categoryId > 0) {
        $stmt = @mysqli_prepare(
            $connection,
            'UPDATE categories
             SET name = ?, slug = ?, note = ?, document_path = ?, hero_headline = ?, banner_image_path = ?, hero_video_path = ?, sort_order = ?
             WHERE id = ?'
        );

        if (!($stmt instanceof mysqli_stmt)) {
            return ['success' => false, 'message' => 'Category could not be updated.'];
        }

        mysqli_stmt_bind_param($stmt, 'sssssssii', $name, $slug, $note, $documentPath, $heroHeadline, $bannerImagePath, $heroVideoPath, $sortOrder, $categoryId);
        $saved = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return ['success' => $saved, 'message' => $saved ? 'Category updated successfully.' : 'Category could not be updated.'];
    }

    $stmt = @mysqli_prepare(
        $connection,
        'INSERT INTO categories (name, slug, note, document_path, hero_headline, banner_image_path, hero_video_path, sort_order)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );

    if (!($stmt instanceof mysqli_stmt)) {
        return ['success' => false, 'message' => 'Category could not be saved.'];
    }

    mysqli_stmt_bind_param($stmt, 'sssssssi', $name, $slug, $note, $documentPath, $heroHeadline, $bannerImagePath, $heroVideoPath, $sortOrder);
    $saved = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return ['success' => $saved, 'message' => $saved ? 'Category saved successfully.' : 'Category could not be saved.'];
}

function store_delete_brand_profile(mysqli $connection, int $brandId): array
{
    if ($brandId <= 0) {
        return ['success' => false, 'message' => 'Brand could not be found.'];
    }

    $stmt = @mysqli_prepare($connection, 'SELECT COUNT(*) AS total FROM products WHERE brand_id = ?');
    if ($stmt instanceof mysqli_stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $brandId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result instanceof mysqli_result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);
        if ((int) ($row['total'] ?? 0) > 0) {
            return ['success' => false, 'message' => 'Is brand ke sath products linked hain. Pehle products move ya delete karo.'];
        }
    }

    $deleteStmt = @mysqli_prepare($connection, 'DELETE FROM brands WHERE id = ?');
    if (!($deleteStmt instanceof mysqli_stmt)) {
        return ['success' => false, 'message' => 'Brand could not be deleted.'];
    }

    mysqli_stmt_bind_param($deleteStmt, 'i', $brandId);
    $saved = mysqli_stmt_execute($deleteStmt);
    mysqli_stmt_close($deleteStmt);

    return ['success' => $saved, 'message' => $saved ? 'Brand deleted successfully.' : 'Brand could not be deleted.'];
}

function store_delete_category_profile(mysqli $connection, int $categoryId): array
{
    if ($categoryId <= 0) {
        return ['success' => false, 'message' => 'Category could not be found.'];
    }

    $stmt = @mysqli_prepare($connection, 'SELECT COUNT(*) AS total FROM products WHERE category_id = ?');
    if ($stmt instanceof mysqli_stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $categoryId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result instanceof mysqli_result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);
        if ((int) ($row['total'] ?? 0) > 0) {
            return ['success' => false, 'message' => 'Is category ke sath products linked hain. Pehle products move ya delete karo.'];
        }
    }

    $deleteStmt = @mysqli_prepare($connection, 'DELETE FROM categories WHERE id = ?');
    if (!($deleteStmt instanceof mysqli_stmt)) {
        return ['success' => false, 'message' => 'Category could not be deleted.'];
    }

    mysqli_stmt_bind_param($deleteStmt, 'i', $categoryId);
    $saved = mysqli_stmt_execute($deleteStmt);
    mysqli_stmt_close($deleteStmt);

    return ['success' => $saved, 'message' => $saved ? 'Category deleted successfully.' : 'Category could not be deleted.'];
}

function store_cms_brand_route_map(mysqli $connection): array
{
    $routes = [];

    foreach (store_brand_catalog($connection) as $brand) {
        $routes[(string) $brand['slug']] = store_app_relative_url('brand.php?brand=' . rawurlencode((string) $brand['slug']));
    }

    return $routes;
}

function store_cms_category_meta_payload(mysqli $connection): array
{
    $items = [];

    foreach (store_category_catalog($connection) as $category) {
        $items[(string) $category['slug']] = [
            'label' => (string) $category['name'],
            'doc' => './' . ltrim((string) ($category['document_path'] ?? 'docs/haier-frostcool-inverter-ac.doc'), './'),
            'note' => (string) ($category['note'] ?? ''),
            'heroHeadline' => (string) ($category['hero_headline'] ?? ''),
            'bannerImage' => (string) ($category['banner_image_path'] ?? ''),
            'heroVideo' => (string) ($category['hero_video_path'] ?? ''),
        ];
    }

    return $items;
}

function store_cms_brand_experience_payload(mysqli $connection): array
{
    $items = [];

    foreach (store_brand_catalog($connection) as $brand) {
        $items[(string) $brand['slug']] = [
            'splashPageName' => trim((string) ($brand['name'] ?? '')) . ' Splash Page',
            'featuredProductSlug' => (string) ($brand['featured_product_slug'] ?? ''),
            'featuredProductLabel' => (string) ($brand['hero_title'] ?? ''),
            'heroImage' => (string) ($brand['slider_banner_path'] ?? ''),
            'heroPoster' => (string) ($brand['hero_poster_path'] ?? ''),
            'heroVideo' => (string) ($brand['hero_video_path'] ?? ''),
            'splashVideo' => (string) ($brand['splash_video_path'] ?? ''),
            'splashLogo' => (string) ($brand['splash_logo_path'] ?? ''),
            'headline' => (string) ($brand['hero_title'] ?? ''),
            'summary' => (string) ($brand['hero_summary'] ?? ''),
            'tags' => $brand['badges'] ?? [],
        ];
    }

    return $items;
}

function store_cms_site_media_payload(mysqli $connection): array
{
    $kitchenPromo = store_setting($connection, 'home_kitchen_promo', 'img/banner/ketchen.webp');
    $kitchenAlt = store_setting($connection, 'home_kitchen_promo_alt', 'La Imperial kitchen highlight visual');
    $smartPromo = store_setting($connection, 'home_smart_promo', 'img/banner/smart.webp');
    $smartAlt = store_setting($connection, 'home_smart_promo_alt', 'Kitchen edit promotional poster');
    $gallery = store_cms_safe_json_list(store_setting($connection, 'home_gallery_images_json', '[]'));

    return [
        'siteLogo' => [
            'src' => store_setting($connection, 'site_logo_path', 'img/logo/logo.png'),
            'alt' => store_setting($connection, 'site_logo_alt', 'La Imparial'),
        ],
        'splashLogo' => store_setting($connection, 'site_splash_logo', 'img/logo/image.png'),
        'homeKitchenPromo' => [
            'src' => $kitchenPromo,
            'alt' => $kitchenAlt,
        ],
        'homeEssentialGallery' => $gallery,
        'homeSmartPromo' => [
            'src' => $smartPromo,
            'alt' => $smartAlt,
        ],
        'brandHighlightImages' => [],
    ];
}

function store_cms_catalog_export(mysqli $connection): array
{
    $products = store_all_products($connection);
    $labelsByProduct = store_product_label_index_by_product_ids($connection, array_map('intval', array_column($products, 'id')));

    foreach ($products as &$product) {
        $product['gallery'] = store_parse_gallery_images($product['gallery_images'] ?? '');
        $product['labels'] = $labelsByProduct[(int) ($product['id'] ?? 0)] ?? [];
    }
    unset($product);

    return [
        'success' => true,
        'brands' => store_brand_catalog($connection),
        'categories' => store_category_catalog($connection),
        'labels' => store_product_labels($connection),
        'products' => $products,
        'settings' => array_intersect_key(store_settings($connection), array_flip(store_cms_site_setting_keys())),
    ];
}

function store_cms_upsert_product_from_array(mysqli $connection, array $item, string $brandOverride = ''): array
{
    $name = trim((string) ($item['name'] ?? $item['title'] ?? ''));
    if ($name === '') {
        return ['success' => false, 'message' => 'Product name missing.'];
    }

    $slug = trim((string) ($item['slug'] ?? ''));
    $slug = $slug !== '' ? store_slug_from_text($slug) : store_slug_from_text($name);
    $brandName = trim((string) ($brandOverride !== '' ? $brandOverride : ($item['brand_name'] ?? $item['brand'] ?? $item['brandSlug'] ?? 'General')));
    $brandSlug = trim((string) ($item['brand_slug'] ?? ''));
    $categoryName = trim((string) ($item['category_name'] ?? $item['categoryLabel'] ?? $item['category'] ?? 'General'));
    $categorySlug = trim((string) ($item['category_slug'] ?? ''));
    $summary = trim((string) ($item['summary'] ?? $item['description'] ?? ''));
    $description = trim((string) ($item['description'] ?? $summary));
    $documentPath = trim((string) ($item['document_path'] ?? $item['document'] ?? 'docs/haier-frostcool-inverter-ac.doc'));
    $imagePath = trim((string) ($item['image_path'] ?? $item['thumbnail'] ?? $item['image'] ?? 'img/logo/logo.png'));
    $price = (float) ($item['price'] ?? 0);
    $originalPrice = trim((string) ($item['original_price'] ?? '')) !== '' ? (float) $item['original_price'] : (float) ($item['old_price'] ?? 0);
    $discountPercent = (int) ($item['discount_percent'] ?? $item['discountPercentage'] ?? 0);
    $isFeatured = (int) (!empty($item['is_featured']) || !empty($item['featured']) || !empty($item['isTrending']));
    $customLabel = trim((string) ($item['custom_label'] ?? $item['customLabel'] ?? ''));

    if ($summary === '') {
        $summary = $name . ' catalog summary';
    }
    if ($description === '') {
        $description = $summary;
    }
    if ($price <= 0) {
        $price = 69990;
    }
    if ($originalPrice <= 0) {
        $originalPrice = $discountPercent > 0 ? (float) round($price / (1 - ($discountPercent / 100))) : $price;
    }
    if ($discountPercent <= 0 && $originalPrice > $price) {
        $discountPercent = (int) round((1 - ($price / $originalPrice)) * 100);
    }

    $brandId = $brandSlug !== '' ? store_term_id($connection, 'brands', store_slugify($brandSlug)) : 0;
    if ($brandId <= 0) {
        $brandId = store_find_or_create_brand($connection, $brandName, (string) ($item['brand_logo'] ?? $item['logo_path'] ?? ''));
    }

    $categoryId = $categorySlug !== '' ? store_term_id($connection, 'categories', store_slugify($categorySlug)) : 0;
    if ($categoryId <= 0) {
        $categoryId = store_find_or_create_term($connection, 'categories', $categoryName);
    }

    if ($brandId <= 0 || $categoryId <= 0) {
        return ['success' => false, 'message' => 'Brand ya category create nahin ho saka.'];
    }

    $gallery = $item['gallery'] ?? $item['gallery_images'] ?? [];
    if (is_string($gallery)) {
        $decoded = json_decode($gallery, true);
        $gallery = is_array($decoded) ? $decoded : [];
    }
    $galleryPaths = [];
    if (is_array($gallery)) {
        foreach ($gallery as $galleryItem) {
            $path = trim((string) (is_array($galleryItem) ? ($galleryItem['src'] ?? '') : $galleryItem));
            if ($path !== '') {
                $galleryPaths[] = $path;
            }
        }
    }
    $galleryJson = store_encode_gallery_images($galleryPaths);

    $stmt = @mysqli_prepare(
        $connection,
        'INSERT INTO products (
            name, slug, brand_id, category_id, summary, description, document_path, image_path,
            price, original_price, discount_percent, is_featured, gallery_images, custom_label, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            brand_id = VALUES(brand_id),
            category_id = VALUES(category_id),
            summary = VALUES(summary),
            description = VALUES(description),
            document_path = VALUES(document_path),
            image_path = VALUES(image_path),
            price = VALUES(price),
            original_price = VALUES(original_price),
            discount_percent = VALUES(discount_percent),
            is_featured = VALUES(is_featured),
            gallery_images = VALUES(gallery_images),
            custom_label = VALUES(custom_label)'
    );

    if (!($stmt instanceof mysqli_stmt)) {
        return ['success' => false, 'message' => 'Product could not be prepared.'];
    }

    $createdBy = store_current_user_id();
    mysqli_stmt_bind_param(
        $stmt,
        'ssiissssddiissi',
        $name,
        $slug,
        $brandId,
        $categoryId,
        $summary,
        $description,
        $documentPath,
        $imagePath,
        $price,
        $originalPrice,
        $discountPercent,
        $isFeatured,
        $galleryJson,
        $customLabel,
        $createdBy
    );
    $saved = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if (!$saved) {
        return ['success' => false, 'message' => 'Product could not be synced.'];
    }

    $productId = store_product_id_by_slug($connection, $slug);
    $labelIds = store_cms_extract_label_ids($connection, $item['labels'] ?? []);
    store_sync_product_labels($connection, $productId, $labelIds);

    return ['success' => true, 'product_id' => $productId, 'slug' => $slug];
}

function store_cms_sync_catalog_payload(mysqli $connection, array $payload, string $brandOverride = '', int $limit = 20): array
{
    $createdProducts = 0;
    $productsData = [];

    if (isset($payload['brands']) && is_array($payload['brands'])) {
        foreach ($payload['brands'] as $brand) {
            if (!is_array($brand)) {
                continue;
            }
            store_save_brand_profile($connection, [
                'brand_id' => (int) ($brand['id'] ?? 0),
                'name' => (string) ($brand['name'] ?? ''),
                'slug' => (string) ($brand['slug'] ?? ''),
                'logo_path' => (string) ($brand['logo_path'] ?? $brand['logo'] ?? ''),
                'hero_title' => (string) ($brand['hero_title'] ?? $brand['headline'] ?? ''),
                'hero_summary' => (string) ($brand['hero_summary'] ?? $brand['summary'] ?? ''),
                'slider_banner_path' => (string) ($brand['slider_banner_path'] ?? $brand['heroImage'] ?? ''),
                'hero_poster_path' => (string) ($brand['hero_poster_path'] ?? $brand['heroPoster'] ?? ''),
                'hero_video_path' => (string) ($brand['hero_video_path'] ?? $brand['heroVideo'] ?? ''),
                'splash_video_path' => (string) ($brand['splash_video_path'] ?? $brand['splashVideo'] ?? ''),
                'splash_logo_path' => (string) ($brand['splash_logo_path'] ?? $brand['splashLogo'] ?? ''),
                'featured_product_slug' => (string) ($brand['featured_product_slug'] ?? $brand['featuredProductSlug'] ?? ''),
                'badges' => is_array($brand['tags'] ?? null) ? implode(', ', $brand['tags']) : (string) ($brand['badges'] ?? ''),
                'sort_order' => (int) ($brand['sort_order'] ?? 0),
                'is_visible' => !isset($brand['is_visible']) || (int) $brand['is_visible'] === 1 ? '1' : '',
            ]);
        }
    }

    if (isset($payload['categories']) && is_array($payload['categories'])) {
        foreach ($payload['categories'] as $category) {
            if (!is_array($category)) {
                continue;
            }
            store_save_category_profile($connection, [
                'category_id' => (int) ($category['id'] ?? 0),
                'name' => (string) ($category['name'] ?? $category['label'] ?? ''),
                'slug' => (string) ($category['slug'] ?? ''),
                'note' => (string) ($category['note'] ?? ''),
                'document_path' => (string) ($category['document_path'] ?? $category['doc'] ?? ''),
                'hero_headline' => (string) ($category['hero_headline'] ?? $category['heroHeadline'] ?? ''),
                'banner_image_path' => (string) ($category['banner_image_path'] ?? $category['bannerImage'] ?? ''),
                'hero_video_path' => (string) ($category['hero_video_path'] ?? $category['heroVideo'] ?? ''),
                'sort_order' => (int) ($category['sort_order'] ?? 0),
            ]);
        }
    }

    if (isset($payload['labels']) && is_array($payload['labels'])) {
        foreach ($payload['labels'] as $label) {
            if (!is_array($label)) {
                continue;
            }
            store_save_product_label($connection, [
                'label_id' => (int) ($label['id'] ?? 0),
                'name' => (string) ($label['name'] ?? ''),
                'slug' => (string) ($label['slug'] ?? ''),
                'color_hex' => (string) ($label['color_hex'] ?? $label['color'] ?? '#0b1688'),
                'description_text' => (string) ($label['description_text'] ?? $label['description'] ?? ''),
                'sort_order' => (int) ($label['sort_order'] ?? 0),
            ]);
        }
    }

    if (isset($payload['products']) && is_array($payload['products'])) {
        $productsData = $payload['products'];
    } elseif (isset($payload['items']) && is_array($payload['items'])) {
        $productsData = $payload['items'];
    } elseif (array_values($payload) === $payload) {
        $productsData = $payload;
    }

    foreach (array_slice($productsData, 0, max(1, min(100, $limit))) as $item) {
        if (!is_array($item)) {
            continue;
        }

        $result = store_cms_upsert_product_from_array($connection, $item, $brandOverride);
        if (($result['success'] ?? false) === true) {
            $createdProducts++;
        }
    }

    return [
        'success' => true,
        'message' => $createdProducts > 0
            ? $createdProducts . ' products synced successfully.'
            : 'Catalog metadata processed successfully.',
        'synced_products' => $createdProducts,
    ];
}
