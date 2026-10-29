<?php

// start session
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// check session key exists
if (empty($_SESSION['sessionKey'])) {
    header("Location: login.php");
    exit;
}

// validating key with backend
$payload = json_encode([
    "sessionKey" => $_SESSION['sessionKey']
]);


// http request
$options = [
    "http" => [
        "method" => "POST",
        "header" => "Content-Type: application/json\r\n",
        "content" => $payload, //session key in the request
        "timeout" => 5,
        "ignore_errors" => true // return errors if they exist
    ]
];

$context = stream_context_create($options);


// what james handler gets sent
$response = @file_get_contents(
    "http://100.110.65.121/validate_session_handler.php",
    false,
    $context
);

// decode response back
$result = is_string($response)
    ? json_decode($response, true)
    : null;

// fail closed if the backend is unreachable or key is invalid
if (
    !is_array($result) ||
    ($result['valid'] ?? false) !== true
) {
    // clear session
    $_SESSION = [];

    // delete session cookie
    if (ini_get("session.use_cookies")) {
        $cookieParams = session_get_cookie_params();

        setcookie(
            session_name(),
            "",
            [
                "expires" => time() - 42000,
                "path" => $cookieParams["path"],
                "domain" => $cookieParams["domain"],
                "secure" => $cookieParams["secure"],
                "httponly" => $cookieParams["httponly"],
                "samesite" => $cookieParams["samesite"] ?? "Lax"
            ]
        );
    }


    // redirect to login, destroy the php session
    session_destroy();

    header("Location: login.php");
    exit;
}

?>