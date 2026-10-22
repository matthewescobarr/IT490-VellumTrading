<?php

declare(strict_types=1);

function stockPriceError(
    string $symbol,
    string $code,
    string $message,
    ?int $httpStatus = null
): array {
    return [
        'success' => false,
        'symbol' => $symbol,
        'error' => ['code' => $code, 'message' => $message],
        'http_status' => $httpStatus,
        'source' => 'Twelve Data',
    ];
}


function getStockPrice(string $symbol): array
{
    $symbol = strtoupper(trim($symbol));

    // formats the symbol
    if (!preg_match('/^[A-Z0-9][A-Z0-9.\-]{0,14}$/', $symbol)) {
        return stockPriceError($symbol, 'invalid_symbol', 'Invalid stock symbol format.');
    }

    $apiKey = getenv('TWELVE_DATA_API_KEY');
    if (!is_string($apiKey) || trim($apiKey) === '') {
        return stockPriceError($symbol, 'missing_api_key', 'TWELVE_DATA_API_KEY is not configured.');
    }

    $url = 'https://api.twelvedata.com/price?' . http_build_query([
        'symbol' => $symbol,
    ], '', '&', PHP_QUERY_RFC3986);

    $ch = curl_init($url);
    if ($ch === false) {
        return stockPriceError($symbol, 'request_setup_failed', 'Could not initialize the HTTP request.');
    }

    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Authorization: apikey ' . trim($apiKey),
        ],
    ];

    if (!curl_setopt_array($ch, $options)) {
        curl_close($ch);
        return stockPriceError($symbol, 'request_setup_failed', 'Could not configure the HTTP request.');
    }

    $body = curl_exec($ch);
    $httpStatus = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $transferError = curl_error($ch);
    curl_close($ch);

    if ($body === false) {
        return stockPriceError($symbol, 'network_error', $transferError ?: 'HTTP request failed.', $httpStatus);
    }

    $data = json_decode($body, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
        return stockPriceError($symbol, 'invalid_json', 'Twelve Data returned invalid JSON.', $httpStatus);
    }

    if ($httpStatus === 429 || ($data['code'] ?? null) == 429) {
        return stockPriceError($symbol, 'rate_limited', 'Twelve Data request limit reached. Try again later.', $httpStatus);
    }

    if ($httpStatus !== 200 || ($data['status'] ?? null) === 'error') {
        $message = isset($data['message']) && is_string($data['message'])
            ? substr($data['message'], 0, 300)
            : 'Twelve Data did not accept the request.';
        return stockPriceError($symbol, 'provider_error', $message, $httpStatus);
    }

    $price = $data['price'] ?? null;
    if (!is_string($price) && !is_int($price) && !is_float($price)) {
        return stockPriceError($symbol, 'missing_price', 'Price was missing from the response.', $httpStatus);
    }
    if (!is_numeric($price) || !is_finite((float) $price) || (float) $price <= 0) {
        return stockPriceError($symbol, 'invalid_price', 'Price was not a valid positive number.', $httpStatus);
    }

    return [
        'success' => true,
        'symbol' => $symbol,
        'price' => (string) $price,
        'source' => 'Twelve Data',
        'retrieved_at' => gmdate('Y-m-d\TH:i:s\Z'),
    ];
}

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath((string) $_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    if (PHP_SAPI !== 'cli') {
        http_response_code(404);
        exit;
    }
    $symbol = $argv[1] ?? 'AAPL';
    $result = getStockPrice($symbol);
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;
    exit($result['success'] ? 0 : 1);
}