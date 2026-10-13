<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['sessionKey'])) {
    http_response_code(400);
    echo json_encode(["valid" => false]);
    exit;
}

$request = [
    "type" => "validate_session",
    "sessionId" => $data['sessionKey']
];

$client = new rabbitMQClient("testRabbitMQ.ini", "testServer");
$response = $client->send_request($request);

echo json_encode([
    "valid" => ($response === true)
]);
?>
