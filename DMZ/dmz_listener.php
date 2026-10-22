<?php

$repoRoot = dirname(__DIR__);
chdir($repoRoot);
require_once $repoRoot . '/rabbitMQLib.inc';
require_once __DIR__ . '/stock_price_data.php';

function processDmzRequest($request): array
{
    if (!is_array($request)) {
        return stockPriceError('', 'invalid_request', 'Expected a JSON object.');
    }
    if (($request['type'] ?? null) !== 'stock_price') {
        return stockPriceError('', 'unsupported_request', 'Supported type: stock_price.');
    }
    if (!isset($request['symbol']) || !is_string($request['symbol'])) {
        return stockPriceError('', 'missing_symbol', 'Request must include a string symbol.');
    }
    return getStockPrice($request['symbol']);
}

// Making sure the API key is set
if (!getenv('TWELVE_DATA_API_KEY')) {
    fwrite(STDERR, "TWELVE_DATA_API_KEY environment variable is not set.\n");
    exit(1);
}

try {
    $server = new rabbitMQServer('DMZ/dmzRabbitMQ.ini', 'dmzServer');
    fwrite(STDOUT, "DMZ listener waiting for stock_price requests...\n");
    $server->process_requests('processDmzRequest');
} catch (Throwable $e) {
    fwrite(STDERR, "DMZ listener error: " . $e->getMessage() . PHP_EOL);
    exit(1);
}