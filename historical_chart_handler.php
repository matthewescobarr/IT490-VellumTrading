<?php
header('Content-Type: application/json; charset=utf-8');

require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

function respond(int $status, array $body): void {
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['success' => false, 'message' => 'Use POST with a JSON body.']);
}

$data = json_decode(file_get_contents('php://input'), true);
$symbol = strtoupper(trim((string)($data['symbol'] ?? '')));

if (!preg_match('/^[A-Z0-9.]{1,15}$/', $symbol)) {
    respond(400, ['success' => false, 'message' => 'Enter a valid stock symbol.']);
}

try {
    $client = new rabbitMQClient('testRabbitMQ.ini', 'testServer');
    $response = $client->send_request(['type' => 'stock_price', 'symbol' => $symbol]);

    if (!is_array($response) || ($response['success'] ?? false) !== true) {
        respond(502, [
            'success' => false,
            'message' => 'Could not retrieve the stock price.',
            'details' => is_array($response) ? ($response['error'] ?? $response['message'] ?? null) : null
        ]);
    }

    respond(200, $response);
} catch (Throwable $e) {
    error_log('stock_price_handler.php: ' . $e->getMessage());
    respond(502, ['success' => false, 'message' => 'Stock-price service is unavailable.']);
}
?>
