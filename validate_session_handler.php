<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "valid" => false,
        "message" => "POST requests only."
    ]);
    exit;
}

// Load RabbitMQ libraries
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

// Read incoming JSON
$data = json_decode(file_get_contents("php://input"), true);

// Validate the session key
if (
    !is_array($data) ||
    empty($data['sessionKey']) ||
    !is_string($data['sessionKey'])
) {
    http_response_code(400);
    echo json_encode([
        "valid" => false,
        "message" => "Missing or invalid session key."
    ]);
    exit;
}

// Create the request for the database listener
$request = [
    "type" => "validate_session",
    "sessionId" => $data['sessionKey']
];

// Send the request through RabbitMQ
$client = new rabbitMQClient("testRabbitMQ.ini", "testServer");
$response = $client->send_request($request);

// Interpret the database listener's response
$isValid = ($response === true);

if (is_array($response)) {
    $isValid = (($response['valid'] ?? false) === true);
}

// Return an appropriate HTTP status for invalid sessions
if (!$isValid) {
    http_response_code(401);
}

// Return exactly ONE JSON response
echo json_encode([
    "valid" => $isValid
]);

?>
