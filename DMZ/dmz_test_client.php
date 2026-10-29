<?php

$root = dirname(__DIR__);
chdir($root);
require_once $root . '/rabbitMQLib.inc';

$type = $argv[1] ?? 'stock_quote';
$symbol = $argv[2] ?? 'AAPL';
$request = ['type' => $type, 'symbol' => $symbol];
if ($type === 'historical_chart') {
    $request['timeframe'] = $argv[3] ?? '1Day';
}

$client = new rabbitMQClient('DMZ/dmzRabbitMQ.ini', 'dmzServer');
$response = $client->send_request($request);
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;