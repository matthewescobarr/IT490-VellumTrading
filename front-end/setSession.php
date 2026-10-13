<?php

session_start();

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['sessionKey'])) {
    http_response_code(400);
    echo json_encode(["success" => false]);
    exit;
}

session_regenerate_id(true);

$_SESSION['sessionKey'] = $data['sessionKey'];

echo json_encode(["success" => true]);

?>
