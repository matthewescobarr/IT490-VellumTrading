<?php

header('Content-Type: application/json');

require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
	echo json_encode([
		"success" => false,
		"message" => "Invalid reponse."
	]);
	exit;
}

if (
	empty($data['name']) ||
	empty($data['email']) ||
	empty($data['password'])
) {
	echo json_encode([
		"success" => false,
		"message" => "Missing required information."
	]);
	exit;
}

$request = array();

$request['type'] = 'register';
$request['name'] = $data['name'];
$request['email'] = $data['email'];
$request['password'] = $data['password'];

$client = new rabbitMQClient("testRabbitMQ.ini", "testServer");

$response = $client->send_request($request);

echo json_encode($response);

?>
