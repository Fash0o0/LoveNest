<?php
session_start();
include_once 'Nav.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
$momentRow = null;
if ($id) {
    $stmt = $connect->prepare('SELECT * FROM article WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id); $stmt->execute();
    $momentRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
if ($momentRow) require __DIR__ . '/littleForm.php';
else echo '<p class="alert alert-warning">记录不存在，请返回点滴列表。</p>';
include_once 'Footer.php';
?>
</body></html>
