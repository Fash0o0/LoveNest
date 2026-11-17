(function () {
    'use strict';

    var cleanup = function () {};
    function initialize() {
        cleanup();
        var page = document.querySelector('.weather-page');
        var slot = page && page.querySelector('.weather-switch-slot');
        var bar = slot && slot.querySelector('.weather-switch');
        var header = document.querySelector('.header-wrap');
        var host = header && header.querySelector('.logo');
        if (!bar || !host) return;
        var mobile = window.matchMedia('(max-width: 700px)');
        var frame = 0;
        header.classList.add('has-weather-context');
        host.appendChild(bar);
        function update() {
            frame = 0;
            var heading = page.querySelector('.is-selected .weather-person__heading');
            var docked = mobile.matches && heading && heading.getBoundingClientRect().bottom <= header.getBoundingClientRect().bottom + 12;
            bar.classList.toggle('is-docked', docked);
        }
        function schedule() {
            if (!frame) frame = window.requestAnimationFrame(update);
        }
        window.addEventListener('scroll', schedule, { passive: true });
        window.addEventListener('resize', schedule);
        // Font loading can change the labels' width after initial layout.
        var resizeObserver = typeof ResizeObserver === 'function' ? new ResizeObserver(schedule) : null;
        if (resizeObserver) { resizeObserver.observe(bar); resizeObserver.observe(header); }
        update();
        cleanup = function () {
            window.cancelAnimationFrame(frame);
            window.removeEventListener('scroll', schedule);
            window.removeEventListener('resize', schedule);
            if (resizeObserver) resizeObserver.disconnect();
            slot.appendChild(bar);
            bar.classList.remove('is-docked');
            header.classList.remove('has-weather-context');
            cleanup = function () {};
        };
    }

    // Delegation handles the same button in either the page or persistent header.
    document.addEventListener('click', function (event) {
        var button = event.target.closest('.weather-switch__item');
        var inline = !button && event.target.closest('.weather-person__switch');
        if (!button && !inline) return;
        var page = (button || inline).closest('.weather-page') || document.querySelector('.weather-page');
        if (!page) return;
        if (inline) button = document.querySelector('.weather-switch__item');
        if (!button) return;
        var person = button.dataset.weatherPerson === 'boy' ? 'girl' : 'boy';
        button.dataset.weatherPerson = person;
        var currentName = '';
        var nextName = '';
        button.querySelectorAll('.weather-switch__label').forEach(function (label) {
            var selected = label.dataset.weatherPerson === person;
            label.hidden = !selected;
            if (selected) currentName = label.textContent.trim();
            else nextName = label.textContent.trim();
        });
        button.setAttribute('aria-label', '当前为' + currentName + '的天气，点击切换为' + nextName);
        var restoreFocus = inline && document.activeElement === inline;
        page.querySelectorAll('.weather-person').forEach(function (panel) {
            var selected = panel.dataset.weatherPerson === person;
            panel.classList.toggle('is-selected', selected);
            if (selected && restoreFocus) {
                panel.querySelector('.weather-person__switch').focus({ preventScroll: true });
            }
        });
    });

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize);
    else initialize();
    if (window.jQuery) {
        window.jQuery(document).on('pjax:end.weatherSwitch', initialize)
            .on('pjax:beforeReplace.weatherSwitch', function () { cleanup(); });
    }
}());
