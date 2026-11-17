<?php
session_start();
require __DIR__ . '/listPhotoCommon.php';
$connect = albumAuthorizeRequest();
list($name, $icon, $img) = listPhotoValues('img');
$stmt = $connect->prepare('INSERT INTO lovelist (eventname, icon, imgurl) VALUES (?, ?, ?)');
$stmt->bind_param('sis', $name, $icon, $img);
if (!$stmt->execute()) albumFailure('新增事件失败，请稍后重试。', 500);
header('Content-Type: text/plain; charset=utf-8');
echo '1';
