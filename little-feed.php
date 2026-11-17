<?php
declare(strict_types=1);
require_once __DIR__ . '/admin/connect.php';
require_once __DIR__ . '/little-data.php';
require_once __DIR__ . '/little-view.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
try {
    $cursor = isset($_GET['cursor']) && is_string($_GET['cursor']) ? json_decode($_GET['cursor'], true) : null;
    if (!is_array($cursor) || !$cursor) throw new InvalidArgumentException('无效的时间轴位置。');
    $batch = little_fetch($connect, $cursor);
    $settingsResult = mysqli_query($connect, 'SELECT * FROM text LIMIT 1');
    $settings = mysqli_fetch_assoc($settingsResult) ?: [];
    ob_start();
    foreach ($batch['rows'] as $row) little_card(little_record($row, $settings));
    echo json_encode(['html'=>ob_get_clean(), 'hasMore'=>$batch['hasMore'], 'cursor'=>$batch['cursor']], JSON_INVALID_UTF8_SUBSTITUTE);
} catch (Throwable $error) {
    http_response_code($error instanceof InvalidArgumentException ? 400 : 500);
    echo json_encode(['error'=>'暂时无法加载回忆，请稍后重试。']);
}
