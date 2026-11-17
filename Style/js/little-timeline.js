(function () {
    'use strict';
    let feedObserver;
    let loadController;
    let timelineCleanup = () => {};
    let refreshTimeline = () => {};
    let closingTimer;
    let photoRequest = 0;
    let displayedPhotoIndex = 0;
    let pendingPhoto = null;
    let finishPhotoSlide = null;
    let slideTimer;
    const boundDialogs = new WeakSet();
    let selected = null;
    let photoIndex = 0;
    let returnFocus = null;
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
    const getDialog = () => document.getElementById('love-detail');

    function resetPhotoSlide(dialog) {
        ++photoRequest; pendingPhoto = null; finishPhotoSlide = null;
        window.clearTimeout(slideTimer);
        const track = dialog.querySelector('.love-detail__track');
        const image = dialog.querySelector('.love-detail__image');
        track.classList.remove('is-switching', 'is-slide-prev');
        track.replaceChildren(image);
        dialog.querySelector('.love-detail__stage').removeAttribute('aria-busy');
    }

    function renderPhoto(photo) {
        const { dialog, request, src, alt, index, animate } = photo;
        if (request !== photoRequest || !dialog.open) return;
        if (finishPhotoSlide) { pendingPhoto = photo; return; }
        const image = dialog.querySelector('.love-detail__image');
        const track = dialog.querySelector('.love-detail__track');
        const stage = dialog.querySelector('.love-detail__stage');
        const slide = animate && !reduced.matches && image.style.visibility !== 'hidden' && image.complete && image.naturalWidth > 0;
        const outgoing = slide ? image.cloneNode() : null;
        const previous = displayedPhotoIndex;
        displayedPhotoIndex = index;
        image.src = src; image.alt = alt; image.style.visibility = '';
        if (!slide) { track.replaceChildren(image); stage.removeAttribute('aria-busy'); return; }
        outgoing.className = 'love-detail__outgoing'; outgoing.alt = ''; outgoing.setAttribute('aria-hidden', 'true');
        if (index < previous) track.replaceChildren(image, outgoing);
        else track.replaceChildren(outgoing, image);
        track.classList.toggle('is-slide-prev', index < previous);
        void track.offsetWidth;
        track.classList.add('is-switching');
        function finish() {
            if (finishPhotoSlide !== finish) return;
            window.clearTimeout(slideTimer);
            track.classList.remove('is-switching', 'is-slide-prev');
            track.replaceChildren(image);
            finishPhotoSlide = null;
            if (request === photoRequest) stage.removeAttribute('aria-busy');
            const queued = pendingPhoto; pendingPhoto = null;
            if (queued) renderPhoto(queued);
        }
        finishPhotoSlide = finish;
        const duration = getComputedStyle(track).animationDuration.split(',')[0].trim();
        const milliseconds = parseFloat(duration) * (duration.endsWith('ms') ? 1 : 1000);
        slideTimer = window.setTimeout(finish, (milliseconds || 360) + 80);
    }

    function selectPhoto(index, animate = true) {
        const dialog = getDialog();
        if (!dialog || !selected || !selected.images.length) return;
        const nextIndex = Math.max(0, Math.min(index, selected.images.length - 1));
        if (animate && nextIndex === photoIndex) return;
        photoIndex = nextIndex;
        pendingPhoto = null;
        const request = ++photoRequest;
        const image = dialog.querySelector('.love-detail__image');
        const stage = dialog.querySelector('.love-detail__stage');
        const src = selected.images[photoIndex];
        const alt = selected.title + ' · 照片' + (photoIndex + 1);
        stage.setAttribute('aria-busy', 'true');
        dialog.querySelector('.love-detail__image-error').hidden = true;
        const incoming = new Image();
        incoming.onload = () => {
            renderPhoto({ dialog, request, src, alt, index: nextIndex, animate });
        };
        incoming.onerror = () => {
            if (request !== photoRequest || !dialog.open) return;
            stage.removeAttribute('aria-busy'); image.style.visibility = 'hidden';
            dialog.querySelector('.love-detail__image-error').hidden = false;
        };
        incoming.src = src;
        dialog.querySelector('.love-detail__counter').textContent = (photoIndex + 1) + ' / ' + selected.images.length;
        dialog.querySelector('.love-detail__prev').disabled = photoIndex === 0;
        dialog.querySelector('.love-detail__next').disabled = photoIndex === selected.images.length - 1;
        dialog.querySelectorAll('.love-detail__thumbs button').forEach((button, i) => button.setAttribute('aria-current', i === photoIndex ? 'true' : 'false'));
        const current = dialog.querySelector('.love-detail__thumbs button[aria-current="true"]');
        if (current) current.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: reduced.matches ? 'instant' : 'smooth' });
    }

    function openRecord(id, index) {
        const card = document.querySelector('.love-post[data-record="' + Number(id) + '"]');
        const dialog = getDialog();
        if (!card || !dialog) return;
        resetPhotoSlide(dialog);
        window.clearTimeout(closingTimer);
        dialog.classList.remove('is-closing', 'is-opening');
        selected = JSON.parse(card.querySelector('.love-post__data').textContent);
        returnFocus = document.activeElement;
        dialog.querySelector('#love-detail-title').textContent = selected.title;
        dialog.querySelector('.love-detail__author').textContent = selected.author;
        dialog.querySelector('.love-detail__date').textContent = selected.date;
        dialog.querySelector('.love-detail__date').dateTime = selected.iso;
        dialog.querySelector('.love-detail__avatar').src = selected.avatar;
        dialog.querySelector('.love-detail__body').innerHTML = selected.body;
        dialog.classList.toggle('is-text-only', !selected.images.length);
        const thumbs = dialog.querySelector('.love-detail__thumbs');
        thumbs.replaceChildren();
        selected.images.forEach((src, i) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.setAttribute('aria-label', '查看第' + (i + 1) + '张照片');
            const thumbnail = document.createElement('img');
            thumbnail.src = src; thumbnail.alt = ''; thumbnail.loading = 'lazy';
            button.append(thumbnail);
            button.addEventListener('click', () => selectPhoto(i));
            thumbs.append(button);
        });
        dialog.querySelector('.love-detail__image').style.visibility = 'hidden';
        dialog.showModal();
        if (!reduced.matches) dialog.classList.add('is-opening');
        document.documentElement.classList.add('love-detail-open');
        dialog.querySelector('.love-detail__panel').scrollTop = 0;
        dialog.querySelector('.love-detail__content').scrollTop = 0;
        selectPhoto(index || 0, false);
        dialog.querySelector('[data-detail-close]').focus({ preventScroll: true });
    }

    function closeRecord(immediate = false) {
        const dialog = getDialog();
        if (!dialog || !dialog.open) return;
        if (immediate || reduced.matches) {
            window.clearTimeout(closingTimer);
            dialog.classList.remove('is-opening', 'is-closing');
            dialog.close(); return;
        }
        if (dialog.classList.contains('is-closing')) return;
        dialog.classList.remove('is-opening');
        dialog.classList.add('is-closing');
        closingTimer = window.setTimeout(() => { if (dialog.open) dialog.close(); }, 240);
    }

    function setupTimeline() {
        const feed = document.querySelector('.love-timeline');
        if (!feed) return;
        const header = document.querySelector('.header-wrap');
        const yearIndicator = document.querySelector('.timeline-year-indicator');
        if (header) header.classList.add('has-timeline-context');
        // History snapshots can contain the active state from another scroll position.
        feed.querySelectorAll('.is-active').forEach(item => item.classList.remove('is-active'));
        let frame = 0;
        let geometry = [];
        let years = [];
        let active = null;
        let lineTop = 0;
        let height = 1;
        function update() {
            frame = 0;
            const top = feed.getBoundingClientRect().top;
            const readingPoint = window.innerHeight * .55 - top;
            const progress = reduced.matches ? 1 : Math.max(0, Math.min(1, (readingPoint - lineTop) / height));
            feed.style.setProperty('--timeline-progress', String(progress));
            // Compensate for scaleY so the fade remains about 72 screen pixels.
            feed.style.setProperty('--timeline-tail-size', Math.min(height, 72 / Math.max(progress, .001)) + 'px');
            let currentYear = null;
            years.forEach((year, i) => {
                const next = years[i + 1];
                const isCurrent = readingPoint >= year.top && (!next || readingPoint < next.top);
                year.item.classList.toggle('is-current-year', isCurrent);
                if (isCurrent) currentYear = year;
            });
            if (yearIndicator && header) {
                const docked = currentYear && currentYear.label.getBoundingClientRect().bottom <= header.getBoundingClientRect().bottom + 12;
                const text = docked ? currentYear.item.dataset.year : '';
                if (yearIndicator.textContent !== text) yearIndicator.textContent = text;
                yearIndicator.setAttribute('aria-hidden', String(!docked));
            }
            const reached = geometry.filter(entry => entry.top <= readingPoint);
            const current = reached.length ? reached[reached.length - 1].item : null;
            if (current !== active) {
                if (active) active.classList.remove('is-active');
                active = current;
                if (active) active.classList.add('is-active');
            }
        }
        function schedule() { if (!frame) frame = requestAnimationFrame(update); }
        function measure() {
            const rect = feed.getBoundingClientRect();
            lineTop = parseFloat(getComputedStyle(feed, '::after').top) || 0;
            height = Math.max(1, rect.height - lineTop);
            geometry = Array.from(feed.querySelectorAll('.love-timeline__item'), item => {
                const marker = item.querySelector('.love-timeline__marker');
                // Marker centers stay anchored to their records, including when highlighted.
                return { item, top: item.getBoundingClientRect().top - rect.top + marker.offsetTop + marker.offsetHeight / 2 };
            });
            years = geometry.filter(entry => !entry.item.querySelector('.love-timeline__year').hidden).map(entry => ({
                ...entry, label: entry.item.querySelector('.love-timeline__year')
            }));
            schedule();
        }
        const resizeObserver = 'ResizeObserver' in window ? new ResizeObserver(measure) : null;
        if (resizeObserver) resizeObserver.observe(feed);
        window.addEventListener('scroll', schedule, { passive: true });
        window.addEventListener('resize', measure);
        refreshTimeline = measure;
        measure();
        timelineCleanup = () => {
            cancelAnimationFrame(frame);
            if (resizeObserver) resizeObserver.disconnect();
            window.removeEventListener('scroll', schedule);
            window.removeEventListener('resize', measure);
            years.forEach(year => year.item.classList.remove('is-current-year'));
            if (yearIndicator) { yearIndicator.textContent = ''; yearIndicator.setAttribute('aria-hidden', 'true'); }
            if (header) header.classList.remove('has-timeline-context');
            timelineCleanup = () => {}; refreshTimeline = () => {};
        };
    }

    function preparePosts() {
        let year = null;
        document.querySelectorAll('.love-timeline__item').forEach(item => {
            item.querySelector('.love-timeline__year').hidden = year === item.dataset.year;
            year = item.dataset.year;
        });
        refreshTimeline();
        const feed = document.querySelector('.love-timeline');
        if (feed) feed.dispatchEvent(new CustomEvent('motion:refresh', { bubbles: true }));
    }

    async function loadEarlier() {
        const button = document.querySelector('.love-page__load');
        const feed = document.querySelector('.love-timeline');
        const status = document.querySelector('.love-page__loading');
        if (!button || button.disabled || !feed) return;
        button.disabled = true; status.textContent = '正在翻开更早的回忆…';
        const controller = new AbortController(); loadController = controller;
        try {
            const url = new URL(feed.dataset.feedUrl, window.location.href);
            url.searchParams.set('cursor', button.dataset.cursor);
            const response = await fetch(url, { signal: controller.signal });
            const data = await response.json();
            if (!response.ok || typeof data.html !== 'string') throw new Error('加载失败');
            if (!feed.isConnected) return;
            feed.insertAdjacentHTML('beforeend', data.html);
            preparePosts(); status.textContent = '';
            if (data.hasMore) button.dataset.cursor = JSON.stringify(data.cursor);
            else {
                if (feedObserver) feedObserver.disconnect();
                const end = document.createElement('p'); end.className = 'love-page__end'; end.textContent = '故事从这里开始'; button.replaceWith(end);
            }
        } catch (error) {
            if (error.name !== 'AbortError' && status.isConnected) { status.textContent = '暂时无法加载，请点击按钮重试。'; if (feedObserver) feedObserver.disconnect(); }
        } finally { button.disabled = false; if (loadController === controller) loadController = null; }
    }

    function initialize() {
        if (feedObserver) feedObserver.disconnect();
        timelineCleanup();
        if (loadController) loadController.abort();
        document.documentElement.classList.remove('love-detail-open');
        const page = document.querySelector('.love-page');
        if (!page) return;
        preparePosts();
        setupTimeline();
        const dialog = getDialog();
        bindDialog(dialog);
        document.documentElement.classList.toggle('love-detail-open', dialog.open);
        const load = page.querySelector('.love-page__load');
        if (load && 'IntersectionObserver' in window) { feedObserver = new IntersectionObserver(entries => { if (entries.some(entry => entry.isIntersecting)) loadEarlier(); }, { rootMargin: '240px' }); feedObserver.observe(load); }
        if (page.dataset.autoOpen) openRecord(page.dataset.autoOpen, 0);
    }

    function bindDialog(dialog) {
        // PJAX may initialize the same fragment again; bind dialog controls once.
        if (boundDialogs.has(dialog)) return;
        boundDialogs.add(dialog);
        dialog.addEventListener('close', () => {
            dialog.querySelectorAll('video').forEach(video => video.pause());
            if (dialog.open || dialog !== getDialog()) return;
            window.clearTimeout(closingTimer); resetPhotoSlide(dialog);
            dialog.classList.remove('is-opening', 'is-closing');
            document.documentElement.classList.remove('love-detail-open');
            if (returnFocus && returnFocus.isConnected) returnFocus.focus({ preventScroll: true });
            selected = null;
        });
        dialog.addEventListener('cancel', event => { event.preventDefault(); closeRecord(); });
        dialog.addEventListener('animationend', event => {
            if (event.target !== dialog) return;
            if (event.animationName === 'love-detail-in') dialog.classList.remove('is-opening');
            if (event.animationName === 'love-detail-out') closeRecord(true);
        });
        dialog.querySelector('.love-detail__track').addEventListener('animationend', event => {
            if (event.target.classList.contains('love-detail__track') && finishPhotoSlide) finishPhotoSlide();
        });
        dialog.addEventListener('click', event => { if (event.target === dialog) { const r = dialog.getBoundingClientRect(); if (event.clientX < r.left || event.clientX > r.right || event.clientY < r.top || event.clientY > r.bottom) closeRecord(); } });
        dialog.querySelector('[data-detail-close]').addEventListener('click', () => closeRecord());
        dialog.querySelector('.love-detail__prev').addEventListener('click', () => selectPhoto(photoIndex - 1));
        dialog.querySelector('.love-detail__next').addEventListener('click', () => selectPhoto(photoIndex + 1));
        dialog.querySelector('.love-detail__image').addEventListener('error', () => { dialog.querySelector('.love-detail__image-error').hidden = false; });
        let touch = null;
        const stage = dialog.querySelector('.love-detail__stage');
        stage.addEventListener('touchstart', event => { if (event.touches.length === 1) touch = { x: event.touches[0].clientX, y: event.touches[0].clientY }; }, { passive: true });
        stage.addEventListener('touchend', event => { if (!touch) return; const dx = event.changedTouches[0].clientX - touch.x; const dy = event.changedTouches[0].clientY - touch.y; if (Math.abs(dx) > 45 && Math.abs(dx) > Math.abs(dy) * 1.5) selectPhoto(photoIndex + (dx < 0 ? 1 : -1)); touch = null; }, { passive: true });
    }

    document.addEventListener('click', event => {
        if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        const opener = event.target.closest('[data-open-record]');
        if (opener) { event.preventDefault(); event.stopPropagation(); openRecord(opener.dataset.openRecord, Number(opener.dataset.photoIndex) || 0); return; }
        const post = event.target.closest('.love-post');
        if (post && !event.target.closest('a,button') && !window.getSelection().toString()) { openRecord(post.dataset.record, 0); return; }
        if (event.target.closest('.love-page__load')) loadEarlier();
    }, true);
    document.addEventListener('keydown', event => {
        const dialog = getDialog();
        if (!dialog || !dialog.open) return;
        if (event.key === 'Escape') { event.preventDefault(); closeRecord(); }
        if (event.target.closest('.love-detail__viewer') || event.target.matches('[data-detail-close]')) {
            if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') { event.preventDefault(); selectPhoto(photoIndex + (event.key === 'ArrowLeft' ? -1 : 1)); }
        }
    });
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize); else initialize();
    function onMotionChange() {
        refreshTimeline();
        if (reduced.matches && finishPhotoSlide) finishPhotoSlide();
        if (reduced.matches && getDialog()?.classList.contains('is-closing')) closeRecord(true);
    }
    if (reduced.addEventListener) reduced.addEventListener('change', onMotionChange);
    else if (reduced.addListener) reduced.addListener(onMotionChange);
    if (window.jQuery) window.jQuery(document).on('pjax:end.littleTimeline', initialize).on('pjax:beforeReplace.littleTimeline', () => { closeRecord(true); timelineCleanup(); if (loadController) loadController.abort(); });
}());
