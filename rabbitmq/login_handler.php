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

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
	echo json_encode([
		"success" => false,
		"message" => "Invalid request."
	]);
	exit;
}

if (
	empty($data['email']) ||
	empty($data['password'])
) {
	echo json_encode([
		"success" => false,
		"message" => "Missing email or password"
	]);
	exit;
}

$request = array();

$request['type'] = 'login';
$request['email'] = $data['email'];
$request['password'] = $data['password'];

$client = new rabbitMQClient("testRabbitMQ.ini", "testServer");

$response = $client->send_request($request);

echo json_encode($response);

?>

