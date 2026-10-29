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
	'message' => 'Use POST with a JSON body.'
	]);
}

$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data)) {
	respond(400, [
		'success' => false,
		'message' => 'invalid JSON request.'
	]);
}

$sessionKey = trim((string)($data['sessionKey'] ?? $data['session_key'] ?? ''));

if ($sessionKey === '') {
	respond(401, [
		'success' => false,
		'message' => 'A valid login session is required.'
	]);
}

try {
	$client = new rabbitMQClient('testRabbitMQ.ini', 'testServer');
	$response = $client->send_request([
		'type' => 'get_portfolio_history',
		'sessionKey' => $sessionKey
	]);

	if (!is_array($response) || 
		($response['success'] ?? false) !== true) {
		respond(502, [
			'success' => false,
			'message' => 'Could not retrieve portfolio history.',
			'details' => is_array($response)
				?($response['error'] ?? $response['message'] ?? null)
				: null
			]);
	}
	respond(200, $response);
} catch (Throwable $e) {
	error_log('historical_chart_handler.php:' . $e->getMessage());

	respond(502, [
		'success' => false,
		'message' => 'Portfolio history service is unavailable.'
	]);
}
?>



