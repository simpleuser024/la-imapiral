<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/connection.php';
require_once __DIR__ . '/store.php';

function render_catalog_template(string $templateFile, array $options = []): void
{
    global $con;

    $templatePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . $templateFile;

    if (!is_file($templatePath)) {
        http_response_code(404);
        echo 'Template not found.';
        return;
    }

    $html = file_get_contents($templatePath);

    if ($html === false) {
        http_response_code(500);
        echo 'Template could not be loaded.';
        return;
    }

    if (!empty($options['brandSlug'])) {
        $brandSlug = trim((string) $options['brandSlug']);

        if ($brandSlug !== '') {
            if (preg_match('/<body[^>]*data-brand-page="[^"]*"[^>]*>/i', $html) === 1) {
                $html = preg_replace('/(<body[^>]*data-brand-page=")[^"]*("[^>]*>)/i', '$1' . e($brandSlug) . '$2', $html, 1) ?? $html;
            } else {
                $html = preg_replace('/<body([^>]*)>/i', '<body$1 data-brand-page="' . e($brandSlug) . '">', $html, 1) ?? $html;
            }
        }
    }

    ensure_store_state();

    $payload = $con instanceof mysqli ? build_store_client_payload($con) : build_store_seed_client_payload();
    $editorConfig = $con instanceof mysqli ? store_public_editor_config($con) : ['canManageSettings' => false];
    $routes = $payload['routes'];
    $flash = pull_store_flash();
    $seoDescription = $con instanceof mysqli ? store_setting($con, 'seo_default_description', 'La Imparial appliance catalogue') : 'La Imparial appliance catalogue';
    $seoKeywords = $con instanceof mysqli ? store_setting($con, 'seo_default_keywords', 'La Imparial, appliances, product catalogue') : 'La Imparial, appliances, product catalogue';
    $themeCss = $con instanceof mysqli ? store_theme_css_variables($con) : '';
    $flashHtml = '';
    $siteBaseHref = store_absolute_url('/');
    $phpStoreCssHref = store_asset_url('css/php-store.css');
    $responsiveCssHref = store_asset_url('css/responsive-enhancements.css');
    $productStoreScript = store_asset_url('js/product-store.js');
    $siteInteractionsScript = store_asset_url('js/site-interactions.js');
    $themeToggleScript = store_asset_url('js/theme-toggle.js');
    $colorMode = $con instanceof mysqli ? store_setting($con, 'color_mode', 'light') : 'light';
    $colorModeJs = json_encode($colorMode, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

    if (is_array($flash)) {
        $flashHtml = '<div class="php-flash php-flash--' . e($flash['type']) . '">' . e($flash['message']) . '</div>';
    } elseif (!($con instanceof mysqli)) {
        $flashHtml = '<div class="php-flash php-flash--warning">Database is offline right now. Showing fallback catalog data.</div>';
    }

    $html = preg_replace(
        '#<a href="\./compare\.html"><img src="img/icon/heart\.png" alt="Compare"></a>#',
        '<a href="./favorites.php"><img src="img/icon/heart.png" alt="Favorites"> <span data-favorite-count>0</span></a>',
        $html
    ) ?? $html;

    $html = preg_replace(
        '#<a href="\./contact\.html"><img src="img/icon/cart\.png" alt="Contact">\s*<span>1</span></a>#',
        '<a href="./cart.php"><img src="img/icon/cart.png" alt="Cart"> <span data-cart-count>0</span></a>',
        $html
    ) ?? $html;

    $adminIcon = store_is_admin()
        ? '<a href="./admin/" class="header__icon-add" title="Admin Dashboard" aria-label="Admin Dashboard"><i class="fa fa-th-large" aria-hidden="true"></i></a>'
        : '';
    $editorIcon = $con instanceof mysqli && store_user_has_permission($con, 'manage_settings')
        ? '<a href="./website-editor.php" class="header__icon-add" title="Website Editor" aria-label="Website Editor"><i class="fa fa-paint-brush" aria-hidden="true"></i></a>'
        : '';
    $addIcon = $con instanceof mysqli && store_user_has_permission($con, 'create_product')
        ? '<a href="./product-form.php" class="header__icon-add" title="Add Product" aria-label="Add Product"><i class="fa fa-plus" aria-hidden="true"></i></a>'
        : '';
    $profileHref = store_is_logged_in() ? './profile.php' : './login.php';
    $profileAvatar = (string) ($_SESSION['user_avatar'] ?? '');
    $profileFit = (string) ($_SESSION['user_avatar_fit'] ?? 'cover');
    $profileAlt = store_is_logged_in() ? 'Profile' : 'Login';
    $profileAvatarMarkup = $profileAvatar !== ''
        ? '<span class="header__profile-avatar"><img src="' . e($profileAvatar) . '" alt="' . e($profileAlt) . '" style="object-fit:' . e($profileFit) . ';"></span>'
        : '<span class="header__profile-avatar"><i class="fa fa-user" aria-hidden="true"></i></span>';
    $profileMarkup = '<a href="' . $profileHref . '" class="header__profile-link" title="' . e($profileAlt) . '" aria-label="' . e($profileAlt) . '">' . $profileAvatarMarkup . '</a>';

    $html = preg_replace(
        '~<a href="#" class="search-switch"><img src="img/icon/search\.png" alt="Search"></a>~',
        '<a href="#" class="search-switch"><img src="img/icon/search.png" alt="Search"></a>' . $adminIcon . $editorIcon . $addIcon,
        $html
    ) ?? $html;

    $html = preg_replace(
        '#<div class="price"[^>]*>.*?</div>#s',
        $profileMarkup,
        $html
    ) ?? $html;

    $replacements = [
        './index.html' => './index.php',
        './shop.html' => './shop.php',
        './shop-details.html' => './shop-details.php',
        './compare.html' => './compare.php',
        './shopping-cart.html' => './cart.php',
        './about.html' => './about.php',
        './blog.html' => './blog.php',
        './blog-details.html' => './blog-details.php',
        './checkout.html' => './checkout.php',
        './contact.html' => './contact.php',
        './main.html' => './main.php',
        './haier.html' => './haier.php',
        './dawlance.html' => './dawlance.php',
        './samsung.html' => './samsung.php',
        './lg.html' => './lg.php',
        './orient.html' => './orient.php',
        './pel.html' => './pel.php',
    ];

    $html = strtr($html, $replacements);

    $html = preg_replace(
        '#<meta name="description" content="[^"]*">#i',
        '<meta name="description" content="' . e($seoDescription) . '">',
        $html,
        1
    ) ?? $html;
    $html = preg_replace(
        '#<meta name="keywords" content="[^"]*">#i',
        '<meta name="keywords" content="' . e($seoKeywords) . '">',
        $html,
        1
    ) ?? $html;

    $html = preg_replace(
        '#<head>#i',
        '<head>' . PHP_EOL . '    <base href="' . e($siteBaseHref) . '">' . PHP_EOL . '    <script>(function(){var mode=' . $colorModeJs . ';try{mode=window.localStorage.getItem(\'la_imparial_theme\')||mode;}catch(error){}document.documentElement.setAttribute(\'data-theme\',mode||\'light\');}());</script>',
        $html,
        1
    ) ?? $html;

    $dataScript = '<script>window.LA_IMPARIAL_STORE = ' . json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ';window.LA_IMPARIAL_EDITOR = ' . json_encode($editorConfig, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ';</script>';
    $cssLink = '    <link rel="stylesheet" href="' . e($phpStoreCssHref) . '" type="text/css">' . PHP_EOL
        . '    <link rel="stylesheet" href="' . e($responsiveCssHref) . '" type="text/css">' . PHP_EOL
        . ($themeCss !== '' ? '    <style>' . $themeCss . '</style>' . PHP_EOL : '')
        . '</head>';
    $html = str_replace('</head>', $cssLink, $html);

    if (str_contains($html, '<script src="js/product-store.js"></script>')) {
        $html = str_replace(
            '<script src="js/product-store.js"></script>',
            $dataScript . PHP_EOL . '    <script src="' . e($productStoreScript) . '"></script>' . PHP_EOL . '    <script src="' . e($siteInteractionsScript) . '"></script>' . PHP_EOL . '    <script src="' . e($themeToggleScript) . '"></script>',
            $html
        );
    } else {
        $html = str_replace('</body>', $dataScript . PHP_EOL . '    <script src="' . e($siteInteractionsScript) . '"></script>' . PHP_EOL . '    <script src="' . e($themeToggleScript) . '"></script>' . PHP_EOL . '</body>', $html);
    }

    if ($flashHtml !== '') {
        $updatedHtml = preg_replace('#<body([^>]*)>#i', '<body$1>' . PHP_EOL . $flashHtml, $html, 1);
        if (is_string($updatedHtml) && $updatedHtml !== $html) {
            $html = $updatedHtml;
        } elseif (str_contains($html, '<body>')) {
            $html = str_replace('<body>', '<body>' . PHP_EOL . $flashHtml, $html);
        }
    }

    echo $html;
}
