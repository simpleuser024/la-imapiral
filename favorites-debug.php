<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/render.php';
require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/includes/store.php';

ensure_store_state();

$payload = $con instanceof mysqli ? build_store_client_payload($con) : [];

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Favorites Debugger</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; }
        h2 { color: #ff006e; }
        code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; }
        pre { background: #f0f0f0; padding: 15px; overflow-x: auto; border-radius: 5px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
    </style>
</head>
<body>

<h1>💎 La Imperial Favorites Debugger</h1>

<div class="box">
    <h2>Server-Side Session Data</h2>
    <p><strong>Session ID:</strong> <?php echo session_id(); ?></p>
    <p><strong>Session Favorites:</strong> <?php echo isset($_SESSION['favorites']) ? '<span class="success">✓ Present</span>' : '<span class="error">✗ Missing</span>'; ?></p>
    <p><strong>Count:</strong> <?php echo count($_SESSION['favorites'] ?? []); ?></p>
    <pre><?php echo json_encode($_SESSION['favorites'] ?? [], JSON_PRETTY_PRINT); ?></pre>
</div>

<div class="box">
    <h2>Payload Data (Sent to JavaScript)</h2>
    <p><strong>Session included in payload:</strong> <?php echo isset($payload['session']) ? '<span class="success">✓ Yes</span>' : '<span class="error">✗ No</span>'; ?></p>
    <p><strong>Favorites in payload:</strong> <?php echo isset($payload['session']['favorites']) ? '<span class="success">✓ Yes</span>' : '<span class="error">✗ No</span>'; ?></p>
    <p><strong>Count:</strong> <?php echo isset($payload['session']['favorites']) ? count($payload['session']['favorites']) : 0; ?></p>
    <pre><?php echo json_encode($payload['session']['favorites'] ?? [], JSON_PRETTY_PRINT); ?></pre>
</div>

<div class="box">
    <h2>JSON Payload (100% of what gets sent to JS)</h2>
    <pre id="payload"></pre>
</div>

<div class="box">
    <h2>💚 What JavaScript Sees</h2>
    <p id="js-status">Loading...</p>
    <pre id="js-data"></pre>
</div>

<div class="box">
    <h2>🔗 Test Links</h2>
    <p>Click these to add favorites:</p>
    <ul>
        <li><a href="./store-action.php?action=toggle_favorite&product=haier-frostcool-15-ac&redirect=<?php echo urlencode('./favorites-debug.php'); ?>">Add Haier FrostCool</a></li>
        <li><a href="./store-action.php?action=toggle_favorite&product=samsung-windhub-15-ac&redirect=<?php echo urlencode('./favorites-debug.php'); ?>">Add Samsung WindHub</a></li>
        <li><a href="./shop.php">Go to Shop</a></li>
    </ul>
</div>

<script>
    // Show what the server sent
    var payload = <?php echo json_encode($payload, JSON_UNESCAPED_SLASHES); ?>;
    document.getElementById('payload').textContent = JSON.stringify(payload, null, 2);
    
    // Simulate what JavaScript will receive
    window.LA_IMPARIAL_STORE = payload;
    var storeSession = payload.session || {};
    var favs = storeSession.favorites || [];
    
    document.getElementById('js-status').innerHTML = 
        '<strong>storeSession.favorites exists:</strong> ' + (favs ? '✓ Yes' : '✗ No') + '<br>' +
        '<strong>Type:</strong> ' + typeof favs + '<br>' +
        '<strong>Count:</strong> ' + favs.length + '<br>' +
        '<strong>Value:</strong> ' + JSON.stringify(favs);
    
    document.getElementById('js-data').textContent = JSON.stringify({
        hasSession: !!storeSession,
        hasFavorites: !!favs,
        favoritesLength: favs.length,
        favorites: favs
    }, null, 2);
    
    // Test the isProductFavorited logic
    function testIsProductFavorited(slug) {
        var appFavs = (storeSession && storeSession.favorites) || [];
        var result = appFavs.indexOf(slug) > -1;
        console.log('isProductFavorited("' + slug + '"): ' + result + ' (in array: ' + JSON.stringify(appFavs) + ')');
        return result;
    }
    
    console.log('=== Favorites Debug ===');
    console.log('storeSession:', storeSession);
    console.log('favorites array:', favs);
    console.log('Test haier-frostcool-15-ac:', testIsProductFavorited('haier-frostcool-15-ac'));
    console.log('Test samsung-windhub-15-ac:', testIsProductFavorited('samsung-windhub-15-ac'));
</script>

</body>
</html>
