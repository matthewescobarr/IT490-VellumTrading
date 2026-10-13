<?php

header('Content-Type: application/json');

require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Invalid request."
    ]);
    exit;
}

if (empty($data['email']) || empty($data['password'])) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Missing email or password."
    ]);
    exit;
}

$request = [
    "type" => "login",
    "email" => $data['email'],
    "password" => $data['password']
];

$client = new rabbitMQClient("testRabbitMQ.ini", "testServer");
$response = $client->send_request($request);

if (is_string($response) && trim($response) !== '') {
    echo json_encode([
        "success" => true,
        "sessionKey" => $response
    ]);
} else {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Login failed. Invalid credentials."
    ]);
}

?>

