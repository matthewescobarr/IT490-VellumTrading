<?php
header('Content-Type: aplication/json; charset=utf-8');

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

$sessionKey = trim((string)($data['session_key'] ?? ''));

if ($sessionKey === '') {
	respond(401, [
		'success' => false,
		'message' => 'A valid login session is required.'
	]);
}

try {
	$client = new rabbitMQClient('testRabbitMQ.ini', 'testServer');
	$response = $client->send_request([
		'type' => 'historical_chart',
		'session_key' => $sessionKey
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



