<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// A session key must exist.
if (empty($_SESSION['sessionKey'])) {
    header("Location: login.php");
    exit;
}

// Validate the key with the trusted backend.
$payload = json_encode([
    "sessionKey" => $_SESSION['sessionKey']
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

// Fail closed if the backend is unreachable or the key is invalid.
if (
    !is_array($result) ||
    ($result['valid'] ?? false) !== true
) {
    $_SESSION = [];

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

    session_destroy();

    header("Location: login.php");
    exit;
}

?>