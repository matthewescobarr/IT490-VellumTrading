<?php
header('Content-Type: application/json; charset=utf-8');
$allowed_origin = ['http://www.sample.com', 'http://100.115.133.49'];

if (isset($_SERVER['HTTP_ORIGIN']) &&
   in_array( $_SERVER['HTTP_ORIGIN'], $allowed_origin, true)) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
}


header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Use POST with a JSON body.'
    ]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data) || empty($data['symbol']) || !is_string($data['symbol'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'A stock symbol is required.'
    ]);
    exit;
}

try {
    $client = new rabbitMQClient('testRabbitMQ.ini', 'dmz');

    $response = $client->send_request([
        'type' => 'stock_price',
        'symbol' => strtoupper(trim($data['symbol']))
    ]);

    if (!is_array($response) || ($response['success'] ?? false) !== true) {
        http_response_code(502);
        echo json_encode([
            'success' => false,
            'message' => 'Could not retrieve the stock price.',
            'details' => is_array($response)
                ? ($response['error'] ?? $response['message'] ?? null)
                : null
        ]);
        exit;
    }

    echo json_encode($response, JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
    error_log('Stock price handler error: ' . $e->getMessage());

    http_response_code(502);
    echo json_encode([
        'success' => false,
        'message' => 'Stock price service is temporarily unavailable.'
    ]);
}
?>
