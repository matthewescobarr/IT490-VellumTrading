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


function respond(int $status, array $body): void {
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, [
	'success' => false, 
	'message' => 'Use POST with a JSON body.']);
}

$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data)) {
	respond(400, [
		'success' => false,
		'message' => 'Invalid JSON request.'
	]);
}

$action = strtolower(trim((string)($data['action'] ?? 'get')));
$sessionKey = trim((string)($data['sessionKey'] ?? ''));

if ($sessionKey === '') {
	respond (401, [
		'success' => false,
		'message' => 'An active session is required'
	]);
}

if (!in_array($action, ['get', 'add', 'remove', 'save'], true)) {
	respond(400, [
		'success' => false,
		'message' => 'action must be get add remove or save'
	]);
}

if ($action === 'save') {
	$totalValue = $data['total_value'] ?? null;
	
	if (!is_numeric($totalValue) || !is_finite((float)$totalValue) || (float)$totalValue < 0) {
		respond(400, [
			'success' => false,
			'message' => 'provide non-negative value'
		]);
	}

	$request = [
		'type' => 'portfolio_history',
		'action' => 'save',
		'sessionKey' => $sessionKey,
		'total_value' => round((float)$totalValue, 2)
	];
} else { 
	$request = [
		'type' => 'portfolio',
		'action' => $action,
		'sessionKey' => $sessionKey
	];

	if ($action === 'add') {
		$symbol = strtoupper(trim((string)($data['symbol'] ?? '')));
		$quantity = filter_var(
			$data['quantity'] ?? null,
			FILTER_VALIDATE_FLOAT
		);
		 if (!preg_match('/^[A-Z0-9.]{1,15}$/', $symbol) || $quantity === false || $quantity <= 0) {
			respond(400, [
                		'success' => false,
                		'message' => 'Provide a valid symbol and positive quantity.'
            		]);
       		 }
		$request['symbol'] = $symbol;
		$request['quantity'] = $quantity;
		} elseif ($action === 'remove') {
			$holdingId = filter_var($data['holdingId'] ?? null, FILTER_VALIDATE_INT);
			if ($holdingId === false || $holdingId < 1) {
				respond(400, [
					'success' => false,
					'message' => 'provide valid holding id.'
				]);
			}
			$request['holdingId'] = $holdingId;
		}
}

try {
	$client = new rabbitMQClient('testRabbitMQ.ini', 'testServer');
	$response = $client->send_request($request);

	if (!is_array($response) || ($response['success'] ?? false) !== true) {
		error_log(
			'portfolio_handler.php: database response: ' . json_encode($response)
		);
		respond(502, [
			'success' => false, 
			'message' => 'the database did not confirm the request',
			'details' => is_array($response) ? ($response['error'] ?? $response['message'] ?? null) : null 
		]);
	}
	
	respond(200, [
		'success' => true,
		'message' => $action === 'save' ? 'portfolio history saved' : 'portfolio request complete',
		'result' => $response
	]);
} catch (Throwable $e) {
    error_log('portfolio_handler.php: ' . $e->getMessage());

    respond(502, [
        'success' => false,
        'message' => 'Portfolio service is unavailable.'
    ]);
}
?>
