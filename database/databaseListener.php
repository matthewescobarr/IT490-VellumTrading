#!/usr/bin/php
<?php
require_once(__DIR__ . '/../path.inc');
require_once(__DIR__ . '/../get_host_info.inc');
require_once(__DIR__ . '/../rabbitMQLib.inc');
require_once(__DIR__ . '/db_config.php');
require_once(__DIR__ . '/trade_functions.php');
require_once(__DIR__ . '/portfolio_functions.php');

$mydb = new mysqli($db_host, $db_user, $db_password, $db_name);

if ($mydb->errno != 0) {
  echo "Failed to connect to database: " . $mydb->error . PHP_EOL;
  exit(0);
}

echo "Successfully connected to vellum_trading" . PHP_EOL;

function doRegister($name,$email,$password)
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

    $mydb->begin_transaction();

    try{
      $query = "INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)";
      $stmt = $mydb->prepare($query);
      $stmt->bind_param("sss", $name, $email, $password_hash);
      if (!$stmt->execute()) {
        $mydb->rollback();
        return false;
      }

      $user_id = $mydb->insert_id;
      $query = "INSERT INTO virtual_accounts (user_id) VALUES (?)";
      $stmt = $mydb->prepare($query);
      $stmt->bind_param("i", $user_id);
      if (!$stmt->execute()) {
        $mydb->rollback();
        return false;
      }
    
      $mydb->commit();

      return true;
    } catch (mysqli_sql_exception  $e) {
      $mydb->rollback();
      error_log("Registration database error: " . e->getMessage());
      return false;
    }
}

function doLogin($email,$password)
{
    // lookup username in databas
    // check password
    global $mydb;

    $query = "SELECT user_id, password_hash FROM users WHERE email = ?";
    $stmt = $mydb->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
      return false;
    }


    $row = $result->fetch_assoc();
    //return false if not valid
    if (!password_verify($password, $row['password_hash'])) {
      return false;
    } else {
      $sessionKey = hash('sha256', random_bytes(32));

      $query = "INSERT INTO sessions (user_id, session_key) VALUES (?, ?)";
      $stmt = $mydb->prepare($query);
      $stmt->bind_param("is", $row['user_id'], $sessionKey);

      if ($stmt->execute()) {
        return $sessionKey;
      } else {
        return false;
      }
    }
}

function doValidate($sessionId) {
  global $mydb;
  
  try {
  $query = "SELECT created_at FROM sessions WHERE session_key = ? AND created_at > NOW() - INTERVAL 1 HOUR";
  $stmt = $mydb->prepare($query);
  $stmt->bind_param("s", $sessionId);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows == 0) {
    return false;
  } else {
    return true;
  }
  } catch (mysqli_sql_exception $e) {
    error_log("Session Validation Error:" . $e->getMessage());
    return false;
  }
}

function requestProcessor($request)
{
  echo "received request type:" . ($request['type'] ?? 'unknown') . PHP_EOL;
  //var_dump($request);
  if(!isset($request['type']))
  {
    return "ERROR: unsupported message type";
  }
  switch ($request['type'])
  {
    case "register":
      return doRegister($request['name'],$request['email'],$request['password']);
    case "login":
      return doLogin($request['email'],$request['password']);
    case "validate_session":
      return doValidate($request['sessionId']);
    case "trade":
      return processTrade($request);
    case "portfolio":
      return getPortfolio($request);
    case "portfolio_history":
      return savePortfolioHistory($request);
    case "get_portfolio_history":
      return getPortfolioHistory($request);
  }
  return array("returnCode" => '0', 'message'=>"Server received request and processed");
}

$server = new rabbitMQServer("testRabbitMQ.ini","database");

echo "testRabbitMQServer BEGIN".PHP_EOL;
$server->process_requests('requestProcessor');
echo "testRabbitMQServer END".PHP_EOL;
exit();
?>
