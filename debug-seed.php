<?php
// Debug and seed database script
// Visit this file to check database state and reseed if needed

include_once 'connection.php';
include_once 'includes/store.php';

// Initialize store (this will seed the database if needed)
store_initialize($con);

// Check database state
$result = mysqli_query($con, "SELECT COUNT(*) as count FROM products");
$row = mysqli_fetch_assoc($result);
$productCount = $row['count'];

echo "<h2>Database Status</h2>";
echo "<p>Total products in database: <strong>" . $productCount . "</strong></p>";

if ($productCount == 0) {
    echo "<p style='color: red;'>⚠️ No products found! Attempting to reseed...</p>";
    store_seed_catalog($con);
    
    $result = mysqli_query($con, "SELECT COUNT(*) as count FROM products");
    $row = mysqli_fetch_assoc($result);
    echo "<p>✅ Database reseeded! New count: <strong>" . $row['count'] . "</strong> products</p>";
} else {
    echo "<p style='color: green;'>✅ Database has products!</p>";
}

// Sample products check
echo "<h2>Sample Products</h2>";
$query = "SELECT id, slug, name, image_path FROM products LIMIT 10";
$result = mysqli_query($con, $query);

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Slug</th><th>Name</th><th>Image Path</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['slug'] . "</td>";
    echo "<td>" . $row['name'] . "</td>";
    echo "<td>" . $row['image_path'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check specific slugs that we fixed
echo "<h2>Checking Fixed Slugs</h2>";
$slugsToCheck = [
    'haier-aquaflow-dispenser',
    'dawlance-hydrosip-dispenser',
    'samsung-aquahub-dispenser',
    'lg-pureflow-dispenser',
    'orient-aquapure-dispenser',
    'pel-hydrofresh-dispenser'
];

foreach ($slugsToCheck as $slug) {
    $query = "SELECT id FROM products WHERE slug = '" . mysqli_real_escape_string($con, $slug) . "'";
    $result = mysqli_query($con, $query);
    $found = mysqli_num_rows($result) > 0;
    $status = $found ? "✅ FOUND" : "❌ NOT FOUND";
    echo "<p>" . $slug . ": " . $status . "</p>";
}

mysqli_close($con);
?>
