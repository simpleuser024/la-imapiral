<?php
declare(strict_types=1);

require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/includes/store.php';

ensure_store_state();

$checks = [
    'Database Connection' => $con instanceof mysqli ? '✓ Connected' : '✗ Connection Failed',
];

// Check tables
foreach (['categories', 'brands', 'products', 'cart_items', 'favorites'] as $table) {
    $exists = store_table_exists($con, $table);
    $checks["Table: $table"] = $exists ? '✓ Exists' : '✗ Missing';
}

// Count products
if ($con instanceof mysqli) {
    $result = @mysqli_query($con, 'SELECT COUNT(*) AS total FROM products');
    if ($result instanceof mysqli_result) {
        $row = mysqli_fetch_assoc($result);
        $count = (int) ($row['total'] ?? 0);
        $checks['Products in DB'] = "$count products" . ($count > 0 ? ' ✓' : ' ✗ NEEDS SEEDING');
    }
    
    // Check session
    $checks['Session Cart'] = count($_SESSION['cart'] ?? []) . ' items';
    $checks['Session Favorites'] = count($_SESSION['favorites'] ?? []) . ' items';
    
    // Test sample product
    $sampleId = store_product_id_by_slug($con, 'haier-frostcool-15-ac');
    $checks['Sample Product ID (haier-frostcool-15-ac)'] = $sampleId > 0 ? "✓ ID: $sampleId" : '✗ Not Found';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>La Imperial - Setup Verification</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #333; }
        .check-item { padding: 12px; margin: 8px 0; border-radius: 4px; background: #f9f9f9; border-left: 4px solid #ddd; }
        .check-item strong { display: block; margin-bottom: 4px; }
        .check-item.ok { border-left-color: #4CAF50; background: #f1f8f4; }
        .check-item.error { border-left-color: #f44336; background: #fdf4f2; }
    </style>
</head>
<body>
    <div class="container">
        <h1>La Imperial - Setup Verification</h1>
        <p>Checking system configuration...</p>
        
        <?php foreach ($checks as $name => $status): ?>
            <div class="check-item <?php echo strpos($status, '✓') !== false ? 'ok' : 'error'; ?>">
                <strong><?php echo htmlspecialchars($name); ?>:</strong>
                <?php echo htmlspecialchars($status); ?>
            </div>
        <?php endforeach; ?>
        
        <p><br><a href="./compare.php">→ Go to Compare Page</a></p>
    </div>
</body>
</html>
