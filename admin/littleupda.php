<?php
session_start();
require_once __DIR__ . '/littleSaveCommon.php';
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
if (!$id) albumFailure('点滴编号无效。');
little_save_response((int) $id);
