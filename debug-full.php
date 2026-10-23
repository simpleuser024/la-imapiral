<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/render.php';
require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/includes/store.php';

ensure_store_state();

// Build the payload
$payload = $con instanceof mysqli ? build_store_client_payload($con) : [];

$favorites = $_SESSION['favorites'] ?? [];
$cartCount = $_SESSION['cart'] ?? [];

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Full Debug Page</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #ff006e; }
        h2 { color: #ff006e; margin-top: 0; }
        pre { background: #f0f0f0; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
        .success { color: #118a73; }
        .error { color: #d85a65; }
        .test-button { padding: 10px 20px; background: #ff006e; color: white; border: none; border-radius: 5px; margin: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .test-button:hover { background: #ff3d82; }
    </style>
</head>
<body>

<h1>La Imperial - Full Debug Page</h1>

<div class="section">
    <h2>✅ Session State</h2>
    <p><strong>Session ID:</strong> <?php echo htmlspecialchars(session_id()); ?></p>
    <p><strong>Favorites Count:</strong> <span class="<?php echo count($favorites) > 0 ? 'success' : 'error'; ?>"><?php echo count($favorites); ?></span></p>
    <p><strong>Cart Items Count:</strong> <?php echo count($cartCount); ?></p>
    
    <h3>Favorites Array:</h3>
    <pre><?php echo json_encode($favorites, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?></pre>
</div>

<div class="section">
    <h2>🔍 Payload Check</h2>
    <p><strong>Payload Favorites:</strong> <?php echo isset($payload['session']['favorites']) ? 'Present ✓' : 'Missing ✗'; ?></p>
    <p><strong>Payload Favorites Count:</strong> <?php echo isset($payload['session']['favorites']) ? count($payload['session']['favorites']) : 0; ?></p>
    
    <h3>Payload Favorites Array:</h3>
    <pre><?php echo json_encode($payload['session']['favorites'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?></pre>
</div>

<div class="section">
    <h2>🧪 Test Actions</h2>
    <p>Click a button to add a product to favorites:</p>
    
    <a href="./store-action.php?action=toggle_favorite&product=haier-frostcool-15-ac&redirect=<?php echo urlencode('./debug-full.php'); ?>" class="test-button">
        ❤️ Add Haier FrostCool
    </a>
    
    <a href="./store-action.php?action=toggle_favorite&product=samsung-windhub-15-ac&redirect=<?php echo urlencode('./debug-full.php'); ?>" class="test-button">
        ❤️ Add Samsung WindHub
    </a>
    
    <a href="./store-action.php?action=toggle_favorite&product=lg-airflow-15-ac&redirect=<?php echo urlencode('./debug-full.php'); ?>" class="test-button">
        ❤️ Add LG AirFlow
    </a>
    
    <hr>
    
    <a href="./shop.php" class="test-button" style="background: #0f79f3;">
        Go to Shop Page
    </a>
    
    <a href="./emoji-test.html" class="test-button" style="background: #118a73;">
        Test Emoji Display
    </a>
</div>

<div class="section">
    <h2>📊 Products Check</h2>
    <?php
    if (isset($payload['brandGroups']) && is_array($payload['brandGroups'])) {
        $totalProducts = 0;
        foreach ($payload['brandGroups'] as $brand) {
            $totalProducts += isset($brand['products']) ? count($brand['products']) : 0;
        }
        echo "<p><strong>Total Products from Server:</strong> $totalProducts</p>";
        
        if ($totalProducts > 0) {
            $firstProduct = $payload['brandGroups'][0]['products'][0] ?? null;
            if ($firstProduct) {
                ?>
                <h3>Sample Product:</h3>
                <pre><?php echo json_encode($firstProduct, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?></pre>
                <?php
            }
        }
    }
    ?>
</div>

<div class="section">
    <h2>🎯 Heart Icon Test</h2>
    <p>Sample heart icons:</p>
    <ul>
        <li><span style="font-size: 20px;">❤️</span> Favorited (Red Heart)</li>
        <li><span style="font-size: 20px;">🤍</span> Not Favorited (White Heart)</li>
    </ul>
</div>

<script>
// Log payload to console for debugging
console.log('Payload from Server:', <?php echo json_encode($payload, JSON_UNESCAPED_SLASHES); ?>);
console.log('Session Favorites:', <?php echo json_encode($payload['session']['favorites'] ?? [], JSON_UNESCAPED_SLASHES); ?>);
</script>

</body>
</html>
