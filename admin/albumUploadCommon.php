<?php
function albumFailure($message, $status = 400)
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('error' => $message), JSON_UNESCAPED_UNICODE);
    exit;
}

function albumAuthorizeRequest()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Allow: POST');
        albumFailure('请使用上传或保存按钮提交。', 405);
    }
    if (empty($_SESSION['loginadmin']) || !is_string($_SESSION['loginadmin'])) {
        albumFailure('登录已失效，请重新登录后台。', 401);
    }
    if (empty($_SESSION['album_csrf']) || !isset($_POST['csrf']) ||
        !is_string($_POST['csrf']) || !hash_equals($_SESSION['album_csrf'], $_POST['csrf'])) {
        albumFailure('页面已失效，请刷新后重试。', 403);
    }
    require __DIR__ . '/connect.php';
    $stmt = $connect->prepare('SELECT id FROM login WHERE user = ? LIMIT 1');
    $stmt->bind_param('s', $_SESSION['loginadmin']);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows !== 1) {
        albumFailure('登录已失效，请重新登录后台。', 401);
    }
    $stmt->close();
    return $connect;
}

function albumMetadata()
{
    $date = isset($_POST['imgDatd']) && is_string($_POST['imgDatd']) ? trim($_POST['imgDatd']) : '';
    $description = isset($_POST['imgText']) && is_string($_POST['imgText']) ? trim($_POST['imgText']) : '';
    if (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D', $date)) {
        albumFailure('请选择有效日期。');
    }
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    if (!$parsed || $parsed->format('Y-m-d') !== $date || $date < '1900-01-01') {
        albumFailure('请选择有效日期。');
    }
    if ($description === '' || !preg_match('//u', $description) ||
        preg_match_all('/./us', $description) > 25 || preg_match('/[\x00-\x1f\x7f]/u', $description)) {
        albumFailure('请填写1至25个字符的图片描述。');
    }
    return array($date, $description);
}

function albumFilename($description)
{
    $name = preg_replace('/[^\p{L}\p{N}\p{M}_-]+/u', '-', $description);
    $name = trim($name, '-_');
    if ($name === '') {
        albumFailure('图片描述至少需要包含一个文字或数字，才能作为文件名。');
    }
    if (preg_match('/^(CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])$/i', $name)) {
        $name = '照片-' . $name;
    }
    return $name;
}
