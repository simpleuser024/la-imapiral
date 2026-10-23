<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/custom-page.php';

$productSlug = trim((string) ($_REQUEST['product'] ?? ''));
$product = $con instanceof mysqli && $productSlug !== '' ? store_product_by_slug($con, $productSlug) : null;
$defaultSubject = trim((string) ($_REQUEST['subject'] ?? ($product ? 'Support for ' . $product['name'] : '')));
$defaultMessage = trim((string) ($_REQUEST['message'] ?? ''));

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $con instanceof mysqli) {
    $result = store_create_contact_inquiry($con, $_POST);
    store_flash($result['success'] ? 'success' : 'danger', $result['message']);

    $redirect = './contact.php';
    $query = [];
    if ($productSlug !== '') {
        $query['product'] = $productSlug;
    }
    if ($defaultSubject !== '') {
        $query['subject'] = $defaultSubject;
    }
    if ($query !== []) {
        $redirect .= '?' . http_build_query($query);
    }

    header('Location: ' . $redirect);
    exit;
}

$user = $con instanceof mysqli ? store_current_user($con) : null;
$prefillName = trim((string) ($user['name'] ?? ''));
$prefillEmail = trim((string) ($user['email'] ?? ''));

render_custom_page_header('La Imparial | Contact Support');
?>
<section class="catalog-admin">
    <div class="container">
        <div class="catalog-page__header">
            <h2>Contact Admin Support</h2>
            <p>Send a real inquiry to the La Imparial admin team. Your message will be stored, emailed, and visible from the admin dashboard.</p>
        </div>

        <div class="row">
            <div class="col-lg-5">
                <div class="catalog-admin__panel" style="height: 100%;">
                    <h4>Direct Support</h4>
                    <div class="catalog-card__meta" style="margin: 18px 0;">
                        <span>Admin Email</span>
                        <span><?php echo e(STORE_ADMIN_EMAIL); ?></span>
                    </div>
                    <p>Use this form for product help, order guidance, account recovery, pricing questions, or admin verification.</p>
                    <div style="display:grid; gap:16px; margin-top:22px;">
                        <div style="padding:16px; border:1px solid #e5e7eb; border-radius:16px; background:#f8fafc;">
                            <strong style="display:block; margin-bottom:6px;">Password Recovery</strong>
                            <p style="margin:0 0 10px;">Forgot your password? Use the verification-based recovery flow and contact admin if you still need help.</p>
                            <a class="quick-action--detail" href="./forgot-password.php">Open Recovery</a>
                        </div>
                        <div style="padding:16px; border:1px solid #e5e7eb; border-radius:16px; background:#f8fafc;">
                            <strong style="display:block; margin-bottom:6px;">User Portal</strong>
                            <p style="margin:0 0 10px;">Open your account area for products, favorites, cart, reviews, and support shortcuts.</p>
                            <a class="quick-action--detail" href="./user/">Open User Area</a>
                        </div>
                        <?php if (store_is_admin()): ?>
                            <div style="padding:16px; border:1px solid #e5e7eb; border-radius:16px; background:#eef6ff;">
                                <strong style="display:block; margin-bottom:6px;">Admin Dashboard</strong>
                                <p style="margin:0 0 10px;">Review support messages, recent reviews, user activity, and catalog controls.</p>
                                <a class="quick-action--cart" href="./admin/">Open Admin Dashboard</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="catalog-admin__panel">
                    <h4>Send Inquiry</h4>
                    <form action="./contact.php<?php echo $productSlug !== '' ? '?product=' . urlencode($productSlug) : ''; ?>" method="post" class="form-row-gap">
                        <input type="hidden" name="product" value="<?php echo e($productSlug); ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Full Name</label>
                                    <input type="text" class="form-control" name="name" value="<?php echo e($prefillName); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" class="form-control" name="email" value="<?php echo e($prefillEmail); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Phone</label>
                                    <input type="text" class="form-control" name="phone" placeholder="03xx-xxxxxxx">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Subject</label>
                                    <input type="text" class="form-control" name="subject" value="<?php echo e($defaultSubject); ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Message</label>
                            <textarea class="form-control" name="message" rows="6" required><?php echo e($defaultMessage); ?></textarea>
                        </div>
                        <div class="cart-page__actions">
                            <button type="submit" class="quick-action--cart">Send to Admin</button>
                            <a class="quick-action--detail" href="./shop.php?view=products">Browse Products</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php if ($product): ?>
            <div class="catalog-page__header" style="margin-top: 40px;">
                <h3>Selected Product Context</h3>
                <p>Your inquiry will be attached to this product for the admin team.</p>
            </div>
            <div class="catalog-admin__panel">
                <article class="catalog-card">
                    <img src="<?php echo e($product['image_path']); ?>" alt="<?php echo e($product['name']); ?>">
                    <div>
                        <div class="catalog-card__meta">
                            <span><?php echo e($product['brand_name']); ?></span>
                            <span><?php echo e($product['category_name']); ?></span>
                        </div>
                        <h4><?php echo e($product['name']); ?></h4>
                        <p><?php echo e($product['summary']); ?></p>
                        <div class="catalog-card__price"><?php echo e(store_format_money((float) $product['price'])); ?></div>
                    </div>
                    <div class="favorite-page__actions">
                        <a class="quick-action--detail" href="./shop-details.php?product=<?php echo urlencode($product['slug']); ?>">Open Product</a>
                    </div>
                </article>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php render_custom_page_footer(); ?>
