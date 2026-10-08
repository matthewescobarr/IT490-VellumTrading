#!/usr/bin/php
<?php
require_once(__DIR__ . '/../path.inc');
require_once(__DIR__ . '/../get_host_info.inc');
require_once(__DIR__ . '/../rabbitMQLib.inc');
require_once(__DIR__ . '/db_config.php');

$mydb = new mysqli($db_host, $db_user, $db_password, $db_name);

if ($mydb->errno != 0) {
  echo "Failed to connect to database: " . $mydb->error . PHP_EOL;
  exit(0);
}

echo "Successfully connected to vellum_trading" . PHP_EOL;

function doRegister($email,$password)
{
    // lookup username in database
    // check password
    global $mydb;

    $query = "SELECT user_id FROM users WHERE email = ?";
    $stmt = $mydb->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
      return false;
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $query = "INSERT INTO users (email, password_hash) VALUES (?, ?)";
    $stmt = $mydb->prepare($query);
    $stmt->bind_param("ss", $email, $password_hash);
    $stmt->execute();
    
    //return false if not valid
    if ($mydb->errno != 0) {
      return false;
    }

    return true;
    
}

function doLogin($username,$password)
{
    // lookup username in databas
    // check password
    return true;
    //return false if not valid
}

function requestProcessor($request)
{
  echo "received request".PHP_EOL;
  //var_dump($request);
  if(!isset($request['type']))
  {
    return "ERROR: unsupported message type";
  }
  switch ($request['type'])
  {
    case "register":
      return doRegister($request['email'],$request['password']);
    case "login":
      return doLogin($request['email'],$request['password']);
    case "validate_session":
      return doValidate($request['sessionId']);
  }
  return array("returnCode" => '0', 'message'=>"Server received request and processed");
}

$server = new rabbitMQServer("testRabbitMQ.ini","testServer");

echo "testRabbitMQServer BEGIN".PHP_EOL;
$server->process_requests('requestProcessor');
echo "testRabbitMQServer END".PHP_EOL;
exit();
?>

