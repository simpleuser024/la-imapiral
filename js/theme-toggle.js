(function (window, document) {
    'use strict';

    var storageKey = 'la_imparial_theme';
    var editor = window.LA_IMPARIAL_EDITOR || {};
    var settings = editor.settings || {};
    var serverMode = settings.color_mode || document.documentElement.getAttribute('data-theme') || 'light';

    function getStoredTheme() {
        try {
            return window.localStorage.getItem(storageKey) || '';
        } catch (error) {
            return '';
        }
    }

    function saveTheme(theme) {
        try {
            window.localStorage.setItem(storageKey, theme);
        } catch (error) {
            return;
        }
    }

    function normalizeTheme(theme) {
        return theme === 'dark' ? 'dark' : 'light';
    }

    function applyTheme(theme) {
        var nextTheme = normalizeTheme(theme);
        document.documentElement.setAttribute('data-theme', nextTheme);
        document.body.setAttribute('data-theme', nextTheme);
        Array.prototype.forEach.call(document.querySelectorAll('[data-theme-toggle]'), function (button) {
            button.setAttribute('aria-pressed', nextTheme === 'dark' ? 'true' : 'false');
            button.innerHTML = nextTheme === 'dark'
                ? '<i class="fa fa-sun-o" aria-hidden="true"></i><span>Light</span>'
                : '<i class="fa fa-moon-o" aria-hidden="true"></i><span>Dark</span>';
        });
    }

    function createToggle() {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'la-theme-toggle';
        button.setAttribute('data-theme-toggle', '1');
        button.setAttribute('aria-label', 'Toggle dark and light theme');
        button.addEventListener('click', function () {
            var current = normalizeTheme(document.documentElement.getAttribute('data-theme'));
            var next = current === 'dark' ? 'light' : 'dark';
            saveTheme(next);
            applyTheme(next);
        });
        return button;
    }

    function mountToggle(selector) {
        var target = document.querySelector(selector);

        if (!target || target.querySelector('[data-theme-toggle]')) {
            return;
        }

        target.insertBefore(createToggle(), target.firstChild);
    }

    function onReady(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
            return;
        }

        callback();
    }

    applyTheme(getStoredTheme() || serverMode);

    onReady(function () {
        mountToggle('.header__nav__option');
        mountToggle('.offcanvas__nav__option');
        mountToggle('.admin-topbar__actions');
        mountToggle('.top-actions');
        applyTheme(getStoredTheme() || serverMode);
    });
}(window, document));
