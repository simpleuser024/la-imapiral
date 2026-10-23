(function (window, document) {
    'use strict';

    var storePayload = window.LA_IMPARIAL_STORE || null;
    var storeRoutes = storePayload && storePayload.routes ? storePayload.routes : null;
    var storeSession = storePayload && storePayload.session ? storePayload.session : null;
    var reviewMap = storePayload && storePayload.reviewMap ? storePayload.reviewMap : {};
    var reviewSummary = storePayload && storePayload.reviewSummary ? storePayload.reviewSummary : {};

    var categoryMeta = {
        'air-conditioners': {
            label: 'Air Conditioners',
            doc: './docs/haier-frostcool-inverter-ac.doc',
            note: 'Air conditioners focus on fast cooling, inverter performance, and all-day room comfort.'
        },
        'washing-machines': {
            label: 'Washing Machines',
            doc: './docs/haier-twintub-washer-dryer.doc',
            note: 'Washing machines focus on drum care, family-size wash loads, and easy cycle control.'
        },
        'washer-dryers': {
            label: 'Washer Dryers',
            doc: './docs/haier-twintub-washer-dryer.doc',
            note: 'Washer dryers focus on complete wash-and-dry convenience for everyday households.'
        },
        refrigerators: {
            label: 'Refrigerators',
            doc: './docs/dawlance-elegance-9193-refrigerator.doc',
            note: 'Refrigerators focus on food freshness, family storage space, and balanced cooling performance.'
        },
        'deep-freezers': {
            label: 'Deep Freezers',
            doc: './docs/dawlance-elegance-9193-refrigerator.doc',
            note: 'Deep freezers focus on frozen storage volume, reliable cooling retention, and long-term food safety.'
        },
        'microwave-ovens': {
            label: 'Microwave Ovens',
            doc: './docs/orient-majesty-microwave.doc',
            note: 'Microwave ovens focus on quick reheating, smart presets, and compact kitchen convenience.'
        },
        'cooking-ranges': {
            label: 'Cooking Ranges',
            doc: './docs/orient-majesty-microwave.doc',
            note: 'Cooking ranges focus on everyday baking, grilling, and full-meal kitchen support.'
        },
        'water-dispensers': {
            label: 'Water Dispensers',
            doc: './docs/lg-smartwash-dryer-combo.doc',
            note: 'Water dispensers focus on instant hot and cold access with practical family-use designs.'
        }
    };

    // Replace these paths with your actual product photos.
    // These image paths automatically show on: Home, Brand pages, Categories, Product Details, and Compare page.
    var defaultProductImage = 'img/product/product-1.jpg';
    var productImageMap = {
        // HAIER
        'haier-frostcool-15-ac': 'img/products/haier/FrostCool 1.5 Ton Inverter AC haier.jpg', // FrostCool 1.5 Ton Inverter AC
        'haier-polar-breeze-1-ac': 'img/products/haier/Polar Breeze 1 Ton Split AC haier.jpg', // Polar Breeze 1 Ton Split AC
        'haier-turbochill-2-ac': 'img/products/haier/Turbo Chill 2 Ton DC Inverter AC haier.jpg', // Turbo Chill 2 Ton DC Inverter AC
        'haier-twintub-12kg': 'img/products/haier/TwinTub 12kg Washer Dryer haier.jpg', // TwinTub 12kg Washer Dryer
        'haier-frontcare-8kg': 'img/products/haier/FrontCare 8kg Washer haier.jpg', // FrontCare 8kg Washer
        'haier-spindry-10kg': 'img/products/haier/TwinTub 12kg Washer Dryer haier.jpg', // Legacy washer alias fallback
        'haier-aquaflow-dispenser': 'img/products/haier/AquaFlow Bottom Load Water Dispenser haier.jpg', // AquaFlow Bottom Load Water Dispenser
        'haier-freshbox-438': 'img/products/haier/FreshBox 438 Refrigerator haier.jpg', // FreshBox 438 Refrigerator
        'haier-smartstore-520': 'img/products/haier/SmartStore 520 Deep Freezer haier.jpg', // SmartStore 520 Deep Freezer
        'haier-chefwave-28': 'img/products/haier/ChefWave 28 Microwave Oven haier.jpg', // ChefWave 28 Microwave Oven
        'haier-heatserve-32': 'img/products/haier/HeatServe 32 Cooking Range haier.jpg', // HeatServe 32 Cooking Range

        // DAWLANCE
        'dawlance-coolmax-18-ac': 'img/products/dowlance/CoolMax 1.8 Ton Inverter AC dawlance.webp', // CoolMax 1.8 Ton Inverter AC
        'dawlance-airglide-1-ac': 'img/products/dowlance/AirGlide 1 Ton Split AC dawlance.webp', // AirGlide 1 Ton Split AC
        'dawlance-powercool-2-ac': 'img/products/dowlance/PowerCool 2 Ton Inverter AC dawlance.webp', // PowerCool 2 Ton Inverter AC
        'dawlance-spinplus-10kg': 'img/products/dowlance/SpinPlus 10kg Washer dawlance.webp', // SpinPlus 10kg Washer
        'dawlance-twincare-12kg': 'img/products/dowlance/TwinCare 12kg Washer Dryer dawlance.jpeg', // TwinCare 12kg Washer Dryer
        'dawlance-ecowash-8kg': 'img/products/dowlance/SpinPlus 10kg Washer dawlance.webp', // Legacy washer alias fallback
        'dawlance-hydrosip-dispenser': 'img/products/dowlance/HydroSip Top Load Water Dispenser dawlance.webp', // HydroSip Top Load Water Dispenser
        'dawlance-elegance-9193': 'img/products/dowlance/Elegance 9193 Refrigerator dawlance.webp', // Elegance 9193 Refrigerator
        'dawlance-urbanchill-505': 'img/products/dowlance/UrbanChill 505 Deep Freezer dawlance.webp', // UrbanChill 505 Deep Freezer
        'dawlance-chefmaster-30': 'img/products/dowlance/ChefMaster 30 Microwave Oven dawlance.webp', // ChefMaster 30 Microwave Oven
        'dawlance-grillpro-25': 'img/products/dowlance/GrillPro 25 Cooking Range dawlance.png', // GrillPro 25 Cooking Range

        // SAMSUNG
        'samsung-windhub-15-ac': 'img/products/samsung/WindHub 1.5 Ton Digital AC samsung.jpg', // WindHub 1.5 Ton Digital AC
        'samsung-airsense-1-ac': 'img/products/samsung/AirSense 1 Ton Inverter AC samsung.jfif', // AirSense 1 Ton Inverter AC
        'samsung-maxcool-2-ac': 'img/products/samsung/MaxCool 2 Ton Split AC samsung.webp', // MaxCool 2 Ton Split AC
        'samsung-ecobubble-9kg': 'img/products/samsung/EcoBubble 9kg Front Load Washer samsung.jfif', // EcoBubble 9kg Front Load Washer
        'samsung-washsmart-11kg': 'img/products/samsung/WashSmart 11kg Laundry System samsung.jfif', // WashSmart 11kg Laundry System
        'samsung-drumcare-8kg': 'img/products/samsung/EcoBubble 9kg Front Load Washer samsung.jfif', // Legacy washer alias fallback
        'samsung-aquahub-dispenser': 'img/products/samsung/AquaHub Floor Standing Water Dispenser samsung.avif', // AquaHub Floor Standing Water Dispenser
        'samsung-coolstore-420': 'img/products/samsung/CoolStore 420 Refrigerator samsung.webp', // CoolStore 420 Refrigerator
        'samsung-twinfresh-510': 'img/products/samsung/TwinFresh 510 Deep Freezer samsung.avif', // TwinFresh 510 Deep Freezer
        'samsung-heatbox-32': 'img/products/samsung/HeatBox 32 Microwave Oven samsung.avif', // HeatBox 32 Microwave Oven
        'samsung-kitchenhub-26': 'img/products/samsung/KitchenHub 26 Cooking Range samsung.avif', // KitchenHub 26 Cooking Range

        // LG
        'lg-airflow-15-ac': 'img/products/lg/AirFlow 1.5 Ton Inverter AC lg.avif', // AirFlow 1.5 Ton Inverter AC
        'lg-breezesmart-1-ac': 'img/products/lg/BreezeSmart 1 Ton Split AC lg.jfif', // BreezeSmart 1 Ton Split AC
        'lg-dualcool-2-ac': 'img/products/lg/DualCool 2 Ton Inverter AC lg.jfif', // DualCool 2 Ton Inverter AC
        'lg-smartwash-8kg': 'img/products/lg/SmartWash 8kg Dryer Combo lg.webp', // SmartWash 8kg Dryer Combo
        'lg-turboclean-10kg': 'img/products/lg/TurboClean 10kg Washer lg.avif', // TurboClean 10kg Washer
        'lg-freshrinse-12kg': 'img/products/lg/SmartWash 8kg Dryer Combo lg.webp', // Legacy laundry alias fallback
        'lg-pureflow-dispenser': 'img/products/lg/PureFlow Bottom Load Water Dispenser lg.avif', // PureFlow Bottom Load Water Dispenser
        'lg-heatchef-30': 'img/products/lg/HeatChef 30 Microwave Oven lg.avif', // HeatChef 30 Microwave Oven
        'lg-freshline-450': 'img/products/lg/FreshLine 450 Refrigerator lg.avif', // FreshLine 450 Refrigerator
        'lg-instastore-530': 'img/products/lg/InstaStore 530 Deep Freezer lg.avif', // InstaStore 530 Deep Freezer
        'lg-grillmate-27': 'img/products/lg/GrillMate 27 Cooking Range lg.jfif', // GrillMate 27 Cooking Range

        // ORIENT
        'orient-arctic-15-ac': 'img/products/orient/Arctic 1.5 Ton Inverter AC orient.jfif', // Arctic 1.5 Ton Inverter AC
        'orient-coolwave-1-ac': 'img/products/orient/CoolWave 1 Ton Split AC orient.webp', // CoolWave 1 Ton Split AC
        'orient-turbobreeze-2-ac': 'img/products/orient/TurboBreeze 2 Ton Inverter AC orient.webp', // TurboBreeze 2 Ton Inverter AC
        'orient-freshkeeper-390': 'img/products/orient/FreshKeeper 390 Refrigerator orient.webp', // FreshKeeper 390 Refrigerator
        'orient-coolpantry-480': 'img/products/orient/CoolPantry 480 Deep Freezer orient.jpg', // CoolPantry 480 Deep Freezer
        'orient-majesty-30': 'img/products/orient/Majesty 30 Microwave Oven orient.jpg', // Majesty 30 Microwave Oven
        'orient-quickchef-24': 'img/products/orient/QuickChef 24 Cooking Range orient.webp', // QuickChef 24 Cooking Range
        'orient-laundrypro-9kg': 'img/products/orient/LaundryPro 9kg Washer orient.jpg', // LaundryPro 9kg Washer
        'orient-drycare-7kg': 'img/products/orient/TwinWash 11kg Washer Dryer orient.webp', // Legacy laundry alias fallback
        'orient-aquapure-dispenser': 'img/products/orient/AquaPure Water Dispenser orient.webp', // AquaPure Water Dispenser
        'orient-twinwash-11kg': 'img/products/orient/TwinWash 11kg Washer Dryer orient.webp', // TwinWash 11kg Washer Dryer

        // PEL
        'pel-arcticpro-15-ac': 'img/products/pel/ArcticPro 1.5 Ton Inverter AC pel.webp', // ArcticPro 1.5 Ton Inverter AC
        'pel-chillmax-1-ac': 'img/products/pel/ChillMax 1 Ton Split AC pel.webp', // ChillMax 1 Ton Split AC
        'pel-smartcool-2-ac': 'img/products/pel/SmartCool 2 Ton DC Inverter AC pel.webp', // SmartCool 2 Ton DC Inverter AC
        'pel-washmate-9kg': 'img/products/pel/WashMate 9kg Washer pel.jpg', // WashMate 9kg Washer
        'pel-twinspin-12kg': 'img/products/pel/TwinSpin 12kg Washer Dryer pel.webp', // TwinSpin 12kg Washer Dryer
        'pel-quickrinse-8kg': 'img/products/pel/WashMate 9kg Washer pel.jpg', // Legacy washer alias fallback
        'pel-hydrofresh-dispenser': 'img/products/pel/HydroFresh Water Dispenser pel.webp', // HydroFresh Water Dispenser
        'pel-iceline-410': 'img/products/pel/IceLine 410 Refrigerator pel.jfif', // IceLine 410 Refrigerator
        'pel-familyfresh-500': 'img/products/pel/FamilyFresh 500 Deep Freezer pel.webp', // FamilyFresh 500 Deep Freezer
        'pel-microbake-28': 'img/products/pel/MicroBake 28 Microwave Oven pel.webp', // MicroBake 28 Microwave Oven
        'pel-grillwave-26': 'img/products/pel/GrillWave 26 Cooking Range pel.jfif' // GrillWave 26 Cooking Range
    };

    // BRAND LOGOS FOR THE MOVING MARQUEE
    // Put your logo files in `img/brand-logos/` and set the path here.
    // Example:
    // haier: 'img/brand-logos/haier.png'
    var brandLogoImageMap = {
        haier: 'img/brand-logos/haier.png',
        dawlance: 'img/brand-logos/Dawlance.png',
        samsung: 'img/brand-logos/Samsung.svg',
        lg: 'img/brand-logos/lg.png',
        orient: 'img/brand-logos/orient.png',
        pel: 'img/brand-logos/pel.svg'
    };

    // HOME PAGE CUSTOM IMAGES
    // `homeKitchenPromo.src` = left-side Kitchen Appliance banner image on home page
    // `homeEssentialGallery[0].src` = Everyday Living / Essential Home Appliances main image
    // `homeSmartPromo.src` = big poster image in Kitchen Edit section
    // `brandHighlightImages` = brand page highlight card images
    // Replace these with your own chosen images whenever you are ready.
    var showcaseMediaConfig = {
        homeKitchenPromo: {
            src: 'img/banner/ketchen.webp',
            alt: 'La Imperial kitchen highlight visual'
        },
        homeEssentialGallery: [
            { src: 'img/banner/home.webp', alt: 'La Imperial appliances in a home setting' },
            { src: 'img/banner/banner-2.jpg', alt: 'Cooling and comfort interior highlight' },
            { src: 'img/banner/banner-3.jpg', alt: 'Modern appliance showcase detail' },
            { src: 'img/hero/hero-1.jpg', alt: 'Premium appliance lifestyle scene' }
        ],
        homeSmartPromo: {
            src: 'img/banner/smart.webp',
            alt: 'Kitchen edit promotional poster'
        },
        brandHighlightImages: {
            haier: [
                { src: getProductPrimaryImage('haier-frostcool-15-ac'), alt: 'Haier FrostCool 1.5 Ton Inverter AC' },
                { src: getProductPrimaryImage('haier-frontcare-8kg'), alt: 'Haier FrontCare 8kg Washer' },
                { src: getProductPrimaryImage('haier-freshbox-438'), alt: 'Haier FreshBox 438 Refrigerator' },
                { src: getProductPrimaryImage('haier-heatserve-32'), alt: 'Haier HeatServe 32 Cooking Range' }
            ],
            dawlance: [
                { src: getProductPrimaryImage('dawlance-coolmax-18-ac'), alt: 'Dawlance CoolMax 1.8 Ton Inverter AC' },
                { src: getProductPrimaryImage('dawlance-twincare-12kg'), alt: 'Dawlance TwinCare 12kg Washer Dryer' },
                { src: getProductPrimaryImage('dawlance-elegance-9193'), alt: 'Dawlance Elegance 9193 Refrigerator' },
                { src: getProductPrimaryImage('dawlance-grillpro-25'), alt: 'Dawlance GrillPro 25 Cooking Range' }
            ],
            samsung: [
                { src: getProductPrimaryImage('samsung-windhub-15-ac'), alt: 'Samsung WindHub 1.5 Ton Digital AC' },
                { src: getProductPrimaryImage('samsung-ecobubble-9kg'), alt: 'Samsung EcoBubble 9kg Front Load Washer' },
                { src: getProductPrimaryImage('samsung-coolstore-420'), alt: 'Samsung CoolStore 420 Refrigerator' },
                { src: getProductPrimaryImage('samsung-kitchenhub-26'), alt: 'Samsung KitchenHub 26 Cooking Range' }
            ],
            lg: [
                { src: getProductPrimaryImage('lg-airflow-15-ac'), alt: 'LG AirFlow 1.5 Ton Inverter AC' },
                { src: getProductPrimaryImage('lg-smartwash-8kg'), alt: 'LG SmartWash 8kg Dryer Combo' },
                { src: getProductPrimaryImage('lg-freshline-450'), alt: 'LG FreshLine 450 Refrigerator' },
                { src: getProductPrimaryImage('lg-heatchef-30'), alt: 'LG HeatChef 30 Microwave Oven' }
            ],
            orient: [
                { src: getProductPrimaryImage('orient-arctic-15-ac'), alt: 'Orient Arctic 1.5 Ton Inverter AC' },
                { src: getProductPrimaryImage('orient-laundrypro-9kg'), alt: 'Orient LaundryPro 9kg Washer' },
                { src: getProductPrimaryImage('orient-freshkeeper-390'), alt: 'Orient FreshKeeper 390 Refrigerator' },
                { src: getProductPrimaryImage('orient-majesty-30'), alt: 'Orient Majesty 30 Microwave Oven' }
            ],
            pel: [
                { src: getProductPrimaryImage('pel-arcticpro-15-ac'), alt: 'PEL ArcticPro 1.5 Ton Inverter AC' },
                { src: getProductPrimaryImage('pel-washmate-9kg'), alt: 'PEL WashMate 9kg Washer' },
                { src: getProductPrimaryImage('pel-iceline-410'), alt: 'PEL IceLine 410 Refrigerator' },
                { src: getProductPrimaryImage('pel-microbake-28'), alt: 'PEL MicroBake 28 Microwave Oven' }
            ]
        }
    };

    var featuredProductSlugs = [
        'haier-frostcool-15-ac',
        'dawlance-elegance-9193',
        'samsung-ecobubble-9kg',
        'lg-dualcool-2-ac',
        'orient-majesty-30',
        'pel-familyfresh-500',
        'haier-smartstore-520',
        'pel-smartcool-2-ac'
    ];

    if (storePayload && storePayload.brandLogoImageMap) {
        brandLogoImageMap = storePayload.brandLogoImageMap;
    }
    if (storePayload && storePayload.productImageMap) {
        productImageMap = storePayload.productImageMap;
    }
    if (storePayload && storePayload.featuredProductSlugs) {
        featuredProductSlugs = storePayload.featuredProductSlugs;
    }
    if (storePayload && storePayload.categories) {
        categoryMeta = storePayload.categories;
    }
    if (storePayload && storePayload.siteMediaConfig) {
        if (storePayload.siteMediaConfig.homeKitchenPromo) {
            showcaseMediaConfig.homeKitchenPromo = storePayload.siteMediaConfig.homeKitchenPromo;
        }
        if (storePayload.siteMediaConfig.homeEssentialGallery && storePayload.siteMediaConfig.homeEssentialGallery.length) {
            showcaseMediaConfig.homeEssentialGallery = storePayload.siteMediaConfig.homeEssentialGallery;
        }
        if (storePayload.siteMediaConfig.homeSmartPromo) {
            showcaseMediaConfig.homeSmartPromo = storePayload.siteMediaConfig.homeSmartPromo;
        }
        if (storePayload.siteMediaConfig.brandHighlightImages && Object.keys(storePayload.siteMediaConfig.brandHighlightImages).length) {
            showcaseMediaConfig.brandHighlightImages = storePayload.siteMediaConfig.brandHighlightImages;
        }
        if (storePayload.siteMediaConfig.siteLogo) {
            showcaseMediaConfig.siteLogo = storePayload.siteMediaConfig.siteLogo;
        }
        if (storePayload.siteMediaConfig.splashLogo) {
            showcaseMediaConfig.splashLogo = storePayload.siteMediaConfig.splashLogo;
        }
    }

    function getRoute(key, fallback) {
        if (storeRoutes && storeRoutes[key]) {
            return storeRoutes[key];
        }
        return fallback;
    }

    function appBasePath() {
        var homeRoute = getRoute('home', './index.php');
        var path = '';

        try {
            path = new URL(homeRoute, window.location.origin).pathname;
        } catch (error) {
            path = String(homeRoute || '');
        }

        path = String(path || '').replace(/\\/g, '/').replace(/\/index\.(?:php|html)$/i, '').replace(/\/{2,}/g, '/');

        if (path === '' || path === '.') {
            return '';
        }

        if (path.charAt(0) !== '/') {
            path = '/' + path;
        }

        return path.replace(/\/$/, '');
    }

    function normalizeAppHref(href) {
        var base = appBasePath();
        var sanitized;
        var resolved;

        if (typeof href !== 'string' || href === '') {
            return href;
        }

        if (/^(?:https?:|mailto:|tel:|javascript:|#)/i.test(href)) {
            return href;
        }

        sanitized = href.replace(/\\/g, '/').replace(/[A-Za-z]:/g, '').replace(/\/{2,}/g, '/');

        try {
            resolved = new URL(sanitized, window.location.origin + (base ? base + '/' : '/'));
            return resolved.pathname + resolved.search + resolved.hash;
        } catch (error) {
            return sanitized;
        }
    }

    function toPhpHref(href) {
        if (typeof href !== 'string') {
            return href;
        }

        return normalizeAppHref(href.replace(/\.html/gi, '.php'));
    }

    var brandAccent = {
        haier: '#2e76d0',
        dawlance: '#00895c',
        samsung: '#1b4fd5',
        lg: '#b20d5d',
        orient: '#d56c11',
        pel: '#118a73'
    };

    var brandStories = {
        haier: {
            headline: 'Haier smart cooling, laundry, and kitchen comfort in one premium lineup.',
            summary: 'Explore Haier inverter cooling, daily laundry support, refrigeration essentials, and compact kitchen convenience from one connected brand showcase.',
            tags: ['Inverter Cooling', 'Laundry Care', 'Kitchen Heat']
        },
        dawlance: {
            headline: 'Dawlance everyday home appliances built for family comfort and practical use.',
            summary: 'Browse Dawlance products for bedrooms, kitchens, family laundry, and frozen storage with focused everyday performance and reliable value.',
            tags: ['Family Use', 'Cooling Power', 'Kitchen Range']
        },
        samsung: {
            headline: 'Samsung appliances for connected living, cleaner laundry, and balanced home comfort.',
            summary: 'Discover Samsung products across smart cooling, digital wash systems, refrigeration, and quick-heat kitchen solutions.',
            tags: ['Digital Care', 'Smart Cooling', 'Modern Design']
        },
        lg: {
            headline: 'LG premium appliances designed for refined comfort, efficient wash cycles, and clean lines.',
            summary: 'Open the LG collection for elegant cooling, practical laundry systems, and kitchen products that feel polished and high-end.',
            tags: ['Premium Comfort', 'Smart Wash', 'Elegant Kitchen']
        },
        orient: {
            headline: 'Orient appliances that balance value, kitchen speed, and dependable daily cooling.',
            summary: 'Check Orient products for fast room comfort, compact refrigeration, family-use washers, and kitchen appliances built for routine use.',
            tags: ['Value Range', 'Quick Heat', 'Compact Comfort']
        },
        pel: {
            headline: 'PEL family appliances built around cooling strength, frozen storage, and everyday convenience.',
            summary: 'Explore PEL air conditioners, freezers, refrigerators, washers, and compact kitchen tools designed for busy households.',
            tags: ['Family Cooling', 'Frozen Storage', 'Daily Utility']
        }
    };

    // BRAND PAGE MEDIA SETTINGS
    // `heroImage`  = homepage top brand slider image
    // `heroPoster` = brand page banner/poster image when no hero video is set
    // `heroVideo`  = main brand page video in the banner area
    // `splashVideo` = intro overlay video shown before the brand page
    var brandExperienceConfig = {
        haier: {
            splashPageName: 'HAIER Splash Page',
            featuredProductSlug: 'haier-frontcare-8kg',
            featuredProductLabel: 'FrontCare 8kg Washer',
            heroImage: 'img/slider/haier.jpg',
            heroPoster: 'img/banner/banner-1.jpg',
            heroVideo: 'Ads-Video/Haier.mp4',
            splashVideo: 'videos/brands/haier-intro.mp4'
        },
        dawlance: {
            splashPageName: 'DAWLANCE Splash Page',
            featuredProductSlug: 'dawlance-chefmaster-30',
            featuredProductLabel: 'ChefMaster 30 Microwave Oven',
            heroImage: 'img/slider/dowlance.png',
            heroPoster: 'img/banner/banner-2.jpg',
            heroVideo: 'Ads-Video/Dawlance.mp4',
            splashVideo: 'videos/brands/dawlance-intro.mp4'
        },
        samsung: {
            splashPageName: 'SAMSUNG Splash Page',
            featuredProductSlug: 'samsung-ecobubble-9kg',
            featuredProductLabel: 'EcoBubble 9kg Front Load Washer',
            heroImage: 'img/slider/sumsung.jfif',
            heroPoster: 'img/banner/banner-3.jpg',
            heroVideo: 'Ads-Video/Sumsung.mp4',
            splashVideo: 'videos/brands/samsung-intro.mp4'
        },
        lg: {
            splashPageName: 'LG Splash Page',
            featuredProductSlug: 'lg-smartwash-8kg',
            featuredProductLabel: 'SmartWash 8kg Dryer Combo',
            heroImage: 'img/slider/lg.jfif',
            heroPoster: 'img/about/about-us.jpg',
            heroVideo: 'Ads-Video/Lg.mp4',
            splashVideo: 'videos/brands/lg-intro.mp4'
        },
        orient: {
            splashPageName: 'ORIENT Splash Page',
            featuredProductSlug: 'orient-majesty-30',
            featuredProductLabel: 'Majesty 30 Microwave Oven',
            heroImage: 'img/slider/orient.jfif',
            heroPoster: 'img/hero/hero-2.jpg',
            heroVideo: 'Ads-Video/Orient.mp4',
            splashVideo: 'videos/brands/orients-intro.mp4'
        },
        pel: {
            splashPageName: 'PEL Splash Page',
            featuredProductSlug: 'pel-familyfresh-500',
            featuredProductLabel: 'FamilyFresh 500 Deep Freezer',
            heroImage: 'img/slider/pel.jfif',
            heroPoster: 'img/banner/banner-1.jpg',
            heroVideo: 'Ads-Video/Pel.mp4',
            splashVideo: 'videos/brands/pel-intro.mp4'
        }
    };

    if (storePayload && storePayload.brandExperienceConfig) {
        Object.keys(storePayload.brandExperienceConfig).forEach(function (key) {
            var incoming = storePayload.brandExperienceConfig[key] || {};
            var merged = Object.assign({}, brandExperienceConfig[key] || {});

            Object.keys(incoming).forEach(function (field) {
                var value = incoming[field];

                if (typeof value === 'string' && value.trim() === '') {
                    return;
                }

                if (Array.isArray(value) && value.length === 0) {
                    return;
                }

                merged[field] = value;
            });

            brandExperienceConfig[key] = merged;
        });
    }

    var brandGroups = [
        {
            name: 'HAIER',
            slug: 'haier',
            products: [
                { slug: 'haier-frostcool-15-ac', name: 'FrostCool 1.5 Ton Inverter AC', category: 'cooling', summary: 'Quick chill mode and low-noise airflow for bedrooms and offices.', image: 'img/products/haier/FrostCool 1.5 Ton Inverter AC haier.jpg' },
                { slug: 'haier-polar-breeze-1-ac', name: 'Polar Breeze 1 Ton Split AC', category: 'cooling', summary: 'Compact cooling performance built for smaller rooms and focused energy use.', image: 'img/products/haier/Polar Breeze 1 Ton Split AC haier.jpg' },
                { slug: 'haier-turbochill-2-ac', name: 'Turbo Chill 2 Ton DC Inverter AC', category: 'cooling', summary: 'High-capacity cooling support for larger lounges and active family spaces.', image: 'img/products/haier/Turbo Chill 2 Ton DC Inverter AC haier.jpg' },
                { slug: 'haier-twintub-12kg', name: 'TwinTub 12kg Washer Dryer', category: 'laundry', summary: 'Large-capacity washing with spin dry support for family laundry loads.', image: 'img/products/haier/TwinTub 12kg Washer Dryer haier.jpg' },
                { slug: 'haier-frontcare-8kg', name: 'FrontCare 8kg Washer', category: 'laundry', summary: 'Digital wash control with balanced drum care for regular household use.', image: 'img/products/haier/FrontCare 8kg Washer haier.jpg' },
                { slug: 'haier-aquaflow-dispenser', name: 'AquaFlow Bottom Load Water Dispenser', category: 'laundry', summary: 'Hot and cold water access with a clean family-friendly dispenser design.', image: 'img/products/haier/AquaFlow Bottom Load Water Dispenser haier.jpg' },
                { slug: 'haier-freshbox-438', name: 'FreshBox 438 Refrigerator', category: 'refrigeration', summary: 'Organized cooling shelves and practical storage zones for daily groceries.', image: 'img/products/haier/FreshBox 438 Refrigerator haier.jpg' },
                { slug: 'haier-smartstore-520', name: 'SmartStore 520 Deep Freezer', category: 'refrigeration', summary: 'Large frozen storage space with stable cooling for long-term food preservation.', image: 'img/products/haier/SmartStore 520 Deep Freezer haier.jpg' },
                { slug: 'haier-chefwave-28', name: 'ChefWave 28 Microwave Oven', category: 'kitchen', summary: 'Quick heat presets and compact placement for routine kitchen tasks.', image: 'img/products/haier/ChefWave 28 Microwave Oven haier.jpg' },
                { slug: 'haier-heatserve-32', name: 'HeatServe 32 Cooking Range', category: 'kitchen', summary: 'Everyday baking and stovetop support with simple family kitchen controls.', image: 'img/products/haier/HeatServe 32 Cooking Range haier.jpg' }
            ]
        },
        {
            name: 'DAWLANCE',
            slug: 'dawlance',
            products: [
                { slug: 'dawlance-coolmax-18-ac', name: 'CoolMax 1.8 Ton Inverter AC', category: 'cooling', summary: 'Fast room cooling with strong airflow and stable inverter control.', image: 'img/products/dowlance/CoolMax 1.8 Ton Inverter AC dawlance.webp' },
                { slug: 'dawlance-airglide-1-ac', name: 'AirGlide 1 Ton Split AC', category: 'cooling', summary: 'Compact split AC performance designed for bedrooms and office cabins.', image: 'img/products/dowlance/AirGlide 1 Ton Split AC dawlance.webp' },
                { slug: 'dawlance-powercool-2-ac', name: 'PowerCool 2 Ton Inverter AC', category: 'cooling', summary: 'Strong cooling coverage for larger spaces with steady temperature control.', image: 'img/products/dowlance/PowerCool 2 Ton Inverter AC dawlance.webp' },
                { slug: 'dawlance-spinplus-10kg', name: 'SpinPlus 10kg Washer', category: 'laundry', summary: 'Large drum washing with fabric-friendly cycles for regular home loads.', image: 'img/products/dowlance/SpinPlus 10kg Washer dawlance.webp' },
                { slug: 'dawlance-twincare-12kg', name: 'TwinCare 12kg Washer Dryer', category: 'laundry', summary: 'Family laundry support with twin-tub convenience and easy daily handling.', image: 'img/products/dowlance/TwinCare 12kg Washer Dryer dawlance.jpeg' },
                { slug: 'dawlance-hydrosip-dispenser', name: 'HydroSip Top Load Water Dispenser', category: 'laundry', summary: 'Reliable hot and cold water dispensing designed for offices and family homes.', image: 'img/products/dowlance/HydroSip Top Load Water Dispenser dawlance.webp' },
                { slug: 'dawlance-elegance-9193', name: 'Elegance 9193 Refrigerator', category: 'refrigeration', summary: 'Efficient cooling and durable trays for long-term household use.', image: 'img/products/dowlance/Elegance 9193 Refrigerator dawlance.webp' },
                { slug: 'dawlance-urbanchill-505', name: 'UrbanChill 505 Deep Freezer', category: 'refrigeration', summary: 'Large frozen storage compartments for homes that need more preserving capacity.', image: 'img/products/dowlance/UrbanChill 505 Deep Freezer dawlance.webp' },
                { slug: 'dawlance-chefmaster-30', name: 'ChefMaster 30 Microwave Oven', category: 'kitchen', summary: 'Easy control settings for reheating, warming, and light daily cooking.', image: 'img/products/dowlance/ChefMaster 30 Microwave Oven dawlance.webp' },
                { slug: 'dawlance-grillpro-25', name: 'GrillPro 25 Cooking Range', category: 'kitchen', summary: 'Compact cooking range support for grilling, baking, and fast home cooking.', image: 'img/products/dowlance/GrillPro 25 Cooking Range dawlance.png' }
            ]
        },
        {
            name: 'SAMSUNG',
            slug: 'samsung',
            products: [
                { slug: 'samsung-windhub-15-ac', name: 'WindHub 1.5 Ton Digital AC', category: 'cooling', summary: 'Digital airflow control and steady room cooling for daily comfort.', image: 'img/products/samsung/WindHub 1.5 Ton Digital AC samsung.jpg' },
                { slug: 'samsung-airsense-1-ac', name: 'AirSense 1 Ton Inverter AC', category: 'cooling', summary: 'Compact inverter cooling with quick response for study rooms and bedrooms.', image: 'img/products/samsung/AirSense 1 Ton Inverter AC samsung.jfif' },
                { slug: 'samsung-maxcool-2-ac', name: 'MaxCool 2 Ton Split AC', category: 'cooling', summary: 'Wide airflow throw and strong cooling support for open family areas.', image: 'img/products/samsung/MaxCool 2 Ton Split AC samsung.webp' },
                { slug: 'samsung-ecobubble-9kg', name: 'EcoBubble 9kg Front Load Washer', category: 'laundry', summary: 'Multiple wash programs and digital controls for modern laundry rooms.', image: 'img/products/samsung/EcoBubble 9kg Front Load Washer samsung.jfif' },
                { slug: 'samsung-washsmart-11kg', name: 'WashSmart 11kg Laundry System', category: 'laundry', summary: 'High-capacity performance and easy front access for busy households.', image: 'img/products/samsung/WashSmart 11kg Laundry System samsung.jfif' },
                { slug: 'samsung-aquahub-dispenser', name: 'AquaHub Floor Standing Water Dispenser', category: 'laundry', summary: 'Modern dispenser styling with instant cold and hot water convenience.', image: 'img/products/samsung/AquaHub Floor Standing Water Dispenser samsung.avif' },
                { slug: 'samsung-coolstore-420', name: 'CoolStore 420 Refrigerator', category: 'refrigeration', summary: 'Balanced interior cooling with organized shelf placement and tray support.', image: 'img/products/samsung/CoolStore 420 Refrigerator samsung.webp' },
                { slug: 'samsung-twinfresh-510', name: 'TwinFresh 510 Deep Freezer', category: 'refrigeration', summary: 'Double-door freezer storage with dependable temperature retention for bulk use.', image: 'img/products/samsung/TwinFresh 510 Deep Freezer samsung.avif' },
                { slug: 'samsung-heatbox-32', name: 'HeatBox 32 Microwave Oven', category: 'kitchen', summary: 'Quick preset heating with a clean control panel and everyday convenience.', image: 'img/products/samsung/HeatBox 32 Microwave Oven samsung.avif' },
                { slug: 'samsung-kitchenhub-26', name: 'KitchenHub 26 Cooking Range', category: 'kitchen', summary: 'Modern cooking range support for grilling, baking, and everyday meal prep.', image: 'img/products/samsung/KitchenHub 26 Cooking Range samsung.avif' }
            ]
        },
        {
            name: 'LG',
            slug: 'lg',
            products: [
                { slug: 'lg-airflow-15-ac', name: 'AirFlow 1.5 Ton Inverter AC', category: 'cooling', summary: 'Power-aware cooling with room-friendly airflow and stable performance.', image: 'img/products/lg/AirFlow 1.5 Ton Inverter AC lg.avif' },
                { slug: 'lg-breezesmart-1-ac', name: 'BreezeSmart 1 Ton Split AC', category: 'cooling', summary: 'Compact AC support for apartments, study rooms, and focused spaces.', image: 'img/products/lg/BreezeSmart 1 Ton Split AC lg.jfif' },
                { slug: 'lg-dualcool-2-ac', name: 'DualCool 2 Ton Inverter AC', category: 'cooling', summary: 'Heavy-duty cooling coverage for open lounges and frequent daytime use.', image: 'img/products/lg/DualCool 2 Ton Inverter AC lg.jfif' },
                { slug: 'lg-smartwash-8kg', name: 'SmartWash 8kg Dryer Combo', category: 'laundry', summary: 'Combined washing and drying in a compact body for smaller homes.', image: 'img/products/lg/SmartWash 8kg Dryer Combo lg.webp' },
                { slug: 'lg-turboclean-10kg', name: 'TurboClean 10kg Washer', category: 'laundry', summary: 'Fast wash cycles and balanced drum care for regular family laundry.', image: 'img/products/lg/TurboClean 10kg Washer lg.avif' },
                { slug: 'lg-pureflow-dispenser', name: 'PureFlow Bottom Load Water Dispenser', category: 'laundry', summary: 'Premium dispenser convenience with clean storage and easy everyday refilling.', image: 'img/products/lg/PureFlow Bottom Load Water Dispenser lg.avif' },
                { slug: 'lg-heatchef-30', name: 'HeatChef 30 Microwave Oven', category: 'kitchen', summary: 'Fast reheat support and compact counter placement for daily use.', image: 'img/products/lg/HeatChef 30 Microwave Oven lg.avif' },
                { slug: 'lg-freshline-450', name: 'FreshLine 450 Refrigerator', category: 'refrigeration', summary: 'Reliable cooling zones and organized storage space for family kitchens.', image: 'img/products/lg/FreshLine 450 Refrigerator lg.avif' },
                { slug: 'lg-instastore-530', name: 'InstaStore 530 Deep Freezer', category: 'refrigeration', summary: 'Expanded freezer storage and neat organization for stocked-up family homes.', image: 'img/products/lg/InstaStore 530 Deep Freezer lg.avif' },
                { slug: 'lg-grillmate-27', name: 'GrillMate 27 Cooking Range', category: 'kitchen', summary: 'Everyday baking and cooking support with easy controls and clean styling.', image: 'img/products/lg/GrillMate 27 Cooking Range lg.jfif' }
            ]
        },
        {
            name: 'ORIENT',
            slug: 'orient',
            products: [
                { slug: 'orient-arctic-15-ac', name: 'Arctic 1.5 Ton Inverter AC', category: 'cooling', summary: 'Stable cooling output with strong airflow for regular daytime comfort.', image: 'img/products/orient/Arctic 1.5 Ton Inverter AC orient.jfif' },
                { slug: 'orient-coolwave-1-ac', name: 'CoolWave 1 Ton Split AC', category: 'cooling', summary: 'Focused room cooling with a compact body and practical inverter support.', image: 'img/products/orient/CoolWave 1 Ton Split AC orient.webp' },
                { slug: 'orient-turbobreeze-2-ac', name: 'TurboBreeze 2 Ton Inverter AC', category: 'cooling', summary: 'Powerful airflow and broad cooling coverage for active home use.', image: 'img/products/orient/TurboBreeze 2 Ton Inverter AC orient.webp' },
                { slug: 'orient-freshkeeper-390', name: 'FreshKeeper 390 Refrigerator', category: 'refrigeration', summary: 'Compact family storage with reliable shelf space and cooling coverage.', image: 'img/products/orient/FreshKeeper 390 Refrigerator orient.webp' },
                { slug: 'orient-coolpantry-480', name: 'CoolPantry 480 Deep Freezer', category: 'refrigeration', summary: 'Neat freezer layout and wide compartments for frozen family storage.', image: 'img/products/orient/CoolPantry 480 Deep Freezer orient.jpg' },
                { slug: 'orient-majesty-30', name: 'Majesty 30 Microwave Oven', category: 'kitchen', summary: 'Quick heat presets and everyday cooking convenience for compact kitchens.', image: 'img/products/orient/Majesty 30 Microwave Oven orient.jpg' },
                { slug: 'orient-quickchef-24', name: 'QuickChef 24 Cooking Range', category: 'kitchen', summary: 'Daily reheat and grill functionality in a cooking-range format.', image: 'img/products/orient/QuickChef 24 Cooking Range orient.webp' },
                { slug: 'orient-laundrypro-9kg', name: 'LaundryPro 9kg Washer', category: 'laundry', summary: 'Balanced washing performance and easy cycle selection for regular family use.', image: 'img/products/orient/LaundryPro 9kg Washer orient.jpg' },
                { slug: 'orient-aquapure-dispenser', name: 'AquaPure Water Dispenser', category: 'laundry', summary: 'Hot and cold water access with a sleek shape for homes and offices.', image: 'img/products/orient/AquaPure Water Dispenser orient.webp' },
                { slug: 'orient-twinwash-11kg', name: 'TwinWash 11kg Washer Dryer', category: 'laundry', summary: 'Practical wash and spin workflow for medium and large laundry loads.', image: 'img/products/orient/TwinWash 11kg Washer Dryer orient.webp' }
            ]
        },
        {
            name: 'PEL',
            slug: 'pel',
            products: [
                { slug: 'pel-arcticpro-15-ac', name: 'ArcticPro 1.5 Ton Inverter AC', category: 'cooling', summary: 'Smooth cooling delivery with practical controls for daily room comfort.', image: 'img/products/pel/ArcticPro 1.5 Ton Inverter AC pel.webp' },
                { slug: 'pel-chillmax-1-ac', name: 'ChillMax 1 Ton Split AC', category: 'cooling', summary: 'Small-room cooling support with focused airflow and efficient power use.', image: 'img/products/pel/ChillMax 1 Ton Split AC pel.webp' },
                { slug: 'pel-smartcool-2-ac', name: 'SmartCool 2 Ton DC Inverter AC', category: 'cooling', summary: 'Large-space cooling support with steady airflow and inverter efficiency.', image: 'img/products/pel/SmartCool 2 Ton DC Inverter AC pel.webp' },
                { slug: 'pel-washmate-9kg', name: 'WashMate 9kg Washer', category: 'laundry', summary: 'Simple wash cycle control and balanced drum performance for weekly use.', image: 'img/products/pel/WashMate 9kg Washer pel.jpg' },
                { slug: 'pel-twinspin-12kg', name: 'TwinSpin 12kg Washer Dryer', category: 'laundry', summary: 'High-capacity washing and spin support for family laundry schedules.', image: 'img/products/pel/TwinSpin 12kg Washer Dryer pel.webp' },
                { slug: 'pel-hydrofresh-dispenser', name: 'HydroFresh Water Dispenser', category: 'laundry', summary: 'Clean top-load dispenser design with instant refreshment for busy homes.', image: 'img/products/pel/HydroFresh Water Dispenser pel.webp' },
                { slug: 'pel-iceline-410', name: 'IceLine 410 Refrigerator', category: 'refrigeration', summary: 'Dependable cooling and practical shelf organization for small families.', image: 'img/products/pel/IceLine 410 Refrigerator pel.jfif' },
                { slug: 'pel-familyfresh-500', name: 'FamilyFresh 500 Deep Freezer', category: 'refrigeration', summary: 'Wide deep-freezer storage and even cooling for larger family kitchens.', image: 'img/products/pel/FamilyFresh 500 Deep Freezer pel.webp' },
                { slug: 'pel-microbake-28', name: 'MicroBake 28 Microwave Oven', category: 'kitchen', summary: 'Quick heat and reheat support with a compact countertop footprint.', image: 'img/products/pel/MicroBake 28 Microwave Oven pel.webp' },
                { slug: 'pel-grillwave-26', name: 'GrillWave 26 Cooking Range', category: 'kitchen', summary: 'Daily grilling and baking support with straightforward cooking-range control.', image: 'img/products/pel/GrillWave 26 Cooking Range pel.jfif' }
            ]
        }
    ];

    if (storePayload && Array.isArray(storePayload.brandGroups) && storePayload.brandGroups.length) {
        brandGroups = storePayload.brandGroups;
    }

    function getProductPrimaryImage(productSlug) {
        return productImageMap[productSlug] || defaultProductImage;
    }

    function resolveMediaAsset(asset, fallbackSrc, fallbackAlt) {
        if (typeof asset === 'string') {
            return {
                src: asset || fallbackSrc,
                alt: fallbackAlt || 'La Imperial showcase media'
            };
        }

        if (asset && typeof asset === 'object') {
            return {
                src: asset.src || fallbackSrc,
                alt: asset.alt || fallbackAlt || 'La Imperial showcase media'
            };
        }

        return {
            src: fallbackSrc,
            alt: fallbackAlt || 'La Imperial showcase media'
        };
    }

    function uniqueTextList(items) {
        var seen = {};

        return items.filter(function (item) {
            var normalized = item && item.trim();
            if (!normalized || seen[normalized]) {
                return false;
            }

            seen[normalized] = true;
            return true;
        });
    }

    function extractProductMeasure(productName) {
        var match = productName.match(/(\d+(?:\.\d+)?)\s*(Ton|kg|L)/i);
        return match ? (match[1] + ' ' + match[2]) : '';
    }

    function getProductCapabilityLabel(product) {
        var text = (product.name + ' ' + product.summary).toLowerCase();

        if (text.indexOf('inverter') > -1 || text.indexOf('dc') > -1) {
            return 'Inverter Performance';
        }
        if (text.indexOf('digital') > -1) {
            return 'Digital Controls';
        }
        if (text.indexOf('front') > -1) {
            return 'Front Access Design';
        }
        if (text.indexOf('bottom load') > -1 || text.indexOf('top load') > -1 || text.indexOf('floor standing') > -1) {
            return 'Water Dispensing Ready';
        }
        if (text.indexOf('microwave') > -1 || text.indexOf('grill') > -1 || text.indexOf('cooking') > -1 || text.indexOf('heat') > -1) {
            return 'Kitchen Ready';
        }
        if (text.indexOf('freezer') > -1 || text.indexOf('refrigerator') > -1) {
            return 'Cooling Storage';
        }

        return 'Guide Ready';
    }

    function getProductScanFacts(product) {
        var measure = extractProductMeasure(product.name);

        return uniqueTextList([
            product.brand,
            product.categoryLabel,
            measure,
            getProductCapabilityLabel(product),
            product.isDiscounted ? (product.discountPercent + '% Off') : 'Catalogue Ready'
        ]).slice(0, 4);
    }

    function buildProductHighlights(product) {
        var text = (product.name + ' ' + product.summary + ' ' + categoryMeta[product.category].note).toLowerCase();
        var measure = extractProductMeasure(product.name);

        return uniqueTextList([
            product.summary,
            measure ? (measure + ' configuration selected for steady everyday use.') : '',
            text.indexOf('inverter') > -1 || text.indexOf('dc') > -1 ? 'Inverter-led performance supports smoother daily efficiency.' : '',
            text.indexOf('digital') > -1 ? 'Digital controls help make routine operation quicker and easier to follow.' : '',
            text.indexOf('front') > -1 ? 'Front-facing access keeps loading and regular handling more convenient.' : '',
            text.indexOf('twin') > -1 ? 'Twin-system design keeps family laundry or workflow handling practical.' : '',
            text.indexOf('bottom load') > -1 || text.indexOf('top load') > -1 || text.indexOf('floor standing') > -1 ? 'Dispenser-focused layout is shaped around daily hot and cold access.' : '',
            categoryMeta[product.category].note
        ]).slice(0, 3);
    }

    function getProductShowcaseMedia(product, slotIndex) {
        var highlightGroup = showcaseMediaConfig.brandHighlightImages[product.brandSlug] || [];
        var fallbackAlt = product.name + ' highlight visual';
        var matchedMedia = null;
        var productName = String(product.name || '').toLowerCase();
        var index = 0;

        for (index = 0; index < highlightGroup.length; index += 1) {
            if (!highlightGroup[index]) {
                continue;
            }

            if (highlightGroup[index].src === product.image || String(highlightGroup[index].alt || '').toLowerCase().indexOf(productName) > -1) {
                matchedMedia = highlightGroup[index];
                break;
            }
        }

        if (matchedMedia) {
            return resolveMediaAsset(matchedMedia, product.image, fallbackAlt);
        }

        if (!highlightGroup.length || typeof slotIndex !== 'number') {
            return resolveMediaAsset(null, product.image, fallbackAlt);
        }

        return resolveMediaAsset(null, product.image, fallbackAlt);
    }

    function getBrandHeroMedia(brandSlug, fallbackProduct) {
        var experience = getBrandExperience(brandSlug) || {};
        var fallbackSrc = getBrandLogoImagePath(brandSlug) || 'img/banner/banner-1.jpg';
        var fallbackAlt = (getBrandBySlug(brandSlug) || { name: 'Brand' }).name + ' homepage hero visual';

        return resolveMediaAsset(experience.heroImage, fallbackSrc, fallbackAlt);
    }

    function getBrandHeroPoster(brandSlug, fallbackProduct) {
        var experience = getBrandExperience(brandSlug) || {};
        var fallbackSrc = getBrandLogoImagePath(brandSlug) || 'img/banner/banner-1.jpg';
        var fallbackAlt = (getBrandBySlug(brandSlug) || { name: 'Brand' }).name + ' brand page poster';

        return resolveMediaAsset(experience.heroPoster || experience.heroImage, fallbackSrc, fallbackAlt);
    }

    function getBrandHeroVideoPath(brandSlug) {
        var experience = getBrandExperience(brandSlug) || {};

        return experience.heroVideo || '';
    }

    function getBrandSplashVideoPath(brandSlug) {
        var experience = getBrandExperience(brandSlug) || {};

        return experience.splashVideo || '';
    }

    function buildProductGallery(primaryImage) {
        return [primaryImage, primaryImage, primaryImage, primaryImage];
    }

    function getProductGallery(product) {
        var gallery = Array.isArray(product.gallery) ? product.gallery.slice(0) : [];
        var primary = product.image || (gallery.length ? gallery[0] : '');

        if (!gallery.length && primary) {
            gallery = buildProductGallery(primary);
        }

        while (gallery.length < 4 && primary) {
            gallery.push(primary);
        }

        return gallery.slice(0, 4);
    }

    function titleCaseFromSlug(slug) {
        return String(slug || '')
            .replace(/[-_]+/g, ' ')
            .replace(/\s+/g, ' ')
            .trim()
            .replace(/\b\w/g, function (match) {
                return match.toUpperCase();
            });
    }

    function ensureCategoryMeta(categorySlug, fallbackDoc) {
        if (!categorySlug) {
            return categoryMeta['air-conditioners'];
        }

        if (!categoryMeta[categorySlug]) {
            categoryMeta[categorySlug] = {
                label: titleCaseFromSlug(categorySlug),
                doc: fallbackDoc || 'docs/haier-frostcool-inverter-ac.doc',
                note: 'Explore appliances in this category with quick summaries, pricing, and feature guides.'
            };
        }

        return categoryMeta[categorySlug];
    }

    function createProducts() {
        var products = [];
        var index = 0;

        brandGroups.forEach(function (brandGroup) {
            brandGroup.products.forEach(function (product) {
                // For data coming from server, use categorySlug. For hardcoded data, map 'category' to proper categorySlug
                var categoryKey = product.categorySlug || inferCategoryKey(product.name);
                var category = ensureCategoryMeta(categoryKey, product.document);
                var primaryImage = product.image || getProductPrimaryImage(product.slug);
                var gallery = getProductGallery({ gallery: product.gallery, image: primaryImage });
                var highlights = buildProductHighlights({
                    name: product.name,
                    summary: product.summary,
                    category: categoryKey
                });
                var price = typeof product.price === 'number' && product.price > 0 ? product.price : (69990 + (index * 2150));
                var discountPercent = typeof product.discountPercent === 'number'
                    ? product.discountPercent
                    : (index % 5 === 0 ? 18 : index % 4 === 0 ? 12 : index % 3 === 0 ? 8 : 0);
                var originalPrice = typeof product.originalPrice === 'number' && product.originalPrice > 0
                    ? product.originalPrice
                    : (discountPercent ? Math.round(price / (1 - (discountPercent / 100))) : price);
                var isTrending = typeof product.isTrending === 'boolean'
                    ? product.isTrending
                    : (featuredProductSlugs.indexOf(product.slug) > -1 || index % 3 === 0);
                var documentPath = product.document || category.doc;
                products.push({
                    slug: product.slug,
                    name: product.name,
                    brand: brandGroup.name,
                    brandSlug: brandGroup.slug,
                    customLabel: product.customLabel || '',
                    labels: Array.isArray(product.labels) ? product.labels : [],
                    category: categoryKey,
                    categoryLabel: category.label,
                    summary: product.summary,
                    detail: product.summary + ' ' + category.note,
                    document: documentPath,
                    image: primaryImage,
                    gallery: gallery,
                    compareImage: primaryImage,
                    highlightImage: primaryImage,
                    highlights: highlights,
                    price: price,
                    originalPrice: originalPrice,
                    discountPercent: discountPercent,
                    isDiscounted: discountPercent > 0 && originalPrice > price,
                    isTrending: isTrending
                });
                index += 1;
            });
        });

        return products;
    }

    var products = createProducts();
    var storeStats = {
        totalProducts: products.length,
        totalBrands: brandGroups.length,
        totalCategories: Object.keys(categoryMeta).length
    };
    
    // Debug logging
    if (typeof console !== 'undefined' && console.log) {
        var favList = (storeSession && storeSession.favorites) ? storeSession.favorites : [];
        console.log('La Imperial Store Loaded:', {
            productsCount: products.length,
            brandsCount: brandGroups.length,
            categoriesCount: storeStats.totalCategories,
            favoritesCount: favList.length,
            favorites: favList,
            sample: products.length > 0 ? products[0] : 'No products'
        });
        console.log('isProductFavorited test:', 'haier-frostcool-15-ac is favorited?', isProductFavorited('haier-frostcool-15-ac'));
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function inferCategoryKey(productName) {
        var name = productName.toLowerCase();

        if (name.indexOf('water dispenser') > -1) {
            return 'water-dispensers';
        }
        if (name.indexOf('deep freezer') > -1 || name.indexOf('freezer') > -1) {
            return 'deep-freezers';
        }
        if (name.indexOf('microwave') > -1) {
            return 'microwave-ovens';
        }
        if (name.indexOf('cooking range') > -1 || name.indexOf('oven') > -1 || name.indexOf('range') > -1) {
            return 'cooking-ranges';
        }
        if (name.indexOf('refrigerator') > -1) {
            return 'refrigerators';
        }
        if (name.indexOf('washer dryer') > -1 || name.indexOf('dryer combo') > -1 || name.indexOf('laundry system') > -1) {
            return 'washer-dryers';
        }
        if (name.indexOf('washer') > -1 || name.indexOf('washing machine') > -1) {
            return 'washing-machines';
        }

        return 'air-conditioners';
    }

    function formatCurrency(amount) {
        return 'PKR ' + amount.toLocaleString('en-US');
    }

    function toCssUrl(path) {
        return 'url("' + encodeURI(path).replace(/"/g, '%22') + '")';
    }

    function productCardTemplate(product, options) {
        var detailHref = options && options.detailHref ? options.detailHref : buildDetailHref(product);
        var compareHref = options && options.compareHref ? options.compareHref : buildCompareHref(product, null, getProductsByBrand(product.brandSlug));
        var columnClass = options && options.columnClass ? options.columnClass : 'col-lg-4 col-md-6 col-sm-6';
        var stateLabel = product.isTrending ? 'Trending' : (product.isDiscounted ? 'Hot Deal' : 'New');
        var savingsLabel = product.customLabel ? product.customLabel : (product.isDiscounted ? (product.discountPercent + '% Off') : 'Featured');
        var isFav = isProductFavorited(product.slug);
        var viewCount = Number(product.viewCount || 0);
        return [
            '<div class="', columnClass, ' mix ', product.category, ' ', product.brandSlug, '">',
            '<article class="product__item product__item--catalog', product.isDiscounted ? ' sale' : '', '" data-product-slug="', product.slug, '">',
            '<div class="product__item__pic">',
            '<img src="', product.image, '" alt="', escapeHtml(product.name), '" class="product__item__image">',
            '<div class="product__item__badges">',
            '<span class="label">', escapeHtml(product.brand), '</span>',
            '<span class="product__item__deal">', escapeHtml(savingsLabel), '</span>',
            '</div>',
            '<div class="product__item__float-actions">',
            '<a href="', detailHref, '" class="product__item__float-link" title="Open Details" aria-label="Open Details"><i class="fa fa-search"></i><span>Details</span></a>',
            '<a href="', buildActionHref('toggle_favorite', product.slug), '" class="product__item__float-link product__item__float-link--favorite ', isFav ? 'is-favorited' : '', '" title="', isFav ? 'Remove Favorite' : 'Add Favorite', '" aria-label="', isFav ? 'Remove Favorite' : 'Add Favorite', '"><i class="fa ', isFav ? 'fa-heart' : 'fa-heart-o', '"></i><span>', isFav ? 'Saved' : 'Favorite', '</span></a>',
            '<a href="', buildActionHref('add_to_cart', product.slug), '" class="product__item__float-link" title="Add to Cart" aria-label="Add to Cart"><i class="fa fa-shopping-cart"></i><span>Add Cart</span></a>',
            '<a href="', compareHref, '" class="product__item__float-link" title="Compare Product" aria-label="Compare Product"><i class="fa fa-exchange"></i><span>Compare</span></a>',
            '</div>',
            '</div>',
            '<div class="product__item__text">',
            '<div class="product__item__meta-top">',
            '<span class="product__item__category-pill">', escapeHtml(product.categoryLabel), '</span>',
            '<span class="product__item__status">', escapeHtml(stateLabel), '</span>',
            '</div>',
            '<h6><a href="', detailHref, '" class="product__item__title-link">', escapeHtml(product.name), '</a></h6>',
            '<p class="product__summary">', escapeHtml(product.summary), '</p>',
            '<div class="product__item__helper-row">',
            '<span class="product__item__helper">', escapeHtml(product.brand), '</span>',
            '<span class="product__item__helper product__item__helper--views"><i class="fa fa-eye"></i> ', viewCount, ' views</span>',
            '</div>',
            '<div class="product__item__price-row">',
            '<h5>', formatCurrency(product.price), '</h5>',
            product.isDiscounted ? '<span class="product__item__old-price">' + formatCurrency(product.originalPrice) + '</span>' : '',
            '</div>',
            '</div>',
            '</article>',
            '</div>'
        ].join('');
    }

    function compareRowTemplate(product) {
        return [
            '<tr>',
            '<td class="product__cart__item">',
            '<div class="product__cart__item__pic"><img src="', product.compareImage, '" alt="', escapeHtml(product.name), '"></div>',
            '<div class="product__cart__item__text">',
            '<h6>', escapeHtml(product.name), '</h6>',
            '<h5>', escapeHtml(product.summary), '</h5>',
            '</div>',
            '</td>',
            '<td class="quantity__item">', escapeHtml(product.brand), '</td>',
            '<td class="cart__price">', escapeHtml(product.categoryLabel), '</td>',
            '<td class="cart__close"><a href="', product.document, '" download>Download</a></td>',
            '</tr>'
        ].join('');
    }

    function linkListTemplate(items) {
        return items.map(function (product) {
            return '<a href="' + product.document + '" download>' + escapeHtml(product.name) + '</a>';
        }).join('');
    }

    function applySetBackgrounds(scope) {
        var root = scope || document;
        var items = root.querySelectorAll('.set-bg');
        Array.prototype.forEach.call(items, function (item) {
            var bg = item.getAttribute('data-setbg');
            if (bg) {
                item.style.backgroundImage = toCssUrl(bg);
            }
        });
    }

    function getProductBySlug(slug) {
        for (var i = 0; i < products.length; i += 1) {
            if (products[i].slug === slug) {
                return products[i];
            }
        }
        return products[0];
    }

    function getBrandBySlug(brandSlug) {
        for (var i = 0; i < brandGroups.length; i += 1) {
            if (brandGroups[i].slug === brandSlug) {
                return brandGroups[i];
            }
        }
        return null;
    }

    function getBrandPageHref(brandSlug) {
        if (storeRoutes && storeRoutes.brandPages && storeRoutes.brandPages[brandSlug]) {
            return storeRoutes.brandPages[brandSlug];
        }

        return toPhpHref('./brand.php?brand=' + encodeURIComponent(brandSlug));
    }

    function getBrandPageSlugFromPage(pageName) {
        var i = 0;

        for (i = 0; i < brandGroups.length; i += 1) {
            if (pageName === brandGroups[i].slug + '.html' || pageName === brandGroups[i].slug + '.php') {
                return brandGroups[i].slug;
            }
        }

        return null;
    }

    function getBrandPageSlugFromHref(href) {
        try {
            var parsedUrl = new URL(href, window.location.origin);
            var requestedBrand = parsedUrl.searchParams.get('brand');

            if (requestedBrand && getBrandBySlug(requestedBrand)) {
                return requestedBrand;
            }

            href = parsedUrl.pathname + parsedUrl.search;
        } catch (error) {
            href = String(href || '');
        }

        var i = 0;

        for (i = 0; i < brandGroups.length; i += 1) {
            if (href.indexOf(brandGroups[i].slug + '.html') > -1 || href.indexOf(brandGroups[i].slug + '.php') > -1) {
                return brandGroups[i].slug;
            }
        }

        return null;
    }

    function getProductsByBrand(brandSlug) {
        return products.filter(function (product) {
            return product.brandSlug === brandSlug;
        });
    }

    function getProductsByCategory(categorySlug) {
        return products.filter(function (product) {
            return product.category === categorySlug;
        });
    }

    function isProductFavorited(productSlug) {
        return storeSession && storeSession.favorites && storeSession.favorites.indexOf(productSlug) > -1;
    }

    function getFeaturedProducts() {
        return featuredProductSlugs.map(function (slug) {
            return getProductBySlug(slug);
        });
    }

    function getDefaultCompareProducts() {
        var compareSet = [];

        brandGroups.forEach(function (brandGroup) {
            getProductsByBrand(brandGroup.slug).slice(0, 2).forEach(function (product) {
                compareSet.push(product);
            });
        });

        return compareSet;
    }

    function getRelatedProducts(product, limit) {
        var related = getProductsByBrand(product.brandSlug).filter(function (item) {
            return item.slug !== product.slug;
        });

        if (related.length < limit) {
            related = related.concat(getProductsByCategory(product.category).filter(function (item) {
                return item.slug !== product.slug && item.brandSlug !== product.brandSlug;
            }));
        }

        return related.slice(0, limit);
    }

    function queryParam(name) {
        return new URLSearchParams(window.location.search).get(name);
    }

    function currentListingHref() {
        var page = getCurrentPage();
        return './' + page + window.location.search;
    }

    function buildDetailHref(product, backHref) {
        var returnTo = backHref || currentListingHref();
        return toPhpHref('./shop-details.html?product=' + product.slug + '&back=' + encodeURIComponent(returnTo));
    }

    function getAlternateProduct(product, preferredPool) {
        var pool = preferredPool && preferredPool.length ? preferredPool : getProductsByBrand(product.brandSlug);
        var i = 0;

        for (i = 0; i < pool.length; i += 1) {
            if (pool[i].slug !== product.slug) {
                return pool[i];
            }
        }

        for (i = 0; i < products.length; i += 1) {
            if (products[i].slug !== product.slug) {
                return products[i];
            }
        }

        return product;
    }

    function buildCompareHref(primaryProduct, secondaryProduct, preferredPool) {
        var compareWith = secondaryProduct && secondaryProduct.slug !== primaryProduct.slug ? secondaryProduct : getAlternateProduct(primaryProduct, preferredPool);
        return toPhpHref('./compare.html?left=' + primaryProduct.slug + '&right=' + compareWith.slug);
    }

    function cleanPathname(pathname) {
        var normalized;
        var basePath = appBasePath();
        var marker = (basePath ? basePath : '/la-imapiral') + '/';
        var lastMarker;

        if (!pathname) {
            return './index.php';
        }

        normalized = String(pathname)
            .replace(/\\/g, '/')
            .replace(/[A-Za-z]:/g, '')
            .replace(/\/(?:xampp|wamp64|laragon)\/htdocs\/[^/]+/ig, '')
            .replace(/\/{2,}/g, '/');
        lastMarker = normalized.toLowerCase().lastIndexOf(marker);

        if (lastMarker > -1) {
            normalized = normalized.substring(lastMarker + marker.length - 1);
        }

        if (normalized === '' || normalized === '/' || /\/la-imapiral\/?$/i.test(normalized)) {
            return './index.php';
        }

        if (normalized.charAt(0) !== '/') {
            normalized = '/' + normalized;
        }

        if (!/(\.php|\.html|\/)$/i.test(normalized)) {
            var parts = normalized.split('/').filter(Boolean);
            normalized = parts.length ? '/' + parts[parts.length - 1] : '/index.php';
        }

        return '.' + normalized;
    }

    function repairBrokenAppLocation() {
        var pathname = String(window.location.pathname || '');
        var normalized = cleanPathname(pathname);
        var needsRepair = /[A-Za-z]:/i.test(pathname) ||
            /(?:xampp|wamp64|laragon)\/htdocs/i.test(pathname) ||
            pathname.toLowerCase().split((appBasePath() || '/la-imapiral').toLowerCase()).length > 2;
        var nextUrl = toPhpHref(normalized + window.location.search + window.location.hash);

        if (!needsRepair) {
            return false;
        }

        if (nextUrl && nextUrl !== (window.location.pathname + window.location.search + window.location.hash)) {
            window.location.replace(nextUrl);
            return true;
        }

        return false;
    }

    function buildActionHref(action, productSlug, redirect) {
        var base = getRoute('action', './store-action.php');
        var next = redirect || (cleanPathname(window.location.pathname) + window.location.search);
        return base + '?action=' + encodeURIComponent(action) + '&product=' + encodeURIComponent(productSlug) + '&redirect=' + encodeURIComponent(next);
    }

    function setText(id, value) {
        var element = document.getElementById(id);
        if (element) {
            element.textContent = value;
        }
    }

    function setHtml(id, value) {
        var element = document.getElementById(id);
        if (element) {
            element.innerHTML = value;
        }
    }

    function setHref(id, value) {
        var element = document.getElementById(id);
        if (element) {
            element.setAttribute('href', value);
        }
    }

    function setAttribute(id, name, value) {
        var element = document.getElementById(id);
        if (element) {
            element.setAttribute(name, value);
        }
    }

    function setImage(id, src, alt) {
        setAttribute(id, 'src', src);
        setAttribute(id, 'alt', alt);
    }

    function setImageBySelector(selector, src, alt) {
        var element = document.querySelector(selector);

        if (element) {
            element.setAttribute('src', src);
            element.setAttribute('alt', alt);
        }
    }

    function setBackground(id, src) {
        var element = document.getElementById(id);
        if (element) {
            element.setAttribute('data-setbg', src);
            element.style.backgroundImage = toCssUrl(src);
        }
    }

    function setTextForAll(selector, value) {
        Array.prototype.forEach.call(document.querySelectorAll(selector), function (element) {
            element.textContent = value;
        });
    }

    function ensureQuickViewStructure() {
        var content = document.querySelector('.product-quick-view__content');
        var category = document.getElementById('quick-view-category');
        var summary = document.getElementById('quick-view-summary');
        var actions = content ? content.querySelector('.product-quick-view__actions') : null;
        var pricing;
        var badges;
        var highlights;
        var cartLink;
        var favoriteLink;

        if (!content || !category || !summary || !actions) {
            return;
        }

        pricing = document.getElementById('quick-view-pricing');
        badges = document.getElementById('quick-view-badges');
        highlights = document.getElementById('quick-view-highlights');

        if (!pricing) {
            pricing = document.createElement('div');
            pricing.id = 'quick-view-pricing';
            pricing.className = 'product-quick-view__pricing';
            category.insertAdjacentElement('afterend', pricing);
        }

        if (!badges) {
            badges = document.createElement('div');
            badges.id = 'quick-view-badges';
            badges.className = 'product-quick-view__badges';
            pricing.insertAdjacentElement('afterend', badges);
        }

        if (!highlights) {
            highlights = document.createElement('ul');
            highlights.id = 'quick-view-highlights';
            highlights.className = 'product-quick-view__highlights';
            summary.insertAdjacentElement('afterend', highlights);
        }

        cartLink = document.getElementById('quick-view-cart-link');
        favoriteLink = document.getElementById('quick-view-favorite-link');

        if (!cartLink && actions) {
            cartLink = document.createElement('a');
            cartLink.id = 'quick-view-cart-link';
            cartLink.className = 'quick-action--cart';
            cartLink.textContent = 'Add to Cart';
            actions.appendChild(cartLink);
        }

        if (!favoriteLink && actions) {
            favoriteLink = document.createElement('a');
            favoriteLink.id = 'quick-view-favorite-link';
            favoriteLink.className = 'quick-action--favorite';
            favoriteLink.textContent = 'Favorite';
            actions.appendChild(favoriteLink);
        }
    }

    function refreshNiceSelect(selector) {
        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.niceSelect) {
            window.jQuery(selector || 'select').niceSelect('update');
        }
    }

    function buildCompareSelectMedia(target, imageSrc, labelText) {
        var image;
        var label;

        if (!target) {
            return;
        }

        target.textContent = '';

        if (imageSrc) {
            image = document.createElement('img');
            image.className = 'compare-select__thumb';
            image.src = imageSrc;
            image.alt = labelText;
            target.appendChild(image);
        }

        label = document.createElement('span');
        label.className = 'compare-select__label';
        label.textContent = labelText;
        target.appendChild(label);
    }

    function syncCompareSelectMedia(selectElementId) {
        var selectEl = document.getElementById(selectElementId);
        var niceSelect = selectEl ? selectEl.nextElementSibling : null;
        var current = niceSelect ? niceSelect.querySelector('.current') : null;
        var list = niceSelect ? niceSelect.querySelector('.list') : null;
        var selectedOption = selectEl && selectEl.selectedIndex > -1 ? selectEl.options[selectEl.selectedIndex] : null;

        if (!selectEl || !niceSelect || !niceSelect.classList || !niceSelect.classList.contains('nice-select')) {
            return false;
        }

        niceSelect.classList.add('compare-select--media');

        if (list) {
            list.style.maxHeight = '320px';
            list.style.overflowY = 'auto';
            list.style.overscrollBehavior = 'contain';
            list.style.scrollbarGutter = 'stable';
        }

        Array.prototype.forEach.call(niceSelect.querySelectorAll('.option'), function (optionEl) {
            var optionValue = optionEl.getAttribute('data-value');
            var optionData = optionValue ? selectEl.querySelector('option[value="' + optionValue + '"]') : null;

            if (!optionData) {
                return;
            }

            optionEl.classList.add('product-option');
            buildCompareSelectMedia(optionEl, optionData.getAttribute('data-image'), optionData.textContent);
        });

        if (current && selectedOption) {
            buildCompareSelectMedia(current, selectedOption.getAttribute('data-image'), selectedOption.textContent);
        }

        return true;
    }

    function scheduleCompareSelectMediaSync(selectElementId, attempt) {
        var nextAttempt = typeof attempt === 'number' ? attempt : 0;
        var delay = nextAttempt === 0 ? 30 : 90;

        window.setTimeout(function () {
            if (!syncCompareSelectMedia(selectElementId) && nextAttempt < 4) {
                scheduleCompareSelectMediaSync(selectElementId, nextAttempt + 1);
            }
        }, delay);
    }

    function getCurrentPage() {
        var pathname = window.location.pathname;
        // Remove Windows drive letters (C:, D:, etc.) from buggy server or file-style paths
        pathname = pathname.replace(/\/[A-Za-z]:/g, '/');
        pathname = pathname.replace(/[A-Za-z]:/g, '');
        var page = pathname.split('/').pop();
        return page || (storeRoutes ? 'index.php' : 'index.html');
    }

    function getCurrentBrandPageSlug() {
        var bodyBrand = document.body ? document.body.getAttribute('data-brand-page') : '';
        var queryBrand = queryParam('brand');
        if (bodyBrand && getBrandBySlug(bodyBrand)) {
            return bodyBrand;
        }
        if (queryBrand && getBrandBySlug(queryBrand)) {
            return queryBrand;
        }
        return getBrandPageSlugFromPage(getCurrentPage());
    }

    function getBrandExperience(brandSlug) {
        return brandExperienceConfig[brandSlug] || null;
    }

    function getBrandHeroProduct(brandSlug) {
        var experience = getBrandExperience(brandSlug);
        var configuredProduct = experience ? getProductBySlug(experience.featuredProductSlug) : null;

        return configuredProduct || sortByHomePriority(getProductsByBrand(brandSlug))[0] || null;
    }

    function getNavigationType() {
        var entries = window.performance && typeof window.performance.getEntriesByType === 'function'
            ? window.performance.getEntriesByType('navigation')
            : [];

        if (entries && entries[0] && entries[0].type) {
            return entries[0].type;
        }

        if (window.performance && window.performance.navigation) {
            if (window.performance.navigation.type === 1) {
                return 'reload';
            }
            if (window.performance.navigation.type === 2) {
                return 'back_forward';
            }
        }

        return 'navigate';
    }

    function renderHeaderNav() {
        var currentPage = getCurrentPage();
        var currentBrandPageSlug = getCurrentBrandPageSlug();
        var brandParam = queryParam('brand');
        var viewParam = queryParam('view') || 'products';
        var categoryParam = queryParam('category');
        var navItems = [
            { label: 'Home', href: getRoute('home', './index.html'), active: currentPage === 'index.html' || currentPage === 'index.php' },
            { label: 'About Us', href: getRoute('about', './about.html'), active: currentPage === 'about.html' || currentPage === 'about.php' },
            {
                label: 'Brands',
                href: toPhpHref('./index.html#brand-lineup'),
                active: Boolean(currentBrandPageSlug) || ((currentPage === 'shop.html' || currentPage === 'shop.php') && viewParam === 'brand' && Boolean(brandParam)),
                dropdown: brandGroups.map(function (brandGroup) {
                    return {
                        label: brandGroup.name,
                        href: getBrandPageHref(brandGroup.slug)
                    };
                })
            },
            {
                label: 'Categories',
                href: toPhpHref('./shop.html?view=categories'),
                active: (currentPage === 'shop.html' || currentPage === 'shop.php') && viewParam === 'categories',
                dropdown: [{
                    label: 'All Categories',
                    href: toPhpHref('./shop.html?view=categories')
                }].concat(Object.keys(categoryMeta).map(function (key) {
                    return {
                        label: categoryMeta[key].label,
                        href: toPhpHref('./shop.html?view=categories&category=' + key),
                        active: categoryParam === key
                    };
                }))
            },
            { label: 'Product Details', href: toPhpHref('./shop.html?view=products'), active: (((currentPage === 'shop.html' || currentPage === 'shop.php') && viewParam === 'products') || currentPage === 'shop-details.html' || currentPage === 'shop-details.php') && !currentBrandPageSlug },
            { label: 'Compare Products', href: getRoute('compare', './compare.html'), active: currentPage === 'compare.html' || currentPage === 'compare.php' || currentPage === 'shopping-cart.html' || currentPage === 'shopping-cart.php' },
            { label: 'Contact Us', href: getRoute('contact', './contact.html'), active: currentPage === 'contact.html' || currentPage === 'contact.php' }
        ];

        Array.prototype.forEach.call(document.querySelectorAll('.header__menu.mobile-menu > ul'), function (nav) {
            nav.classList.add('header__menu__catalog');
            nav.innerHTML = navItems.map(function (item) {
                if (item.dropdown) {
                    return [
                        '<li', item.active ? ' class="active"' : '', '>',
                        '<a href="', item.href, '">', escapeHtml(item.label), '</a>',
                        '<ul class="dropdown">',
                        item.dropdown.map(function (child) {
                            return '<li><a href="' + child.href + '">' + escapeHtml(child.label) + '</a></li>';
                        }).join(''),
                        '</ul>',
                        '</li>'
                    ].join('');
                }

                return [
                    '<li', item.active ? ' class="active"' : '', '>',
                    '<a href="', item.href, '">', escapeHtml(item.label), '</a>',
                    '</li>'
                ].join('');
            }).join('');
        });
    }

    function renderBrandHoverLists() {
        var brandListHtml = brandGroups.map(function (brandGroup) {
            return '<li>' + escapeHtml(brandGroup.name) + '</li>';
        }).join('');

        Array.prototype.forEach.call(document.querySelectorAll('.header__top__hover, .offcanvas__top__hover'), function (wrapper) {
            var label = wrapper.querySelector('span');
            var list = wrapper.querySelector('ul');

            if (label && list && /brands/i.test(label.textContent)) {
                list.innerHTML = brandListHtml;
            }
        });
    }

    function appendLinkIfMissing(container, href, label) {
        var link;

        if (!container || container.querySelector('a[href="' + href + '"]')) {
            return;
        }

        link = document.createElement('a');
        link.setAttribute('href', href);
        link.textContent = label;
        container.appendChild(link);
    }

    function renderSharedAboutLinks() {
        Array.prototype.forEach.call(document.querySelectorAll('.header__top__links, .offcanvas__links'), function (container) {
            appendLinkIfMissing(container, getRoute('about', './about.html'), 'About Us');
        });

        Array.prototype.forEach.call(document.querySelectorAll('.footer__widget'), function (widget) {
            var title = widget.querySelector('h6');
            var list = widget.querySelector('ul');
            var item;
            var link;

            if (!title || !list || !/explore/i.test(title.textContent) || list.querySelector('a[href="' + getRoute('about', './about.html') + '"]')) {
                return;
            }

            item = document.createElement('li');
            link = document.createElement('a');
            link.setAttribute('href', getRoute('about', './about.html'));
            link.textContent = 'About Us';
            item.appendChild(link);
            list.appendChild(item);
        });
    }

    function setShopPageState(title, sidebarFilter, topFilter) {
        var breadcrumbTitle = document.querySelector('.breadcrumb__text h4');
        var breadcrumbTrail = document.querySelector('.breadcrumb__links span');

        if (breadcrumbTitle) {
            breadcrumbTitle.textContent = title;
        }

        if (breadcrumbTrail) {
            breadcrumbTrail.textContent = title;
        }

        Array.prototype.forEach.call(document.querySelectorAll('.shop__sidebar__categories .js-mix-filter, .shop__sidebar__brand .js-mix-filter'), function (link) {
            link.classList.remove('active');
            if (link.getAttribute('data-filter') === sidebarFilter) {
                link.classList.add('active');
            }
        });

        Array.prototype.forEach.call(document.querySelectorAll('.filter__controls li'), function (item) {
            item.classList.remove('active');
            if (item.getAttribute('data-filter') === topFilter) {
                item.classList.add('active');
            }
        });
    }

    function renderStoreTotals() {
        setTextForAll('[data-store-total]', storeStats.totalProducts + ' Products');
        setTextForAll('[data-store-brand-total]', storeStats.totalBrands + ' Brands');
        setTextForAll('[data-store-category-total]', storeStats.totalCategories + ' Categories');
        setTextForAll('[data-store-brand-count]', String(storeStats.totalBrands));
        setTextForAll('[data-store-total-number]', String(storeStats.totalProducts));
        setTextForAll('[data-store-brand-number]', String(storeStats.totalBrands));
        setTextForAll('[data-store-category-number]', String(storeStats.totalCategories));

        if (storeSession) {
            setTextForAll('[data-cart-count]', String(storeSession.cartCount || 0));
            setTextForAll('[data-favorite-count]', String(storeSession.favoriteCount || 0));
        }
    }

    function bindSearchSystem() {
        var searchInput = document.getElementById('search-input');
        var searchForms = document.querySelectorAll('.search-model-form');
        var resultsPanel;

        if (!searchInput || !searchForms.length) {
            return;
        }

        if (queryParam('search')) {
            searchInput.value = queryParam('search');
        }

        resultsPanel = document.createElement('div');
        resultsPanel.className = 'la-search-results';
        resultsPanel.setAttribute('role', 'listbox');
        searchInput.insertAdjacentElement('afterend', resultsPanel);

        function resultTemplate(item) {
            var image = item.image || 'img/logo/logo.png';
            return [
                '<a class="la-search-result" href="', item.href, '">',
                '<img src="', image, '" alt="', escapeHtml(item.title), '">',
                '<span><strong>', escapeHtml(item.title), '</strong><span>', escapeHtml(item.meta), '</span></span>',
                '<em>', escapeHtml(item.type), '</em>',
                '</a>'
            ].join('');
        }

        function collectSearchResults(query) {
            var normalized = query.toLowerCase();
            var foundProducts = products.filter(function (product) {
                var haystack = [
                    product.name,
                    product.summary,
                    product.brand,
                    product.categoryLabel,
                    product.customLabel
                ].join(' ').toLowerCase();
                return haystack.indexOf(normalized) > -1;
            }).slice(0, 7).map(function (product) {
                return {
                    type: 'Product',
                    title: product.name,
                    meta: product.brand + ' / ' + product.categoryLabel + ' / ' + formatHomeCurrency(product.price),
                    image: product.image,
                    href: buildDetailHref(product.slug)
                };
            });
            var foundBrands = brandGroups.filter(function (brand) {
                return (brand.name + ' ' + brand.slug).toLowerCase().indexOf(normalized) > -1;
            }).slice(0, 4).map(function (brand) {
                return {
                    type: 'Brand',
                    title: brand.name,
                    meta: (brand.products || []).length + ' products',
                    image: brandLogoImageMap[brand.slug] || 'img/logo/logo.png',
                    href: getBrandPageHref(brand.slug)
                };
            });
            var foundCategories = Object.keys(categoryMeta).filter(function (slug) {
                var category = categoryMeta[slug] || {};
                return (slug + ' ' + category.label + ' ' + category.note).toLowerCase().indexOf(normalized) > -1;
            }).slice(0, 4).map(function (slug) {
                return {
                    type: 'Category',
                    title: categoryMeta[slug].label,
                    meta: categoryMeta[slug].note || 'Open category collection',
                    image: categoryMeta[slug].bannerImage || 'img/logo/logo.png',
                    href: toPhpHref('./shop.html?view=categories&category=' + slug)
                };
            });

            return foundProducts.concat(foundBrands, foundCategories).slice(0, 12);
        }

        function renderSearchResults() {
            var query = searchInput.value.trim();
            var results;

            if (query.length < 2) {
                resultsPanel.classList.remove('is-visible');
                resultsPanel.innerHTML = '';
                return;
            }

            results = collectSearchResults(query);
            resultsPanel.innerHTML = results.length
                ? results.map(resultTemplate).join('')
                : '<div class="la-search-result"><img src="img/logo/logo.png" alt="No results"><span><strong>No matches found</strong><span>Try brand, category, or product name.</span></span><em>Search</em></div>';
            resultsPanel.classList.add('is-visible');
        }

        searchInput.addEventListener('input', renderSearchResults);
        searchInput.addEventListener('focus', renderSearchResults);

        Array.prototype.forEach.call(searchForms, function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                var query = searchInput.value.trim();
                if (!query) {
                    return;
                }
                window.location.href = toPhpHref('./shop.html?view=products&search=' + encodeURIComponent(query));
            });
        });

        document.addEventListener('click', function (event) {
            if (!event.target.closest('.search-model-form')) {
                resultsPanel.classList.remove('is-visible');
            }
        });
    }

    function injectPremiumThemeStyles() {
        var style = document.getElementById('la-imperial-premium-theme');

        if (style) {
            return;
        }

        style = document.createElement('style');
        style.id = 'la-imperial-premium-theme';
        style.textContent = [
            '.header{position:relative;z-index:40;background:#fff;box-shadow:0 22px 52px rgba(22,28,49,.08);}',
            '.header__top{background:linear-gradient(135deg,#0f1627 0%,#182238 58%,#0d1524 100%);}',
            '.header__top__left p,.header__top__links a,.header__top__hover>span{color:#fff;}',
            '.header__top__hover ul{border-radius:16px;overflow:hidden;}',
            '.header>.container{margin:14px auto;padding:0 24px;border-radius:28px;border:1px solid rgba(214,176,102,.18);background:linear-gradient(135deg,#ffffff 0%,#f8f6f1 100%);box-shadow:0 16px 34px rgba(33,40,74,.08);}',
            '.header>.container>.row{display:flex;align-items:center;flex-wrap:nowrap;}',
            '.header__menu.mobile-menu>ul.header__menu__catalog{display:flex;align-items:center;justify-content:center;flex-wrap:nowrap;gap:12px 10px;}',
            '.header__menu.mobile-menu>ul.header__menu__catalog>li{margin-right:0;flex:0 0 auto;}',
            '.header__menu.mobile-menu>ul.header__menu__catalog>li>a{padding:13px 18px;border-radius:999px;background:rgba(255,255,255,.72);border:1px solid rgba(32,36,61,.08);color:#20243d;font-size:12px;font-weight:800;letter-spacing:.05em;white-space:nowrap;box-shadow:0 10px 24px rgba(33,40,74,.04);}',
            '.header__menu.mobile-menu>ul.header__menu__catalog>li.active>a{background:linear-gradient(135deg,#151d2f 0%,#24304d 100%);border-color:#151d2f;color:#fff;box-shadow:0 18px 34px rgba(21,29,47,.18);}',
            '.header__menu.mobile-menu>ul.header__menu__catalog>li>a:hover{background:#f3ebe0;border-color:#e3d3bc;color:#20243d;}',
            '.header__menu.mobile-menu>ul.header__menu__catalog>li .dropdown{left:50%;top:calc(100% + 12px);min-width:250px;padding:14px 0;border-radius:24px;transform:translateX(-50%);background:linear-gradient(135deg,#111827 0%,#1d2740 100%);box-shadow:0 24px 48px rgba(17,24,39,.24);max-height:min(60vh,380px);overflow-y:auto;}',
            '.header__menu.mobile-menu>ul.header__menu__catalog>li .dropdown li a{padding:11px 22px;font-size:13px;font-weight:700;color:#f8fafc;}',
            '.header__nav__option{display:flex;align-items:center;justify-content:flex-end;gap:10px;flex-wrap:nowrap;}',
            '.header__nav__option a{display:inline-flex;align-items:center;justify-content:center;width:42px;height:42px;margin-right:0;border-radius:14px;background:#fff;border:1px solid rgba(32,36,61,.08);box-shadow:0 10px 22px rgba(33,40,74,.05);flex:0 0 auto;}',
            '.header__nav__option .price{margin-left:0;top:0;height:42px;padding:0 16px;border-radius:999px;background:linear-gradient(135deg,#151d2f 0%,#24304d 100%);color:#fff;font-size:12px;display:inline-flex;align-items:center;justify-content:center;letter-spacing:.06em;text-transform:uppercase;}',
            '.canvas__open{border-radius:12px;box-shadow:0 10px 24px rgba(33,40,74,.08);}',
            '@media only screen and (min-width:1200px){.header>.container>.row>[class*=col-lg-]:first-child{flex:0 0 14%;max-width:14%;}.header>.container>.row>[class*=col-lg-]:nth-child(2){flex:0 0 56%;max-width:56%;}.header>.container>.row>[class*=col-lg-]:last-child{flex:0 0 30%;max-width:30%;}}',
            '@media only screen and (max-width: 1199px){.header__menu.mobile-menu>ul.header__menu__catalog{gap:6px;flex-wrap:wrap;}.header__menu.mobile-menu>ul.header__menu__catalog>li>a{padding:10px 12px;letter-spacing:.02em;}.header__nav__option{flex-wrap:wrap;justify-content:flex-end;}.header__nav__option a{width:40px;height:40px;}}',
            '@media only screen and (max-width: 991px){.header>.container{padding:0 18px;margin:10px auto;}.header__logo img{height:64px;}}'
        ].join('');
        document.head.appendChild(style);
    }

    function applyBrandLogos() {
        var siteLogo = showcaseMediaConfig.siteLogo || { src: 'img/logo/logo.png', alt: 'La Imperial' };

        Array.prototype.forEach.call(document.querySelectorAll('.header__logo img, .footer__logo img, .offcanvas__logo img, .store-overview__logo, .home-welcome__logo'), function (image) {
            image.setAttribute('src', siteLogo.src || 'img/logo/logo.png');
            image.setAttribute('alt', siteLogo.alt || 'La Imperial');
        });
    }

    function getBrandVideoPath(brandSlug) {
        return getBrandHeroVideoPath(brandSlug) || getBrandSplashVideoPath(brandSlug) || '';
    }

    function getBrandLogoImagePath(brandSlug) {
        return brandLogoImageMap[brandSlug] || '';
    }

    function brandLogoMarkup(brandGroup) {
        var accent = brandAccent[brandGroup.slug] || '#ffffff';
        var logoImage = getBrandLogoImagePath(brandGroup.slug);

        if (logoImage) {
            return [
                '<img src="', logoImage, '" alt="', escapeHtml(brandGroup.name), ' logo">'
            ].join('');
        }

        return [
            '<span class="brand-marquee__wordmark" style="--brand-accent:', accent, ';">', escapeHtml(brandGroup.name), '</span>'
        ].join('');
    }

    function brandHeroSlideTemplate(brandGroup) {
        var story = brandStories[brandGroup.slug] || {};
        var primaryProduct = getBrandHeroProduct(brandGroup.slug);
        var experience = getBrandExperience(brandGroup.slug) || {};
        var accent = brandAccent[brandGroup.slug] || '#1f2937';
        var heroMedia;
        var headline = experience.headline || brandGroup.headline || story.headline || (brandGroup.name + ' Premium Appliance Collection');
        var summary = experience.summary || brandGroup.summary || story.summary || ('Explore ' + brandGroup.name + ' appliances across cooling, laundry, refrigeration, and kitchen categories.');

        if (!primaryProduct) {
            return '';
        }

        heroMedia = getBrandHeroMedia(brandGroup.slug, primaryProduct);

        return [
            '<div class="hero__items hero__items--brand" style="--hero-accent:', accent, ';">',
            '<div class="container">',
            '<div class="hero__brand-shell">',
            '<div class="row align-items-center">',
            '<div class="col-lg-7">',
            '<div class="hero__text hero__text--brand">',
            '<h6>', escapeHtml(brandGroup.name), ' Brand Showcase</h6>',
            '<h2>', escapeHtml(headline), '</h2>',
            '<p>', escapeHtml(summary), '</p>',
            '<div class="hero__brand__caption">Featured model: <strong>', escapeHtml(experience.featuredProductLabel || primaryProduct.name), '</strong> <span>', formatHomeCurrency(primaryProduct.price), '</span></div>',
            '<div class="hero__brand__actions">',
            '<a href="', getBrandPageHref(brandGroup.slug), '" class="primary-btn">Explore ', escapeHtml(brandGroup.name), ' <span class="arrow_right"></span></a>',
            '<a href="', getBrandPageHref(brandGroup.slug), '#listing-showcase" class="hero__brand__link">Brand Video</a>',
            '</div>',
            '</div>',
            '</div>',
            '<div class="col-lg-5">',
            '<div class="hero__brand__single">',
            '<div class="hero__brand__single__frame">',
            '<img src="', heroMedia.src, '" alt="', escapeHtml(heroMedia.alt || primaryProduct.name), '">',
            '</div>',
            '</div>',
            '</div>',
            '</div>',
            '</div>',
            '</div>',
            '</div>'
        ].join('');
    }

    function renderHomeBrandHero() {
        var slider = document.getElementById('home-brand-hero-slider');

        if (!slider) {
            return;
        }

        slider.innerHTML = brandGroups.map(brandHeroSlideTemplate).join('');

        if (window.LaImperialInitHeroSlider && typeof window.LaImperialInitHeroSlider === 'function') {
            window.LaImperialInitHeroSlider(true);
        }
    }

    function renderHomeBrandMarquee() {
        var track = document.getElementById('brand-marquee-track');
        if (!track) {
            return;
        }

        var marqueeItems = [];
        for (var copy = 0; copy < 2; copy += 1) {
            brandGroups.forEach(function (brandGroup) {
                marqueeItems.push([
                    '<a class="brand-marquee__item" href="', getBrandPageHref(brandGroup.slug), '">',
                    '<span class="brand-marquee__logo">', brandLogoMarkup(brandGroup), '</span>',
                    '</a>'
                ].join(''));
            });
        }

        track.innerHTML = marqueeItems.join('');
    }

    function spotlightProductTemplate(product, label, showcaseMedia) {
        var media = showcaseMedia || resolveMediaAsset(null, product.image, product.name);

        return [
            '<div class="col-lg-3 col-md-6 col-sm-6">',
            '<div class="spotlight-product">',
            '<div class="spotlight-product__pic">',
            '<img src="', media.src, '" alt="', escapeHtml(media.alt || product.name), '" class="spotlight-product__image">',
            '<span class="spotlight-product__tag">', escapeHtml(label), '</span>',
            '</div>',
            '<div class="spotlight-product__text">',
            '<h6>', escapeHtml(product.name), '</h6>',
            '<p>', escapeHtml(product.summary), '</p>',
            '<div class="spotlight-product__meta">',
            '<span>', formatCurrency(product.price), '</span>',
            product.isDiscounted ? '<span>- ' + product.discountPercent + '%</span>' : '<span>New</span>',
            '</div>',
            '<div class="spotlight-product__links">',
            '<a href="', buildDetailHref(product), '">Full Details</a>',
            '<a href="', buildCompareHref(product, null, getProductsByBrand(product.brandSlug)), '">Compare</a>',
            '</div>',
            '</div>',
            '</div>',
            '</div>'
        ].join('');
    }

    function fillProductSet(source, predicate, limit) {
        var filtered = source.filter(predicate);
        if (filtered.length >= limit) {
            return filtered.slice(0, limit);
        }

        return filtered.concat(source.filter(function (product) {
            return filtered.indexOf(product) === -1;
        })).slice(0, limit);
    }

    function uniqueProductsBySlug(items) {
        var seen = {};

        return items.filter(function (product) {
            if (!product || seen[product.slug]) {
                return false;
            }

            seen[product.slug] = true;
            return true;
        });
    }

    function homePriorityScore(product) {
        var score = 0;

        if (featuredProductSlugs.indexOf(product.slug) > -1) {
            score += 80;
        }

        if (product.isTrending) {
            score += 30;
        }

        if (product.isDiscounted) {
            score += 18 + product.discountPercent;
        }

        if (product.category === 'microwave-ovens' || product.category === 'cooking-ranges' || product.category === 'water-dispensers') {
            score += 6;
        }

        return score;
    }

    function sortByHomePriority(items) {
        return items.slice().sort(function (left, right) {
            return homePriorityScore(right) - homePriorityScore(left) ||
                left.price - right.price ||
                left.name.localeCompare(right.name);
        });
    }

    function pickHomeProducts(predicate, limit, excludedSlugs) {
        var excluded = {};
        var preferred = [];
        var fallback = [];

        (excludedSlugs || []).forEach(function (slug) {
            excluded[slug] = true;
        });

        preferred = sortByHomePriority(products.filter(function (product) {
            return !excluded[product.slug] && predicate(product);
        }));

        if (preferred.length >= limit) {
            return preferred.slice(0, limit);
        }

        fallback = sortByHomePriority(products.filter(function (product) {
            return !excluded[product.slug] && preferred.indexOf(product) === -1;
        }));

        return preferred.concat(fallback).slice(0, limit);
    }

    function collectSlugs(target, items) {
        items.forEach(function (product) {
            if (target.indexOf(product.slug) === -1) {
                target.push(product.slug);
            }
        });

        return target;
    }

    function formatHomeCurrency(amount) {
        return 'Rs. ' + amount.toLocaleString('en-US');
    }

    function getHomeBackHref(anchorId) {
        return toPhpHref('./index.html#' + anchorId);
    }

    function homeBadgeLabel(product) {
        if (product.isDiscounted) {
            return product.discountPercent + '% Off';
        }

        if (product.isTrending) {
            return 'Hot';
        }

        return 'New';
    }

    function homeProductCardTemplate(product, anchorId) {
        var detailHref = buildDetailHref(product, getHomeBackHref(anchorId));
        var compareHref = buildCompareHref(product, null, getProductsByCategory(product.category));
        var hasOriginalPrice = product.originalPrice > product.price;
        var saving = hasOriginalPrice ? formatHomeCurrency(product.originalPrice - product.price) : '';

        return [
            '<article class="homeeliance-card">',
            '<a class="homeeliance-card__media" href="', detailHref, '">',
            '<span class="homeeliance-card__badge">', escapeHtml(homeBadgeLabel(product)), '</span>',
            '<img src="', product.image, '" alt="', escapeHtml(product.name), '">',
            '</a>',
            '<div class="homeeliance-card__body">',
            '<span class="homeeliance-card__brand">', escapeHtml(product.brand), '</span>',
            '<h3 class="homeeliance-card__title"><a href="', detailHref, '">', escapeHtml(product.name), '</a></h3>',
            '<p class="homeeliance-card__meta-copy">', escapeHtml(product.categoryLabel), '</p>',
            '<div class="homeeliance-card__meta">',
            '<span class="homeeliance-card__price">', formatHomeCurrency(product.price), '</span>',
            hasOriginalPrice ? '<span class="homeeliance-card__price homeeliance-card__price--old">' + formatHomeCurrency(product.originalPrice) + '</span>' : '',
            '</div>',
            '<p class="homeeliance-card__saving">', hasOriginalPrice ? 'Save ' + saving : escapeHtml(product.summary), '</p>',
            '<div class="homeeliance-card__buttons">',
            '<a class="homeeliance-card__btn homeeliance-card__btn--red" href="', detailHref, '">View Details</a>',
            '<a class="homeeliance-card__btn homeeliance-card__btn--green" href="', compareHref, '">Compare Product</a>',
            '</div>',
            '</div>',
            '</article>'
        ].join('');
    }

    function homeMiniProductTemplate(product, anchorId) {
        var detailHref = buildDetailHref(product, getHomeBackHref(anchorId));

        return [
            '<article class="homeeliance-mini-card">',
            '<a class="homeeliance-mini-card__media" href="', detailHref, '">',
            '<img src="', product.image, '" alt="', escapeHtml(product.name), '">',
            '</a>',
            '<div class="homeeliance-mini-card__body">',
            '<span class="homeeliance-mini-card__brand">', escapeHtml(product.brand), '</span>',
            '<h3><a href="', detailHref, '">', escapeHtml(product.name), '</a></h3>',
            '<div class="homeeliance-mini-card__meta">',
            '<span>', formatHomeCurrency(product.price), '</span>',
            product.isDiscounted ? '<span>' + product.discountPercent + '% Off</span>' : '<span>' + escapeHtml(product.categoryLabel) + '</span>',
            '</div>',
            '</div>',
            '</article>'
        ].join('');
    }

    function setHomeEssentialGallery(items) {
        var galleryConfig = showcaseMediaConfig.homeEssentialGallery || [];
        var primaryMedia;

        if (!items.length) {
            return;
        }

        primaryMedia = resolveMediaAsset(galleryConfig[0], items[0].image, items[0].name);
        setImage('home-essential-main-image', primaryMedia.src, primaryMedia.alt);
    }

    function setHomeKitchenFeature(primaryProduct) {
        var detailHref;
        var media;

        if (!primaryProduct) {
            return;
        }

        media = resolveMediaAsset(showcaseMediaConfig.homeKitchenPromo, primaryProduct.image, primaryProduct.name);
        detailHref = buildDetailHref(primaryProduct, getHomeBackHref('home-kitchen-grid'));
        setText('home-kitchen-ad-title', primaryProduct.name);
        setText('home-kitchen-ad-summary', primaryProduct.summary);
        setText('home-kitchen-ad-brand', primaryProduct.brand + ' | ' + primaryProduct.categoryLabel);
        setText('home-kitchen-ad-price', formatHomeCurrency(primaryProduct.price));
        setHref('home-kitchen-ad-link', detailHref);
        setImage('home-kitchen-ad-image', media.src, media.alt);
    }

    function homeSmartStripTemplate(product) {
        var detailHref = buildDetailHref(product, getHomeBackHref('store-snapshot'));
        var hasOriginalPrice = product.originalPrice > product.price;

        return [
            '<a class="homeeliance-smart-card" href="', detailHref, '">',
            '<span class="homeeliance-smart-card__image"><img src="', product.image, '" alt="', escapeHtml(product.name), '"></span>',
            '<span class="homeeliance-smart-card__body">',
            '<small>', escapeHtml(product.brand), '</small>',
            '<strong>', escapeHtml(product.name), '</strong>',
            '<span class="homeeliance-smart-card__price">',
            hasOriginalPrice ? '<em>' + formatHomeCurrency(product.originalPrice) + '</em>' : '',
            '<b>', formatHomeCurrency(product.price), '</b>',
            '</span>',
            '</span>',
            '</a>'
        ].join('');
    }

    function setHomeSmartFeature(primaryProduct) {
        var kitchenProducts;
        var supportProducts;
        var strip = document.getElementById('home-smart-strip');
        var poster = document.querySelector('.homeeliance-smart-promo__poster');
        var detailHref;
        var compareHref;
        var media;

        if (!primaryProduct) {
            return;
        }

        kitchenProducts = sortByHomePriority(products.filter(function (product) {
            return product.slug !== primaryProduct.slug &&
                (product.category === 'microwave-ovens' || product.category === 'cooking-ranges');
        }));
        supportProducts = kitchenProducts.slice(0, 4);
        detailHref = buildDetailHref(primaryProduct, getHomeBackHref('store-snapshot'));
        compareHref = buildCompareHref(primaryProduct, supportProducts[0], getProductsByCategory(primaryProduct.category));
        setText('home-smart-feature-title', primaryProduct.name);
        setText('home-smart-feature-summary', primaryProduct.summary);
        setText('home-smart-feature-brand', primaryProduct.brand + ' | ' + primaryProduct.categoryLabel);
        setText('home-smart-feature-price', formatHomeCurrency(primaryProduct.price));
        setHref('home-smart-feature-link', detailHref);
        setHref('home-smart-feature-compare', compareHref);
        media = resolveMediaAsset(showcaseMediaConfig.homeSmartPromo, primaryProduct.image, primaryProduct.name);

        if (poster) {
            poster.setAttribute('src', media.src);
            poster.setAttribute('alt', media.alt);
        }

        if (strip) {
            strip.innerHTML = [primaryProduct].concat(supportProducts).slice(0, 5).map(homeSmartStripTemplate).join('');
        }
    }

    function renderListingShowcase(context) {
        var section = document.getElementById('listing-showcase');
        var video = document.getElementById('listing-hero-video');
        var videoSource = document.getElementById('listing-hero-video-source');
        var videoNote = document.getElementById('listing-hero-video-note');
        var trendingGrid = document.getElementById('listing-trending-grid');
        var discountedGrid = document.getElementById('listing-discounted-grid');
        var featuredProducts = context.products;
        var poster = featuredProducts[0] ? featuredProducts[0].image : 'img/banner/banner-1.jpg';
        var videoSrc = '';
        var kicker = 'Store Highlights';
        var title = 'All Product Details';
        var summary = 'Explore trending appliances, discounted offers, and complete product details from every brand.';
        var trendingLabel = 'Trending Picks';
        var discountedLabel = 'Discounted Products';
        var compareHref = toPhpHref('./compare.html');
        var comparePool = context.comparePool && context.comparePool.length ? context.comparePool : context.products;
        var heroPosterMedia;

        if (!section || !trendingGrid || !discountedGrid) {
            return;
        }

        if (context.brand) {
            kicker = context.brand.name + ' Showcase';
            title = context.brand.name + ' Appliances';
            summary = context.brand.summary || (context.brand.name + ' collection with a separate hero media area, fast-moving products, and current discounted models.');
            trendingLabel = context.brand.name + ' Trending';
            discountedLabel = context.brand.name + ' Discounted';
            videoSrc = getBrandHeroVideoPath(context.brand.slug);
            heroPosterMedia = getBrandHeroPoster(context.brand.slug, featuredProducts[0]);
            poster = heroPosterMedia.src;
            if (context.category) {
                summary = context.brand.name + ' ' + context.category.label.toLowerCase() + ' collection with separate hero media, filtered products, and compare-ready highlights.';
                trendingLabel = context.brand.name + ' ' + context.category.label + ' Trending';
                discountedLabel = context.brand.name + ' ' + context.category.label + ' Discounted';
            }
            compareHref = buildCompareHref(context.products[0], context.products[1], comparePool);
        } else if (context.category) {
            kicker = context.category.label;
            title = context.category.label + ' Collection';
            summary = context.category.note + ' Open any product for details, related recommendations, and direct compare access.';
            trendingLabel = context.category.label + ' Trending';
            discountedLabel = context.category.label + ' Discounts';
            compareHref = buildCompareHref(context.products[0], context.products[1], comparePool);
        } else if (context.view === 'products') {
            section.classList.add('listing-showcase--hidden');
            return;
        }

        section.classList.remove('listing-showcase--hidden');
        setText('listing-hero-kicker', kicker);
        setText('listing-hero-title', title);
        setText('listing-hero-summary', summary);
        setText('listing-trending-title', trendingLabel);
        setText('listing-discount-title', discountedLabel);
        setHref('listing-hero-compare-link', compareHref);

        if (video) {
            video.setAttribute('poster', poster);

            if (context.brand && videoSrc) {
                video.setAttribute('controls', 'controls');
                video.setAttribute('preload', 'metadata');
                video.setAttribute('autoplay', 'autoplay');
            } else {
                video.removeAttribute('controls');
                video.removeAttribute('autoplay');
            }
        }

        if (videoSource) {
            videoSource.setAttribute('src', videoSrc);
            if (video && typeof video.load === 'function') {
                video.load();
            }
        }

        if (videoNote) {
            if (context.brand && videoSrc) {
                videoNote.textContent = 'Hero video is separate from the splash intro for ' + context.brand.name + '. Hover to preview the full brand showcase.';
            } else if (context.brand) {
                videoNote.textContent = 'Hero poster is active for ' + context.brand.name + '. Add a separate hero video path in product-store.js whenever you want to swap this panel to video.';
            } else {
                videoNote.textContent = 'Category pages use this hero area for a strong promo visual while the trending and discounted products update below.';
            }
        }

        if (video && typeof video.pause === 'function' && !context.brand) {
            video.pause();
        }

        if (video && typeof video.play === 'function' && context.brand && videoSrc) {
            var playPromise = video.play();
            if (playPromise && typeof playPromise.catch === 'function') {
                playPromise.catch(function () {
                    return null;
                });
            }
        }

        trendingGrid.innerHTML = fillProductSet(context.products, function (product) {
            return product.isTrending;
        }, 4).map(function (product, index) {
            return spotlightProductTemplate(product, 'Trending', getProductShowcaseMedia(product, index));
        }).join('');

        discountedGrid.innerHTML = fillProductSet(context.products, function (product) {
            return product.isDiscounted;
        }, 4).map(function (product, index) {
            return spotlightProductTemplate(product, product.discountPercent + '% Off', getProductShowcaseMedia(product, index + 4));
        }).join('');

        applySetBackgrounds(section);
    }

    function renderBrandSplash() {
        var brandSlug = getCurrentBrandPageSlug();
        var brand = getBrandBySlug(brandSlug);
        var experience = getBrandExperience(brandSlug);
        var product = brandSlug ? getBrandHeroProduct(brandSlug) : null;
        var overlay;
        var splashVideoPath = brandSlug ? getBrandSplashVideoPath(brandSlug) : '';
        var splashLogoPath = experience && experience.splashLogo ? experience.splashLogo : (showcaseMediaConfig.splashLogo || 'img/logo/image.png');
        var splashKey = brandSlug ? ('la-imparial-splash-' + brandSlug) : '';
        var video;
        var closeButton;
        var closeTimer;
        var body = document.body;

        function releaseBrandIntro() {
            if (body) {
                body.classList.remove('brand-intro-entry');
            }
        }

        if (!brandSlug || !brand || !experience || !product || !splashVideoPath) {
            releaseBrandIntro();
            return;
        }

        if (document.getElementById('brand-splash-overlay')) {
            return;
        }

        overlay = document.createElement('div');
        overlay.id = 'brand-splash-overlay';
        overlay.className = 'brand-splash';
        overlay.innerHTML = [
            '<div class="brand-splash__panel">',
            '<div class="brand-splash__backdrop"></div>',
            '<img class="brand-splash__logo" src="', splashLogoPath, '" alt="La Imperial">',
            '<button type="button" class="brand-splash__skip">Skip Intro</button>',
            '<div class="brand-splash__media">',
            '<video class="brand-splash__video" autoplay muted playsinline preload="auto">',
            '<source src="', splashVideoPath, '" type="video/mp4">',
            '</video>',
            '<div class="brand-splash__overlay"></div>',
            '<div class="brand-splash__brandline">',
            '<strong>', escapeHtml(brand.name), '</strong>',
            '<span>', escapeHtml(experience.featuredProductLabel || product.name), '</span>',
            '</div>',
            '</div>',
            '</div>'
        ].join('');

        document.body.appendChild(overlay);

        video = overlay.querySelector('.brand-splash__video');
        closeButton = overlay.querySelector('.brand-splash__skip');

        function closeSplash() {
            window.clearTimeout(closeTimer);
            overlay.classList.remove('brand-splash--active');
            releaseBrandIntro();
            if (splashKey && window.sessionStorage) {
                window.sessionStorage.setItem(splashKey, '1');
            }
            window.setTimeout(function () {
                if (overlay && overlay.parentNode) {
                    overlay.parentNode.removeChild(overlay);
                }
            }, 280);
        }

        closeTimer = window.setTimeout(closeSplash, 9000);

        if (closeButton) {
            closeButton.addEventListener('click', closeSplash);
        }

        if (video) {
            video.addEventListener('ended', closeSplash);
            video.addEventListener('error', function () {
                overlay.classList.add('brand-splash--fallback');
                closeTimer = window.setTimeout(closeSplash, 1800);
            });
            video.addEventListener('loadedmetadata', function () {
                if (video.duration && isFinite(video.duration)) {
                    window.clearTimeout(closeTimer);
                    closeTimer = window.setTimeout(closeSplash, Math.max(2000, Math.ceil(video.duration * 1000) + 300));
                }
            });

            if (typeof video.play === 'function') {
                window.setTimeout(function () {
                    var playPromise = video.play();
                    if (playPromise && typeof playPromise.catch === 'function') {
                        playPromise.catch(function () {
                            overlay.classList.add('brand-splash--fallback');
                        });
                    }
                }, 180);
            }
        }

        window.setTimeout(function () {
            overlay.classList.add('brand-splash--active');
        }, 50);
    }

    function getRepeatedLabelGroups(source) {
        var map = {};

        (source || []).forEach(function (product) {
            var key = (product.customLabel || '').trim();

            if (!key) {
                return;
            }

            if (!map[key]) {
                map[key] = [];
            }

            map[key].push(product);
        });

        return Object.keys(map).map(function (label) {
            return {
                label: label,
                products: map[label]
            };
        }).filter(function (group) {
            return group.products.length > 1;
        }).sort(function (left, right) {
            return right.products.length - left.products.length || left.label.localeCompare(right.label);
        });
    }

    function bindLabelSliderShell(shell) {
        var viewport;

        if (!shell || shell.dataset.bound) {
            return;
        }

        viewport = shell.querySelector('.label-slider__viewport');
        shell.dataset.bound = 'true';

        shell.addEventListener('click', function (event) {
            var button = event.target.closest('[data-slider-step]');
            var step;

            if (!button || !viewport) {
                return;
            }

            step = parseInt(button.getAttribute('data-slider-step'), 10) || 0;
            viewport.scrollBy({
                left: step * 280,
                behavior: 'smooth'
            });
        });
    }

    function renderLabelSlider(target, source, options) {
        var groups = getRepeatedLabelGroups(source);
        var shell;
        var firstProduct;
        var summaryNames;
        var sliderId;

        if (!target || !target.parentNode) {
            return;
        }

        shell = target.parentNode.querySelector('.label-slider-shell[data-slider-key="' + escapeHtml((options && options.key) || 'default') + '"]');

        if (!groups.length) {
            if (shell && shell.parentNode) {
                shell.parentNode.removeChild(shell);
            }
            return;
        }

        if (!shell) {
            shell = document.createElement('section');
            shell.className = 'label-slider-shell';
            shell.setAttribute('data-slider-key', (options && options.key) || 'default');
            target.parentNode.insertBefore(shell, target);
        }

        sliderId = 'label-slider-' + ((options && options.key) || 'default');
        shell.innerHTML = [
            '<div class="label-slider-shell__top">',
            '<div>',
            '<h4>', escapeHtml((options && options.title) || 'Label Collections'), '</h4>',
            '<p>', escapeHtml((options && options.copy) || 'Products with the same label stay together in one clean slider.'), '</p>',
            '</div>',
            '<div class="label-slider__controls">',
            '<button type="button" data-slider-step="-1" aria-label="Scroll left"><i class="fa fa-angle-left"></i></button>',
            '<button type="button" data-slider-step="1" aria-label="Scroll right"><i class="fa fa-angle-right"></i></button>',
            '</div>',
            '</div>',
            '<div class="label-slider__viewport" id="', sliderId, '">',
            '<div class="label-slider__track">',
            groups.map(function (group) {
                firstProduct = group.products[0];
                summaryNames = group.products.slice(0, 3).map(function (product) {
                    return product.name;
                }).join(', ');

                return [
                    '<article class="label-slider__item">',
                    '<span>', escapeHtml(group.label), '</span>',
                    '<strong>', group.products.length, ' products share this label</strong>',
                    '<small>', escapeHtml(summaryNames), group.products.length > 3 ? ' +' + (group.products.length - 3) + ' more' : '', '</small>',
                    '<a href="', buildDetailHref(firstProduct), '">Open ', escapeHtml(firstProduct.name), '</a>',
                    '</article>'
                ].join('');
            }).join(''),
            '</div>',
            '</div>'
        ].join('');

        bindLabelSliderShell(shell);
    }

    function renderIndexPage() {
        var legacyContainer = document.getElementById('featured-product-grid');
        var bestSellerGrid = document.getElementById('home-best-seller-grid');
        var kitchenGrid = document.getElementById('home-kitchen-grid');
        var essentialGrid = document.getElementById('home-essential-strip');
        var featuredGrid = document.getElementById('home-featured-grid');
        var smartStrip = document.getElementById('home-smart-strip');
        var homeRoot = document.querySelector('.homeeliance-home') || document;
        var usedSlugs = [];
        var bestSellers;
        var kitchenProducts;
        var essentialProducts;
        var featuredProducts;
        var smartProducts;

        if (bestSellerGrid && kitchenGrid && essentialGrid && featuredGrid && smartStrip) {
            bestSellers = pickHomeProducts(function (product) {
                return product.isTrending || product.isDiscounted;
            }, 5, usedSlugs);
            collectSlugs(usedSlugs, bestSellers);

            kitchenProducts = pickHomeProducts(function (product) {
                return product.category === 'microwave-ovens' || product.category === 'cooking-ranges' || product.category === 'water-dispensers';
            }, 4, usedSlugs);
            collectSlugs(usedSlugs, kitchenProducts);

            essentialProducts = pickHomeProducts(function (product) {
                return product.category === 'air-conditioners' || product.category === 'washing-machines' || product.category === 'washer-dryers' || product.category === 'refrigerators' || product.category === 'deep-freezers';
            }, 5, usedSlugs);
            collectSlugs(usedSlugs, essentialProducts);

            featuredProducts = uniqueProductsBySlug(
                getFeaturedProducts().concat(
                    pickHomeProducts(function (product) {
                        return featuredProductSlugs.indexOf(product.slug) > -1 || product.isDiscounted || product.isTrending;
                    }, 10, usedSlugs)
                )
            ).slice(0, 10);
            collectSlugs(usedSlugs, featuredProducts);

            smartProducts = pickHomeProducts(function (product) {
                return product.category === 'microwave-ovens' || product.category === 'cooking-ranges';
            }, 1, usedSlugs);

            bestSellerGrid.innerHTML = bestSellers.map(function (product) {
                return homeProductCardTemplate(product, 'home-best-seller-grid');
            }).join('');

            kitchenGrid.innerHTML = kitchenProducts.map(function (product) {
                return homeProductCardTemplate(product, 'home-kitchen-grid');
            }).join('');

            essentialGrid.innerHTML = essentialProducts.map(function (product) {
                return homeMiniProductTemplate(product, 'home-essential-strip');
            }).join('');

            featuredGrid.innerHTML = featuredProducts.map(function (product) {
                return homeProductCardTemplate(product, 'special-products');
            }).join('');

            renderLabelSlider(featuredGrid, products, {
                key: 'home',
                title: 'Shared Product Labels',
                copy: 'If one label is used on multiple products, they now stay in one horizontal slider instead of wrapping.'
            });
            setHomeEssentialGallery(essentialProducts);
            setHomeKitchenFeature(kitchenProducts[0]);
            setHomeSmartFeature(smartProducts[0]);
            applySetBackgrounds(homeRoot);
            return;
        }

        if (!legacyContainer) {
            return;
        }

        legacyContainer.innerHTML = getFeaturedProducts().map(productCardTemplate).join('');
        renderLabelSlider(legacyContainer, products, {
            key: 'legacy-home',
            title: 'Shared Product Labels',
            copy: 'Products with the same custom label stay in one clean slider rail.'
        });
        applySetBackgrounds(legacyContainer);
    }

    function sortProducts(items, sortValue) {
        var sorted = items.slice();

        if (sortValue === 'brand-asc') {
            sorted.sort(function (left, right) {
                return left.brand.localeCompare(right.brand) || left.name.localeCompare(right.name);
            });
        } else if (sortValue === 'name-asc') {
            sorted.sort(function (left, right) {
                return left.name.localeCompare(right.name);
            });
        } else if (sortValue === 'category') {
            sorted.sort(function (left, right) {
                return left.categoryLabel.localeCompare(right.categoryLabel) || left.name.localeCompare(right.name);
            });
        }

        return sorted;
    }

    function renderDetailOptionSummary(product) {
        var optionContainer = document.querySelector('.product__details__option');
        var facts = getProductScanFacts(product);

        if (!optionContainer) {
            return;
        }

        optionContainer.innerHTML = [
            '<div class="product__details__info-chips">',
            facts.map(function (fact) {
                return '<span>' + escapeHtml(fact) + '</span>';
            }).join(''),
            '</div>'
        ].join('');
    }

    function renderQuickView(product) {
        var quickViewHighlights;

        ensureQuickViewStructure();
        setText('quick-view-name', product.name);
        setText('quick-view-brand', product.brand);
        setText('quick-view-category', product.categoryLabel);
        setText('quick-view-summary', product.detail);
        setHtml('quick-view-pricing', [
            '<strong>', formatCurrency(product.price), '</strong>',
            product.isDiscounted ? '<span>Was ' + formatCurrency(product.originalPrice) + '</span>' : '<span>Current catalogue price</span>',
            '<small>Guide, gallery, and compare actions are available from this quick preview.</small>'
        ].join(''));
        setHtml('quick-view-badges', getProductScanFacts(product).map(function (fact) {
            return '<span>' + escapeHtml(fact) + '</span>';
        }).join(''));
        setHref('quick-view-detail-link', buildDetailHref(product));
        setHref('quick-view-compare-link', buildCompareHref(product, null, getProductsByBrand(product.brandSlug)));
        setHref('quick-view-cart-link', buildActionHref('add_to_cart', product.slug));
        setHref('quick-view-favorite-link', buildActionHref('toggle_favorite', product.slug));
        setAttribute('quick-view-image', 'src', product.image);
        setAttribute('quick-view-image', 'alt', product.name);

        quickViewHighlights = document.getElementById('quick-view-highlights');
        if (quickViewHighlights) {
            quickViewHighlights.innerHTML = buildProductHighlights(product).map(function (highlight) {
                return '<li>' + escapeHtml(highlight) + '</li>';
            }).join('');
        }
    }

    function bindQuickView() {
        Array.prototype.forEach.call(document.querySelectorAll('.js-quick-view'), function (link) {
            link.addEventListener('click', function (event) {
                event.preventDefault();
                renderQuickView(getProductBySlug(link.getAttribute('data-product')));
                if (window.jQuery) {
                    window.jQuery('#quickViewModal').modal('show');
                }
            });
        });
    }

    function ensureCatalogGridShell(container) {
        var parent = container ? container.parentNode : null;
        var shell = parent && parent.classList && parent.classList.contains('catalog-product-grid-shell') ? parent : null;

        if (!container || !parent) {
            return null;
        }

        if (shell) {
            return shell;
        }

        shell = document.createElement('div');
        shell.className = 'catalog-product-grid-shell';
        parent.insertBefore(shell, container);
        shell.appendChild(container);

        return shell;
    }

    function setCatalogGridScrollState(container, shouldScroll, itemCount) {
        var shell = ensureCatalogGridShell(container);

        if (!shell) {
            return;
        }

        shell.classList.toggle('catalog-product-grid-shell--scrollable', Boolean(shouldScroll));
        shell.setAttribute('data-product-count', String(itemCount || 0));

        if (!shouldScroll) {
            shell.scrollTop = 0;
        }
    }

    function renderShopPage() {
        var container = document.getElementById('catalog-product-grid');
        var brandList = document.querySelector('.shop__sidebar__brand ul');
        var currentBrandPageSlug = getCurrentBrandPageSlug();
        var requestedBrand = getBrandBySlug(currentBrandPageSlug || queryParam('brand'));
        var requestedCategory = queryParam('category');
        var searchTerm = queryParam('search') || '';
        var viewParam = queryParam('view') || 'products';
        var isBrandContext = Boolean(requestedBrand) && (Boolean(currentBrandPageSlug) || viewParam === 'brand');
        var sortSelect = document.getElementById('product-sort-select');
        var resultsCopy = document.getElementById('shop-results-copy');
        var sortValue = queryParam('sort') || 'featured';
        var listingProducts = products;
        var renderProducts = products;
        var categoryCountBase = products;
        var listingTitle = viewParam === 'categories' ? 'Categories' : 'Product Details';
        var activeCategory = categoryMeta[requestedCategory] ? categoryMeta[requestedCategory] : null;
        var visibleProductCount = 0;
        var shouldUseGridScroll = false;
        if (!container) {
            return;
        }

        if (brandList) {
            if (isBrandContext && requestedBrand) {
                brandList.innerHTML = '<li><a href="#" class="js-mix-filter active" data-filter=".' + requestedBrand.slug + '">' + escapeHtml(requestedBrand.name) + '</a></li>';
            } else {
                brandList.innerHTML = brandGroups.map(function (brandGroup) {
                    return '<li><a href="#" class="js-mix-filter" data-filter=".' + brandGroup.slug + '">' + escapeHtml(brandGroup.name) + '</a></li>';
                }).join('');
            }
        }

        if (isBrandContext && requestedBrand) {
            listingProducts = getProductsByBrand(requestedBrand.slug);
            renderProducts = listingProducts;
            categoryCountBase = listingProducts;
            listingTitle = requestedBrand.name + ' Appliances';
            container.setAttribute('data-initial-filter', activeCategory ? '.' + requestedCategory : '*');
            setShopPageState(listingTitle, activeCategory ? '.' + requestedCategory : '*', activeCategory ? '.' + requestedCategory : '*');
            document.title = 'La Imperial | ' + requestedBrand.name + ' Appliances';
        } else if (categoryMeta[requestedCategory]) {
            container.setAttribute('data-initial-filter', '.' + requestedCategory);
            listingProducts = getProductsByCategory(requestedCategory);
            listingTitle = categoryMeta[requestedCategory].label;
            setShopPageState(listingTitle, '.' + requestedCategory, '.' + requestedCategory);
            document.title = 'La Imperial | ' + listingTitle;
        } else {
            container.setAttribute('data-initial-filter', '*');
            setShopPageState(listingTitle, '*', '*');
            document.title = 'La Imperial | ' + listingTitle;
        }

        if (searchTerm) {
            renderProducts = listingProducts.filter(function (product) {
                var haystack = (product.name + ' ' + product.summary + ' ' + product.brand + ' ' + product.categoryLabel).toLowerCase();
                return haystack.indexOf(searchTerm.toLowerCase()) > -1;
            });
        }

        if (resultsCopy) {
            if (searchTerm) {
                resultsCopy.textContent = 'Showing ' + renderProducts.length + ' results for "' + searchTerm + '"';
            } else if (isBrandContext && requestedBrand && activeCategory) {
                resultsCopy.textContent = 'Showing ' + listingProducts.filter(function (product) {
                    return product.category === requestedCategory;
                }).length + ' ' + requestedBrand.name + ' products in ' + activeCategory.label;
            } else if (isBrandContext && requestedBrand) {
                resultsCopy.textContent = 'Showing ' + listingProducts.length + ' products for ' + requestedBrand.name;
            } else if (categoryMeta[requestedCategory]) {
                resultsCopy.textContent = 'Showing ' + listingProducts.length + ' products in ' + categoryMeta[requestedCategory].label;
            } else if (viewParam === 'categories') {
                resultsCopy.textContent = 'Browse all ' + listingProducts.length + ' products category wise';
            } else {
                resultsCopy.textContent = 'Showing ' + listingProducts.length + ' products with quick view and sort';
            }
        }

        if (sortSelect) {
            sortSelect.value = sortValue;
            refreshNiceSelect('#product-sort-select');
        }

        if (sortSelect && !sortSelect.dataset.bound) {
            sortSelect.dataset.bound = 'true';
            sortSelect.addEventListener('change', function () {
                var params = new URLSearchParams(window.location.search);
                params.set('sort', sortSelect.value);
                window.location.search = params.toString();
            });
        }

        container.innerHTML = sortProducts(renderProducts, sortValue).map(productCardTemplate).join('');
        renderLabelSlider(container, renderProducts.length ? renderProducts : listingProducts, {
            key: (requestedBrand ? requestedBrand.slug : (requestedCategory || viewParam || 'shop')),
            title: isBrandContext && requestedBrand ? (requestedBrand.name + ' Label Slider') : 'Label Slider',
            copy: 'Products sharing one custom label stay in a no-wrap slider so the page remains cleaner.'
        });
        visibleProductCount = activeCategory ? renderProducts.filter(function (product) {
            return product.category === requestedCategory;
        }).length : renderProducts.length;
        shouldUseGridScroll = (isBrandContext || viewParam === 'categories' || Boolean(activeCategory)) && visibleProductCount > 6;
        setCatalogGridScrollState(container, shouldUseGridScroll, visibleProductCount);
        applySetBackgrounds(container);
        bindQuickView();
        renderListingShowcase({
            view: isBrandContext ? 'brand' : viewParam,
            brand: isBrandContext ? requestedBrand : null,
            category: isBrandContext ? activeCategory : (categoryMeta[requestedCategory] || null),
            comparePool: isBrandContext && requestedBrand ? getProductsByBrand(requestedBrand.slug) : listingProducts,
            products: (isBrandContext && activeCategory) ? listingProducts.filter(function (product) {
                return product.category === requestedCategory;
            }) : (listingProducts.length ? listingProducts : products)
        });

        Array.prototype.forEach.call(document.querySelectorAll('.shop__sidebar__categories .js-mix-filter'), function (link) {
            var filterValue = link.getAttribute('data-filter');
            if (filterValue === '*') {
                link.textContent = 'All Appliances (' + categoryCountBase.length + ')';
                if (typeof console !== 'undefined') console.log('All Appliances:', categoryCountBase.length);
                return;
            }

            var categorySlug = filterValue.replace('.', '');
            if (categoryMeta[categorySlug]) {
                var matches = categoryCountBase.filter(function (product) {
                    return product.category === categorySlug;
                });
                link.textContent = categoryMeta[categorySlug].label + ' (' + matches.length + ')';
                if (typeof console !== 'undefined') console.log('Category ' + categorySlug + ':', matches.length, 'products');
            }
        });

        Array.prototype.forEach.call(document.querySelectorAll('.shop__sidebar__brand .js-mix-filter'), function (link) {
            var brandSlug = link.getAttribute('data-filter').replace('.', '');
            var brand = getBrandBySlug(brandSlug);

            if (brand) {
                link.textContent = brand.name + ' (' + getProductsByBrand(brandSlug).length + ')';
                if (isBrandContext && requestedBrand && brandSlug === requestedBrand.slug) {
                    link.classList.add('active');
                }
            }
        });
    }

    function renderDetailsPage() {
        if (!document.getElementById('detail-product-name')) {
            return;
        }

        var product = getProductBySlug(queryParam('product'));
        var brandProducts = getProductsByBrand(product.brandSlug);
        var relatedProducts = getRelatedProducts(product, 4);
        var gallery = getProductGallery(product);
        var backParam = queryParam('back');
        var backHref = toPhpHref(backParam || './shop.html?view=products');
        var backLabel = 'Back To Product Details';

        if (backHref.indexOf('view=categories') > -1) {
            backLabel = 'Back To Categories';
        } else if (backHref.indexOf('index.html') > -1 || backHref.indexOf('index.php') > -1) {
            backLabel = 'Back To Home';
        } else if (getBrandPageSlugFromHref(backHref)) {
            backLabel = 'Back To ' + getBrandBySlug(getBrandPageSlugFromHref(backHref)).name;
        }

        setText('detail-product-name', product.name);
        setHtml('detail-category-brand', escapeHtml(product.categoryLabel) + ' <span>' + escapeHtml(product.brand) + ' Collection</span>');
        setText('detail-summary', product.detail);
        setText('detail-breadcrumb-name', product.name);
        setHref('detail-document-link', product.document);
        setHref('detail-compare-link', buildCompareHref(product, relatedProducts[0], brandProducts));
        setHref('detail-back-link', backHref);
        setHtml('detail-back-link', '<i class="fa fa-arrow-left"></i> ' + escapeHtml(backLabel));
        setImage('detail-main-image-1', gallery[0], product.name);
        renderDetailGallery(gallery, product.name);
        renderDetailActions(product, relatedProducts[0], brandProducts, backHref);
        renderDetailOptionSummary(product);
        setHtml('detail-reference-list', [
            '<li><span>Document:</span> ', escapeHtml(product.document.replace('./docs/', '')), '</li>',
            '<li><span>Category:</span> ', escapeHtml(product.categoryLabel), '</li>',
            '<li><span>Price:</span> ', formatCurrency(product.price), '</li>',
            '<li><span>Views:</span> <strong data-detail-view-count>', Number(product.viewCount || 0), '</strong></li>',
            '<li><span>Status:</span> ', product.isDiscounted ? (product.discountPercent + '% Off') : 'Catalogue Ready', '</li>'
        ].join(''));
        setHtml('detail-summary-content', [
            '<h5>What This Product Offers</h5>',
            '<p class="detail-tab-lead">', escapeHtml(product.summary), '</p>',
            '<div class="detail-info-grid">',
            '<div class="detail-info-card"><span>Brand</span><strong>', escapeHtml(product.brand), '</strong></div>',
            '<div class="detail-info-card"><span>Category</span><strong>', escapeHtml(product.categoryLabel), '</strong></div>',
            '<div class="detail-info-card"><span>Price</span><strong>', formatCurrency(product.price), '</strong></div>',
            '<div class="detail-info-card"><span>Views</span><strong data-detail-view-count>', Number(product.viewCount || 0), '</strong></div>',
            '<div class="detail-info-card"><span>Guide</span><strong>', escapeHtml(product.document.replace('./docs/', '')), '</strong></div>',
            '</div>',
            '<ul class="detail-bullet-list">',
            buildProductHighlights(product).map(function (highlight) {
                return '<li>' + escapeHtml(highlight) + '</li>';
            }).join(''),
            '</ul>'
        ].join(''));
        setHtml('detail-document-links', linkListTemplate(brandProducts));
        setHtml('detail-compare-note', [
            '<h5>How To Compare</h5>',
            '<p class="detail-tab-lead">Open compare with this product already selected, then choose one more different product to see a direct side-by-side report.</p>',
            '<ul class="detail-bullet-list">',
            '<li>Start with another ', escapeHtml(product.brand), ' model if you want a same-brand shortlist.</li>',
            '<li>Switch to a different brand when you want a wider value and feature comparison.</li>',
            '<li>Use the feature guide downloads first, then move to the compare page for a faster final decision.</li>',
            '</ul>'
        ].join(''));

        renderReviewSection(product);
        updateDetailViewCount(product.viewCount || 0);
        recordProductView(product);

        var relatedContainer = document.getElementById('related-product-grid');
        if (relatedContainer) {
            relatedContainer.innerHTML = relatedProducts.map(function (item) {
                return productCardTemplate(item, {
                    columnClass: 'col-lg-3 col-md-6 col-sm-6',
                    detailHref: buildDetailHref(item, backHref),
                    compareHref: buildCompareHref(product, item, brandProducts)
                });
            }).join('');
            applySetBackgrounds(relatedContainer);
            bindQuickView();
        }

        document.title = 'La Imperial | ' + product.name;
    }

    function updateDetailViewCount(count) {
        Array.prototype.forEach.call(document.querySelectorAll('[data-detail-view-count]'), function (node) {
            node.textContent = String(Math.max(0, Number(count || 0)));
        });
    }

    function recordProductView(product) {
        var actionUrl;

        if (!product || !product.slug || !window.fetch) {
            return;
        }

        if (product.__viewRecorded) {
            updateDetailViewCount(product.viewCount || 0);
            return;
        }

        product.__viewRecorded = true;
        actionUrl = buildActionHref('record_view', product.slug, cleanPathname(window.location.pathname) + window.location.search) + '&ajax=1';

        window.fetch(actionUrl, {
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(function (response) {
            return response.json();
        }).then(function (payload) {
            var nextCount;

            if (!payload || !payload.success) {
                return;
            }

            nextCount = Number((payload.state && payload.state.viewCount) || (payload.product && payload.product.viewCount) || product.viewCount || 0);
            product.viewCount = nextCount;
            updateDetailViewCount(nextCount);
        }).catch(function () {});
    }

    function renderDetailGallery(gallery, altText) {
        var mainImage = document.getElementById('detail-main-image-1');
        var container = document.querySelector('.detail-gallery');
        var wrapper;

        if (!mainImage || !gallery || !gallery.length) {
            return;
        }

        if (!container) {
            wrapper = document.createElement('div');
            wrapper.className = 'detail-gallery';
            mainImage.parentNode.appendChild(wrapper);
            container = wrapper;
        }

        container.innerHTML = gallery.map(function (src, index) {
            return [
                '<button type="button" class="detail-gallery__thumb', index === 0 ? ' is-active' : '', '" data-index="', index, '">',
                '<img src="', src, '" alt="', escapeHtml(altText), ' view ', index + 1, '">',
                '</button>'
            ].join('');
        }).join('');

        Array.prototype.forEach.call(container.querySelectorAll('.detail-gallery__thumb'), function (thumb) {
            thumb.addEventListener('click', function () {
                var index = parseInt(thumb.getAttribute('data-index'), 10);
                var nextSrc = gallery[index] || gallery[0];
                setImage('detail-main-image-1', nextSrc, altText);
                Array.prototype.forEach.call(container.querySelectorAll('.detail-gallery__thumb'), function (node) {
                    node.classList.remove('is-active');
                });
                thumb.classList.add('is-active');
            });
        });
    }

    function renderDetailActions(product, compareTarget, brandProducts, backHref) {
        var cartOption = document.querySelector('.product__details__cart__option');
        var container = document.querySelector('.detail-extra-actions');

        if (!cartOption) {
            return;
        }

        if (!container) {
            container = document.createElement('div');
            container.className = 'detail-extra-actions';
            cartOption.insertAdjacentElement('afterend', container);
        }

        container.innerHTML = [
            '<a class="quick-action--cart" href="', buildActionHref('add_to_cart', product.slug), '">Add to Cart</a>',
            '<a class="quick-action--favorite" href="', buildActionHref('toggle_favorite', product.slug), '">Favorite</a>',
            '<a class="quick-action--detail" href="', buildCompareHref(product, compareTarget, brandProducts), '">Compare</a>'
        ].join('');
    }

    function getReviewSummaryBySlug(slug) {
        return (reviewSummary && reviewSummary[slug]) ? reviewSummary[slug] : { count: 0, average: 0 };
    }

    function getProductReviewsBySlug(slug) {
        return (reviewMap && reviewMap[slug]) ? reviewMap[slug] : [];
    }

    function renderReviewStars(rating) {
        var rounded = Math.round(rating || 0);
        var stars = [];
        var i;
        for (i = 1; i <= 5; i += 1) {
            stars.push('<i class="fa ' + (i <= rounded ? 'fa-star' : 'fa-star-o') + '" aria-hidden="true"></i>');
        }
        return stars.join('');
    }

    function formatReviewDate(value) {
        if (!value) {
            return '';
        }
        var parsed = new Date(value);
        if (Number.isNaN(parsed.getTime())) {
            return value;
        }
        return parsed.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    }

    function renderReviewSection(product) {
        var tabList = document.querySelector('.product__details__tab .nav-tabs');
        var tabContent = document.querySelector('.product__details__tab .tab-content');
        var reviewPane = document.getElementById('tabs-reviews');
        var reviewContent;

        if (!tabList || !tabContent) {
            return;
        }

        if (!reviewPane) {
            tabList.insertAdjacentHTML('beforeend', '<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tabs-reviews" role="tab">Reviews</a></li>');
            tabContent.insertAdjacentHTML(
                'beforeend',
                '<div class="tab-pane" id="tabs-reviews" role="tabpanel">' +
                    '<div class="product__details__tab__content">' +
                        '<div class="product__details__tab__content__item" id="detail-review-content"></div>' +
                    '</div>' +
                '</div>'
            );
        }

        reviewContent = document.getElementById('detail-review-content');
        if (!reviewContent) {
            return;
        }

        var summary = getReviewSummaryBySlug(product.slug);
        var reviews = getProductReviewsBySlug(product.slug);
        var average = summary.count ? summary.average : 0;
        var reviewAction = getRoute('review', './review-action.php');
        var redirect = cleanPathname(window.location.pathname) + window.location.search;
        var loggedIn = storeSession && storeSession.user && storeSession.user.id;
        var loginHref = getRoute('login', './login.php') + '?redirect=' + encodeURIComponent(redirect);

        var reviewListHtml = reviews.length ? reviews.map(function (item) {
            var avatar = item.userAvatar ? item.userAvatar : 'img/logo/logo.png';
            return [
                '<div class="detail-review-card">',
                '<div class="detail-review-card__avatar"><img src="', avatar, '" alt="', escapeHtml(item.userName || 'User'), '"></div>',
                '<div class="detail-review-card__body">',
                '<div class="detail-review-card__meta">',
                '<strong>', escapeHtml(item.userName || 'User'), '</strong>',
                '<span>', formatReviewDate(item.createdAt), '</span>',
                '</div>',
                '<div class="detail-review-card__stars">', renderReviewStars(item.rating), '</div>',
                '<p>', escapeHtml(item.comment || ''), '</p>',
                '</div>',
                '</div>'
            ].join('');
        }).join('') : '<p class="detail-review-empty">No reviews yet. Be the first to review this product.</p>';

        var formHtml = loggedIn ? [
            '<form class="detail-review-form" action="', reviewAction, '" method="post">',
            '<input type="hidden" name="product" value="', escapeHtml(product.slug), '">',
            '<input type="hidden" name="redirect" value="', escapeHtml(redirect), '">',
            '<div class="detail-review-form__row">',
            '<label>Rating</label>',
            '<select name="rating" required>',
            '<option value="">Select</option>',
            '<option value="5">5 - Excellent</option>',
            '<option value="4">4 - Very Good</option>',
            '<option value="3">3 - Good</option>',
            '<option value="2">2 - Fair</option>',
            '<option value="1">1 - Poor</option>',
            '</select>',
            '</div>',
            '<div class="detail-review-form__row">',
            '<label>Review</label>',
            '<textarea name="comment" rows="4" placeholder="Share your experience" required></textarea>',
            '</div>',
            '<button type="submit" class="quick-action--cart">Submit Review</button>',
            '</form>'
        ].join('') : [
            '<div class="detail-review-login">',
            '<p>Please login to add a review for this product.</p>',
            '<a class="quick-action--detail" href="', loginHref, '">Login to Review</a>',
            '</div>'
        ].join('');

        reviewContent.innerHTML = [
            '<div class="detail-review-summary">',
            '<div class="detail-review-summary__score">',
            '<strong>', average.toFixed(1), '</strong>',
            '<span>', summary.count, ' reviews</span>',
            '</div>',
            '<div class="detail-review-summary__stars">', renderReviewStars(average), '</div>',
            '</div>',
            '<div class="detail-review-grid">',
            '<div class="detail-review-list">', reviewListHtml, '</div>',
            '<div class="detail-review-form-wrap">', formHtml, '</div>',
            '</div>'
        ].join('');
    }

    function getComparisonProducts() {
        var leftSlug = queryParam('left');
        var rightSlug = queryParam('right');
        var focus = queryParam('focus');
        var focusProducts = focus ? getProductsByBrand(focus) : [];
        var leftProduct = leftSlug ? getProductBySlug(leftSlug) : (focusProducts[0] || getFeaturedProducts()[0]);
        var rightProduct = rightSlug ? getProductBySlug(rightSlug) : (focusProducts[1] || getFeaturedProducts()[1]);

        if (leftProduct.slug === rightProduct.slug) {
            rightProduct = products.filter(function (product) {
                return product.slug !== leftProduct.slug;
            })[0];
        }

        return {
            left: leftProduct,
            right: rightProduct
        };
    }

    function featureStrengthScore(product) {
        var text = (product.name + ' ' + product.summary).toLowerCase();
        var score = 52;
        var keywords = [
            ['inverter', 8],
            ['smart', 7],
            ['digital', 6],
            ['front load', 6],
            ['front', 4],
            ['dc', 5],
            ['dual', 5],
            ['turbo', 5],
            ['twin', 4],
            ['combo', 5],
            ['split', 4],
            ['bottom load', 5],
            ['hot and cold', 5],
            ['family', 4],
            ['large', 4],
            ['quick', 3],
            ['compact', 2],
            ['grill', 4]
        ];

        keywords.forEach(function (keywordMeta) {
            if (text.indexOf(keywordMeta[0]) > -1) {
                score += keywordMeta[1];
            }
        });

        if (product.isTrending) {
            score += 6;
        }

        if (product.isDiscounted) {
            score += Math.max(2, Math.round(product.discountPercent / 3));
        }

        if (product.document) {
            score += 4;
        }

        return score;
    }

    function valueScore(product) {
        var priceBonus = Math.max(10, Math.round((190000 - product.price) / 3500));
        return priceBonus + product.discountPercent + (product.isTrending ? 8 : 0) + Math.round(featureStrengthScore(product) / 12);
    }

    function comparisonScore(product) {
        return featureStrengthScore(product) + valueScore(product);
    }

    function compareMetricText(currentValue, otherValue, lowerIsBetter) {
        var difference = Math.abs(currentValue - otherValue);

        if (difference === 0) {
            return 'Same';
        }

        if (lowerIsBetter) {
            return currentValue < otherValue ? formatCurrency(difference) + ' lower' : formatCurrency(difference) + ' higher';
        }

        return currentValue > otherValue ? difference + ' points higher' : difference + ' points lower';
    }

    function compareWinnerLabel(leftValue, rightValue, leftProduct, rightProduct, lowerIsBetter) {
        if (leftValue === rightValue) {
            return 'Balanced';
        }

        if (lowerIsBetter) {
            return leftValue < rightValue ? leftProduct.name : rightProduct.name;
        }

        return leftValue > rightValue ? leftProduct.name : rightProduct.name;
    }

    function getCompareReport(leftProduct, rightProduct) {
        var leftFeatureScore = featureStrengthScore(leftProduct);
        var rightFeatureScore = featureStrengthScore(rightProduct);
        var leftValueScore = valueScore(leftProduct);
        var rightValueScore = valueScore(rightProduct);
        var leftOverallScore = comparisonScore(leftProduct);
        var rightOverallScore = comparisonScore(rightProduct);
        var winner = leftOverallScore >= rightOverallScore ? leftProduct : rightProduct;
        var loser = winner.slug === leftProduct.slug ? rightProduct : leftProduct;
        var lowerPriceWinner = compareWinnerLabel(leftProduct.price, rightProduct.price, leftProduct, rightProduct, true);
        var featureWinner = compareWinnerLabel(leftFeatureScore, rightFeatureScore, leftProduct, rightProduct, false);
        var valueWinner = compareWinnerLabel(leftValueScore, rightValueScore, leftProduct, rightProduct, false);
        var savings = Math.abs(leftProduct.price - rightProduct.price);

        return {
            title: 'Better Pick Right Now: ' + winner.name,
            summary: winner.name + ' is the better overall choice right now because it gives a stronger balance of price, feature strength, and buying value than ' + loser.name + '.',
            winner: winner,
            loser: loser,
            insights: [
                {
                    label: 'Better Overall',
                    value: winner.name,
                    meta: 'Overall score ' + comparisonScore(winner) + ' vs ' + comparisonScore(loser)
                },
                {
                    label: 'Lower Price',
                    value: lowerPriceWinner,
                    meta: savings ? 'Price gap: ' + formatCurrency(savings) : 'Both products are priced the same'
                },
                {
                    label: 'Feature Strength',
                    value: featureWinner,
                    meta: leftFeatureScore + ' vs ' + rightFeatureScore + ' feature points'
                },
                {
                    label: 'Best Value',
                    value: valueWinner,
                    meta: leftValueScore + ' vs ' + rightValueScore + ' value points'
                }
            ],
            points: [
                lowerPriceWinner === 'Balanced' ? 'Both products have the same current price, so the final choice depends more on features and value.' : lowerPriceWinner + ' is cheaper right now by ' + formatCurrency(savings) + '.',
                featureWinner === 'Balanced' ? 'Both products offer a balanced feature set in this comparison.' : featureWinner + ' gives the stronger feature package in this matchup.',
                valueWinner === 'Balanced' ? 'Overall value is balanced between both products after price and discount review.' : valueWinner + ' gives better value when price, discount, and product strength are reviewed together.',
                'Best next step: open ' + winner.name + ' in Product Details and review it against your final budget and room needs.'
            ]
        };
    }

    function buildCompareInsightCards(report) {
        return report.insights.map(function (insight) {
            return [
                '<div class="compare-insight-card">',
                '<span>', escapeHtml(insight.label), '</span>',
                '<strong>', escapeHtml(insight.value), '</strong>',
                '<p>', escapeHtml(insight.meta), '</p>',
                '</div>'
            ].join('');
        }).join('');
    }

    function compareWinnerFlags(leftValue, rightValue, lowerIsBetter) {
        if (leftValue === rightValue) {
            return {
                left: false,
                right: false
            };
        }

        if (lowerIsBetter) {
            return {
                left: leftValue < rightValue,
                right: rightValue < leftValue
            };
        }

        return {
            left: leftValue > rightValue,
            right: rightValue > leftValue
        };
    }

    function compareTableRow(label, leftValue, rightValue, options) {
        var leftClass = '';
        var rightClass = '';
        var winnerFlags;

        if (options && options.compare) {
            winnerFlags = compareWinnerFlags(options.compare.left, options.compare.right, options.compare.lowerIsBetter);
            leftClass = winnerFlags.left ? ' compare-value--winner' : '';
            rightClass = winnerFlags.right ? ' compare-value--winner' : '';
        }

        return [
            '<tr>',
            '<td>', escapeHtml(label), '</td>',
            '<td class="compare-value', leftClass, '">', options && options.leftHtml ? leftValue : escapeHtml(leftValue), '</td>',
            '<td class="compare-value', rightClass, '">', options && options.rightHtml ? rightValue : escapeHtml(rightValue), '</td>',
            '</tr>'
        ].join('');
    }

    function compareFieldRows(leftProduct, rightProduct) {
        var leftFeatureScore = featureStrengthScore(leftProduct);
        var rightFeatureScore = featureStrengthScore(rightProduct);
        var leftValueScore = valueScore(leftProduct);
        var rightValueScore = valueScore(rightProduct);

        return [
            compareTableRow('Brand', leftProduct.brand, rightProduct.brand),
            compareTableRow('Category Type', leftProduct.categoryLabel, rightProduct.categoryLabel),
            compareTableRow('Price Today', formatCurrency(leftProduct.price), formatCurrency(rightProduct.price), {
                compare: {
                    left: leftProduct.price,
                    right: rightProduct.price,
                    lowerIsBetter: true
                }
            }),
            compareTableRow('Was Price', formatCurrency(leftProduct.originalPrice), formatCurrency(rightProduct.originalPrice)),
            compareTableRow('Current Deal', leftProduct.isDiscounted ? leftProduct.discountPercent + '% Off' : 'Regular Price', rightProduct.isDiscounted ? rightProduct.discountPercent + '% Off' : 'Regular Price', {
                compare: {
                    left: leftProduct.discountPercent,
                    right: rightProduct.discountPercent,
                    lowerIsBetter: false
                }
            }),
            compareTableRow('Feature Strength', leftFeatureScore + ' pts', rightFeatureScore + ' pts', {
                compare: {
                    left: leftFeatureScore,
                    right: rightFeatureScore,
                    lowerIsBetter: false
                }
            }),
            compareTableRow('Value For Money', leftValueScore + ' pts', rightValueScore + ' pts', {
                compare: {
                    left: leftValueScore,
                    right: rightValueScore,
                    lowerIsBetter: false
                }
            }),
            compareTableRow('Buyer Interest', leftProduct.isTrending ? 'Trending Now' : 'Standard Pick', rightProduct.isTrending ? 'Trending Now' : 'Standard Pick', {
                compare: {
                    left: leftProduct.isTrending ? 1 : 0,
                    right: rightProduct.isTrending ? 1 : 0,
                    lowerIsBetter: false
                }
            }),
            compareTableRow('Price Difference', compareMetricText(leftProduct.price, rightProduct.price, true), compareMetricText(rightProduct.price, leftProduct.price, true)),
            compareTableRow('Why It Stands Out', leftProduct.summary, rightProduct.summary),
            compareTableRow('Product Guide', '<a class="compare-guide-link" href="' + leftProduct.document + '" download>Download Guide</a>', '<a class="compare-guide-link" href="' + rightProduct.document + '" download>Download Guide</a>', {
                leftHtml: true,
                rightHtml: true
            })
        ].join('');
    }

    function compareBackHref(leftProduct, rightProduct) {
        return toPhpHref('./compare.html?left=' + leftProduct.slug + '&right=' + rightProduct.slug);
    }

    function buildComparePreviewCard(product, sideLabel, pairedProduct) {
        var compareHref = compareBackHref(product, pairedProduct);
        var detailHref = buildDetailHref(product, compareHref);
        var dealText = product.isDiscounted ? product.discountPercent + '% Off' : 'Regular Price';
        var flagText = product.isTrending ? 'Trending' : 'Ready To Compare';

        return [
            '<div class="compare-preview-card__top">',
            '<span class="compare-preview-card__eyebrow">', escapeHtml(sideLabel), '</span>',
            '<span class="compare-preview-card__flag">', escapeHtml(flagText), '</span>',
            '</div>',
            '<div class="compare-preview-card__media">',
            '<img src="', product.image, '" alt="', escapeHtml(product.name), '">',
            '</div>',
            '<div class="compare-preview-card__body">',
            '<span class="compare-preview-card__brand">', escapeHtml(product.brand), ' | ', escapeHtml(product.categoryLabel), '</span>',
            '<h5>', escapeHtml(product.name), '</h5>',
            '<p>', escapeHtml(product.summary), '</p>',
            '<div class="compare-preview-card__meta">',
            '<strong>', formatCurrency(product.price), '</strong>',
            '<span>', escapeHtml(dealText), '</span>',
            '</div>',
            '<div class="compare-preview-card__pills">',
            '<span>', escapeHtml(product.categoryLabel), '</span>',
            '<span>', product.isTrending ? 'Hot Pick' : 'Catalog Pick', '</span>',
            '<span>Guide Ready</span>',
            '</div>',
            '<div class="compare-preview-card__actions">',
            '<a href="', detailHref, '">View Details</a>',
            '<a class="compare-guide-link" href="', product.document, '" download>Download Guide</a>',
            '</div>',
            '</div>'
        ].join('');
    }

    function renderComparePreviewPanels(leftProduct, rightProduct) {
        setHtml('compare-preview-left', buildComparePreviewCard(leftProduct, 'First Pick', rightProduct));
        setHtml('compare-preview-right', buildComparePreviewCard(rightProduct, 'Second Pick', leftProduct));
    }

    function renderCompareWinnerBanner(report, leftProduct, rightProduct) {
        var banner = document.getElementById('compare-winner-banner');
        var detailLink = document.getElementById('compare-winner-detail-link');
        var winner;

        if (!banner) {
            return;
        }

        if (!report) {
            banner.classList.add('compare-winner-banner--empty');
            setText('compare-winner-kicker', 'Comparison Ready');
            setText('compare-winner-title', 'Choose two products to reveal the smarter buy.');
            setText('compare-winner-summary', leftProduct.name + ' and ' + rightProduct.name + ' are ready. Click Compare Products and this page will clearly show which model is cheaper, which one is stronger, and which one is the better final buy.');
            if (detailLink) {
                detailLink.textContent = 'Browse More Products';
                detailLink.setAttribute('href', toPhpHref('./shop.html?view=products'));
            }
            return;
        }

        winner = report.winner;
        banner.classList.remove('compare-winner-banner--empty');
        setText('compare-winner-kicker', 'Winner Right Now');
        setText('compare-winner-title', report.title);
        setText('compare-winner-summary', report.summary);
        if (detailLink) {
            detailLink.textContent = 'Open ' + winner.brand + ' Details';
            detailLink.setAttribute('href', buildDetailHref(winner, compareBackHref(leftProduct, rightProduct)));
        }
    }

    function getCompareStarterPairs() {
        return [
            {
                label: 'Cooling Face-Off',
                left: 'haier-frostcool-15-ac',
                right: 'dawlance-coolmax-18-ac'
            },
            {
                label: 'Smart Washer Battle',
                left: 'samsung-ecobubble-9kg',
                right: 'lg-smartwash-8kg'
            },
            {
                label: 'Kitchen Quick Pick',
                left: 'dawlance-chefmaster-30',
                right: 'orient-majesty-30'
            },
            {
                label: 'Family Freezer Match',
                left: 'pel-familyfresh-500',
                right: 'haier-smartstore-520'
            },
            {
                label: 'Premium AC Choice',
                left: 'lg-dualcool-2-ac',
                right: 'samsung-maxcool-2-ac'
            }
        ];
    }

    function renderCompareMatchups() {
        var container = document.getElementById('compare-matchups');

        if (!container) {
            return;
        }

        container.innerHTML = getCompareStarterPairs().map(function (pair) {
            return '<button type="button" class="compare-matchup-chip" data-left="' + pair.left + '" data-right="' + pair.right + '">' + escapeHtml(pair.label) + '</button>';
        }).join('');
    }

    function bindCompareSelectors(leftProduct, rightProduct) {
        var leftSelect = document.getElementById('compare-product-left');
        var rightSelect = document.getElementById('compare-product-right');
        var allOptions = products.map(function (product) {
            return '<option value="' + product.slug + '" data-image="' + product.image + '" title="' + escapeHtml(product.brand + ' - ' + product.name) + '">' + escapeHtml(product.brand + ' - ' + product.name) + '</option>';
        }).join('');

        if (!leftSelect || !rightSelect) {
            return;
        }

        leftSelect.innerHTML = allOptions;
        rightSelect.innerHTML = allOptions;
        leftSelect.value = leftProduct.slug;
        rightSelect.value = rightProduct.slug;
        refreshNiceSelect('#compare-product-left');
        refreshNiceSelect('#compare-product-right');
        scheduleCompareSelectMediaSync('compare-product-left');
        scheduleCompareSelectMediaSync('compare-product-right');

        function syncSelectionPreview() {
            var selectedLeft = getProductBySlug(leftSelect.value);
            var selectedRight = getProductBySlug(rightSelect.value);

            setText('compare-header-left', selectedLeft.name);
            setText('compare-header-right', selectedRight.name);
            renderComparePreviewPanels(selectedLeft, selectedRight);
            renderEmptyCompareState(selectedLeft, selectedRight);
            scheduleCompareSelectMediaSync('compare-product-left');
            scheduleCompareSelectMediaSync('compare-product-right');
        }

        if (!leftSelect.dataset.bound) {
            leftSelect.dataset.bound = 'true';
            leftSelect.addEventListener('change', syncSelectionPreview);
        }

        if (!rightSelect.dataset.bound) {
            rightSelect.dataset.bound = 'true';
            rightSelect.addEventListener('change', syncSelectionPreview);
        }

        syncSelectionPreview();

        return {
            leftSelect: leftSelect,
            rightSelect: rightSelect
        };
    }

    function updateCompareQuickGuide(leftProduct, rightProduct) {
        var uniqueBrands = {};
        var uniqueCategories = {};

        uniqueBrands[leftProduct.brandSlug] = true;
        uniqueBrands[rightProduct.brandSlug] = true;
        uniqueCategories[leftProduct.category] = true;
        uniqueCategories[rightProduct.category] = true;

        setText('compare-brand-count', String(Object.keys(uniqueBrands).length));
        setText('compare-category-count', String(Object.keys(uniqueCategories).length));
        setText('compare-document-count', '2');
    }

    function updateCompareUrl(leftProduct, rightProduct) {
        var params = new URLSearchParams(window.location.search);
        var nextUrl = toPhpHref('./compare.html');

        params.set('left', leftProduct.slug);
        params.set('right', rightProduct.slug);
        params.delete('focus');

        if (params.toString()) {
            nextUrl += '?' + params.toString();
        }

        if (window.history && typeof window.history.replaceState === 'function') {
            window.history.replaceState({}, document.title, nextUrl);
        }
    }

    function renderEmptyCompareState(leftProduct, rightProduct) {
        var body = document.getElementById('compare-table-body');
        var reportCard = document.getElementById('compare-report-card');
        var compareButton = document.getElementById('compare-products-trigger');

        if (reportCard) {
            reportCard.classList.add('compare-report-card--empty');
        }

        setText('compare-header-left', leftProduct.name);
        setText('compare-header-right', rightProduct.name);
        if (body) {
            body.innerHTML = '<tr class="compare-placeholder-row"><td colspan="3">Select two different products and click Compare Products to generate the full report below.</td></tr>';
        }

        setText('compare-report-title', 'Comparison Report');
        setText('compare-report-summary', 'Choose any two appliances and this report will clearly explain the lower price, stronger features, better value, and smarter overall choice.');
        setHtml('compare-insight-grid', '');
        setHtml('compare-report-points', '');
        setText('compare-action-note', 'You are one click away from a clearer buying decision. Compare these two products to reveal the better option.');
        setText('compare-note-field', 'This compare studio turns two products into one simple answer: which one is cheaper, which one is stronger, and which one is worth choosing.');
        renderCompareWinnerBanner(null, leftProduct, rightProduct);
        if (compareButton) {
            compareButton.textContent = 'Compare These Products';
        }
        updateCompareQuickGuide(leftProduct, rightProduct);
    }

    function renderComparedProducts(leftProduct, rightProduct) {
        var body = document.getElementById('compare-table-body');
        var reportCard = document.getElementById('compare-report-card');
        var report = getCompareReport(leftProduct, rightProduct);

        if (reportCard) {
            reportCard.classList.remove('compare-report-card--empty');
        }

        setText('compare-header-left', leftProduct.name);
        setText('compare-header-right', rightProduct.name);
        if (body) {
            body.innerHTML = compareFieldRows(leftProduct, rightProduct);
        }

        setText('compare-report-title', report.title);
        setText('compare-report-summary', report.summary);
        setHtml('compare-insight-grid', buildCompareInsightCards(report));
        setHtml('compare-report-points', report.points.map(function (point) {
            return '<li>' + escapeHtml(point) + '</li>';
        }).join(''));
        setText('compare-action-note', winnerLine(report));
        setText('compare-note-field', report.points[0] + ' ' + report.points[1]);
        renderComparePreviewPanels(leftProduct, rightProduct);
        renderCompareWinnerBanner(report, leftProduct, rightProduct);
        updateCompareQuickGuide(leftProduct, rightProduct);
        bindCompareReportDownload(report, [leftProduct, rightProduct]);
        updateCompareUrl(leftProduct, rightProduct);
    }

    function winnerLine(report) {
        return report.title + '. Review the full breakdown below.';
    }

    function bindCompareReportDownload(report, compareProducts) {
        var button = document.getElementById('compare-report-download');
        if (!button) {
            return;
        }

        button.onclick = function (event) {
            event.preventDefault();
            var reportWindow = window.open('', '_blank', 'width=900,height=700');
            if (!reportWindow) {
                return;
            }

            reportWindow.document.write([
                '<html><head><title>', escapeHtml(report.title), '</title>',
                '<style>body{font-family:Arial,sans-serif;padding:40px;color:#111;}h1{margin-bottom:12px;}p{line-height:1.7;}ul{padding-left:18px;}table{width:100%;border-collapse:collapse;margin-top:24px;}th,td{border:1px solid #ddd;padding:10px;text-align:left;}th{background:#f5f5f5;}</style>',
                '</head><body>',
                '<h1>', escapeHtml(report.title), '</h1>',
                '<p>', escapeHtml(report.summary), '</p>',
                '<ul>',
                report.points.map(function (point) {
                    return '<li>' + escapeHtml(point) + '</li>';
                }).join(''),
                '</ul>',
                '<table><thead><tr><th>Product</th><th>Brand</th><th>Category</th></tr></thead><tbody>',
                compareProducts.map(function (product) {
                    return '<tr><td>' + escapeHtml(product.name) + '</td><td>' + escapeHtml(product.brand) + '</td><td>' + escapeHtml(product.categoryLabel) + '</td></tr>';
                }).join(''),
                '</tbody></table>',
                '</body></html>'
            ].join(''));
            reportWindow.document.close();
            reportWindow.focus();
            reportWindow.print();
        };
    }

    function renderComparePage() {
        var body = document.getElementById('compare-table-body');
        var compareButton = document.getElementById('compare-products-trigger');
        var swapButton = document.getElementById('compare-swap-trigger');
        var matchupContainer = document.getElementById('compare-matchups');
        var controls = null;
        var pair = null;
        var hasPreset = Boolean(queryParam('left') || queryParam('right') || queryParam('focus'));

        if (!body || !compareButton) {
            return;
        }

        pair = getComparisonProducts();
        renderCompareMatchups();
        controls = bindCompareSelectors(pair.left, pair.right);
        setText('compare-page-title', 'Compare Products');
        setText('compare-page-crumb', 'Compare Products');

        if (hasPreset) {
            renderComparedProducts(pair.left, pair.right);
        } else {
            renderEmptyCompareState(pair.left, pair.right);
        }

        if (!compareButton.dataset.bound) {
            compareButton.dataset.bound = 'true';
            compareButton.addEventListener('click', function () {
                var selectedLeft = getProductBySlug(controls.leftSelect.value);
                var selectedRight = getProductBySlug(controls.rightSelect.value);

                if (selectedLeft.slug === selectedRight.slug) {
                    setText('compare-action-note', 'Please choose two different products before comparing.');
                    setText('compare-note-field', 'The same product cannot be compared against itself. Choose a different second product and try again.');
                    renderCompareWinnerBanner(null, selectedLeft, selectedRight);
                    return;
                }

                renderComparedProducts(selectedLeft, selectedRight);
            });
        }

        if (swapButton && !swapButton.dataset.bound) {
            swapButton.dataset.bound = 'true';
            swapButton.addEventListener('click', function () {
                var currentLeft = controls.leftSelect.value;
                var currentRight = controls.rightSelect.value;
                controls.leftSelect.value = currentRight;
                controls.rightSelect.value = currentLeft;
                refreshNiceSelect('#compare-product-left');
                refreshNiceSelect('#compare-product-right');
                scheduleCompareSelectMediaSync('compare-product-left');
                scheduleCompareSelectMediaSync('compare-product-right');
                renderComparedProducts(getProductBySlug(controls.leftSelect.value), getProductBySlug(controls.rightSelect.value));
            });
        }

        if (matchupContainer && !matchupContainer.dataset.bound) {
            matchupContainer.dataset.bound = 'true';
            matchupContainer.addEventListener('click', function (event) {
                var button = event.target.closest('.compare-matchup-chip');
                var selectedLeft;
                var selectedRight;

                if (!button) {
                    return;
                }

                controls.leftSelect.value = button.getAttribute('data-left');
                controls.rightSelect.value = button.getAttribute('data-right');
                refreshNiceSelect('#compare-product-left');
                refreshNiceSelect('#compare-product-right');
                scheduleCompareSelectMediaSync('compare-product-left');
                scheduleCompareSelectMediaSync('compare-product-right');
                selectedLeft = getProductBySlug(controls.leftSelect.value);
                selectedRight = getProductBySlug(controls.rightSelect.value);
                renderComparedProducts(selectedLeft, selectedRight);
            });
        }
    }

    function renderAll() {
        if (repairBrokenAppLocation()) {
            return;
        }

        injectPremiumThemeStyles();
        applyBrandLogos();
        renderHeaderNav();
        renderBrandHoverLists();
        renderSharedAboutLinks();
        renderBrandSplash();
        renderStoreTotals();
        bindSearchSystem();
        renderHomeBrandHero();
        renderHomeBrandMarquee();
        renderIndexPage();
        renderShopPage();
        renderDetailsPage();
        renderComparePage();
    }

    window.LaImperialStore = {
        products: products,
        brands: brandGroups.map(function (brandGroup) {
            return { slug: brandGroup.slug, name: brandGroup.name };
        }),
        categories: Object.keys(categoryMeta).map(function (key) {
            return { slug: key, label: categoryMeta[key].label };
        })
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', renderAll);
    } else {
        renderAll();
    }
}(window, document));
