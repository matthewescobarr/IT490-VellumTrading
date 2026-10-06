<?php

require_once('db_config.php');

$mydb = new mysqli($db_host, $db_user, $db_password, $db_name);

if ($mydb->connect_error != 0) {
    echo "Failed to connect to database: " . $mydb->connect_error . PHP_EOL;
    exit(0);
}

echo "Successfully connected to vellum_trading" . PHP_EOL;

?>
