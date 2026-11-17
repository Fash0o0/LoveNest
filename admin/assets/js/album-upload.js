(function () {
    'use strict';
    const form = document.getElementById('album-add-form') || document.getElementById('list-photo-form');
    if (!form) return;
    const isList = form.getAttribute('id') === 'list-photo-form';
    const date = form.elements.imgDatd;
    const description = form.elements.imgText;
    const url = form.elements.imgUrl || form.elements.img || form.elements.imgurl;
    const fileInput = document.getElementById('album-file');
    const uploadButton = document.getElementById('album-upload');
    const saveButton = document.getElementById('album-save');
    const status = document.getElementById('album-upload-status');
    const progress = document.getElementById('album-upload-progress');
    const preview = document.getElementById('album-preview');
    const previewWrap = document.getElementById('album-preview-wrap');
    let busy = false;
    let uploaded = null;
    if (isList && url.value && url.value !== '0') {
        preview.src = url.value;
        previewWrap.hidden = false;
    }

    function message(text, error) {
        status.textContent = text;
        status.className = 'mb-3 ' + (error ? 'text-danger' : 'text-success');
    }

    function setBusy(value) {
        busy = value;
        [uploadButton, saveButton, date, description, url].forEach(function (control) {
            control.disabled = value;
        });
        if (isList) {
            form.elements.eventname.disabled = value;
            form.elements.icon.disabled = value;
        }
    }

    function metadataValid() {
        description.value = description.value.trim();
        if (isList && !description.value) {
            description.value = Array.from(form.elements.eventname.value.trim()).slice(0, 25).join('');
        }
        if (!date.reportValidity() || !description.reportValidity()) return false;
        if (!/[\p{L}\p{N}]/u.test(description.value)) {
            message('图片描述至少需要包含一个文字或数字。', true);
            description.focus();
            return false;
        }
        return true;
    }

    function compress(file) {
        return new Promise(function (resolve, reject) {
            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                reject(new Error('请选择JPG、PNG或WebP照片；其他格式请先转换。'));
                return;
            }
            if (file.size > 40 * 1024 * 1024) {
                reject(new Error('原始照片不能超过40 MB，请先缩小照片。'));
                return;
            }
            const objectUrl = URL.createObjectURL(file);
            const image = new Image();
            image.onload = function () {
                URL.revokeObjectURL(objectUrl);
                try {
                    if (!image.naturalWidth || !image.naturalHeight || image.naturalWidth * image.naturalHeight > 50000000) {
                        throw new Error('照片分辨率过大，请先缩小到5000万像素以内。');
                    }
                    const scale = Math.min(1, 1920 / Math.max(image.naturalWidth, image.naturalHeight));
                    const canvas = document.createElement('canvas');
                    canvas.width = Math.max(1, Math.round(image.naturalWidth * scale));
                    canvas.height = Math.max(1, Math.round(image.naturalHeight * scale));
                    const context = canvas.getContext('2d');
                    if (!context) throw new Error('浏览器无法处理照片，请换一个浏览器重试。');
                    context.drawImage(image, 0, 0, canvas.width, canvas.height);
                    canvas.toBlob(function (blob) {
                        if (!blob || blob.type !== 'image/webp') {
                            reject(new Error('浏览器不支持WebP压缩，请使用新版Edge、Chrome或Safari。'));
                        } else if (blob.size > 2 * 1024 * 1024) {
                            reject(new Error('压缩后仍超过2 MB，请先缩小照片后重试。'));
                        } else {
                            resolve(blob);
                        }
                        canvas.width = canvas.height = 0;
                    }, 'image/webp', 0.82);
                } catch (error) {
                    reject(error);
                }
            };
            image.onerror = function () {
                URL.revokeObjectURL(objectUrl);
                reject(new Error('照片无法读取，请重新选择JPG、PNG或WebP图片。'));
            };
            image.src = objectUrl;
        });
    }

    function sendPhoto(data) {
        return new Promise(function (resolve, reject) {
            const request = new XMLHttpRequest();
            request.open('POST', 'albumUpload.php');
            request.timeout = 90000;
            request.upload.onprogress = function (event) {
                if (event.lengthComputable) {
                    progress.value = Math.round(event.loaded / event.total * 100);
                    message(progress.value === 100 ? '上传完成，正在保存照片…' : '正在上传照片：' + progress.value + '%');
                }
            };
            request.onload = function () {
                let result;
                try { result = JSON.parse(request.responseText); } catch (error) {
                    reject(new Error('服务器未返回上传结果，请检查登录状态后重试。'));
                    return;
                }
                if (request.status >= 200 && request.status < 300 && result.url) resolve(result.url);
                else reject(new Error(result.error || '上传失败，请稍后重试。'));
            };
            request.onerror = function () { reject(new Error('网络连接失败，请检查网络后重试。')); };
            request.ontimeout = function () { reject(new Error('上传超时，请检查网络或缩小照片后重试。')); };
            request.send(data);
        });
    }

    uploadButton.addEventListener('click', function () {
        if (!busy && metadataValid()) fileInput.click();
    });

    fileInput.addEventListener('change', async function () {
        const file = fileInput.files[0];
        if (!file || busy || !metadataValid()) return;
        const chosenDate = date.value;
        const chosenDescription = description.value;
        setBusy(true);
        progress.value = 0;
        progress.hidden = false;
        message('正在压缩照片…');
        try {
            const blob = await compress(file);
            const data = new FormData();
            data.append('csrf', form.elements.csrf.value);
            data.append('imgDatd', chosenDate);
            data.append('imgText', chosenDescription);
            data.append('photo', blob, 'photo.webp');
            url.value = await sendPhoto(data);
            uploaded = { url: url.value, date: chosenDate, description: chosenDescription };
            preview.src = url.value;
            previewWrap.hidden = false;
            message(isList ? '上传成功，请点击“提交修改”保存清单。' : '上传成功，请点击“新增相册”保存。');
        } catch (error) {
            message(error.message, true);
        } finally {
            setBusy(false);
            progress.hidden = true;
            fileInput.value = '';
        }
    });

    function metadataChanged() {
        // Checklist display data can change without moving an already uploaded file.
        if (isList) return;
        if (uploaded && url.value === uploaded.url &&
            (date.value !== uploaded.date || description.value.trim() !== uploaded.description)) {
            url.value = '';
            previewWrap.hidden = true;
            uploaded = null;
            message('日期或描述已修改，请重新上传，使目录和文件名与新内容一致。', true);
        }
    }
    date.addEventListener('change', metadataChanged);
    description.addEventListener('input', metadataChanged);
    url.addEventListener('input', function () {
        if (uploaded && url.value !== uploaded.url) {
            uploaded = null;
            previewWrap.hidden = true;
        }
    });

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        if (busy || !form.reportValidity() || (!isList && !metadataValid())) return;
        if (isList && !form.elements.eventname.value.trim()) {
            message('请填写事件标题。', true);
            form.elements.eventname.focus();
            return;
        }
        const data = new FormData(form);
        setBusy(true);
        message(isList ? '正在保存清单…' : '正在保存相册…');
        try {
            const response = await fetch(form.action, { method: 'POST', body: data, credentials: 'same-origin' });
            const text = await response.text();
            if (!response.ok || text.trim() !== '1') {
                let detail;
                try { detail = JSON.parse(text).error; } catch (error) { /* Generic fallback below. */ }
                throw new Error(detail || '保存失败，请检查登录状态后重试。');
            }
            message(isList ? '保存成功，正在返回恋爱清单…' : '新增相册成功，正在返回相册列表…');
            setBusy(false);
            window.location.assign(isList ? 'lovelist.php' : 'loveImgSet.php');
        } catch (error) {
            message(error.message, true);
            setBusy(false);
        }
    });

    window.addEventListener('beforeunload', function (event) {
        if (busy) {
            event.preventDefault();
            event.returnValue = '';
        }
    });
}());
