<?php
session_start();
require __DIR__ . '/listPhotoCommon.php';
$connect = albumAuthorizeRequest();
$id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, array('options' => array('min_range' => 1)));
if (!$id) albumFailure('清单事件编号无效，请返回列表重试。');
list($name, $icon, $img) = listPhotoValues('imgurl');
$stmt = $connect->prepare('SELECT id FROM lovelist WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows !== 1) albumFailure('清单事件不存在，请返回列表重试。', 404);
$stmt->close();
$stmt = $connect->prepare('UPDATE lovelist SET eventname = ?, icon = ?, imgurl = ? WHERE id = ?');
$stmt->bind_param('sisi', $name, $icon, $img, $id);
if (!$stmt->execute()) albumFailure('修改事件失败，请稍后重试。', 500);
header('Content-Type: text/plain; charset=utf-8');
echo '1';
