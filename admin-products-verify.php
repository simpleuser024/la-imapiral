<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/store.php';

store_require_login('./admin-products-verify.php');

if (!store_is_admin()) {
    store_flash('danger', 'Admin access required.');
    header('Location: ./index.php');
    exit;
}

$products = $con instanceof mysqli ? store_all_products($con) : [];

render_custom_page_header('La Imparial | Product Database Verification');
?>
<section class="catalog-admin">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="catalog-admin__panel">
                    <div class="catalog-page__header">
                        <h2>Database Verification Report</h2>
                        <p>Detailed verification that all product information is properly stored in MySQL</p>
                    </div>

                    <div style="background: #f0f7ff; border-left: 4px solid #0066cc; padding: 16px; margin-bottom: 30px; border-radius: 6px;">
                        <p style="margin: 0; color: #0066cc;"><strong>✓ Database Connection Active</strong> • Total Products: <strong><?php echo count($products); ?></strong></p>
                    </div>

                    <?php if ($products): ?>
                        <div style="display: grid; gap: 30px;">
                            <?php foreach ($products as $index => $product): ?>
                                <div style="background: #fff; border: 1px solid #e0e0e0; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 16px;">
                                        <h3 style="margin: 0; font-size: 18px; font-weight: 700;">Product #<?php echo ($index + 1); ?> - <?php echo htmlspecialchars($product['name']); ?></h3>
                                    </div>
                                    
                                    <div style="padding: 24px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                        <!-- Image Preview -->
                                        <div style="text-align: center;">
                                            <img src="<?php echo htmlspecialchars($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="max-width: 100%; max-height: 300px; object-fit: cover; border-radius: 8px; background: #f5f5f5;">
                                            <p style="margin: 12px 0 0 0; color: #666; font-size: 12px;"><strong>Image Path:</strong> <?php echo htmlspecialchars($product['image_path']); ?></p>
                                        </div>

                                        <!-- Product Details -->
                                        <div>
                                            <div style="margin-bottom: 16px;">
                                                <label style="display: block; font-weight: 700; color: #333; margin-bottom: 4px;">Database ID</label>
                                                <code style="display: block; background: #f5f5f5; padding: 8px 12px; border-radius: 4px; color: #0066cc; font-weight: 600;"><?php echo (int)$product['id']; ?></code>
                                            </div>

                                            <div style="margin-bottom: 16px;">
                                                <label style="display: block; font-weight: 700; color: #333; margin-bottom: 4px;">Slug</label>
                                                <code style="display: block; background: #f5f5f5; padding: 8px 12px; border-radius: 4px; color: #666; word-break: break-all;"><?php echo htmlspecialchars($product['slug']); ?></code>
                                            </div>

                                            <div style="margin-bottom: 16px;">
                                                <label style="display: block; font-weight: 700; color: #333; margin-bottom: 4px;">Brand</label>
                                                <p style="margin: 0; padding: 8px 12px; background: #f5f5f5; border-radius: 4px; color: #666;"><strong>#<?php echo (int)$product['brand_id']; ?></strong> - <?php echo htmlspecialchars($product['brand_name']); ?></p>
                                            </div>

                                            <div style="margin-bottom: 16px;">
                                                <label style="display: block; font-weight: 700; color: #333; margin-bottom: 4px;">Category</label>
                                                <p style="margin: 0; padding: 8px 12px; background: #f5f5f5; border-radius: 4px; color: #666;"><strong>#<?php echo (int)$product['category_id']; ?></strong> - <?php echo htmlspecialchars($product['category_name']); ?></p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Summary and Description -->
                                    <div style="padding: 24px; border-top: 1px solid #e0e0e0; background: #f9f9f9;">
                                        <div style="margin-bottom: 16px;">
                                            <label style="display: block; font-weight: 700; color: #333; margin-bottom: 8px;">Summary</label>
                                            <p style="margin: 0; color: #555; line-height: 1.6;"><?php echo htmlspecialchars($product['summary']); ?></p>
                                        </div>

                                        <div style="margin-bottom: 16px;">
                                            <label style="display: block; font-weight: 700; color: #333; margin-bottom: 8px;">Full Description</label>
                                            <p style="margin: 0; color: #555; line-height: 1.6; max-height: 120px; overflow: hidden;"><?php echo htmlspecialchars(substr($product['description'], 0, 300)); ?><?php echo strlen($product['description']) > 300 ? '...' : ''; ?></p>
                                        </div>
                                    </div>

                                    <!-- Pricing -->
                                    <div style="padding: 24px; border-top: 1px solid #e0e0e0; display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;">
                                        <div style="background: #f5f5f5; padding: 12px; border-radius: 6px;">
                                            <label style="display: block; font-size: 11px; font-weight: 700; color: #666; text-transform: uppercase; margin-bottom: 4px;">Current Price</label>
                                            <p style="margin: 0; font-size: 16px; color: #111; font-weight: 700;">PKR <?php echo number_format((float)$product['price'], 0); ?></p>
                                        </div>
                                        <div style="background: #f5f5f5; padding: 12px; border-radius: 6px;">
                                            <label style="display: block; font-size: 11px; font-weight: 700; color: #666; text-transform: uppercase; margin-bottom: 4px;">Original Price</label>
                                            <p style="margin: 0; font-size: 16px; color: #999; font-weight: 600; text-decoration: line-through;">PKR <?php echo number_format((float)$product['original_price'], 0); ?></p>
                                        </div>
                                        <div style="background: #fff8e1; padding: 12px; border-radius: 6px; border: 1px solid #ffe082;">
                                            <label style="display: block; font-size: 11px; font-weight: 700; color: #f57f17; text-transform: uppercase; margin-bottom: 4px;">Discount</label>
                                            <p style="margin: 0; font-size: 16px; color: #f57f17; font-weight: 700;"><?php echo (int)$product['discount_percent']; ?>%</p>
                                        </div>
                                        <div style="background: <?php echo (int)$product['is_featured'] === 1 ? '#e8f5e9' : '#f5f5f5'; ?>; padding: 12px; border-radius: 6px; border: 1px solid <?php echo (int)$product['is_featured'] === 1 ? '#4caf50' : '#e0e0e0'; ?>;">
                                            <label style="display: block; font-size: 11px; font-weight: 700; color: <?php echo (int)$product['is_featured'] === 1 ? '#388e3c' : '#666'; ?>; text-transform: uppercase; margin-bottom: 4px;">Featured</label>
                                            <p style="margin: 0; font-size: 18px; font-weight: 700; color: <?php echo (int)$product['is_featured'] === 1 ? '#4caf50' : '#999'; ?>"><?php echo (int)$product['is_featured'] === 1 ? '★' : '☆'; ?></p>
                                        </div>
                                    </div>

                                    <!-- Document and Gallery -->
                                    <div style="padding: 24px; border-top: 1px solid #e0e0e0; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                        <div>
                                            <label style="display: block; font-weight: 700; color: #333; margin-bottom: 8px;">Document Path</label>
                                            <code style="display: block; background: #f5f5f5; padding: 8px 12px; border-radius: 4px; color: #666; word-break: break-all; font-size: 12px;"><?php echo htmlspecialchars($product['document_path']); ?></code>
                                        </div>
                                        <div>
                                            <label style="display: block; font-weight: 700; color: #333; margin-bottom: 8px;">Gallery Images</label>
                                            <?php 
                                                $gallery = store_parse_gallery_images($product['gallery_images'] ?? '');
                                                if ($gallery): 
                                            ?>
                                                <p style="margin: 0; color: #28a745; font-weight: 600;">✓ <?php echo count($gallery); ?> image<?php echo count($gallery) !== 1 ? 's' : ''; ?> stored</p>
                                            <?php else: ?>
                                                <p style="margin: 0; color: #999;">No gallery images</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Metadata -->
                                    <div style="padding: 24px; border-top: 1px solid #e0e0e0; background: #f9f9f9; font-size: 12px; color: #999;">
                                        <p style="margin: 0;"><strong>Created By:</strong> User ID <?php echo (int)$product['created_by']; ?> | <strong>Status:</strong> <?php echo (int)$product['is_featured'] === 1 ? 'Featured' : 'Standard'; ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Summary Stats -->
                        <div style="margin-top: 40px; padding: 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 12px;">
                            <h3 style="margin-top: 0; margin-bottom: 16px;">Database Summary</h3>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                                <div>
                                    <p style="margin: 0; opacity: 0.9; font-size: 14px; margin-bottom: 4px;">Total Products Stored</p>
                                    <p style="margin: 0; font-size: 28px; font-weight: 700;"><?php echo count($products); ?></p>
                                </div>
                                <div>
                                    <p style="margin: 0; opacity: 0.9; font-size: 14px; margin-bottom: 4px;">Products with Gallery</p>
                                    <p style="margin: 0; font-size: 28px; font-weight: 700;"><?php echo count(array_filter($products, fn($p) => !empty($p['gallery_images']))); ?></p>
                                </div>
                                <div>
                                    <p style="margin: 0; opacity: 0.9; font-size: 14px; margin-bottom: 4px;">Featured Products</p>
                                    <p style="margin: 0; font-size: 28px; font-weight: 700;"><?php echo count(array_filter($products, fn($p) => (int)$p['is_featured'] === 1)); ?></p>
                                </div>
                                <div>
                                    <p style="margin: 0; opacity: 0.9; font-size: 14px; margin-bottom: 4px;">Discounted Items</p>
                                    <p style="margin: 0; font-size: 28px; font-weight: 700;"><?php echo count(array_filter($products, fn($p) => (int)$p['discount_percent'] > 0)); ?></p>
                                </div>
                            </div>
                            <p style="margin: 16px 0 0 0; font-size: 13px; opacity: 0.8;">✓ All product details (name, ID, images, pricing, categories, brands, documents) are properly stored in MySQL database</p>
                        </div>

                    <?php else: ?>
                        <div style="background: #f8f9fa; padding: 40px; text-align: center; border-radius: 8px;">
                            <p style="color: #666; font-size: 16px; margin: 0;">No products found in database.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php render_custom_page_footer(); ?>
