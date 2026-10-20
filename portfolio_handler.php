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
$action = (string)($data['action'] ?? 'get');
$sessionKey = (string)($data['sessionKey'] ?? '');

if ($sessionKey === '') {
    respond(401, ['success' => false, 'message' => 'A valid login session is required.']);
}
if (!in_array($action, ['get', 'add', 'remove'], true)) {
    respond(400, ['success' => false, 'message' => 'Action must be get, add, or remove.']);
}

$request = ['type' => 'portfolio', 'action' => $action, 'sessionKey' => $sessionKey];

if ($action === 'add') {
    $symbol = strtoupper(trim((string)($data['symbol'] ?? '')));
    $quantity = filter_var($data['quantity'] ?? null, FILTER_VALIDATE_FLOAT);
    if (!preg_match('/^[A-Z0-9.]{1,15}$/', $symbol) || $quantity === false || $quantity <= 0) {
        respond(400, ['success' => false, 'message' => 'Provide a valid symbol and positive quantity.']);
    }
    $request['symbol'] = $symbol;
    $request['quantity'] = $quantity;
} elseif ($action === 'remove') {
    $holdingId = filter_var($data['holdingId'] ?? null, FILTER_VALIDATE_INT);
    if ($holdingId === false || $holdingId < 1) {
        respond(400, ['success' => false, 'message' => 'Provide a valid holdingId.']);
    }
    $request['holdingId'] = $holdingId;
}

try {
    $client = new rabbitMQClient('testRabbitMQ.ini', 'testServer');
    $response = $client->send_request($request);

    if (!is_array($response) || ($response['success'] ?? false) !== true) {
        respond(502, [
            'success' => false,
            'message' => 'Portfolio request failed. The database route may not be implemented yet.',
            'details' => is_array($response) ? ($response['error'] ?? $response['message'] ?? null) : null
        ]);
    }
    respond(200, $response);
} catch (Throwable $e) {
    error_log('portfolio_handler.php: ' . $e->getMessage());
    respond(502, ['success' => false, 'message' => 'Portfolio service is unavailable.']);
}
?>
