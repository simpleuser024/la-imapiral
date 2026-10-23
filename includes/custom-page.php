<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/connection.php';
require_once __DIR__ . '/store.php';

function render_custom_page_header(string $title, array $seo = []): void
{
    global $con;

    $cartCount = store_cart_count();
    $favoriteCount = count($_SESSION['favorites'] ?? []);
    $flash = pull_store_flash();
    $isLoggedIn = store_is_logged_in();
    $profileHref = $isLoggedIn ? './profile.php' : './login.php';
    $profileAvatar = (string) ($_SESSION['user_avatar'] ?? '');
    $profileFit = (string) ($_SESSION['user_avatar_fit'] ?? 'cover');
    $profileAlt = $isLoggedIn ? 'Profile' : 'Login';
    $profileAvatarMarkup = $profileAvatar !== ''
        ? '<span class="header__profile-avatar"><img src="' . e($profileAvatar) . '" alt="' . e($profileAlt) . '" style="object-fit:' . e($profileFit) . ';"></span>'
        : '<span class="header__profile-avatar"><i class="fa fa-user" aria-hidden="true"></i></span>';
    $profileMarkup = '<a href="' . $profileHref . '" class="header__profile-link" title="' . e($profileAlt) . '" aria-label="' . e($profileAlt) . '">' . $profileAvatarMarkup . '</a>';
    $canCreateProduct = $con instanceof mysqli ? store_user_has_permission($con, 'create_product') : false;
    $canManageSettings = $con instanceof mysqli ? store_user_has_permission($con, 'manage_settings') : false;
    $siteTitle = $con instanceof mysqli ? store_setting($con, 'site_title', 'La Imparial Appliances') : 'La Imparial Appliances';
    $announcementText = $con instanceof mysqli ? store_setting($con, 'announcement_text', 'Discover premium appliances with smart compare, support, and product detail tools.') : 'Discover premium appliances with smart compare, support, and product detail tools.';
    $seoDescription = (string) ($seo['description'] ?? ($con instanceof mysqli ? store_setting($con, 'seo_default_description', 'La Imparial appliance catalogue') : 'La Imparial appliance catalogue'));
    $seoKeywords = (string) ($seo['keywords'] ?? ($con instanceof mysqli ? store_setting($con, 'seo_default_keywords', 'La Imparial, appliances, product catalogue') : 'La Imparial, appliances, product catalogue'));
    $seoOgImage = (string) ($seo['image'] ?? ($con instanceof mysqli ? store_setting($con, 'seo_og_image', 'img/logo/logo.png') : 'img/logo/logo.png'));
    $seoRobots = (string) ($seo['robots'] ?? ($con instanceof mysqli ? store_setting($con, 'seo_robots', 'index,follow') : 'index,follow'));
    $seoType = (string) ($seo['type'] ?? 'website');
    $themeCss = $con instanceof mysqli ? store_theme_css_variables($con) : '';
    $siteBaseHref = store_absolute_url('/');
    $phpStoreCssHref = store_asset_url('css/php-store.css');
    $responsiveCssHref = store_asset_url('css/responsive-enhancements.css');
    $colorMode = $con instanceof mysqli ? store_setting($con, 'color_mode', 'light') : 'light';
    $colorModeJs = json_encode($colorMode, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    $dbFallbackNotice = !($con instanceof mysqli)
        ? '<div class="php-flash php-flash--warning">Database is offline right now. Showing fallback catalog data where possible.</div>'
        : '';
    $adminDashboardMarkup = store_is_admin()
        ? '<a href="./admin/" class="header__icon-add" title="Admin Dashboard" aria-label="Admin Dashboard"><i class="fa fa-th-large" aria-hidden="true"></i></a>'
        : '';
    $websiteEditorMarkup = $canManageSettings
        ? '<a href="./website-editor.php" class="header__icon-add" title="Website Editor" aria-label="Website Editor"><i class="fa fa-paint-brush" aria-hidden="true"></i></a>'
        : '';
    $addProductMarkup = $canCreateProduct
        ? '<a href="./product-form.php" class="header__icon-add" title="Add Product" aria-label="Add Product"><i class="fa fa-plus" aria-hidden="true"></i></a>'
        : '';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="description" content="<?php echo e($seoDescription); ?>">
        <meta name="keywords" content="<?php echo e($seoKeywords); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title><?php echo e($title); ?></title>
        <meta name="theme-color" content="#101724">
        <meta name="robots" content="<?php echo e($seoRobots); ?>">
        <meta property="og:title" content="<?php echo e($title); ?>">
        <meta property="og:description" content="<?php echo e($seoDescription); ?>">
        <meta property="og:image" content="<?php echo e(store_absolute_url($seoOgImage)); ?>">
        <meta property="og:type" content="<?php echo e($seoType); ?>">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="<?php echo e($title); ?>">
        <meta name="twitter:description" content="<?php echo e($seoDescription); ?>">
        <meta name="twitter:image" content="<?php echo e(store_absolute_url($seoOgImage)); ?>">
        <base href="<?php echo e($siteBaseHref); ?>">
        <link rel="canonical" href="<?php echo e((string) ($seo['canonical'] ?? store_absolute_url(basename((string) ($_SERVER['PHP_SELF'] ?? 'index.php'))))); ?>">
        <script>(function(){var mode=<?php echo $colorModeJs; ?>;try{mode=window.localStorage.getItem('la_imparial_theme')||mode;}catch(error){}document.documentElement.setAttribute('data-theme',mode||'light');}());</script>
        <link rel="icon" type="image/png" href="img/favicon/favicon.png">
        <link rel="shortcut icon" href="img/favicon/favicon.png">
        <link rel="apple-touch-icon" href="img/favicon/favicon.png">
        <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="css/bootstrap.min.css" type="text/css">
        <link rel="stylesheet" href="css/font-awesome.min.css" type="text/css">
        <link rel="stylesheet" href="css/elegant-icons.css" type="text/css">
        <link rel="stylesheet" href="css/magnific-popup.css" type="text/css">
        <link rel="stylesheet" href="css/nice-select.css" type="text/css">
        <link rel="stylesheet" href="css/owl.carousel.min.css" type="text/css">
        <link rel="stylesheet" href="css/slicknav.min.css" type="text/css">
        <link rel="stylesheet" href="css/style.css" type="text/css">
        <link rel="stylesheet" href="css/shop.css" type="text/css">
        <link rel="stylesheet" href="css/shop-details.css" type="text/css">
        <link rel="stylesheet" href="<?php echo e($phpStoreCssHref); ?>" type="text/css">
        <link rel="stylesheet" href="<?php echo e($responsiveCssHref); ?>" type="text/css">
        <?php if ($themeCss !== ''): ?>
            <style><?php echo $themeCss; ?></style>
        <?php endif; ?>
    </head>
    <body>
    <div id="preloder">
        <div class="loader"></div>
    </div>

    <?php if (is_array($flash)): ?>
        <div class="php-flash php-flash--<?php echo e($flash['type']); ?>"><?php echo e($flash['message']); ?></div>
    <?php endif; ?>
    <?php if ($dbFallbackNotice !== ''): ?>
        <?php echo $dbFallbackNotice; ?>
    <?php endif; ?>

    <div class="offcanvas-menu-overlay"></div>
    <div class="offcanvas-menu-wrapper">
        <div class="offcanvas__logo">
            <a href="./index.php"><img src="img/logo/logo.png" alt="La Imparial"></a>
        </div>
        <div class="offcanvas__option">
            <div class="offcanvas__links">
                <a href="./index.php#brand-lineup">Brands</a>
                <a href="./shop.php?view=categories">Categories</a>
                <?php if ($canManageSettings): ?><a href="./website-editor.php">Website Editor</a><?php endif; ?>
                <a href="<?php echo $profileHref; ?>"><?php echo $isLoggedIn ? 'Profile' : 'Login'; ?></a>
                <?php if ($isLoggedIn): ?><a href="./logout.php">Logout</a><?php endif; ?>
            </div>
            <div class="offcanvas__top__hover">
                <span>Brands <i class="arrow_carrot-down"></i></span>
                <ul>
                    <li>HAIER</li>
                    <li>DAWLANCE</li>
                    <li>SAMSUNG</li>
                    <li>LG</li>
                    <li>ORIENT</li>
                    <li>PEL</li>
                </ul>
            </div>
        </div>
        <div class="offcanvas__nav__option">
            <a href="#" class="search-switch"><img src="img/icon/search.png" alt="Search"></a>
            <?php echo $adminDashboardMarkup; ?>
            <?php echo $websiteEditorMarkup; ?>
            <?php echo $addProductMarkup; ?>
            <a href="./favorites.php"><img src="img/icon/heart.png" alt="Favorites"> <span data-favorite-count><?php echo $favoriteCount; ?></span></a>
            <a href="./cart.php"><img src="img/icon/cart.png" alt="Cart"> <span data-cart-count><?php echo $cartCount; ?></span></a>
            <?php echo $profileMarkup; ?>
        </div>
        <div id="mobile-menu-wrap"></div>
        <div class="offcanvas__text">
            <p>Filter appliances by brand or category, then open summaries, comparison, and feature guides.</p>
        </div>
    </div>

    <header class="header">
        <div class="header__top">
            <div class="container">
                <div class="row">
                    <div class="col-lg-6 col-md-7">
                        <div class="header__top__left">
                            <p><?php echo e($announcementText); ?></p>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-5">
                        <div class="header__top__right">
                            <div class="header__top__links">
                                <a href="./index.php#brand-lineup">Brands</a>
                                <a href="./shop.php?view=categories">Categories</a>
                                <a href="./compare.php">Compare</a>
                                <?php if ($canManageSettings): ?><a href="./website-editor.php">Website Editor</a><?php endif; ?>
                            </div>
                            <div class="header__top__hover">
                                <span>Brands <i class="arrow_carrot-down"></i></span>
                                <ul>
                                    <li>HAIER</li>
                                    <li>DAWLANCE</li>
                                    <li>SAMSUNG</li>
                                    <li>LG</li>
                                    <li>ORIENT</li>
                                    <li>PEL</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-3">
                    <div class="header__logo">
                        <a href="./index.php"><img src="img/logo/logo.png" alt="La Imparial"></a>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6">
                    <nav class="header__menu mobile-menu">
                        <ul>
                            <li><a href="./index.php">Home</a></li>
                            <li><a href="./index.php#brand-lineup">Brands</a>
                                <ul class="dropdown">
                                    <li><a href="./haier.php">HAIER</a></li>
                                    <li><a href="./dawlance.php">DAWLANCE</a></li>
                                    <li><a href="./samsung.php">SAMSUNG</a></li>
                                    <li><a href="./lg.php">LG</a></li>
                                    <li><a href="./orient.php">ORIENT</a></li>
                                    <li><a href="./pel.php">PEL</a></li>
                                </ul>
                            </li>
                            <li><a href="./shop.php?view=categories">Categories</a></li>
                            <li><a href="./shop.php?view=products">Product Details</a></li>
                            <li><a href="./reviews.php">Reviews</a></li>
                            <li><a href="./compare.php">Compare Products</a></li>
                            <?php if ($canManageSettings): ?>
                                <li><a href="./website-editor.php">Website Editor</a></li>
                            <?php endif; ?>
                            <?php if (store_is_admin()): ?>
                                <li><a href="./admin/">Admin Dashboard</a></li>
                            <?php endif; ?>
                            <li><a href="./contact.php">Contact Us</a></li>
                        </ul>
                    </nav>
                </div>
                <div class="col-lg-3 col-md-3">
                    <div class="header__nav__option">
                        <a href="#" class="search-switch"><img src="img/icon/search.png" alt="Search"></a>
                        <?php echo $adminDashboardMarkup; ?>
                        <?php echo $websiteEditorMarkup; ?>
                        <?php echo $addProductMarkup; ?>
                        <a href="./favorites.php"><img src="img/icon/heart.png" alt="Favorites"> <span data-favorite-count><?php echo $favoriteCount; ?></span></a>
                        <a href="./cart.php"><img src="img/icon/cart.png" alt="Cart"> <span data-cart-count><?php echo $cartCount; ?></span></a>
                        <?php echo $profileMarkup; ?>
                    </div>
                </div>
            </div>
            <div class="canvas__open"><i class="fa fa-bars"></i></div>
        </div>
    </header>
    <?php
}

function render_custom_page_footer(): void
{
    global $con;

    $payload = $con instanceof mysqli ? build_store_client_payload($con) : build_store_seed_client_payload();
    $editorConfig = $con instanceof mysqli ? store_public_editor_config($con) : ['canManageSettings' => false];
    $productStoreScript = store_asset_url('js/product-store.js');
    $siteInteractionsScript = store_asset_url('js/site-interactions.js');
    $themeToggleScript = store_asset_url('js/theme-toggle.js');
    $mainScript = store_asset_url('js/main.js');
    ?>
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6 col-sm-6">
                    <div class="footer__about">
                        <div class="footer__logo">
                            <a href="./index.php"><img src="img/logo/logo.png" alt="La Imparial"></a>
                        </div>
                        <p>La Imparial has been selling appliances for 20 years and now offers a structured online
                            catalogue for product discovery, comparison, and support.</p>
                    </div>
                </div>
                <div class="col-lg-2 offset-lg-1 col-md-3 col-sm-6">
                    <div class="footer__widget">
                        <h6>Explore</h6>
                        <ul>
                            <li><a href="./index.php">Home</a></li>
                            <li><a href="./shop.php?view=products">Product Details</a></li>
                            <li><a href="./shop.php?view=categories">Categories</a></li>
                            <li><a href="./compare.php">Compare Products</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-2 col-md-3 col-sm-6">
                    <div class="footer__widget">
                        <h6>Brands</h6>
                        <ul>
                            <li><a href="./haier.php">HAIER</a></li>
                            <li><a href="./dawlance.php">DAWLANCE</a></li>
                            <li><a href="./samsung.php">SAMSUNG</a></li>
                            <li><a href="./lg.php">LG</a></li>
                            <li><a href="./orient.php">ORIENT</a></li>
                            <li><a href="./pel.php">PEL</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-3 offset-lg-1 col-md-6 col-sm-6">
                    <div class="footer__widget">
                        <h6>Support</h6>
                        <ul>
                            <li><a href="./contact.php">Contact Us</a></li>
                            <li><a href="./blog.php">Brand Stories</a></li>
                            <li><a href="./about.php">About La Imparial</a></li>
                            <li><a href="./checkout.php">Checkout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12 text-center">
                    <div class="footer__copyright__text">
                        <p>
                            Copyright &copy;
                            <script>
                                document.write(new Date().getFullYear());
                            </script>
                            La Imparial Appliances.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <div class="search-model">
        <div class="h-100 d-flex align-items-center justify-content-center">
            <div class="search-close-switch">+</div>
            <form class="search-model-form">
                <input type="text" id="search-input" placeholder="Search appliances">
            </form>
        </div>
    </div>

    <script>window.LA_IMPARIAL_STORE = <?php echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;window.LA_IMPARIAL_EDITOR = <?php echo json_encode($editorConfig, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;</script>
    <script src="js/jquery-3.3.1.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/jquery.nice-select.min.js"></script>
    <script src="js/jquery.nicescroll.min.js"></script>
    <script src="js/jquery.magnific-popup.min.js"></script>
    <script src="js/jquery.countdown.min.js"></script>
    <script src="js/jquery.slicknav.js"></script>
    <script src="js/mixitup.min.js"></script>
    <script src="js/owl.carousel.min.js"></script>
    <script src="<?php echo e($productStoreScript); ?>"></script>
    <script src="<?php echo e($siteInteractionsScript); ?>"></script>
    <script src="<?php echo e($themeToggleScript); ?>"></script>
    <script src="<?php echo e($mainScript); ?>"></script>
    </body>
    </html>
    <?php
}
