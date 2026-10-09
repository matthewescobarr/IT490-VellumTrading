<?php

header('Content-Type: application/json');

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

echo json_encode($reponse);

?>

