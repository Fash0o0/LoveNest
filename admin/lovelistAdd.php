<?php
session_start();
if (empty($_SESSION['album_csrf'])) {
    $_SESSION['album_csrf'] = bin2hex(random_bytes(32));
}
include_once 'Nav.php';
?>

<div class="row">

    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <h4 class="header-title mb-3 size_18">新增事件</h4>

                <form id="list-photo-form" action="listaddPost.php" method="post">
                    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['album_csrf'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="form-group mb-3">
                        <label for="validationCustom01">事件标题</label>
                        <input name="eventname" type="text" class="form-control" id="validationCustom01"
                               placeholder="请输入事件标题" value="" maxlength="200" required>
                    </div>
                    <div class="form-group mb-3">
                        <script>
                            function myOnClickHandler(obj) {
                                var input = document.getElementById("switch3");
                                var imgurl = document.getElementById("img_url")
                                console.log(input);
                                if (obj.checked) {
                                    console.log("打开");
                                    input.setAttribute("value", "1");
                                    imgurl.style.display = "block";
                                } else {
                                    console.log("关闭");
                                    input.setAttribute("value", "0");
                                    imgurl.style.display = "none";
                                }
                            }
                        </script>
                        <label for="validationCustom01">完成状态</label>
                        <input type="checkbox" name="icon" id="switch3" value="1" data-switch="success"
                               onclick="myOnClickHandler(this)" checked>
                        <label id="switchurl" style="display:block;" for="switch3" data-on-label="Yes"
                               data-off-label="No"></label>
                    </div>
                    <div class="form-group mb-3" id="img_url">
                        <label for="album-url">图片地址</label>
                        <input type="text" name="img" class="form-control" id="album-url" maxlength="300"
                               placeholder="上传后自动填写，也可输入图片URL（可留空）" value="">
                    </div>
                    <?php include __DIR__ . '/listPhotoFields.php'; ?>
                    <div class="form-group mb-3 text_right">
                        <button class="btn btn-success mr-2" type="button" id="album-upload">上传照片</button>
                        <button class="btn btn-primary" type="submit" id="album-save">提交修改</button>
                    </div>
                </form>

            </div> <!-- end card-body-->
        </div> <!-- end card-->
    </div> <!-- end col-->
</div>

<script src="assets/js/album-upload.js?v=3" defer></script>

<?php
include_once 'Footer.php';
?>

</body>
</html>
