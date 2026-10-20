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
$action = strtolower(trim((string)($data['action'] ?? '')));
$symbol = strtoupper(trim((string)($data['symbol'] ?? '')));
$quantity = filter_var($data['quantity'] ?? null, FILTER_VALIDATE_FLOAT);
$sessionKey = (string)($data['sessionKey'] ?? '');

if (!in_array($action, ['buy', 'sell'], true)) {
    respond(400, ['success' => false, 'message' => 'Action must be buy or sell.']);
}
if (!preg_match('/^[A-Z0-9.]{1,15}$/', $symbol) || $quantity === false || $quantity <= 0) {
    respond(400, ['success' => false, 'message' => 'Provide a valid symbol and positive quantity.']);
}
if ($sessionKey === '') {
    respond(401, ['success' => false, 'message' => 'A valid login session is required.']);
}

try {
    $client = new rabbitMQClient('testRabbitMQ.ini', 'testServer');
    $response = $client->send_request([
        'type' => 'trade',
        'action' => $action,
        'symbol' => $symbol,
        'quantity' => $quantity,
        'sessionKey' => $sessionKey
    ]);

    if (!is_array($response) || ($response['success'] ?? false) !== true) {
        respond(502, [
            'success' => false,
            'message' => 'Trade request failed. Trading is not implemented until the backend supports it.',
            'details' => is_array($response) ? ($response['error'] ?? $response['message'] ?? null) : null
        ]);
    }
    respond(200, $response);
} catch (Throwable $e) {
    error_log('trade_handler.php: ' . $e->getMessage());
    respond(502, ['success' => false, 'message' => 'Trading service is unavailable.']);
}
?>
