<?php
declare(strict_types=1);

require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/includes/store.php';

ensure_store_state();

$info = [
    'Session ID' => session_id(),
    'User ID' => $_SESSION['user_id'] ?? 'Not logged in (Guest)',
    'Session Cart Items' => count($_SESSION['cart'] ?? []),
    'Session Favorites' => count($_SESSION['favorites'] ?? []),
];

$dbInfo = [];

if ($con instanceof mysqli) {
    // Cart items in database
    $identity = store_identity_context();
    
    if ($identity['session_id']) {
        $result = @mysqli_query($con, "SELECT COUNT(*) as total FROM cart_items WHERE session_id = '" . mysqli_real_escape_string($con, $identity['session_id']) . "'");
        if ($result instanceof mysqli_result) {
            $row = mysqli_fetch_assoc($result);
            $dbInfo['Cart Items in DB (this session)'] = (int)$row['total'] . ' items';
        }
    }
    
    if ($identity['user_id'] > 0) {
        $result = @mysqli_query($con, "SELECT COUNT(*) as total FROM cart_items WHERE user_id = " . (int)$identity['user_id']);
        if ($result instanceof mysqli_result) {
            $row = mysqli_fetch_assoc($result);
            $dbInfo['Cart Items in DB (this user)'] = (int)$row['total'] . ' items';
        }
    }
    
    // Favorites in database
    if ($identity['session_id']) {
        $result = @mysqli_query($con, "SELECT COUNT(*) as total FROM favorites WHERE session_id = '" . mysqli_real_escape_string($con, $identity['session_id']) . "'");
        if ($result instanceof mysqli_result) {
            $row = mysqli_fetch_assoc($result);
            $dbInfo['Favorites in DB (this session)'] = (int)$row['total'] . ' items';
        }
    }
    
    if ($identity['user_id'] > 0) {
        $result = @mysqli_query($con, "SELECT COUNT(*) as total FROM favorites WHERE user_id = " . (int)$identity['user_id']);
        if ($result instanceof mysqli_result) {
            $row = mysqli_fetch_assoc($result);
            $dbInfo['Favorites in DB (this user)'] = (int)$row['total'] . ' items';
        }
    }
    
    // Sample cart data
    if ($identity['session_id']) {
        $result = @mysqli_query($con, "SELECT ci.id, p.name, p.slug, ci.quantity FROM cart_items ci JOIN products p ON ci.product_id = p.id WHERE ci.session_id = '" . mysqli_real_escape_string($con, $identity['session_id']) . "' LIMIT 5");
        if ($result instanceof mysqli_result && mysqli_num_rows($result) > 0) {
            $dbInfo['Sample Cart Items'] = mysqli_num_rows($result) . ' items found';
        }
    }
    
    // Sample favorites data
    if ($identity['session_id']) {
        $result = @mysqli_query($con, "SELECT f.id, p.name, p.slug FROM favorites f JOIN products p ON f.product_id = p.id WHERE f.session_id = '" . mysqli_real_escape_string($con, $identity['session_id']) . "' LIMIT 5");
        if ($result instanceof mysqli_result && mysqli_num_rows($result) > 0) {
            $dbInfo['Sample Favorites'] = mysqli_num_rows($result) . ' items found';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>La Imperial - Database Verification</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 700px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #111; margin-bottom: 10px; }
        .subtitle { color: #666; margin-bottom: 30px; font-size: 14px; }
        .section { margin-bottom: 30px; }
        .section-title { font-weight: bold; color: #333; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #ddd; }
        .item { padding: 12px; margin: 8px 0; border-radius: 4px; background: #f9f9f9; border-left: 4px solid #ddd; display: flex; justify-content: space-between; }
        .item.success { border-left-color: #4CAF50; background: #f1f8f4; }
        .item.warning { border-left-color: #ff9800; background: #fff3f0; }
        .label { font-weight: 600; color: #333; }
        .value { color: #666; }
        .code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-family: monospace; font-size: 12px; color: #d63384; }
        .success-check { color: #4CAF50; font-weight: bold; }
        .warning-text { color: #ff9800; font-weight: bold; }
        .action-links { margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee; }
        .action-links a { display: inline-block; margin-right: 15px; padding: 10px 20px; background: #0066cc; color: white; text-decoration: none; border-radius: 4px; transition: background 0.3s; }
        .action-links a:hover { background: #0052a3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗄️ Database Verification</h1>
        <p class="subtitle">Checking if your cart and favorites are being saved to MySQL database</p>
        
        <div class="section">
            <div class="section-title">📋 Session Information</div>
            <?php foreach ($info as $label => $value): ?>
                <div class="item <?php echo strpos($value, 'Not logged') !== false ? 'warning' : 'success'; ?>">
                    <span class="label"><?php echo htmlspecialchars($label); ?>:</span>
                    <span class="value"><?php echo htmlspecialchars((string)$value); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="section">
            <div class="section-title">💾 Database Storage Verification</div>
            <?php if (empty($dbInfo)): ?>
                <div class="item warning">
                    <span class="label">Status:</span>
                    <span class="value"><span class="warning-text">⚠ No data yet</span> - Add items to cart or favorites to test database storage</span>
                </div>
            <?php else: ?>
                <?php foreach ($dbInfo as $label => $value): ?>
                    <div class="item success">
                        <span class="label"><?php echo htmlspecialchars($label); ?>:</span>
                        <span class="value"><span class="success-check">✓</span> <?php echo htmlspecialchars((string)$value); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="section">
            <div class="section-title">✅ What This Means</div>
            <div class="item">
                <div style="flex: 1;">
                    <p><strong>Session Cart/Favorites:</strong> Stored in your browser session (temporary)</p>
                    <p><strong>Database Cart/Favorites:</strong> Permanently saved to MySQL database</p>
                    <p style="margin-top: 15px; color: #666;"><em>Data is synced between session and database automatically. When you add items, they're saved to both places instantly!</em></p>
                </div>
            </div>
        </div>

        <div class="action-links">
            <a href="./shop.php">← Shop & Add Items</a>
            <a href="./cart.php">View Cart</a>
            <a href="./favorites.php">View Favorites</a>
        </div>
    </div>
</body>
</html>
