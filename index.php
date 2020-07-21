<?php

require __DIR__ .'/vendor/autoload.php';

use Lambq\Nokvm\Nokvm;

// 高德开放平台应用 API Key
$url = 'http://42.51.1.66/api/';
$key = 'A9ecIhPCYWZh6A1ZHsiJAQXP';
$w = new Nokvm($url, $key);

echo "获取nokvm数据：\n";

$response = $w->info();
print_r($response);