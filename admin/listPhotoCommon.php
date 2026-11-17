<?php
require_once __DIR__ . '/albumUploadCommon.php';

function listPhotoValues($imageField)
{
    $name = isset($_POST['eventname']) && is_string($_POST['eventname']) ? trim($_POST['eventname']) : '';
    if ($name === '' || !preg_match('//u', $name) || preg_match_all('/./us', $name) > 200 ||
        preg_match('/[\x00-\x1f\x7f]/u', $name)) {
        albumFailure('请填写1至200个字符的事件标题。');
    }
    $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    if (preg_match_all('/./us', $name) > 200) albumFailure('事件标题过长，请缩短后重试。');
    $url = isset($_POST[$imageField]) && is_string($_POST[$imageField]) ? trim($_POST[$imageField]) : '';
    if ($url === '' || $url === '0') return array($name, ($_POST['icon'] ?? '') === '1' ? 1 : 0, '0');
    if (!preg_match('//u', $url) || preg_match_all('/./us', $url) > 300 ||
        preg_match('/[\x00-\x20\x7f<>"\x27]/u', $url)) {
        albumFailure('请填写有效图片URL，地址不能超过300个字符。');
    }
    if (strpos($url, '/images/') === 0) {
        $root = realpath(dirname(__DIR__) . '/images');
        $path = realpath(dirname(__DIR__) . $url);
        if (!$root || !$path || strpos($path, $root . DIRECTORY_SEPARATOR) !== 0 ||
            !is_file($path) || strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'webp') {
            albumFailure('本站照片不存在，请重新上传。');
        }
    } elseif (!filter_var($url, FILTER_VALIDATE_URL) ||
        !in_array(strtolower(parse_url($url, PHP_URL_SCHEME)), array('https', 'http'), true)) {
        albumFailure('图片URL需要以https://或http://开头，或使用刚上传的本站照片。');
    }
    $url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    if (preg_match_all('/./us', $url) > 300) albumFailure('图片URL过长，请使用更短的地址。');
    return array($name, ($_POST['icon'] ?? '') === '1' ? 1 : 0, $url);
}
