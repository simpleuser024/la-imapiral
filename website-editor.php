<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/custom-page.php';

store_require_login('./website-editor.php');

if (!($con instanceof mysqli) || !store_user_has_permission($con, 'manage_settings')) {
    store_flash('danger', 'Website editor access is only available for approved manager/admin accounts.');
    header('Location: ./profile.php');
    exit;
}

$csrfKey = 'website_editor_panel';
$csrfToken = store_csrf_token($csrfKey);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $token = (string) ($_POST['csrf_token'] ?? '');
    if (!store_verify_csrf_token($csrfKey, $token)) {
        if (store_request_wants_json()) {
            http_response_code(403);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'success' => false,
                'message' => 'Security verification failed. Please refresh and try again.',
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;
        }

        store_flash('danger', 'Security verification failed. Please try again.');
        header('Location: ./website-editor.php');
        exit;
    }

    $result = store_save_settings($con, $_POST);

    if (store_request_wants_json()) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => (bool) ($result['success'] ?? false),
            'message' => (string) ($result['message'] ?? 'Settings processed.'),
            'editor' => store_public_editor_config($con),
            'cssVariables' => store_theme_css_variables($con),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    store_flash($result['success'] ? 'success' : 'danger', $result['message']);
    header('Location: ./website-editor.php');
    exit;
}

$settings = store_settings($con);

render_custom_page_header('La Imparial | Website Editor');
?>
<section class="catalog-admin">
    <div class="container">
        <div class="catalog-page__header">
            <h2>Website Editor</h2>
            <p>Control theme templates, gradients, card style, buttons, spacing, and website-wide branding from a cleaner editor page.</p>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="catalog-admin__panel">
                    <h4>Theme + Section Editor</h4>
                    <form action="./website-editor.php" method="post" class="form-row-gap">
                        <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                        <div class="catalog-template-panel">
                            <div>
                                <h5>Ready Website Templates</h5>
                                <p>Pick a full-look preset, then adjust any color or spacing before saving.</p>
                            </div>
                            <div class="catalog-template-actions">
                                <button type="button" class="quick-action--detail" data-template-preset="ocean-pro">Ocean Pro</button>
                                <button type="button" class="quick-action--detail" data-template-preset="emerald-retail">Emerald Retail</button>
                                <button type="button" class="quick-action--detail" data-template-preset="graphite-studio">Graphite Studio</button>
                                <label class="quick-action--favorite">
                                    Import JSON
                                    <input type="file" data-template-import accept="application/json,.json" hidden>
                                </label>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Theme Template</label>
                                    <select class="form-control" name="theme_template">
                                        <option value="ocean" <?php echo ($settings['theme_template'] ?? 'ocean') === 'ocean' ? 'selected' : ''; ?>>Ocean Gradient</option>
                                        <option value="sunset" <?php echo ($settings['theme_template'] ?? '') === 'sunset' ? 'selected' : ''; ?>>Sunset Gradient</option>
                                        <option value="emerald" <?php echo ($settings['theme_template'] ?? '') === 'emerald' ? 'selected' : ''; ?>>Emerald Gradient</option>
                                        <option value="graphite" <?php echo ($settings['theme_template'] ?? '') === 'graphite' ? 'selected' : ''; ?>>Graphite Gradient</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Default Color Mode</label>
                                    <select class="form-control" name="color_mode">
                                        <option value="light" <?php echo ($settings['color_mode'] ?? 'light') === 'light' ? 'selected' : ''; ?>>Light</option>
                                        <option value="dark" <?php echo ($settings['color_mode'] ?? '') === 'dark' ? 'selected' : ''; ?>>Dark</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Surface Style</label>
                                    <select class="form-control" name="surface_style">
                                        <option value="soft" <?php echo ($settings['surface_style'] ?? 'soft') === 'soft' ? 'selected' : ''; ?>>Soft Cards</option>
                                        <option value="glass" <?php echo ($settings['surface_style'] ?? '') === 'glass' ? 'selected' : ''; ?>>Glass Cards</option>
                                        <option value="bold" <?php echo ($settings['surface_style'] ?? '') === 'bold' ? 'selected' : ''; ?>>Bold Shadow</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Primary Color</label>
                                    <input type="text" class="form-control" name="theme_primary" value="<?php echo e((string) ($settings['theme_primary'] ?? '#0b1688')); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Secondary Color</label>
                                    <input type="text" class="form-control" name="theme_secondary" value="<?php echo e((string) ($settings['theme_secondary'] ?? '#5561b8')); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Accent Color</label>
                                    <input type="text" class="form-control" name="theme_accent" value="<?php echo e((string) ($settings['theme_accent'] ?? '#0f766e')); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Card Button Color</label>
                                    <input type="text" class="form-control" name="card_button_bg" value="<?php echo e((string) ($settings['card_button_bg'] ?? '#0b1688')); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Card Button Text</label>
                                    <input type="text" class="form-control" name="card_button_text" value="<?php echo e((string) ($settings['card_button_text'] ?? '#ffffff')); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Link Hover Color</label>
                                    <input type="text" class="form-control" name="link_hover_color" value="<?php echo e((string) ($settings['link_hover_color'] ?? '#0b1688')); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Label Slider Background</label>
                                    <input type="text" class="form-control" name="label_slider_bg" value="<?php echo e((string) ($settings['label_slider_bg'] ?? '#0b1688')); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Label Slider Text</label>
                                    <input type="text" class="form-control" name="label_slider_text" value="<?php echo e((string) ($settings['label_slider_text'] ?? '#ffffff')); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Card Radius</label>
                                    <input type="text" class="form-control" name="card_radius" value="<?php echo e((string) ($settings['card_radius'] ?? '18px')); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Button Radius</label>
                                    <input type="text" class="form-control" name="button_radius" value="<?php echo e((string) ($settings['button_radius'] ?? '999px')); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Section Gap</label>
                                    <input type="text" class="form-control" name="section_gap" value="<?php echo e((string) ($settings['section_gap'] ?? '90px')); ?>">
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label>Site Title</label>
                                    <input type="text" class="form-control" name="site_title" value="<?php echo e((string) ($settings['site_title'] ?? 'La Imparial Appliances')); ?>">
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label>Site Tagline</label>
                                    <input type="text" class="form-control" name="site_tagline" value="<?php echo e((string) ($settings['site_tagline'] ?? 'Premium appliances, clean comparisons, and faster support.')); ?>">
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label>Announcement Text</label>
                                    <textarea class="form-control" name="announcement_text" rows="3"><?php echo e((string) ($settings['announcement_text'] ?? '')); ?></textarea>
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="primary-btn">Save Website Theme</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="catalog-admin__helper">
                    <h4>How This Editor Works</h4>
                    <ul>
                        <li>Theme settings save in MySQL and apply across PHP pages without editing each section manually.</li>
                        <li>Template presets give you ready gradient looks that you can still tweak with custom colors.</li>
                        <li>Card radius, button radius, and spacing help redesign multiple sections together instead of one product at a time.</li>
                        <li>For deeper controls like custom product labels, section presets, and imports, use the admin pages alongside this editor.</li>
                    </ul>
                </div>

                <div class="catalog-admin__panel" style="margin-top:20px;">
                    <h4>Quick Routes</h4>
                    <div class="cart-page__actions">
                        <a class="quick-action--detail" href="./admin/customize.php">Open Admin Customize</a>
                        <a class="quick-action--cart" href="./admin/catalog.php">Open Catalog Controls</a>
                        <a class="quick-action--favorite" href="./profile.php">Back to Profile</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var templates = {
        'ocean-pro': {
            theme_template: 'ocean',
            color_mode: 'light',
            surface_style: 'soft',
            theme_primary: '#0b1688',
            theme_secondary: '#2563eb',
            theme_accent: '#0f766e',
            card_button_bg: '#0b1688',
            card_button_text: '#ffffff',
            link_hover_color: '#0f766e',
            label_slider_bg: '#0b1688',
            label_slider_text: '#ffffff',
            card_radius: '14px',
            button_radius: '999px',
            section_gap: '82px'
        },
        'emerald-retail': {
            theme_template: 'emerald',
            color_mode: 'light',
            surface_style: 'glass',
            theme_primary: '#047857',
            theme_secondary: '#10b981',
            theme_accent: '#0ea5e9',
            card_button_bg: '#047857',
            card_button_text: '#ffffff',
            link_hover_color: '#0ea5e9',
            label_slider_bg: '#065f46',
            label_slider_text: '#ffffff',
            card_radius: '12px',
            button_radius: '12px',
            section_gap: '76px'
        },
        'graphite-studio': {
            theme_template: 'graphite',
            color_mode: 'dark',
            surface_style: 'bold',
            theme_primary: '#111827',
            theme_secondary: '#475569',
            theme_accent: '#38bdf8',
            card_button_bg: '#111827',
            card_button_text: '#ffffff',
            link_hover_color: '#38bdf8',
            label_slider_bg: '#1f2937',
            label_slider_text: '#ffffff',
            card_radius: '10px',
            button_radius: '10px',
            section_gap: '70px'
        }
    };

    function applyTemplate(values) {
        Object.keys(values || {}).forEach(function (name) {
            var field = document.querySelector('[name="' + name + '"]');
            if (field) {
                field.value = values[name];
                field.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    }

    Array.prototype.forEach.call(document.querySelectorAll('[data-template-preset]'), function (button) {
        button.addEventListener('click', function () {
            applyTemplate(templates[button.getAttribute('data-template-preset')]);
        });
    });

    Array.prototype.forEach.call(document.querySelectorAll('[data-template-import]'), function (input) {
        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            var reader;
            if (!file) {
                return;
            }
            reader = new FileReader();
            reader.onload = function () {
                try {
                    applyTemplate(JSON.parse(String(reader.result || '{}')));
                } catch (error) {
                    alert('Template JSON could not be read.');
                }
            };
            reader.readAsText(file);
        });
    });
});
</script>
<?php render_custom_page_footer(); ?>
