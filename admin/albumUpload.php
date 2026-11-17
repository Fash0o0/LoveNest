<?php
session_start();
require __DIR__ . '/albumUploadCommon.php';
albumAuthorizeRequest();
list($date, $description) = albumMetadata();
$filename = albumFilename($description);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!isset($_FILES['photo']) || !is_array($_FILES['photo']) ||
    !isset($_FILES['photo']['error']) || is_array($_FILES['photo']['error']) ||
    $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    albumFailure('没有收到完整照片，请重新选择或缩小照片后重试。');
}
$photo = $_FILES['photo'];
if (!is_uploaded_file($photo['tmp_name']) || $photo['size'] <= 0 || $photo['size'] > 2 * 1024 * 1024) {
    albumFailure('压缩后的照片不能超过2 MB，请缩小照片后重试。', 413);
}
$info = @getimagesize($photo['tmp_name']);
if (!$info || $info[2] !== IMAGETYPE_WEBP || $info[0] < 1 || $info[1] < 1 ||
    $info[0] > 1920 || $info[1] > 1920) {
    albumFailure('请上传经过压缩的有效WebP照片，最长边不超过1920像素。');
}
if (!function_exists('imagecreatefromwebp') || !function_exists('imagewebp')) {
    albumFailure('服务器暂不支持WebP图片处理，请联系管理员。', 503);
}
$image = @imagecreatefromwebp($photo['tmp_name']);
if (!$image) {
    albumFailure('照片内容无法读取，请换一张照片重试。');
}

$relativeDirectory = 'images/' . substr($date, 0, 4) . '/' . substr($date, 5, 2);
$directory = dirname(__DIR__);
foreach (explode('/', $relativeDirectory) as $part) {
    $directory .= '/' . $part;
    if (is_link($directory) || (!is_dir($directory) && !@mkdir($directory, 0755)) || !is_dir($directory)) {
        imagedestroy($image);
        albumFailure('无法创建照片目录，请检查服务器存储空间和目录权限。', 500);
    }
}

// Exclusive creation prevents overwrites even for simultaneous requests.
$handle = false;
for ($number = 1; $number <= 1000; $number++) {
    $storedName = $filename . ($number === 1 ? '' : '-' . $number) . '.webp';
    $path = $directory . '/' . $storedName;
    $handle = @fopen($path, 'x+b');
    if ($handle) break;
    if (!file_exists($path)) {
        imagedestroy($image);
        albumFailure('照片无法写入，请检查存储空间和目录权限。', 500);
    }
}
if (!$handle) {
    imagedestroy($image);
    albumFailure('同名照片过多，请换一个图片描述。', 409);
}

// Re-encode to strip metadata and content appended to the uploaded file.
imagealphablending($image, false);
imagesavealpha($image, true);
$written = @imagewebp($image, $handle, 85);
fflush($handle);
$stat = fstat($handle);
fclose($handle);
imagedestroy($image);
if (!$written || !$stat || $stat['size'] < 1 || $stat['size'] > 2 * 1024 * 1024) {
    @unlink($path);
    albumFailure('照片保存失败，请缩小照片后重试。', 500);
}
echo json_encode(array('url' => '/' . $relativeDirectory . '/' . $storedName), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
