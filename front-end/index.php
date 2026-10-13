<?php
session_start();

if (empty($_SESSION['sessionKey'])) {
    header("Location: login.php");
    exit;
}

$sessionKey = $_SESSION['sessionKey'];

$payload = json_encode([
    "sessionKey" => $sessionKey
]);

$options = [
    "http" => [
        "method" => "POST",
        "header" => "Content-Type: application/json\r\n",
        "content" => $payload,
        "timeout" => 3
    ]
];

$context = stream_context_create($options);

$response = @file_get_contents(
    "http://100.110.65.121/validate_session_handler.php",
    false,
    $context
);

$result = $response ? json_decode($response, true) : null;

if (!is_array($result) || ($result['valid'] ?? false) !== true) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}
?>



<!DOCTYPE html>
<html lang="en">
    

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>Vellum Trading</title>

        <link rel="stylesheet" href="style.css">
    </head>

    <body>

            <nav> 

            <div> 
                <a href="index.php">Home</a>
                <a href="dashboard.php">Dashboard</a>
                <a href="search.php">Search Stocks</a>
                <a href="portfolio.php">Portfolio</a>
                <a href="transactions.php">Transactions</a>
                <a href="login.php">Logout</a>
                <a href="register.php">Register</a>
            </div>

        </nav>

        <main> 
            <h1>Welcome to Vellum Trading</h1> 


            <p> 
                Practice buying and seling stocks here with virtual money.
            </p>

        </main>

    </body>

</html>