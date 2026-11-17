<?php
// Existing article URLs continue to open a complete record.
$littleSingleId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
if (!$littleSingleId) {
    http_response_code(404);
    $littleSingleId = PHP_INT_MAX;
}
require __DIR__ . '/little.php';
