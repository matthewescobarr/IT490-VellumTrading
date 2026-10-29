<?php
header('Content-Type: application/json; charset=utf-8');
$allowed_origin = ['http://www.sample.com', 'http://100.115.133.49'];

if (isset($_SERVER['HTTP_ORIGIN']) &&
   in_array( $_SERVER['HTTP_ORIGIN'], $allowed_origin, true)) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
}


header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}


require_once __DIR__ . '/path.inc';
require_once __DIR__ . '/get_host_info.inc';
require_once __DIR__ . '/rabbitMQLib.inc';

function respond(int $status, array $body): void
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_SLASHES);
    exit;
}

function validPrice($value): bool
{
    return is_numeric($value) && is_finite((float)$value) && (float)$value > 0;
}

//Calculate portfolio value w current bid prices for existing stock holding plus avail. cash
function calculatePortfolioValue(string $sessionKey): float
{
	$dbClient = new rabbitMQClient('testRabbitMQ.ini', 'testServer');
	$portfolio = $dbClient->send_request([
		'type' => 'portfolio',
		'action' => 'get',
		'sessionKey' => $sessionKey
	]);
	if (
		!is_array($portfolio) ||
		($portfolio['success'] ?? false) !== true ||
		!isset($portfolio['cash_balance']) ||
		!is_numeric($portfolio['cash_balance']) ||
		!is_finite((float)$portfolio['cash_balance']) ||
		(float)$portfolio['cash_balance'] < 0 ||
		!isset($portfolio['holdings']) || 
		!is_array($portfolio['holdings'])
	) {
		throw new RuntimeException(
			'Could not retrieve valid portfolio from database'
		);
	}

	$totalValue = (float)$portfolio['cash_balance'];
	$dmzClient = new rabbitMQClient('testRabbitMQ.ini', 'dmz');

	foreach($portfolio['holdings'] as $holding) {
		if (
			!is_array($holding) ||
			!isset($holding['symbol'], $holding['quantity']) || 
			!is_numeric($holding['quantity']) ||
			!is_finite((float)$holding['quantity']) || 
			(float)$holding['quantity'] <= 0
		) {
			throw new RuntimeException (
				'the database returned an invalid holding.'
		);
	}

	$symbol = strtoupper(trim((string)$holding['symbol']));
	$quantity = (float)$holding['quantity'];

	if (!preg_match('/^[A-Z0-9.]{1,15}$/', $symbol)) { 
		throw new RuntimeException(
			'the database returned an invalid stock symbole'
		);
	}

	$quote = $dmzClient->send_request([
		'type' => 'stock_quote',
		'symbol' => $symbol
	]);
	if (
		!is_array($quote) ||
		($quote['success'] ?? false) !== true ||
		strtoupper((string)($quote['symbol'] ?? '')) !== $symbol ||
		!validPrice($quote['bid'] ?? null) ||
		!validPrice($quote['ask'] ?? null) || 
		(float)$quote['bid'] > (float)$quote['ask']
	) {
		throw new RuntimeException(
			'could not retrieve valid quote for ' . $symbol
		);
	}

	$totalValue += $quantity * (float)$quote['bid'];
	}

	if (!is_finite($totalValue) || $totalValue < 0) {
		throw new RuntimeException ('calculated portfolio value is invalid');
	}

	return round($totalValue, 2);
}

//save portfolio history via rabbit
function savePortfolioSnapshot(
	string $sessionKey,
	float $totalValue
): array {
	$dbClient = new rabbitMQClient('testRabbitMQ.ini', 'testServer');
	$response = $dbClient->send_request([
		'type' => 'portfolio_history',
		'action' => 'save',
		'sessionKey' => $sessionKey,
		'total_value' => round($totalValue, 2)
	]);
	if (!is_array($response) || ($response['success'] ?? false) !== true) {
		error_log('portfolio save failed: ' . json_encode($response)
		);

		return [
			'success' => false,
			'message' => 'trade successful but portfolio not saved.'
		];
	}
	return [
		'success' => true,
		'total_value' => round($totalValue, 2)
	];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	respond(405, [
		'success' => false,
		'message' => 'use post with json body.'
	]);
}

$data = json_decode(file_get_contents('php://input'), true); 

if (!is_array($data)) {
	respond(400, [
		'success' => false,
		'message' => 'invalid json request'
	]);
}

$action = strtolower(trim((string)($data['action'] ?? '')));
$symbol = strtoupper(trim((string)($data['symbol'] ?? '')));
$sessionKey = trim((string)($data['sessionKey'] ?? ''));

$quantity = filter_var(
	$data['quantity'] ?? null,
	FILTER_VALIDATE_INT,
	['options' => ['min_range' => 1]]
);

if (!in_array($action, ['buy', 'sell'], true)) {
	respond(400, [
		'success' => false,
		'message' => 'action must be a buy or a sell'
	]);
}

if (!preg_match('/^[A-Z0-9.]{1,15}$/', $symbol)) {
	respond(400, [
		'success' => false,
		'message' => 'please provide a valid stock symbol'
	]);
}

if ($quantity === false) {
	respond(400, [
		'success' => false,
		'message' => 'quantity has to be a positive whole number'
	]);
}

if ($sessionKey === '') {
	respond(400, [
		'success' => false,
		'message' => 'a valid session is required, please login and try again'
	]);
}

try {
	//get current stock price
	$dmzClient = new rabbitMQClient('testRabbitMQ.ini', 'dmz');
	$quote = $dmzClient->send_request([
		'type' => 'stock_quote',
		'symbol' => $symbol
	]);
	if (!is_array($quote) || ($quote['success'] ?? false) !== true) {
		respond(502, [
			'success' => false,
			'message' => 'the stock search failed',
			'stage' => 'quote'
		]);
	}
	if (strtoupper((string)($quote['symbol'] ?? '')) !== $symbol || !validPrice($quote['bid'] ?? null) || !validPrice($quote['ask'] ?? null) || (float)$quote['bid'] > (float)$quote['ask']) {
		respond(502, [
			'success' => false,
			'message' => 'the quote return invalid bid/ask',
			'stage' => 'quote'
		]);
	}

	//execute trade through db
	$dbClient = new rabbitMQClient('testRabbitMQ.ini', 'database');
	$tradeResponse = $dbClient->send_request([
		'type' => 'trade',
		'sessionKey' => $sessionKey,
		'action' => $action,
		'symbol' => $symbol,
		'quantity' => (int)$quantity,
		'bid' => (float)$quote['bid'],
		'ask' => (float)$quote['ask']
	]);

	if (!is_array($tradeResponse) || ($tradeResponse['success'] ?? false) !== true) {
		respond(502, [
			'success' => false,
			'message' => 'database did not confirm trade',
			'stage' => 'trade',
			'details' => is_array($tradeResponse) ? ($tradeResponse['error'] ?? $tradeResponse['message'] ?? null) : null
		]);
	}



	//retrieve updated portfolio, calculate value, and save
	try {
		$totalValue = calculatePortfolioValue($sessionKey);
		$snapshot = savePortfolioSnapshot($sessionKey, $totalValue);
	} catch (Throwable $snapshotError) {
		error_log(
			'trade_handler.php: snapshot error: ' . $snapshotError->getMessage()
		);
		$snapshot = [
			'success' => false,
			'message' => 'trade successful, portfolio snapshot not saved'
		];
	}

	//return trade result n snapshot status
	respond (200, [
		'success' => true,
		'message' => 'trade processed successfully',
		'trade' => $tradeResponse,
		'quote' => [
			'symbol' => $symbol,
			'bid' => (float)$quote['bid'],
			'ask' => (float)$quote['ask']
		],
		'portfolio_snapshot' => $snapshot
	]);
} catch (Throwable $e) {
	error_log('trade_handler.php: ' . $e->getMessage());
	respond (502, [
		'success' => false,
		'message' => 'trading service could not complete request',
		'details' => $e->getMessage()
	]);
}
?>
