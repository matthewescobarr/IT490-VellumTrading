<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["valid" => false]);
    exit;
}

require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

$data = json_decode(file_get_contents("php://input"), true);

if (
    !is_array($data) ||
    empty($data['sessionKey']) ||
    !is_string($data['sessionKey'])
) {
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

$isValid = ($response === true);

if (!$isValid) {
    http_response_code(401);
}

echo json_encode(["valid" => $isValid]);
echo json_encode([
    "responseType" => gettype($response),
    "response" => $response,
    "valid" => ($response === true)
]);

?>

