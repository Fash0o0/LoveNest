(function () {
    'use strict';

    var refreshIdle;

    function setupCollapse() {
        var nav = document.querySelector('.site-nav');
        if (!nav || nav.classList.contains('is-enhanced')) return;
        var toggle = nav.querySelector('.site-nav__toggle');
        var links = nav.querySelector('.site-nav__links');
        if (!toggle || !links) return;
        var idleTimer, hoverTimer;
        var hovered = false;
        var keyboard = false;
        var lastY = Math.max(0, window.scrollY);
        var downward = 0;

        function protectedInteraction() {
            return hovered || (keyboard && nav.contains(document.activeElement));
        }
        function collapse() {
            window.clearTimeout(idleTimer);
            if (protectedInteraction()) return;
            if (nav.contains(document.activeElement)) document.activeElement.blur();
            nav.classList.add('is-collapsed');
            links.inert = true;
            links.setAttribute('aria-hidden', 'true');
            toggle.setAttribute('aria-expanded', 'false');
            toggle.setAttribute('tabindex', '0');
        }
        function schedule(delay) {
            window.clearTimeout(idleTimer);
            if (!protectedInteraction()) idleTimer = window.setTimeout(collapse, delay);
        }
        function expand() {
            window.clearTimeout(hoverTimer);
            nav.classList.toggle('is-collapsed', false);
            links.inert = false;
            links.removeAttribute('aria-hidden');
            toggle.setAttribute('aria-expanded', 'true');
            toggle.setAttribute('tabindex', '-1');
            downward = 0;
            lastY = Math.max(0, window.scrollY);
            schedule(4000);
        }
        toggle.hidden = false;
        nav.classList.add('is-enhanced');
        toggle.addEventListener('click', function (event) {
            keyboard = event.detail === 0;
            expand();
            if (keyboard) (links.querySelector('.is-active') || links.querySelector('a')).focus();
            else toggle.blur();
        });
        nav.addEventListener('pointerenter', function (event) {
            if (event.pointerType !== 'mouse') return;
            hovered = true;
            window.clearTimeout(idleTimer);
            hoverTimer = window.setTimeout(expand, 120);
        });
        nav.addEventListener('pointerleave', function (event) {
            if (event.pointerType !== 'mouse') return;
            hovered = false;
            window.clearTimeout(hoverTimer);
            schedule(1000);
        });
        document.addEventListener('keydown', function () { keyboard = true; });
        document.addEventListener('pointerdown', function () { keyboard = false; schedule(4000); }, true);
        nav.addEventListener('focusin', function () {
            if (keyboard) window.clearTimeout(idleTimer);
        });
        nav.addEventListener('focusout', function () {
            window.setTimeout(function () { schedule(1000); }, 0);
        });
        nav.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape' || nav.classList.contains('is-collapsed')) return;
            event.preventDefault();
            if (nav.contains(document.activeElement)) document.activeElement.blur();
            hovered = false;
            collapse();
            toggle.focus();
        });
        window.addEventListener('scroll', function () {
            var y = Math.max(0, window.scrollY);
            var delta = y - lastY;
            lastY = y;
            downward = delta > 0 ? downward + delta : 0;
            if (downward >= 60) { collapse(); downward = 0; }
        }, { passive: true });
        refreshIdle = function () {
            downward = 0;
            lastY = Math.max(0, window.scrollY);
            schedule(3000);
        };
        refreshIdle();
    }

    function syncNavigation() {
        var page = window.location.pathname.split('/').pop() || 'index.php';
        if (page === 'page.php') page = 'little.php';
        document.documentElement.classList.toggle('is-home', page === 'index.php');
        document.documentElement.classList.toggle('is-weather', page === 'weather.php');

        document.querySelectorAll('.site-nav__item').forEach(function (link) {
            var active = link.getAttribute('href') === page;
            link.classList.toggle('is-active', active);
            if (active) {
                link.setAttribute('aria-current', 'page');
            } else {
                link.removeAttribute('aria-current');
            }
        });
        setupCollapse();
        if (refreshIdle) refreshIdle();
    }

    // The navigation lives outside the PJAX fragment, so it persists between pages.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', syncNavigation);
    } else {
        syncNavigation();
    }
    window.addEventListener('popstate', syncNavigation);
    if (window.jQuery) {
        window.jQuery(document).on('pjax:end.siteNavigation', syncNavigation);
    }
}());
