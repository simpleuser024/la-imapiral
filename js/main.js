/*  ---------------------------------------------------
    Template Name: Male Fashion
    Description: Male Fashion - ecommerce teplate
    Author: Colorib
    Author URI: https://www.colorib.com/
    Version: 1.0
    Created: Colorib
---------------------------------------------------------  */

'use strict';

(function ($) {
    function toCssUrl(path) {
        return 'url("' + encodeURI(path).replace(/"/g, '%22') + '")';
    }

    var productMixer = null;
    var brandPageMatch = window.location.pathname
        .replace(/\\/g, '/')
        .replace(/[A-Za-z]:/g, '')
        .replace(/\/{2,}/g, '/')
        .match(/(?:^|\/)(haier|dawlance|samsung|lg|orient|pel)\.(?:html|php)$/i);
    var isBrandPage = Boolean(brandPageMatch);
    var navigationEntry = window.performance && typeof window.performance.getEntriesByType === 'function'
        ? window.performance.getEntriesByType('navigation')
        : [];
    var navigationType = (navigationEntry && navigationEntry[0] && navigationEntry[0].type) || '';
    var skipPreloaderForBrandIntro = isBrandPage && navigationType !== 'reload';

    if (!navigationType && window.performance && window.performance.navigation) {
        if (window.performance.navigation.type === 1) {
            navigationType = 'reload';
        } else if (window.performance.navigation.type === 2) {
            navigationType = 'back_forward';
        } else {
            navigationType = 'navigate';
        }

        skipPreloaderForBrandIntro = isBrandPage && navigationType !== 'reload';
    }

    if (skipPreloaderForBrandIntro) {
        $('body').addClass('brand-intro-entry');
        $('#preloder').hide();
        $('.loader').hide();
    }

    /*------------------
        Preloader
    --------------------*/
    $(window).on('load', function () {
        if (skipPreloaderForBrandIntro) {
            $(".loader").hide();
            $("#preloder").hide();
        } else {
            $(".loader").fadeOut();
            $("#preloder").delay(200).fadeOut("slow");
        }

        /*------------------
            Gallery filter
        --------------------*/
        $('.filter__controls li').on('click', function () {
            $('.filter__controls li').removeClass('active');
            $('.js-mix-filter').removeClass('active');
            $(this).addClass('active');
        });
        if ($('.product__filter').length > 0) {
            var containerEl = document.querySelector('.product__filter');
            productMixer = mixitup(containerEl);
            var initialFilter = containerEl.getAttribute('data-initial-filter');
            if (initialFilter && initialFilter !== '*') {
                productMixer.filter(initialFilter);
            }
        }
    });

    /*------------------
        Background Set
    --------------------*/
    $('.set-bg').each(function () {
        var bg = $(this).data('setbg');
        $(this).css('background-image', toCssUrl(bg));
    });

    //Search Switch
    $('.search-switch').on('click', function () {
        $('.search-model').fadeIn(400);
    });

    $('.search-close-switch').on('click', function () {
        $('.search-model').fadeOut(400, function () {
            $('#search-input').val('');
        });
    });

    /*------------------
		Navigation
	--------------------*/
    $(".mobile-menu").slicknav({
        prependTo: '#mobile-menu-wrap',
        allowParentLinks: true
    });

    $('.js-mix-filter').on('click', function (e) {
        e.preventDefault();
        $('.js-mix-filter').removeClass('active');
        $(this).addClass('active');
        $('.filter__controls li').removeClass('active');

        if (productMixer) {
            productMixer.filter($(this).data('filter'));
        }
    });

    /*------------------
        Accordin Active
    --------------------*/
    $('.collapse').on('shown.bs.collapse', function () {
        $(this).prev().addClass('active');
    });

    $('.collapse').on('hidden.bs.collapse', function () {
        $(this).prev().removeClass('active');
    });

    //Canvas Menu
    $(".canvas__open").on('click', function () {
        $(".offcanvas-menu-wrapper").addClass("active");
        $(".offcanvas-menu-overlay").addClass("active");
    });

    $(".offcanvas-menu-overlay").on('click', function () {
        $(".offcanvas-menu-wrapper").removeClass("active");
        $(".offcanvas-menu-overlay").removeClass("active");
    });

    /*-----------------------
        Hero Slider
    ------------------------*/
    function initializeHeroSlider(forceRebuild) {
        $(".hero__slider").each(function () {
            var $slider = $(this);

            if (forceRebuild && $slider.hasClass('owl-loaded')) {
                $slider.trigger('destroy.owl.carousel');
                $slider.removeClass('owl-loaded owl-hidden');
                $slider.find('.owl-stage-outer').children().unwrap();
            }

            if (!$slider.children().length || $slider.hasClass('owl-loaded')) {
                return;
            }

            $slider.owlCarousel({
                loop: true,
                margin: 0,
                items: 1,
                dots: false,
                nav: true,
                navText: ["<span class='arrow_left'><span/>", "<span class='arrow_right'><span/>"],
                animateOut: 'fadeOut',
                animateIn: 'fadeIn',
                smartSpeed: 1200,
                autoHeight: false,
                autoplay: true,
                autoplayTimeout: 5000,
                autoplayHoverPause: true
            });
        });
    }

    window.LaImperialInitHeroSlider = initializeHeroSlider;
    initializeHeroSlider();

    /*--------------------------
        Select
    ----------------------------*/
    $("select").niceSelect();

    /*-------------------
		Radio Btn
	--------------------- */
    $(".product__color__select label, .shop__sidebar__size label, .product__details__option__size label").on('click', function () {
        $(".product__color__select label, .shop__sidebar__size label, .product__details__option__size label").removeClass('active');
        $(this).addClass('active');
    });

    /*-------------------
		Scroll
	--------------------- */
    $(".nice-scroll").niceScroll({
        cursorcolor: "#0d0d0d",
        cursorwidth: "5px",
        background: "#e5e5e5",
        cursorborder: "",
        autohidemode: true,
        horizrailenabled: false
    });

    /*------------------
        CountDown
    --------------------*/
    // For demo preview start
    var today = new Date();
    var dd = String(today.getDate()).padStart(2, '0');
    var mm = String(today.getMonth() + 1).padStart(2, '0'); //January is 0!
    var yyyy = today.getFullYear();

    if(mm == 12) {
        mm = '01';
        yyyy = yyyy + 1;
    } else {
        mm = parseInt(mm) + 1;
        mm = String(mm).padStart(2, '0');
    }
    var timerdate = mm + '/' + dd + '/' + yyyy;
    // For demo preview end


    // Uncomment below and use your date //

    /* var timerdate = "2020/12/30" */

    $("#countdown").countdown(timerdate, function (event) {
        $(this).html(event.strftime("<div class='cd-item'><span>%D</span> <p>Days</p> </div>" + "<div class='cd-item'><span>%H</span> <p>Hours</p> </div>" + "<div class='cd-item'><span>%M</span> <p>Minutes</p> </div>" + "<div class='cd-item'><span>%S</span> <p>Seconds</p> </div>"));
    });

    /*------------------
		Magnific
	--------------------*/
    $('.video-popup').magnificPopup({
        type: 'iframe'
    });

    /*-------------------
		Quantity change
	--------------------- */
    var proQty = $('.pro-qty');
    proQty.prepend('<span class="fa fa-angle-up dec qtybtn"></span>');
    proQty.append('<span class="fa fa-angle-down inc qtybtn"></span>');
    proQty.on('click', '.qtybtn', function () {
        var $button = $(this);
        var oldValue = $button.parent().find('input').val();
        if ($button.hasClass('inc')) {
            var newVal = parseFloat(oldValue) + 1;
        } else {
            // Don't allow decrementing below zero
            if (oldValue > 0) {
                var newVal = parseFloat(oldValue) - 1;
            } else {
                newVal = 0;
            }
        }
        $button.parent().find('input').val(newVal);
    });

    var proQty = $('.pro-qty-2');
    proQty.prepend('<span class="fa fa-angle-left dec qtybtn"></span>');
    proQty.append('<span class="fa fa-angle-right inc qtybtn"></span>');
    proQty.on('click', '.qtybtn', function () {
        var $button = $(this);
        var oldValue = $button.parent().find('input').val();
        if ($button.hasClass('inc')) {
            var newVal = parseFloat(oldValue) + 1;
        } else {
            // Don't allow decrementing below zero
            if (oldValue > 0) {
                var newVal = parseFloat(oldValue) - 1;
            } else {
                newVal = 0;
            }
        }
        $button.parent().find('input').val(newVal);
    });

    /*------------------
        Achieve Counter
    --------------------*/
    $('.cn_num').each(function () {
        $(this).prop('Counter', 0).animate({
            Counter: $(this).text()
        }, {
            duration: 4000,
            easing: 'swing',
            step: function (now) {
                $(this).text(Math.ceil(now));
            }
        });
    });

})(jQuery);
