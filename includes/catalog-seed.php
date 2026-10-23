<?php
declare(strict_types=1);

function catalog_category_meta(): array
{
    return [
        'air-conditioners' => [
            'name' => 'Air Conditioners',
            'document' => 'docs/haier-frostcool-inverter-ac.doc',
            'note' => 'Air conditioners focus on fast cooling, inverter performance, and all-day room comfort.',
        ],
        'washing-machines' => [
            'name' => 'Washing Machines',
            'document' => 'docs/haier-twintub-washer-dryer.doc',
            'note' => 'Washing machines focus on drum care, family-size wash loads, and easy cycle control.',
        ],
        'washer-dryers' => [
            'name' => 'Washer Dryers',
            'document' => 'docs/haier-twintub-washer-dryer.doc',
            'note' => 'Washer dryers focus on complete wash-and-dry convenience for everyday households.',
        ],
        'refrigerators' => [
            'name' => 'Refrigerators',
            'document' => 'docs/dawlance-elegance-9193-refrigerator.doc',
            'note' => 'Refrigerators focus on food freshness, family storage space, and balanced cooling performance.',
        ],
        'deep-freezers' => [
            'name' => 'Deep Freezers',
            'document' => 'docs/dawlance-elegance-9193-refrigerator.doc',
            'note' => 'Deep freezers focus on frozen storage volume, reliable cooling retention, and long-term food safety.',
        ],
        'microwave-ovens' => [
            'name' => 'Microwave Ovens',
            'document' => 'docs/orient-majesty-microwave.doc',
            'note' => 'Microwave ovens focus on quick reheating, smart presets, and compact kitchen convenience.',
        ],
        'cooking-ranges' => [
            'name' => 'Cooking Ranges',
            'document' => 'docs/orient-majesty-microwave.doc',
            'note' => 'Cooking ranges focus on everyday baking, grilling, and full-meal kitchen support.',
        ],
        'water-dispensers' => [
            'name' => 'Water Dispensers',
            'document' => 'docs/lg-smartwash-dryer-combo.doc',
            'note' => 'Water dispensers focus on instant hot and cold access with practical family-use designs.',
        ],
    ];
}

function catalog_infer_category_slug(string $productName): string
{
    $name = strtolower($productName);

    if (str_contains($name, 'water dispenser')) {
        return 'water-dispensers';
    }
    if (str_contains($name, 'deep freezer') || str_contains($name, 'freezer')) {
        return 'deep-freezers';
    }
    if (str_contains($name, 'microwave')) {
        return 'microwave-ovens';
    }
    if (str_contains($name, 'cooking range') || str_contains($name, 'oven') || str_contains($name, 'range')) {
        return 'cooking-ranges';
    }
    if (str_contains($name, 'refrigerator')) {
        return 'refrigerators';
    }
    if (str_contains($name, 'washer dryer') || str_contains($name, 'dryer combo') || str_contains($name, 'laundry system')) {
        return 'washer-dryers';
    }
    if (str_contains($name, 'washer') || str_contains($name, 'washing machine')) {
        return 'washing-machines';
    }

    return 'air-conditioners';
}

function catalog_seed_data(): array
{
    $categoryMeta = catalog_category_meta();
    $brands = [
        ['slug' => 'haier', 'name' => 'HAIER', 'logo_path' => 'img/brand-logos/haier.png'],
        ['slug' => 'dawlance', 'name' => 'DAWLANCE', 'logo_path' => 'img/brand-logos/Dawlance.png'],
        ['slug' => 'samsung', 'name' => 'SAMSUNG', 'logo_path' => 'img/brand-logos/Samsung.svg'],
        ['slug' => 'lg', 'name' => 'LG', 'logo_path' => 'img/brand-logos/lg.png'],
        ['slug' => 'orient', 'name' => 'ORIENT', 'logo_path' => 'img/brand-logos/orient.png'],
        ['slug' => 'pel', 'name' => 'PEL', 'logo_path' => 'img/brand-logos/pel.svg'],
    ];
    $featuredSlugs = [
        'haier-frostcool-15-ac',
        'dawlance-elegance-9193',
        'samsung-ecobubble-9kg',
        'lg-dualcool-2-ac',
        'orient-majesty-30',
        'pel-familyfresh-500',
        'haier-smartstore-520',
        'pel-smartcool-2-ac',
    ];
    $brandGroups = [
        [
            'slug' => 'haier',
            'name' => 'HAIER',
            'products' => [
                ['slug' => 'haier-frostcool-15-ac', 'name' => 'FrostCool 1.5 Ton Inverter AC', 'summary' => 'Quick chill mode and low-noise airflow for bedrooms and offices.', 'image_path' => 'img/products/haier/FrostCool 1.5 Ton Inverter AC haier.jpg'],
                ['slug' => 'haier-polar-breeze-1-ac', 'name' => 'Polar Breeze 1 Ton Split AC', 'summary' => 'Compact cooling performance built for smaller rooms and focused energy use.', 'image_path' => 'img/products/haier/Polar Breeze 1 Ton Split AC haier.jpg'],
                ['slug' => 'haier-turbochill-2-ac', 'name' => 'Turbo Chill 2 Ton DC Inverter AC', 'summary' => 'High-capacity cooling support for larger lounges and active family spaces.', 'image_path' => 'img/products/haier/Turbo Chill 2 Ton DC Inverter AC haier.jpg'],
                ['slug' => 'haier-twintub-12kg', 'name' => 'TwinTub 12kg Washer Dryer', 'summary' => 'Large-capacity washing with spin dry support for family laundry loads.', 'image_path' => 'img/products/haier/TwinTub 12kg Washer Dryer haier.jpg'],
                ['slug' => 'haier-frontcare-8kg', 'name' => 'FrontCare 8kg Washer', 'summary' => 'Digital wash control with balanced drum care for regular household use.', 'image_path' => 'img/products/haier/FrontCare 8kg Washer haier.jpg'],
                ['slug' => 'haier-aquaflow-dispenser', 'name' => 'AquaFlow Bottom Load Water Dispenser', 'summary' => 'Hot and cold water access with a clean family-friendly dispenser design.', 'image_path' => 'img/products/haier/AquaFlow Bottom Load Water Dispenser haier.jpg'],
                ['slug' => 'haier-freshbox-438', 'name' => 'FreshBox 438 Refrigerator', 'summary' => 'Organized cooling shelves and practical storage zones for daily groceries.', 'image_path' => 'img/products/haier/FreshBox 438 Refrigerator haier.jpg'],
                ['slug' => 'haier-smartstore-520', 'name' => 'SmartStore 520 Deep Freezer', 'summary' => 'Large frozen storage space with stable cooling for long-term food preservation.', 'image_path' => 'img/products/haier/SmartStore 520 Deep Freezer haier.jpg'],
                ['slug' => 'haier-chefwave-28', 'name' => 'ChefWave 28 Microwave Oven', 'summary' => 'Quick heat presets and compact placement for routine kitchen tasks.', 'image_path' => 'img/products/haier/ChefWave 28 Microwave Oven haier.jpg'],
                ['slug' => 'haier-heatserve-32', 'name' => 'HeatServe 32 Cooking Range', 'summary' => 'Everyday baking and stovetop support with simple family kitchen controls.', 'image_path' => 'img/products/haier/HeatServe 32 Cooking Range haier.jpg'],
            ],
        ],
        [
            'slug' => 'dawlance',
            'name' => 'DAWLANCE',
            'products' => [
                ['slug' => 'dawlance-coolmax-18-ac', 'name' => 'CoolMax 1.8 Ton Inverter AC', 'summary' => 'Fast room cooling with strong airflow and stable inverter control.', 'image_path' => 'img/products/dowlance/CoolMax 1.8 Ton Inverter AC dawlance.webp'],
                ['slug' => 'dawlance-airglide-1-ac', 'name' => 'AirGlide 1 Ton Split AC', 'summary' => 'Compact split AC performance designed for bedrooms and office cabins.', 'image_path' => 'img/products/dowlance/AirGlide 1 Ton Split AC dawlance.webp'],
                ['slug' => 'dawlance-powercool-2-ac', 'name' => 'PowerCool 2 Ton Inverter AC', 'summary' => 'Strong cooling coverage for larger spaces with steady temperature control.', 'image_path' => 'img/products/dowlance/PowerCool 2 Ton Inverter AC dawlance.webp'],
                ['slug' => 'dawlance-spinplus-10kg', 'name' => 'SpinPlus 10kg Washer', 'summary' => 'Large drum washing with fabric-friendly cycles for regular home loads.', 'image_path' => 'img/products/dowlance/SpinPlus 10kg Washer dawlance.webp'],
                ['slug' => 'dawlance-twincare-12kg', 'name' => 'TwinCare 12kg Washer Dryer', 'summary' => 'Family laundry support with twin-tub convenience and easy daily handling.', 'image_path' => 'img/products/dowlance/TwinCare 12kg Washer Dryer dawlance.jpeg'],
                ['slug' => 'dawlance-hydrosip-dispenser', 'name' => 'HydroSip Top Load Water Dispenser', 'summary' => 'Reliable hot and cold water dispensing designed for offices and family homes.', 'image_path' => 'img/products/dowlance/HydroSip Top Load Water Dispenser dawlance.webp'],
                ['slug' => 'dawlance-elegance-9193', 'name' => 'Elegance 9193 Refrigerator', 'summary' => 'Efficient cooling and durable trays for long-term household use.', 'image_path' => 'img/products/dowlance/Elegance 9193 Refrigerator dawlance.webp'],
                ['slug' => 'dawlance-urbanchill-505', 'name' => 'UrbanChill 505 Deep Freezer', 'summary' => 'Large frozen storage compartments for homes that need more preserving capacity.', 'image_path' => 'img/products/dowlance/UrbanChill 505 Deep Freezer dawlance.webp'],
                ['slug' => 'dawlance-chefmaster-30', 'name' => 'ChefMaster 30 Microwave Oven', 'summary' => 'Easy control settings for reheating, warming, and light daily cooking.', 'image_path' => 'img/products/dowlance/ChefMaster 30 Microwave Oven dawlance.webp'],
                ['slug' => 'dawlance-grillpro-25', 'name' => 'GrillPro 25 Cooking Range', 'summary' => 'Compact cooking range support for grilling, baking, and fast home cooking.', 'image_path' => 'img/products/dowlance/GrillPro 25 Cooking Range dawlance.png'],
            ],
        ],
        [
            'slug' => 'samsung',
            'name' => 'SAMSUNG',
            'products' => [
                ['slug' => 'samsung-windhub-15-ac', 'name' => 'WindHub 1.5 Ton Digital AC', 'summary' => 'Digital airflow control and steady room cooling for daily comfort.', 'image_path' => 'img/products/samsung/WindHub 1.5 Ton Digital AC samsung.jpg'],
                ['slug' => 'samsung-airsense-1-ac', 'name' => 'AirSense 1 Ton Inverter AC', 'summary' => 'Compact inverter cooling with quick response for study rooms and bedrooms.', 'image_path' => 'img/products/samsung/AirSense 1 Ton Inverter AC samsung.jfif'],
                ['slug' => 'samsung-maxcool-2-ac', 'name' => 'MaxCool 2 Ton Split AC', 'summary' => 'Wide airflow throw and strong cooling support for open family areas.', 'image_path' => 'img/products/samsung/MaxCool 2 Ton Split AC samsung.webp'],
                ['slug' => 'samsung-ecobubble-9kg', 'name' => 'EcoBubble 9kg Front Load Washer', 'summary' => 'Multiple wash programs and digital controls for modern laundry rooms.', 'image_path' => 'img/products/samsung/EcoBubble 9kg Front Load Washer samsung.jfif'],
                ['slug' => 'samsung-washsmart-11kg', 'name' => 'WashSmart 11kg Laundry System', 'summary' => 'High-capacity performance and easy front access for busy households.', 'image_path' => 'img/products/samsung/WashSmart 11kg Laundry System samsung.jfif'],
                ['slug' => 'samsung-aquahub-dispenser', 'name' => 'AquaHub Floor Standing Water Dispenser', 'summary' => 'Modern dispenser styling with instant cold and hot water convenience.', 'image_path' => 'img/products/samsung/AquaHub Floor Standing Water Dispenser samsung.avif'],
                ['slug' => 'samsung-coolstore-420', 'name' => 'CoolStore 420 Refrigerator', 'summary' => 'Balanced interior cooling with organized shelf placement and tray support.', 'image_path' => 'img/products/samsung/CoolStore 420 Refrigerator samsung.webp'],
                ['slug' => 'samsung-twinfresh-510', 'name' => 'TwinFresh 510 Deep Freezer', 'summary' => 'Double-door freezer storage with dependable temperature retention for bulk use.', 'image_path' => 'img/products/samsung/TwinFresh 510 Deep Freezer samsung.avif'],
                ['slug' => 'samsung-heatbox-32', 'name' => 'HeatBox 32 Microwave Oven', 'summary' => 'Quick preset heating with a clean control panel and everyday convenience.', 'image_path' => 'img/products/samsung/HeatBox 32 Microwave Oven samsung.avif'],
                ['slug' => 'samsung-kitchenhub-26', 'name' => 'KitchenHub 26 Cooking Range', 'summary' => 'Modern cooking range support for grilling, baking, and everyday meal prep.', 'image_path' => 'img/products/samsung/KitchenHub 26 Cooking Range samsung.avif'],
            ],
        ],
        [
            'slug' => 'lg',
            'name' => 'LG',
            'products' => [
                ['slug' => 'lg-airflow-15-ac', 'name' => 'AirFlow 1.5 Ton Inverter AC', 'summary' => 'Power-aware cooling with room-friendly airflow and stable performance.', 'image_path' => 'img/products/lg/AirFlow 1.5 Ton Inverter AC lg.avif'],
                ['slug' => 'lg-breezesmart-1-ac', 'name' => 'BreezeSmart 1 Ton Split AC', 'summary' => 'Compact AC support for apartments, study rooms, and focused spaces.', 'image_path' => 'img/products/lg/BreezeSmart 1 Ton Split AC lg.jfif'],
                ['slug' => 'lg-dualcool-2-ac', 'name' => 'DualCool 2 Ton Inverter AC', 'summary' => 'Heavy-duty cooling coverage for open lounges and frequent daytime use.', 'image_path' => 'img/products/lg/DualCool 2 Ton Inverter AC lg.jfif'],
                ['slug' => 'lg-smartwash-8kg', 'name' => 'SmartWash 8kg Dryer Combo', 'summary' => 'Combined washing and drying in a compact body for smaller homes.', 'image_path' => 'img/products/lg/SmartWash 8kg Dryer Combo lg.webp'],
                ['slug' => 'lg-turboclean-10kg', 'name' => 'TurboClean 10kg Washer', 'summary' => 'Fast wash cycles and balanced drum care for regular family laundry.', 'image_path' => 'img/products/lg/TurboClean 10kg Washer lg.avif'],
                ['slug' => 'lg-pureflow-dispenser', 'name' => 'PureFlow Bottom Load Water Dispenser', 'summary' => 'Premium dispenser convenience with clean storage and easy everyday refilling.', 'image_path' => 'img/products/lg/PureFlow Bottom Load Water Dispenser lg.avif'],
                ['slug' => 'lg-heatchef-30', 'name' => 'HeatChef 30 Microwave Oven', 'summary' => 'Fast reheat support and compact counter placement for daily use.', 'image_path' => 'img/products/lg/HeatChef 30 Microwave Oven lg.avif'],
                ['slug' => 'lg-freshline-450', 'name' => 'FreshLine 450 Refrigerator', 'summary' => 'Reliable cooling zones and organized storage space for family kitchens.', 'image_path' => 'img/products/lg/FreshLine 450 Refrigerator lg.avif'],
                ['slug' => 'lg-instastore-530', 'name' => 'InstaStore 530 Deep Freezer', 'summary' => 'Expanded freezer storage and neat organization for stocked-up family homes.', 'image_path' => 'img/products/lg/InstaStore 530 Deep Freezer lg.avif'],
                ['slug' => 'lg-grillmate-27', 'name' => 'GrillMate 27 Cooking Range', 'summary' => 'Everyday baking and cooking support with easy controls and clean styling.', 'image_path' => 'img/products/lg/GrillMate 27 Cooking Range lg.jfif'],
            ],
        ],
        [
            'slug' => 'orient',
            'name' => 'ORIENT',
            'products' => [
                ['slug' => 'orient-arctic-15-ac', 'name' => 'Arctic 1.5 Ton Inverter AC', 'summary' => 'Stable cooling output with strong airflow for regular daytime comfort.', 'image_path' => 'img/products/orient/Arctic 1.5 Ton Inverter AC orient.jfif'],
                ['slug' => 'orient-coolwave-1-ac', 'name' => 'CoolWave 1 Ton Split AC', 'summary' => 'Focused room cooling with a compact body and practical inverter support.', 'image_path' => 'img/products/orient/CoolWave 1 Ton Split AC orient.webp'],
                ['slug' => 'orient-turbobreeze-2-ac', 'name' => 'TurboBreeze 2 Ton Inverter AC', 'summary' => 'Powerful airflow and broad cooling coverage for active home use.', 'image_path' => 'img/products/orient/TurboBreeze 2 Ton Inverter AC orient.webp'],
                ['slug' => 'orient-freshkeeper-390', 'name' => 'FreshKeeper 390 Refrigerator', 'summary' => 'Compact family storage with reliable shelf space and cooling coverage.', 'image_path' => 'img/products/orient/FreshKeeper 390 Refrigerator orient.webp'],
                ['slug' => 'orient-coolpantry-480', 'name' => 'CoolPantry 480 Deep Freezer', 'summary' => 'Neat freezer layout and wide compartments for frozen family storage.', 'image_path' => 'img/products/orient/CoolPantry 480 Deep Freezer orient.jpg'],
                ['slug' => 'orient-majesty-30', 'name' => 'Majesty 30 Microwave Oven', 'summary' => 'Quick heat presets and everyday cooking convenience for compact kitchens.', 'image_path' => 'img/products/orient/Majesty 30 Microwave Oven orient.jpg'],
                ['slug' => 'orient-quickchef-24', 'name' => 'QuickChef 24 Cooking Range', 'summary' => 'Daily reheat and grill functionality in a cooking-range format.', 'image_path' => 'img/products/orient/QuickChef 24 Cooking Range orient.webp'],
                ['slug' => 'orient-laundrypro-9kg', 'name' => 'LaundryPro 9kg Washer', 'summary' => 'Balanced washing performance and easy cycle selection for regular family use.', 'image_path' => 'img/products/orient/LaundryPro 9kg Washer orient.jpg'],
                ['slug' => 'orient-aquapure-dispenser', 'name' => 'AquaPure Water Dispenser', 'summary' => 'Hot and cold water access with a sleek shape for homes and offices.', 'image_path' => 'img/products/orient/AquaPure Water Dispenser orient.webp'],
                ['slug' => 'orient-twinwash-11kg', 'name' => 'TwinWash 11kg Washer Dryer', 'summary' => 'Practical wash and spin workflow for medium and large laundry loads.', 'image_path' => 'img/products/orient/TwinWash 11kg Washer Dryer orient.webp'],
            ],
        ],
        [
            'slug' => 'pel',
            'name' => 'PEL',
            'products' => [
                ['slug' => 'pel-arcticpro-15-ac', 'name' => 'ArcticPro 1.5 Ton Inverter AC', 'summary' => 'Smooth cooling delivery with practical controls for daily room comfort.', 'image_path' => 'img/products/pel/ArcticPro 1.5 Ton Inverter AC pel.webp'],
                ['slug' => 'pel-chillmax-1-ac', 'name' => 'ChillMax 1 Ton Split AC', 'summary' => 'Small-room cooling support with focused airflow and efficient power use.', 'image_path' => 'img/products/pel/ChillMax 1 Ton Split AC pel.webp'],
                ['slug' => 'pel-smartcool-2-ac', 'name' => 'SmartCool 2 Ton DC Inverter AC', 'summary' => 'Large-space cooling support with steady airflow and inverter efficiency.', 'image_path' => 'img/products/pel/SmartCool 2 Ton DC Inverter AC pel.webp'],
                ['slug' => 'pel-washmate-9kg', 'name' => 'WashMate 9kg Washer', 'summary' => 'Simple wash cycle control and balanced drum performance for weekly use.', 'image_path' => 'img/products/pel/WashMate 9kg Washer pel.jpg'],
                ['slug' => 'pel-twinspin-12kg', 'name' => 'TwinSpin 12kg Washer Dryer', 'summary' => 'High-capacity washing and spin support for family laundry schedules.', 'image_path' => 'img/products/pel/TwinSpin 12kg Washer Dryer pel.webp'],
                ['slug' => 'pel-hydrofresh-dispenser', 'name' => 'HydroFresh Water Dispenser', 'summary' => 'Clean top-load dispenser design with instant refreshment for busy homes.', 'image_path' => 'img/products/pel/HydroFresh Water Dispenser pel.webp'],
                ['slug' => 'pel-iceline-410', 'name' => 'IceLine 410 Refrigerator', 'summary' => 'Dependable cooling and practical shelf organization for small families.', 'image_path' => 'img/products/pel/IceLine 410 Refrigerator pel.jfif'],
                ['slug' => 'pel-familyfresh-500', 'name' => 'FamilyFresh 500 Deep Freezer', 'summary' => 'Wide deep-freezer storage and even cooling for larger family kitchens.', 'image_path' => 'img/products/pel/FamilyFresh 500 Deep Freezer pel.webp'],
                ['slug' => 'pel-microbake-28', 'name' => 'MicroBake 28 Microwave Oven', 'summary' => 'Quick heat and reheat support with a compact countertop footprint.', 'image_path' => 'img/products/pel/MicroBake 28 Microwave Oven pel.webp'],
                ['slug' => 'pel-grillwave-26', 'name' => 'GrillWave 26 Cooking Range', 'summary' => 'Daily grilling and baking support with straightforward cooking-range control.', 'image_path' => 'img/products/pel/GrillWave 26 Cooking Range pel.jfif'],
            ],
        ],
    ];

    $products = [];
    $index = 0;

    foreach ($brandGroups as $brandGroup) {
        foreach ($brandGroup['products'] as $product) {
            $categorySlug = catalog_infer_category_slug($product['name']);
            $category = $categoryMeta[$categorySlug];
            $price = 69990 + ($index * 2150);
            $discountPercent = $index % 5 === 0 ? 18 : ($index % 4 === 0 ? 12 : ($index % 3 === 0 ? 8 : 0));
            $originalPrice = $discountPercent > 0 ? (int) round($price / (1 - ($discountPercent / 100))) : $price;

            $products[] = [
                'slug' => $product['slug'],
                'name' => $product['name'],
                'brand_slug' => $brandGroup['slug'],
                'brand_name' => $brandGroup['name'],
                'category_slug' => $categorySlug,
                'category_name' => $category['name'],
                'summary' => $product['summary'],
                'description' => $product['summary'] . ' ' . $category['note'],
                'document_path' => $category['document'],
                'image_path' => $product['image_path'],
                'price' => $price,
                'original_price' => $originalPrice,
                'discount_percent' => $discountPercent,
                'is_featured' => in_array($product['slug'], $featuredSlugs, true) ? 1 : 0,
            ];

            $index++;
        }
    }

    return [
        'categories' => $categoryMeta,
        'brands' => $brands,
        'featured_slugs' => $featuredSlugs,
        'products' => $products,
    ];
}
