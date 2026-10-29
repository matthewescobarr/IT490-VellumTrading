<?php

$repoRoot = dirname(__DIR__);
chdir($repoRoot);
require_once $repoRoot . '/rabbitMQLib.inc';
require_once __DIR__ . '/stock_price_data.php';

function processDmzRequest($request): array
{
    if (!is_array($request)) {
        return marketDataError('', 'invalid_request', 'Expected a JSON object.');
    }
    if (!isset($request['symbol']) || !is_string($request['symbol'])) {
        return marketDataError('', 'missing_symbol', 'Request must include a string symbol.');
    }

    $symbol = $request['symbol'];
    switch ($request['type'] ?? '') {
        case 'stock_price':
            return getStockPrice($symbol);
        case 'stock_quote':
            return getStockQuote($symbol);
        case 'historical_chart':
            $timeframe = $request['timeframe'] ?? '1Day';
            $start = $request['start'] ?? null;
            $end = $request['end'] ?? null;
            if (!is_string($timeframe) || ($start !== null && !is_string($start)) ||
                ($end !== null && !is_string($end))) {
                return marketDataError($symbol, 'invalid_parameters', 'Invalid chart parameters.');
            }
            return getHistoricalChart($symbol, $timeframe, $start, $end);
        default:
            return marketDataError($symbol, 'unsupported_request', 'Supported types: stock_price, stock_quote, historical_chart.');
    }
}

if (!getenv('ALPACA_API_KEY_ID') || !getenv('ALPACA_API_SECRET_KEY')) {
    fwrite(STDERR, "Alpaca API credentials are not set.\n");
    exit(1);
}

try {
    $server = new rabbitMQServer('DMZ/dmzRabbitMQ.ini', 'dmzServer');
    fwrite(STDOUT, "DMZ listener waiting for Alpaca requests...\n");
    $server->process_requests('processDmzRequest');
} catch (Throwable $e) {
    fwrite(STDERR, "DMZ listener error: " . $e->getMessage() . PHP_EOL);
    exit(1);
}