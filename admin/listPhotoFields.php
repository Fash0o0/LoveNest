<?php // Shared upload controls for checklist add/edit; photo metadata is used only at upload time. ?>
<div class="form-group mb-3">
    <label for="album-date">照片归档日期</label>
    <input id="album-date" class="form-control col-sm-4" type="date" name="imgDatd" min="1900-01-01" max="9999-12-31" value="<?php echo date('Y-m-d') ?>">
    <small class="form-text text-muted">用于上传照片的年月目录，不影响清单完成状态。</small>
</div>
<div class="form-group mb-3">
    <label for="album-description">照片文件名</label>
    <input id="album-description" name="imgText" type="text" class="form-control" maxlength="25" placeholder="留空使用事件标题的前25个字符">
    <small class="form-text text-muted">上传后修改标题或这些选项，不会重命名已上传的照片。</small>
</div>
<input id="album-file" type="file" accept="image/jpeg,image/png,image/webp" hidden>
<div id="album-upload-status" class="mb-3" role="status" aria-live="polite"></div>
<progress id="album-upload-progress" class="w-100 mb-3" max="100" value="0" aria-label="照片上传进度" hidden></progress>
<div id="album-preview-wrap" class="mb-3" hidden>
    <img id="album-preview" alt="上传照片预览" style="max-width:100%;max-height:320px;border-radius:8px;">
</div>
