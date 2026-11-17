<?php
session_start();
if (empty($_SESSION['album_csrf'])) {
    $_SESSION['album_csrf'] = bin2hex(random_bytes(32));
}
include_once 'Nav.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, array('options' => array('min_range' => 1)));
$stmt = $connect->prepare('SELECT eventname, icon, imgurl FROM lovelist WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->bind_result($name, $icon, $imgurl);
if (!$id || !$stmt->fetch()) {
    echo '<p>清单事件不存在，请返回列表重试。</p>';
    include_once 'Footer.php';
    exit;
}
$stmt->close();
// Decode the stored template-escaped values, then escape once for HTML attributes.
$name = htmlspecialchars(html_entity_decode($name, ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8');
$imgurl = htmlspecialchars(html_entity_decode($imgurl, ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8');
if ($imgurl === '0') $imgurl = '';

?>

<div class="row">

    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <h4 class="header-title mb-3 size_18">修改事件—— <?php echo $name ?></h4>

                <form id="list-photo-form" action="listupda.php" method="post">
                    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['album_csrf'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="form-group mb-3">
                        <label for="validationCustom01">事件标题</label>
                        <input type="text" name="eventname" class="form-control" id="validationCustom01"
                               placeholder="请输入事件标题" value="<?php echo $name ?>" maxlength="200" required>
                    </div>
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
                    <div class="form-group mb-3">
                        <label for="validationCustom01">完成状态</label>
                        <input type="checkbox" name="icon" id="switch3" <?php if ($icon) { ?> checked<?php } ?>
                               data-switch="success" value="1"
                               onclick="myOnClickHandler(this)">
                        <label style="display:block;" for="switch3" data-on-label="Yes" data-off-label="No"></label>
                    </div>
                    <div class="form-group mb-3" id="img_url"
                         style="display: <?php if ($icon) { ?> block<?php } else { ?>none<?php } ?>">
                        <label for="album-url">图片地址</label>
                        <input type="text" name="imgurl" class="form-control" id="album-url" maxlength="300"
                               placeholder="上传后自动填写，也可输入图片URL（可留空）" value="<?php echo $imgurl ?>">
                    </div>
                    <?php include __DIR__ . '/listPhotoFields.php'; ?>
                    <div class="form-group mb-3 text_right">
                        <input name="id" value="<?php echo $id ?>" type="hidden">
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
