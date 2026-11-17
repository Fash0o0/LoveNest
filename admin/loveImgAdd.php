<?php
session_start();
if (empty($_SESSION['album_csrf'])) {
    $_SESSION['album_csrf'] = bin2hex(random_bytes(32));
}
include_once 'Nav.php';
$inv_date = date("Y-m-d");
?>

<div class="row">

    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <h4 class="header-title mb-3 size_18">新增图片</h4>

                <form id="album-add-form" action="ImgAddPost.php" method="post">
                    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['album_csrf'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="form-group mb-3">
                        <label for="album-date">日期</label>
                        <input class="form-control col-sm-4" id="album-date" type="date" name="imgDatd" min="1900-01-01" max="9999-12-31" value="<?php echo $inv_date ?>" required>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="album-description">图片描述<span class="margin_left badge badge-success-lighten">最多25个字符，也用作上传文件名</span></label>
                        <input id="album-description" name="imgText" type="text" class="form-control" placeholder="例如：一起去看海" maxlength="25" required>
                    </div>

                    <div class="form-group mb-3" id="img_url">
                        <label for="album-url">图片URL</label>
                        <input id="album-url" type="text" name="imgUrl" class="form-control" placeholder="上传后自动填写，也可输入图片URL" required>
                    </div>
                    <input id="album-file" type="file" accept="image/jpeg,image/png,image/webp" hidden>
                    <div id="album-upload-status" class="mb-3" role="status" aria-live="polite"></div>
                    <progress id="album-upload-progress" class="w-100 mb-3" max="100" value="0" aria-label="照片上传进度" hidden></progress>
                    <div class="mb-3" id="album-preview-wrap" hidden>
                        <img id="album-preview" alt="上传照片预览" style="max-width:100%;max-height:320px;border-radius:8px;">
                    </div>
                    <div class="form-group mb-3 text_right">
                        <button class="btn btn-success mr-2" type="button" id="album-upload">上传照片</button>
                        <button class="btn btn-primary" type="submit" id="album-save">新增相册</button>
                    </div>
                </form>

            </div> <!-- end card-body-->
        </div> <!-- end card-->
    </div> <!-- end col-->
</div>

<script src="assets/js/album-upload.js?v=1" defer></script>

<?php
include_once 'Footer.php';
?>

</body>
</html>
