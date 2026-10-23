<?php
declare(strict_types=1);

require_once __DIR__ . '/shared.php';

admin_require_panel_permission('manage_settings', 'You do not have permission to customize website settings.');

$csrfKey = 'admin_customize_panel';
$csrfToken = store_csrf_token($csrfKey);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $token = (string) ($_POST['csrf_token'] ?? '');
    if (!store_verify_csrf_token($csrfKey, $token)) {
        store_flash('danger', 'Security verification failed. Please try again.');
        header('Location: ./customize.php');
        exit;
    }

    if ((string) ($_POST['action'] ?? '') === 'save_settings') {
        $result = store_save_settings($con, $_POST);
        store_flash($result['success'] ? 'success' : 'danger', $result['message']);
        header('Location: ./customize.php');
        exit;
    }
}

$settings = store_settings($con);
$googleUrls = store_google_console_urls();
$googleClientReady = store_google_ready();
$googleConfigured = store_google_enabled();

admin_page_start(
    'La Imparial | Admin Customize',
    'customize.php',
    'Customize',
    'Theme, SEO text, support details, and Google login setup in one page.',
    'This panel stores visual settings in MySQL so the website header, meta tags, theme colors, and admin guidance stay easier to maintain from one place.'
);
?>

<section class="admin-content-grid">
    <article class="admin-panel">
        <div class="admin-panel__head">
            <div>
                <h3>Website Customization</h3>
                <p>Save site title, tagline, announcement text, theme colors, support email, and default SEO values.</p>
            </div>
        </div>
        <form method="post" action="./customize.php" class="admin-form-stack">
            <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
            <input type="hidden" name="action" value="save_settings">
            <div class="admin-form-grid admin-form-grid--wide">
                <label>
                    <span>Site Title</span>
                    <input class="admin-input" type="text" name="site_title" value="<?php echo e((string) ($settings['site_title'] ?? 'La Imparial Appliances')); ?>">
                </label>
                <label>
                    <span>Site Tagline</span>
                    <input class="admin-input" type="text" name="site_tagline" value="<?php echo e((string) ($settings['site_tagline'] ?? '')); ?>">
                </label>
                <label>
                    <span>Theme Primary</span>
                    <input class="admin-input" type="text" name="theme_primary" value="<?php echo e((string) ($settings['theme_primary'] ?? '#0b1688')); ?>">
                </label>
                <label>
                    <span>Theme Secondary</span>
                    <input class="admin-input" type="text" name="theme_secondary" value="<?php echo e((string) ($settings['theme_secondary'] ?? '#5561b8')); ?>">
                </label>
                <label>
                    <span>Theme Accent</span>
                    <input class="admin-input" type="text" name="theme_accent" value="<?php echo e((string) ($settings['theme_accent'] ?? '#0f766e')); ?>">
                </label>
                <label>
                    <span>Support Email</span>
                    <input class="admin-input" type="email" name="support_email" value="<?php echo e((string) ($settings['support_email'] ?? '')); ?>">
                </label>
            </div>
            <label>
                <span>Announcement Text</span>
                <textarea class="admin-input" name="announcement_text" rows="3"><?php echo e((string) ($settings['announcement_text'] ?? '')); ?></textarea>
            </label>
            <label>
                <span>Default SEO Description</span>
                <textarea class="admin-input" name="seo_default_description" rows="4"><?php echo e((string) ($settings['seo_default_description'] ?? '')); ?></textarea>
            </label>
            <label>
                <span>Default SEO Keywords</span>
                <input class="admin-input" type="text" name="seo_default_keywords" value="<?php echo e((string) ($settings['seo_default_keywords'] ?? '')); ?>">
            </label>
            <label>
                <span>Open Graph Image Path</span>
                <input class="admin-input" type="text" name="seo_og_image" value="<?php echo e((string) ($settings['seo_og_image'] ?? 'img/logo/logo.png')); ?>">
            </label>
            <div class="admin-form-grid admin-form-grid--wide">
                <label>
                    <span>Site Logo Path</span>
                    <input class="admin-input" type="text" name="site_logo_path" value="<?php echo e((string) ($settings['site_logo_path'] ?? 'img/logo/logo.png')); ?>">
                </label>
                <label>
                    <span>Site Logo Alt</span>
                    <input class="admin-input" type="text" name="site_logo_alt" value="<?php echo e((string) ($settings['site_logo_alt'] ?? 'La Imparial')); ?>">
                </label>
                <label>
                    <span>Splash Logo Path</span>
                    <input class="admin-input" type="text" name="site_splash_logo" value="<?php echo e((string) ($settings['site_splash_logo'] ?? 'img/logo/image.png')); ?>">
                </label>
                <label>
                    <span>Home Kitchen Promo</span>
                    <input class="admin-input" type="text" name="home_kitchen_promo" value="<?php echo e((string) ($settings['home_kitchen_promo'] ?? 'img/banner/ketchen.webp')); ?>">
                </label>
                <label>
                    <span>Kitchen Promo Alt</span>
                    <input class="admin-input" type="text" name="home_kitchen_promo_alt" value="<?php echo e((string) ($settings['home_kitchen_promo_alt'] ?? 'La Imperial kitchen highlight visual')); ?>">
                </label>
                <label>
                    <span>Home Smart Promo</span>
                    <input class="admin-input" type="text" name="home_smart_promo" value="<?php echo e((string) ($settings['home_smart_promo'] ?? 'img/banner/smart.webp')); ?>">
                </label>
                <label>
                    <span>Smart Promo Alt</span>
                    <input class="admin-input" type="text" name="home_smart_promo_alt" value="<?php echo e((string) ($settings['home_smart_promo_alt'] ?? 'Kitchen edit promotional poster')); ?>">
                </label>
            </div>
            <label>
                <span>Home Gallery JSON</span>
                <textarea class="admin-input" name="home_gallery_images_json" rows="6"><?php echo e((string) ($settings['home_gallery_images_json'] ?? '')); ?></textarea>
            </label>
            <button class="admin-button admin-button--primary" type="submit">Save Website Settings</button>
        </form>
    </article>

    <div class="admin-stack">
        <article class="admin-panel">
            <div class="admin-panel__head">
                <div>
                    <h3>Google Login Setup</h3>
                    <p>Use the current XAMPP folder name to register the exact localhost URLs in Google Cloud Console.</p>
                </div>
            </div>
            <div class="admin-list">
                <div class="admin-list__item">
                    <strong>Project Folder</strong>
                    <p><?php echo e((string) $googleUrls['project_folder']); ?></p>
                </div>
                <div class="admin-list__item">
                    <strong>Authorized JavaScript Origins</strong>
                    <p><?php echo e((string) $googleUrls['origin']); ?></p>
                </div>
                <div class="admin-list__item">
                    <strong>Authorized Redirect URI</strong>
                    <p><?php echo e((string) $googleUrls['redirect']); ?></p>
                </div>
                <div class="admin-list__item">
                    <strong>Paste Client ID + Secret In</strong>
                    <p>`connection.php` constants `STORE_GOOGLE_CLIENT_ID` and `STORE_GOOGLE_CLIENT_SECRET`.</p>
                </div>
                <div class="admin-list__item">
                    <strong>Composer Package</strong>
                    <p>`google/apiclient:^2.15` in `composer.json`.</p>
                </div>
            </div>
        </article>

        <article class="admin-panel">
            <div class="admin-panel__head">
                <div>
                    <h3>Google Login Status</h3>
                    <p>Configuration and library readiness for callback-based sign-in.</p>
                </div>
            </div>
            <div class="admin-mini-grid">
                <div class="admin-mini-card">
                    <span>Keys configured</span>
                    <strong><?php echo $googleConfigured ? 'Yes' : 'No'; ?></strong>
                    <p><?php echo $googleConfigured ? 'Client ID and secret are present in connection.php.' : 'Add your Google credentials in connection.php first.'; ?></p>
                </div>
                <div class="admin-mini-card">
                    <span>Client library</span>
                    <strong><?php echo $googleClientReady ? 'Ready' : 'Missing'; ?></strong>
                    <p><?php echo $googleClientReady ? 'Vendor autoload was detected.' : 'Run Composer once to install google/apiclient into vendor/.'; ?></p>
                </div>
            </div>
        </article>

        <article class="admin-panel">
            <div class="admin-panel__head">
                <div>
                    <h3>Theme Preview Notes</h3>
                    <p>Saved colors now feed the website header variables and admin helper styling more cleanly.</p>
                </div>
            </div>
            <div class="admin-list">
                <div class="admin-list__item">
                    <strong>Header focus</strong>
                    <p>The public navbar and buttons now use one profile tab with a cleaner avatar treatment.</p>
                </div>
                <div class="admin-list__item">
                    <strong>SEO focus</strong>
                    <p>Default description, keywords, canonical-style metadata, and OG values are now easier to control.</p>
                </div>
                <div class="admin-list__item">
                    <strong>Review flow</strong>
                    <p>The new dedicated review page works alongside product detail reviews with smoother interaction.</p>
                </div>
            </div>
        </article>
    </div>
</section>

<?php
admin_page_end();
