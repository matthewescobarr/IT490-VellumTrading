<?php

// portfolio retrieval 

function getPortfolio($request)
{
    global $mydb;

    try {
        if (empty($request["sessionKey"])) {
            return [
                "success" => false,
                "message" => "Missing session key"
            ];
        }
        
        $sessionKey = $request['sessionKey'];

        $query = "SELECT user_id FROM sessions WHERE session_key = ? AND created_at > NOW() - INTERVAL 1 HOUR";
        $stmt = $mydb->prepare($query);
        $stmt->bind_param("s", $sessionKey);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return [
                "success" => false, 
                "message" => "invalid or expired session"
            ];
        }

        $session = $result->fetch_assoc();
        $user_id = $session['user_id'];

        $query = "SELECT cash_balance FROM virtual_accounts WHERE user_id = ?";
        $cashStmt = $mydb->prepare($query);
        $cashStmt->bind_param("i", $user_id);
        $cashStmt->execute();

        $cashResult = $cashStmt->get_result();
        if ($cashResult->num_rows === 0) {
            return [
                "success" => false,
                "message" => "virtual account not found"
            ];
        }
        $cash = $cashResult->fetch_assoc();
        $cashBalance = $cash['cash_balance'];

        $query = "SELECT symbol, quantity FROM holdings WHERE user_id = ?";
        $holdingsStmt = $mydb->prepare($query);
        $holdingsStmt->bind_param("i", $user_id);
        $holdingsStmt->execute();

        $holdingsResult = $holdingsStmt->get_result();
        $holdings = [];
        while ($row = $holdingsResult->fetch_assoc()) {
            $holdings[] = $row;
        }
        return [
            "success" => true,
            "cash_balance" => $cashBalance,
            "holdings" => $holdings
        ];
    } catch (Throwable $e) {
        error_log($e->getMessage());
        return [
            "success" => false,
            "message" => "Unable to retrieve user's portfolio"
        ];
    }
}

function savePortfolioHistory($request) {
    global $mydb;

    try {
        if (!isset($request['sessionKey']) || !isset($request['total_value'])) {
            return [
                "success" => false,
                "message" => "Missing required portfolio information"
            ];
        }

        $total_value = $request['total_value'];
        if (!is_numeric($total_value) || $total_value < 0) {
            return [
                "success" => false,
                "message" => "invalid portfolio value"
            ];
        }
        $sessionKey = $request['sessionKey'];

        if (!is_string($sessionKey) || $sessionKey === "") {
            return [
                "success" => false,
                "message" => "Invalid session"
            ];
        }

        $query = "SELECT user_id FROM sessions WHERE session_key = ? AND created_at > NOW() - INTERVAL 1 HOUR";
        $stmt = $mydb->prepare($query);
        $stmt->bind_param("s", $sessionKey);
        $stmt->execute();

        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            return [
                "success" => false,
                "message" => "Invalid or expired session"
            ];
        }
        $session = $result->fetch_assoc();
        $user_id = $session['user_id'];

        $query = "INSERT INTO portfolio_history (user_id, total_value) VALUES (?, ?)";
        $stmt = $mydb->prepare($query);
        $stmt->bind_param("id", $user_id, $total_value);

        if (!$stmt->execute()) {
            return [
                "success" => false,
                "message" => "Portfolio history save failed"
            ];
        }

        return [
            "success" => true,
            "message" => "Portfolio history successfully saved"
        ];
    } catch (Throwable $e) {
        error_log($e->getMessage());

        return [
            "success" => false,
            "message" => "Unable to save  the portfolio history"
        ];
    }
}

function getPortfolioHistory($request) {
    global $mydb;

    if (!isset ($request['sessionKey'])) {
        return [
            "success" => false,
            "message" => "Missing session key"
        ];
    }

    $sessionKey = $request['sessionKey'];

    if (!is_string($sessionKey) || $sessionKey === "") {
        return [
            "success" => false,
            "message" => "Invalid session key"
        ];
    }

    try {
        $query = "SELECT user_id FROM sessions WHERE session_key = ? AND created_at > NOW() - INTERVAL 1 HOUR";
        $stmt = $mydb->prepare($query);
        $stmt->bind_param("s", $sessionKey);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return [
                "success" => false,
                "message" => "Invalid or expired session"
            ];
        }
        $session = $result->fetch_assoc();
        $user_id = $session['user_id'];

        $query = "SELECT total_value, recorded_at FROM portfolio_history WHERE user_id = ? ORDER BY recorded_at ASC";
        $stmt = $mydb->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $history = [];

        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }

        return [
            "success" => true,
            "history" => $history
        ];
    } catch (Throwable $e) {
        error_log($e->getMessage());
        return [
            "success" => false,
            "message" => "Unable to retrieve portfolio history"
        ];
    }
}