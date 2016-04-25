<?php
$file = dirname(dirname(dirname(dirname(__FILE__)))) . '/inc/utf8.php';
$str=file_get_contents($file);
file_put_contents($file, str_replace("‌", "",$str));