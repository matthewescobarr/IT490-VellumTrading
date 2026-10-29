<?php

function marketDataError($symbol, $code, $message, $httpStatus = null)
{
    return [
        'success' => false,
        'symbol' => $symbol,
        'error' => ['code' => $code, 'message' => $message],
        'http_status' => $httpStatus,
        'source' => 'Alpaca',
    ];
}

// Send a request to Alpaca and return the JSON response
function alpacaRequest($symbol, $path, $parameters = [])
{
    // Tells alpaca what market data to use
    $parameters['feed'] = 'iex';
    // completed url would look like https://data.alpaca.markets/v2/stocks/AAPL/quotes/latest?feed=iex
    $url = 'https://data.alpaca.markets/v2/stocks/' . rawurlencode($symbol)
         . '/' . $path . '?' . http_build_query($parameters);

    // initialize curl request with alpaca
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        // return the response as a string
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
        // set the Alpaca API keys for authentication
        CURLOPT_HTTPHEADER => [
            // these keys are gotten when you load your keys before running the lisnter
            'APCA-API-KEY-ID: ' . getenv('ALPACA_API_KEY_ID'),
            'APCA-API-SECRET-KEY: ' . getenv('ALPACA_API_SECRET_KEY'),
        ],
    ]);

    // sends the request to alpaca 
    $body = curl_exec($ch);
    $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    // converts the Alpaca response and if not then it will return an empty string
    $data = json_decode($body ?: '', true);

    // checks if the request was successful 
    if ($httpStatus !== 200 || !is_array($data)) {
        return marketDataError($symbol, 'api_error', 'Alpaca request failed.', $httpStatus);
    }

    return ['success' => true, 'data' => $data];
}

// Bid and ask for simulated buy/sell trades
function getStockQuote($symbol)
{
    $symbol = strtoupper(trim($symbol));
    $result = alpacaRequest($symbol, 'quotes/latest');
    if (!$result['success']) {
        return $result;
    }

    $bid = $result['data']['quote']['bp'] ?? null;
    $ask = $result['data']['quote']['ap'] ?? null;
    if (!is_numeric($bid) || !is_numeric($ask) || $bid <= 0 || $ask <= 0 || $bid > $ask) {
        return marketDataError($symbol, 'invalid_quote', 'Alpaca returned an invalid bid or ask.');
    }

    return [
        'success' => true,
        'symbol' => $symbol,
        'bid' => (string) $bid,
        'ask' => (string) $ask,
        'source' => 'Alpaca',
        'retrieved_at' => gmdate('Y-m-d\TH:i:s\Z'),
    ];
}

// Last traded price for stock displays
function getStockPrice($symbol)
{
    $symbol = strtoupper(trim($symbol));
    $result = alpacaRequest($symbol, 'trades/latest');
    if (!$result['success']) {
        return $result;
    }

    $price = $result['data']['trade']['p'] ?? null;
    if (!is_numeric($price) || $price <= 0) {
        return marketDataError($symbol, 'invalid_price', 'Alpaca returned an invalid stock price.');
    }

    return [
        'success' => true,
        'symbol' => $symbol,
        'price' => (string) $price,
        'source' => 'Alpaca',
        'retrieved_at' => gmdate('Y-m-d\TH:i:s\Z'),
    ];
}

// Closing prices for historical charts
function getHistoricalChart($symbol, $timeframe = '1Day', $start = null, $end = null)
{
    $symbol = strtoupper(trim($symbol));
    $start = $start ?? gmdate('Y-m-d', strtotime('-30 days'));
    $end = $end ?? gmdate('Y-m-d\TH:i:s\Z');

    $result = alpacaRequest($symbol, 'bars', [
        'timeframe' => $timeframe,
        'start' => $start,
        'end' => $end,
        'limit' => 1000,
        'adjustment' => 'raw',
    ]);
    if (!$result['success']) {
        return $result;
    }

    $history = [];
    foreach ($result['data']['bars'] ?? [] as $bar) {
        $history[] = [
            'timestamp' => $bar['t'],
            'close' => (string) $bar['c'],
        ];
    }

    return [
        'success' => true,
        'symbol' => $symbol,
        'timeframe' => $timeframe,
        'bars' => $history,
        'next_page_token' => $result['data']['next_page_token'] ?? null,
        'source' => 'Alpaca',
        'retrieved_at' => gmdate('Y-m-d\TH:i:s\Z'),
    ];
}

// Run this file directly to test Alpaca without RabbitMQ
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $type = $argv[1] ?? 'quote';
    $symbol = $argv[2] ?? 'AAPL';

    switch ($type) {
        case 'quote':
            $response = getStockQuote($symbol);
            break;
        case 'price':
            $response = getStockPrice($symbol);
            break;
        case 'history':
            $response = getHistoricalChart($symbol, $argv[3] ?? '1Day');
            break;
        default:
            $response = marketDataError($symbol, 'invalid_type', 'Use quote, price, or history.');
    }

    echo json_encode($response, JSON_PRETTY_PRINT), PHP_EOL;
    exit($response['success'] ? 0 : 1);
}