<?php
session_start();
require __DIR__ . '/albumUploadCommon.php';
$connect = albumAuthorizeRequest();
list($imgDatd, $description) = albumMetadata();
$imgUrl = isset($_POST['imgUrl']) && is_string($_POST['imgUrl']) ? trim($_POST['imgUrl']) : '';
if ($imgUrl === '' || !preg_match('//u', $imgUrl) || preg_match_all('/./us', $imgUrl) > 200 ||
    preg_match('/[\x00-\x20\x7f<>"\x27]/u', $imgUrl)) {
    albumFailure('请上传照片或填写有效图片URL，地址不能超过200个字符。');
}
if (strpos($imgUrl, '/images/') === 0) {
    $root = realpath(dirname(__DIR__) . '/images');
    $photoPath = realpath(dirname(__DIR__) . $imgUrl);
    if (!$root || !$photoPath || strpos($photoPath, $root . DIRECTORY_SEPARATOR) !== 0 ||
        !is_file($photoPath) || strtolower(pathinfo($photoPath, PATHINFO_EXTENSION)) !== 'webp') {
        albumFailure('本站照片不存在，请重新上传。');
    }
} elseif (!filter_var($imgUrl, FILTER_VALIDATE_URL) ||
    !in_array(strtolower(parse_url($imgUrl, PHP_URL_SCHEME)), array('https', 'http'), true)) {
    albumFailure('图片URL需要以https://或http://开头，或使用刚上传的本站照片。');
}
// Templates expect escaped text in this table; SQL parameters keep quotes safe.
$imgText = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');
$storedUrl = htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8');
if (preg_match_all('/./us', $storedUrl) > 200) {
    albumFailure('图片URL过长，请使用更短的地址。');
}
$stmt = $connect->prepare('INSERT INTO loveImg (imgDatd, imgText, imgUrl) VALUES (?, ?, ?)');
$stmt->bind_param('sss', $imgDatd, $imgText, $storedUrl);
if (!$stmt->execute()) {
    albumFailure('相册保存失败，请稍后重试。', 500);
}
header('Content-Type: text/plain; charset=utf-8');
echo '1';
