(function () {
    'use strict';

    var observer;
    var cleanup = function () {};
    var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    function setupScrollReveal() {
        cleanup();
        var container = document.getElementById('pjax-container');
        if (!container) return;
        if (reducedMotion.matches || !('IntersectionObserver' in window)) return;

        // Animate one surface per card. Row contents reveal separately from the
        // li elements, whose transforms belong to the list filter animation.
        var selector =
            '.title, h4.text-ce, .weather-hero, .weather-person__heading, ' +
            '.card, .card-b, .img_card, .home-clock, .home-weather__card, ' +
            '.weather-panel, .list-item__summary, .love-post';
        var targets = new Set();
        var timers = new Map();

        function finish(element) {
            window.clearTimeout(timers.get(element));
            timers.delete(element);
            element.classList.remove('motion-reveal', 'is-revealed');
            element.style.removeProperty('--reveal-delay');
        }

        function reveal(element, delay) {
            if (!element.classList.contains('motion-reveal') || element.classList.contains('is-revealed')) return;
            observer.unobserve(element);
            element.style.setProperty('--reveal-delay', delay + 'ms');
            element.classList.add('is-revealed');
            // Release opacity/transform after the animation so glass and hover
            // return to their normal rendering. Also clean up background tabs.
            timers.set(element, window.setTimeout(function () { finish(element); }, delay + 720));
        }

        observer = new IntersectionObserver(function (entries) {
            var visible = entries.filter(function (entry) { return entry.isIntersecting; });
            visible.sort(function (a, b) {
                return a.target.compareDocumentPosition(b.target) & 2 ? 1 : -1;
            });
            visible.forEach(function (entry, index) {
                reveal(entry.target, Math.min(index, 5) * 65);
            });
        }, { rootMargin: '0px 0px -100px 0px', threshold: 0 });

        function onAnimationEnd(event) {
            if (event.animationName === 'scroll-reveal-enter') finish(event.target);
        }
        function onFocus(event) {
            // Keyboard navigation must never land on visually hidden content.
            targets.forEach(function (element) {
                if (element.contains(event.target)) {
                    observer.unobserve(element);
                    finish(element);
                }
            });
        }
        function register(root) {
            var added = Array.prototype.slice.call(root.querySelectorAll(selector));
            if (root.matches(selector)) added.unshift(root);
            added.forEach(function (element) {
                if (targets.has(element)) return;
                if (element.parentElement.closest('.card, .card-b, .img_card, .weather-panel, .love-post') && !element.classList.contains('list-item__summary')) return;
                targets.add(element);
                // PJAX history may restore markup captured mid-animation.
                finish(element);
                element.classList.add('motion-reveal');
                observer.observe(element);
            });
        }
        function onAdded(event) { register(event.target); }
        container.addEventListener('animationend', onAnimationEnd);
        container.addEventListener('focusin', onFocus);
        container.addEventListener('motion:refresh', onAdded);
        register(container);
        cleanup = function () {
            observer.disconnect();
            targets.forEach(finish);
            container.removeEventListener('animationend', onAnimationEnd);
            container.removeEventListener('focusin', onFocus);
            container.removeEventListener('motion:refresh', onAdded);
            cleanup = function () {};
        };
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupScrollReveal);
    } else {
        setupScrollReveal();
    }

    if (window.jQuery) {
        window.jQuery(document).on('pjax:end.pageMotion', setupScrollReveal);
        window.jQuery(document).on('pjax:beforeReplace.pageMotion', function () { cleanup(); });
    }
    function onMotionPreferenceChange() {
        // Revealing everything immediately also handles a setting changed mid-scroll.
        if (reducedMotion.matches) cleanup();
        else setupScrollReveal();
    }
    if (reducedMotion.addEventListener) reducedMotion.addEventListener('change', onMotionPreferenceChange);
    else if (reducedMotion.addListener) reducedMotion.addListener(onMotionPreferenceChange);
}());
