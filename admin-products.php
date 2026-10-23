<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/store.php';

store_require_login('./admin-products.php');

if (!store_is_admin()) {
    store_flash('danger', 'Admin access required.');
    header('Location: ./index.php');
    exit;
}

$products = $con instanceof mysqli ? store_all_products($con) : [];
$stats = [
    'total' => count($products),
    'featured' => count(array_filter($products, fn($p) => (int)$p['is_featured'] === 1)),
    'discounted' => count(array_filter($products, fn($p) => (int)$p['discount_percent'] > 0)),
];

render_custom_page_header('La Imparial | Product Management');
?>
<section class="catalog-admin">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="catalog-admin__panel">
                    <div class="catalog-page__header">
                        <h2>Product Management Dashboard</h2>
                        <p>View, verify, and manage all products in your database</p>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px;">
                        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 12px; text-align: center;">
                            <h3 style="font-size: 32px; margin: 0 0 8px 0;"><?php echo $stats['total']; ?></h3>
                            <p style="margin: 0; font-weight: 600; opacity: 0.9;">Total Products</p>
                        </div>
                        <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 25px; border-radius: 12px; text-align: center;">
                            <h3 style="font-size: 32px; margin: 0 0 8px 0;"><?php echo $stats['featured']; ?></h3>
                            <p style="margin: 0; font-weight: 600; opacity: 0.9;">Featured</p>
                        </div>
                        <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 25px; border-radius: 12px; text-align: center;">
                            <h3 style="font-size: 32px; margin: 0 0 8px 0;"><?php echo $stats['discounted']; ?></h3>
                            <p style="margin: 0; font-weight: 600; opacity: 0.9;">Discounted</p>
                        </div>
                    </div>

                    <div style="margin: 30px 0;">
                        <a href="./product-form.php" class="primary-btn">+ Add New Product</a>
                    </div>

                    <?php if ($products): ?>
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
                                <thead>
                                    <tr style="background: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                                        <th style="padding: 15px; text-align: left; font-weight: 700; color: #333;">Image</th>
                                        <th style="padding: 15px; text-align: left; font-weight: 700; color: #333;">Product Name</th>
                                        <th style="padding: 15px; text-align: left; font-weight: 700; color: #333;">Brand</th>
                                        <th style="padding: 15px; text-align: left; font-weight: 700; color: #333;">Category</th>
                                        <th style="padding: 15px; text-align: right; font-weight: 700; color: #333;">Price</th>
                                        <th style="padding: 15px; text-align: center; font-weight: 700; color: #333;">Discount</th>
                                        <th style="padding: 15px; text-align: center; font-weight: 700; color: #333;">Featured</th>
                                        <th style="padding: 15px; text-align: center; font-weight: 700; color: #333;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $product): ?>
                                        <tr style="border-bottom: 1px solid #dee2e6; transition: background 0.3s;">
                                            <td style="padding: 15px;">
                                                <img src="<?php echo htmlspecialchars($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px;">
                                            </td>
                                            <td style="padding: 15px;">
                                                <strong style="color: #111;"><?php echo htmlspecialchars($product['name']); ?></strong><br>
                                                <small style="color: #6b6b6b;">ID: <?php echo (int)$product['id']; ?></small>
                                            </td>
                                            <td style="padding: 15px; color: #555;"><?php echo htmlspecialchars($product['brand_name']); ?></td>
                                            <td style="padding: 15px; color: #555;"><?php echo htmlspecialchars($product['category_name']); ?></td>
                                            <td style="padding: 15px; text-align: right; color: #111; font-weight: 600;">PKR <?php echo number_format((float)$product['price'], 0); ?></td>
                                            <td style="padding: 15px; text-align: center;">
                                                <?php if ((int)$product['discount_percent'] > 0): ?>
                                                    <span style="background: #ff6b6b; color: white; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;"><?php echo (int)$product['discount_percent']; ?>%</span>
                                                <?php else: ?>
                                                    <span style="color: #999;">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 15px; text-align: center;">
                                                <?php if ((int)$product['is_featured'] === 1): ?>
                                                    <span style="color: #28a745; font-weight: 700;">★</span>
                                                <?php else: ?>
                                                    <span style="color: #ccc;">☆</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 15px; text-align: center;">
                                                <a href="./shop-details.php?product=<?php echo urlencode($product['slug']); ?>" style="display: inline-block; padding: 6px 12px; background: #0066cc; color: white; text-decoration: none; border-radius: 4px; font-size: 12px; margin-right: 5px;">View</a>
                                                <a href="./edit-product.php?id=<?php echo (int)$product['id']; ?>" style="display: inline-block; padding: 6px 12px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px; font-size: 12px;">Edit</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div style="background: #f8f9fa; padding: 40px; text-align: center; border-radius: 8px;">
                            <p style="color: #666; font-size: 16px; margin: 0;">No products found. <a href="./product-form.php">Create the first product</a></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    table tbody tr:hover {
        background: #f8f9fa;
    }
</style>

<?php render_custom_page_footer(); ?>
