#!/usr/bin/php
<?php

require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

function sendDatabaseRequest($request)
{
    $client = new rabbitMQClient("testRabbitMQ.ini", "database");
    return $client->send_request($request);
}

function sendDmzRequest($request)
{
	$client = new rabbitMQClient("testRabbitMQ.ini", "dmz");
	return $client->send_request($request);
}

function doLogin($email, $password)
{
    $response = sendDatabaseRequest([
        "type" => "login",
        "email" => $email,
        "password" => $password
    ]);

    if (is_string($response) && trim($response) !== '') {
        return $response;
    }

    if (is_array($response)
        && ($response['success'] ?? false) === true
        && !empty($response['sessionKey'])
        && is_string($response['sessionKey'])) {
        return $response['sessionKey'];
    }

    return false;
}

function doRegister($name, $email, $password)
{
    return sendDatabaseRequest([
        "type" => "register",
        "name" => $name,
        "email" => $email,
        "password" => $password
    ]);
}

function doValidate($sessionId)
{
    if (!is_string($sessionId) || trim($sessionId) === '') {
        return false;
    }

    $response = sendDatabaseRequest([
        "type" => "validate_session",
        "sessionId" => $sessionId
    ]);

    if ($response === true) {
        return true;
    }

    if (is_array($response) && ($response['valid'] ?? false) === true) {
        return true;
    }

    return false;
}
function doStockPrice($symbol)
{
    if (!is_string($symbol) || trim($symbol) === '') {
        return [
            "success" => false,
            "error" => [
                "code" => "missing_symbol",
                "message" => "A stock symbol is required."
            ]
        ];
    }

    $response = sendDmzRequest([
        "type" => "stock_price",
        "symbol" => strtoupper(trim($symbol))
    ]);

    if (is_array($response)) {
        return $response;
    }

    return [
        "success" => false,
        "error" => [
            "code" => "invalid_dmz_response",
            "message" => "The DMZ returned an unexpected response."
        ]
    ];
}

function requestProcessor($request)
{
    if (!is_array($request) || !isset($request['type'])) {
        return [
            "success" => false,
            "message" => "Missing request type."
        ];
    }

    switch ($request['type']) {
        case "login":
            if (empty($request['email']) || empty($request['password'])) {
                return false;
            }

            return doLogin($request['email'], $request['password']);

        case "register":
            if (
                empty($request['name']) ||
                empty($request['email']) ||
                empty($request['password'])
            ) {
                return [
                    "success" => false,
                    "message" => "Missing registration information."
                ];
            }

            return doRegister(
                $request['name'],
                $request['email'],
                $request['password']
            );

        case "validate_session":
            if (empty($request['sessionId'])) {
                return false;
            }

            return doValidate($request['sessionId']);
	case "stock_price":
            if (empty($request['symbol']) || !is_string($request['symbol'])) {
                return [
                    "success" => false,
                    "error" => [
                        "code" => "missing_symbol",
                        "message" => "A stock symbol is required."
                    ]
                ];
            }
            return doStockPrice($request['symbol']);
	case "portfolio":
    if (
        empty($request['sessionKey']) ||
        !isset($request['action'])
    ) {
        return [
            "success" => false,
            "message" => "Missing session key or portfolio action."
        ];
    }

    return sendDatabaseRequest($request);

        default:
            return [
                "success" => false,
                "message" => "Unsupported request type."
            ];
    }
}

$server = new rabbitMQServer("testRabbitMQ.ini", "testServer");

echo "testRabbitMQServer BEGIN" . PHP_EOL;
$server->process_requests('requestProcessor');
echo "testRabbitMQServer END" . PHP_EOL;

exit();
?>

