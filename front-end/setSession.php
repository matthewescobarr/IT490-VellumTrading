<?php

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "POST requests only."
    ]);

    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (
    !is_array($data) ||
    !isset($data["sessionKey"]) ||
    !is_string($data["sessionKey"]) ||
    trim($data["sessionKey"]) === ""
) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "A session key is required."
    ]);

    exit;
}

// Ask the backend to validate the session key before trusting it.
$payload = json_encode([
    "sessionKey" => $data["sessionKey"]
]);

$options = [
    "http" => [
        "method" => "POST",
        "header" => "Content-Type: application/json\r\n",
        "content" => $payload,
        "timeout" => 5,
        "ignore_errors" => true
    ]
];

$context = stream_context_create($options);

$response = @file_get_contents(
    "http://100.110.65.121/validate_session_handler.php",
    false,
    $context
);

$result = is_string($response)
    ? json_decode($response, true)
    : null;

if (
    !is_array($result) ||
    ($result["valid"] ?? false) !== true
) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "The session key could not be validated."
    ]);

    exit;
}

// Only create the session after the backend confirms the key is valid.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!session_regenerate_id(true)) {
    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Could not establish the session."
    ]);

    exit;
}

$_SESSION["sessionKey"] = $data["sessionKey"];

echo json_encode([
    "success" => true
]);

?>