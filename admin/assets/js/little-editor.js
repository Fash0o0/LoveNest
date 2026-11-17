(function () {
    'use strict';
    const form = document.getElementById('little-editor-form');
    if (!form) return;
    const input = document.getElementById('moment-files');
    const choose = document.getElementById('moment-choose');
    const save = document.getElementById('moment-save');
    const hidden = document.getElementById('moment-images');
    const list = document.getElementById('moment-photo-list');
    const count = document.getElementById('moment-image-count');
    const status = document.getElementById('moment-status');
    const progress = document.getElementById('moment-progress');
    let photos = JSON.parse(hidden.value || '[]').map(url => ({ url, state: 'ready' }));
    let busy = false;
    let dirty = false;
    function message(text, error) { status.textContent = text; status.className = 'my-3 ' + (error ? 'text-danger' : 'text-success'); }
    function sync() {
        hidden.value = JSON.stringify(photos.filter(photo => photo.state === 'ready').map(photo => photo.url));
        count.textContent = photos.filter(photo => photo.state === 'ready').length + ' 张照片';
    }
    function render() {
        sync(); list.replaceChildren();
        photos.forEach((photo, index) => {
            const figure = document.createElement('figure'); figure.className = 'moment-photo' + (photo.state === 'failed' ? ' is-failed' : '');
            const image = document.createElement('img'); image.src = photo.url || photo.preview; image.alt = '第' + (index + 1) + '张照片'; image.loading = 'lazy';
            const caption = document.createElement('figcaption'); caption.textContent = (index === 0 ? '封面 · ' : '') + (photo.state === 'ready' ? '已上传' : photo.state === 'failed' ? '上传失败，请重试或移除' : '等待上传');
            figure.append(image, caption);
            const actions = document.createElement('div'); actions.className = 'moment-photo__actions';
            function action(text, label, fn, disabled) { const button = document.createElement('button'); button.type = 'button'; button.textContent = text; button.setAttribute('aria-label', label + '第' + (index + 1) + '张照片'); button.disabled = busy || disabled; button.addEventListener('click', fn); actions.append(button); }
            action('前移', '前移', () => { [photos[index-1], photos[index]] = [photos[index], photos[index-1]]; dirty = true; render(); }, index === 0);
            action('后移', '后移', () => { [photos[index+1], photos[index]] = [photos[index], photos[index+1]]; dirty = true; render(); }, index === photos.length-1);
            action('移除', '移除', () => { if (photo.preview) URL.revokeObjectURL(photo.preview); photos.splice(index, 1); dirty = true; render(); }, false);
            if (photo.state === 'failed') action('重试', '重新上传', () => uploadBatch([photo]), false);
            figure.append(actions); list.append(figure);
        });
    }
    function setBusy(value) { busy = value; choose.disabled = save.disabled = value; render(); }
    function compress(file) {
        return new Promise((resolve, reject) => {
            if (!['image/jpeg','image/png','image/webp'].includes(file.type)) return reject(new Error('请选择JPG、PNG或WebP照片。'));
            if (file.size > 40 * 1024 * 1024) return reject(new Error('单张原图不能超过40 MB，请先缩小照片。'));
            const url = URL.createObjectURL(file); const image = new Image();
            image.onerror = () => { URL.revokeObjectURL(url); reject(new Error('照片无法读取，请换一张重试。')); };
            image.onload = () => {
                URL.revokeObjectURL(url);
                if (!image.naturalWidth || !image.naturalHeight || image.naturalWidth * image.naturalHeight > 50000000) return reject(new Error('单张照片请缩小到5000万像素以内。'));
                const scale = Math.min(1, 1920 / Math.max(image.naturalWidth, image.naturalHeight));
                const canvas = document.createElement('canvas'); canvas.width = Math.max(1, Math.round(image.naturalWidth*scale)); canvas.height = Math.max(1, Math.round(image.naturalHeight*scale));
                const context = canvas.getContext('2d'); if (!context) return reject(new Error('浏览器无法处理照片。'));
                context.drawImage(image, 0, 0, canvas.width, canvas.height);
                canvas.toBlob(blob => { canvas.width = canvas.height = 0; if (!blob || blob.type !== 'image/webp' || blob.size > 2 * 1024 * 1024) reject(new Error('压缩失败或压缩后超过2 MB，请缩小照片重试。')); else resolve(blob); }, 'image/webp', .82);
            };
            image.src = url;
        });
    }
    async function uploadBatch(batch) {
        if (busy) return;
        setBusy(true); progress.hidden = false;
        let failures = 0;
        const archiveDate = form.elements.articletime.value.slice(0, 10);
        const description = Array.from(form.elements.articletitle.value.trim()).slice(0, 25).join('') || '点滴照片';
        try {
            for (let i = 0; i < batch.length; i++) {
                const photo = batch[i];
                message('正在上传第 ' + (i+1) + ' / ' + batch.length + ' 张照片…'); progress.value = Math.round(i / batch.length * 100);
                try {
                    const blob = await compress(photo.file); const data = new FormData();
                    const stem = /[\p{L}\p{N}]/u.test(description) ? Array.from(description).slice(0, 16).join('') : '点滴照片';
                    const suffix = Array.from(crypto.getRandomValues(new Uint8Array(4)), value => value.toString(16).padStart(2, '0')).join('');
                    data.append('csrf', form.elements.csrf.value); data.append('imgDatd', archiveDate); data.append('imgText', stem + '-' + suffix); data.append('photo', blob, 'photo.webp');
                    const response = await fetch('albumUpload.php', { method: 'POST', body: data, credentials: 'same-origin' });
                    const result = await response.json();
                    if (!response.ok || !result.url) throw new Error(result.error || '上传失败');
                    photo.url = result.url; photo.state = 'ready'; photo.file = null;
                    URL.revokeObjectURL(photo.preview); photo.preview = null;
                } catch (error) { photo.state = 'failed'; photo.error = error.message; failures++; }
                sync(); render();
            }
            message(failures ? failures + ' 张照片上传失败，请重试或移除后再发布。' + (batch.find(photo=>photo.error)?.error || '') : '照片已上传，点击下方按钮保存点滴。', failures > 0);
        } finally { setBusy(false); progress.hidden = true; }
    }
    choose.addEventListener('click', () => { if (form.elements.articletime.reportValidity()) input.click(); });
    input.addEventListener('change', () => {
        if (busy) return;
        const batch = Array.from(input.files).map(file => ({ file, preview: URL.createObjectURL(file), state: 'pending' }));
        input.value = ''; if (!batch.length) return;
        photos.push(...batch); dirty = true; render(); uploadBatch(batch);
    });
    form.addEventListener('input', () => { dirty = true; });
    form.addEventListener('submit', async event => {
        event.preventDefault(); if (busy || !form.reportValidity()) return;
        if (window.littleEditor) window.littleEditor.save();
        const body = form.elements.articletext.value.trim();
        if (/<\s*img\b|!\[[^\]]*\]\(/i.test(body)) return message('正文中不要插入图片，请使用“选择上传图片”按钮。', true);
        if (photos.some(photo => photo.state !== 'ready')) return message('还有照片未上传成功，请先重试或移除。', true);
        if (!body && !photos.length) return message('请填写正文或至少上传一张照片。', true);
        sync(); const data = new FormData(form);
        if (window.littleEditor) data.set('articletext', window.littleEditor.getHTML());
        setBusy(true); message('正在保存点滴…');
        try {
            const response = await fetch(form.action, { method: 'POST', body: data, credentials: 'same-origin' }); const text = await response.text();
            if (!response.ok || text.trim() !== '1') { let detail; try { detail = JSON.parse(text).error; } catch (_) {} throw new Error(detail || '保存失败，请检查登录状态后重试。'); }
            dirty = false; window.location.assign('littleSet.php');
        } catch (error) { message(error.message, true); } finally { setBusy(false); }
    });
    window.addEventListener('beforeunload', event => { if (busy || dirty) { event.preventDefault(); event.returnValue = ''; } });
    render();
}());
