<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/little-service.php';
require_once __DIR__ . '/albumUploadCommon.php';

function little_ensure_schema($connection): void
{
    $columns = $connection->query("SHOW COLUMNS FROM article LIKE 'articleimages'");
    if (!$columns) throw new RuntimeException('无法读取点滴表结构。');
    if ($columns->num_rows === 0) {
        // Additive, nullable migration: old records keep their original text and images.
        try {
            if (!$connection->query('ALTER TABLE article ADD COLUMN articleimages LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL')) throw new RuntimeException('数据库升级失败。');
        } catch (Throwable $error) {
            $columns = $connection->query("SHOW COLUMNS FROM article LIKE 'articleimages'");
            if (!$columns || $columns->num_rows === 0) throw new RuntimeException('请先导入 migrations/20261023-little-images.sql，或为数据库账号授予 ALTER 权限。');
        }
    }
}

function little_save($connection, array $input, int $id = 0): void
{
    $result = $connection->query('SELECT boy, girl FROM text LIMIT 1');
    $settings = $result ? $result->fetch_assoc() : [];
    $names = array_map(static fn(string $person): string => html_entity_decode((string) ($settings[$person] ?? ''), ENT_QUOTES, 'UTF-8'), ['boy', 'girl']);
    $data = little_validate($input, $names);
    foreach ($data['images'] as $url) {
        if (strncmp($url, '/images/', 8) === 0 || strncmp($url, 'images/', 7) === 0) {
            $root = realpath(dirname(__DIR__) . '/images');
            $path = realpath(dirname(__DIR__) . '/' . ltrim($url, '/'));
            if (!$root || !$path || strpos($path, $root . DIRECTORY_SEPARATOR) !== 0 || !is_file($path)) throw new InvalidArgumentException('照片文件不存在，请移除后重新上传。');
        }
    }
    little_ensure_schema($connection);
    if ($id > 0) {
        $exists = $connection->prepare('SELECT id FROM article WHERE id = ? LIMIT 1');
        $exists->bind_param('i', $id); $exists->execute(); $exists->store_result();
        if ($exists->num_rows !== 1) throw new InvalidArgumentException('这条点滴已经不存在，请返回列表。');
        $exists->close();
    }
    $json = json_encode($data['images'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    $author = little_escape($data['author']);
    if (preg_match_all('/./us', $author) > 20) throw new InvalidArgumentException('发布者名称超过数据库允许长度。');
    if ($id > 0) {
        $stmt = $connection->prepare('UPDATE article SET articletitle = ?, articletext = ?, articletime = ?, articlename = ?, articleimages = ? WHERE id = ?');
        $stmt->bind_param('sssssi', $data['title'], $data['body'], $data['date'], $author, $json, $id);
    } else {
        $stmt = $connection->prepare('INSERT INTO article (articletitle, articletext, articletime, articlename, articleimages) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('sssss', $data['title'], $data['body'], $data['date'], $author, $json);
    }
    if (!$stmt->execute()) throw new RuntimeException('点滴保存失败，请稍后重试。');
    $stmt->close();
}

function little_save_response(int $id = 0): void
{
    $connection = albumAuthorizeRequest();
    try {
        little_save($connection, $_POST, $id);
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: no-store');
        echo '1';
    } catch (Throwable $error) {
        if ($error instanceof InvalidArgumentException) albumFailure($error->getMessage());
        error_log('Little save failed: ' . $error->getMessage());
        albumFailure('保存失败，请确认点滴数据库升级已完成后重试。', 500);
    }
}
