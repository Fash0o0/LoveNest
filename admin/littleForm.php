<?php
require_once dirname(__DIR__) . '/little-service.php';
if (empty($_SESSION['album_csrf'])) $_SESSION['album_csrf'] = bin2hex(random_bytes(32));
$editing = !empty($momentRow);
$row = $editing ? $momentRow : ['id'=>0, 'articletitle'=>'', 'articletext'=>'', 'articlename'=>$text['boy'], 'articletime'=>(new DateTimeImmutable('now', new DateTimeZone('Asia/Shanghai')))->format('Y-m-d H:i:s'), 'articleimages'=>'[]'];
$record = little_record($row, $text);
$plainBody = $editing ? little_parts((string) $row['articletext'])['body'] : '';
$formDate = str_replace(' ', 'T', substr((string) $row['articletime'], 0, 16));
if (strlen($formDate) === 10) $formDate .= 'T00:00';
?>
<link href="/admin/editormd/css/editormd.css" rel="stylesheet">
<link href="assets/css/little-editor.css?v=1" rel="stylesheet">
<div class="row"><div class="col-lg-12"><div class="card"><div class="card-body">
    <h4 class="header-title mb-3 size_18"><?= $editing ? '编辑点滴' : '发布点滴' ?></h4>
    <form id="little-editor-form" action="<?= $editing ? 'littleupda.php' : 'littleAddPost.php' ?>" method="post">
        <input type="hidden" name="csrf" value="<?= little_escape($_SESSION['album_csrf']) ?>">
        <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int) $row['id'] ?>"><?php endif; ?>
        <div class="form-row">
            <div class="form-group col-sm-4"><label for="moment-author">发布者</label><select class="form-control" id="moment-author" name="articlename"><?php foreach (['boy','girl'] as $person): $name = html_entity_decode((string) $text[$person], ENT_QUOTES, 'UTF-8'); ?><option value="<?= little_escape($name) ?>" <?= $record['author'] === $name ? 'selected' : '' ?>><?= little_escape($name) ?></option><?php endforeach; ?></select></div>
            <div class="form-group col-sm-5"><label for="moment-date">记录日期</label><input class="form-control" type="datetime-local" id="moment-date" name="articletime" value="<?= little_escape($formDate) ?>" required></div>
        </div>
        <div class="form-group"><label for="moment-title">标题</label><input class="form-control" id="moment-title" name="articletitle" value="<?= little_escape($record['title']) ?>" maxlength="100" required placeholder="为这一天写个标题"></div>
        <label for="moment-body">正文</label><p class="text-muted">记录当时的心情。照片请通过下方按钮单独上传。</p>
        <div id="test-editor"><textarea id="moment-body" name="articletext" rows="12"><?= little_escape($plainBody) ?></textarea></div>
        <fieldset class="moment-photos"><legend>记录照片</legend><p class="text-muted">可多次选择、追加图片，不限总张数。按顺序逐张压缩上传，首张作为封面；下方可调整顺序或移除照片。</p>
            <input type="hidden" name="articleimages" id="moment-images" value="<?= little_escape(json_encode($record['images'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>">
            <input id="moment-files" type="file" accept="image/jpeg,image/png,image/webp" multiple hidden>
            <button class="btn btn-success" type="button" id="moment-choose">选择上传图片</button>
            <span id="moment-image-count" class="ml-2 text-muted"></span>
            <div id="moment-photo-list" class="moment-photo-list" aria-label="点滴照片列表"></div>
        </fieldset>
        <div id="moment-status" role="status" aria-live="polite" class="my-3"></div>
        <progress id="moment-progress" max="100" value="0" class="w-100 mb-3" aria-label="照片上传进度" hidden></progress>
        <div class="text_right"><button class="btn btn-primary" type="submit" id="moment-save"><?= $editing ? '保存修改' : '发布点滴' ?></button></div>
    </form>
</div></div></div></div>
<script src="/admin/editormd/editormd.js"></script>
<script>
$(function () {
    if (window.editormd) window.littleEditor = editormd('test-editor', {
        height: 380, htmlDecode: true, saveHTMLToTextarea: true, path: '/admin/editormd/lib/',
        toolbarIcons: function () { return ['undo','redo','|','bold','italic','del','quote','|','h1','h2','h3','|','list-ul','list-ol','hr','link','|','preview','watch','fullscreen']; }
    });
});
</script>
<script src="assets/js/little-editor.js?v=1" defer></script>
