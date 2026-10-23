(function () {
    'use strict';

    function onReady(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
            return;
        }

        callback();
    }

    function bindScrollableMenus() {
        var menus = document.querySelectorAll('.header__menu .dropdown, .header__top__hover ul, .offcanvas__top__hover ul');

        menus.forEach(function (menu) {
            menu.addEventListener('wheel', function (event) {
                if (menu.scrollHeight <= menu.clientHeight) {
                    return;
                }

                event.preventDefault();
                menu.scrollTop += event.deltaY;
            }, { passive: false });
        });
    }

    function bindSearchModel() {
        var openers = document.querySelectorAll('.search-switch');
        var overlay = document.querySelector('.search-model');
        var closer = document.querySelector('.search-close-switch');
        var input = document.getElementById('search-input');

        if (!overlay || !closer || openers.length === 0) {
            return;
        }

        var openSearch = function (event) {
            if (event) {
                event.preventDefault();
            }

            overlay.classList.add('search-model--active');
            document.body.classList.add('search-model-open');

            if (input) {
                window.setTimeout(function () {
                    input.focus();
                }, 40);
            }
        };

        var closeSearch = function () {
            overlay.classList.remove('search-model--active');
            document.body.classList.remove('search-model-open');
        };

        openers.forEach(function (trigger) {
            trigger.addEventListener('click', openSearch);
        });

        closer.addEventListener('click', closeSearch);
        overlay.addEventListener('click', function (event) {
            if (event.target === overlay) {
                closeSearch();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeSearch();
            }
        });
    }

    function bindBrandAnchorFallback() {
        var brandLinks = document.querySelectorAll('a[href="#brand-lineup"]');

        if (brandLinks.length === 0) {
            return;
        }

        brandLinks.forEach(function (link) {
            link.addEventListener('click', function (event) {
                var target = document.getElementById('brand-lineup');

                if (!target) {
                    return;
                }

                event.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
    }

    onReady(function () {
        bindScrollableMenus();
        bindSearchModel();
        bindBrandAnchorFallback();
    });
}());
