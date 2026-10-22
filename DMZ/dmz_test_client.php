<?php

$root = dirname(__DIR__);
chdir($root);
require_once $root . '/rabbitMQLib.inc';

// Will default to AAPL if no symbol is given
$symbol = $argv[1] ?? 'AAPL';

$client = new rabbitMQClient('DMZ/dmzRabbitMQ.ini', 'dmzServer');
$response = $client->send_request([
    'type' => 'stock_price', 
    'symbol' => $symbol]);
echo json_encode($response, JSON_PRETTY_PRINT), PHP_EOL;