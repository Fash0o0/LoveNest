<?php

$ip = $_SERVER['REMOTE_ADDR'];
$time = gmdate("Y-m-d/H:i:s", time() + 8 * 3600);
// Visitor logging must not wait for an external geolocation service.
$gsd = '未知';
$file = __DIR__ . '/ip.txt';

$fp = @fopen($file, "a");
if ($fp) {
    if (filesize($file) === 0) {
        fwrite($fp, "\xEF\xBB\xBF");
    }
    
    $txt = "\n\n" . "$ip" . "\n" . "$gsd" . "----" . "$time" . "\n";
    fputs($fp, $txt);
    fclose($fp);
}
