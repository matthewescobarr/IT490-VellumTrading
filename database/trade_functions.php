<?php

//trade functions for handling buy and sell orders

function processTrade($request)
{
    global $mydb;
    
    if (
        !isset($request['action']) ||
        !isset($request['symbol']) ||
        !isset($request['quantity']) ||
        !isset($request['sessionKey']) ||
        !isset($request['bid']) ||
        !isset($request['ask'])

    ) {
        return [
            "success" => false,
            "message" => "Missing required trade information"
        ];
    }
    $action = $request['action'];
    if ($action !== "buy" && $action !== "sell") {
        return [
            "success" => false,
            "message" => "Invalid trade action"
        ];
    }
    $symbol = $request['symbol'];
    if (!is_string($symbol) || $symbol === "") {
        return [
            "success" => false,
            "message" => "Invalid stock symbol"
        ];
    }
    $quantity = $request['quantity'];
    if (filter_var($quantity, FILTER_VALIDATE_INT) === false || $quantity <= 0) {
        return [
            "success" => false,
            "message" => "Invalid share quantity"
        ];
    }
    $bid = $request['bid'];
    $ask = $request['ask'];
    if (!is_numeric($bid) || !is_numeric($ask) || $bid <= 0 || $ask <= 0) {
        return [
            "success" => false,
            "message" => "Invalid stock quote"
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
    $user = $result->fetch_assoc();
    $user_id = $user['user_id'];

    $mydb->begin_transaction();

    try {
        $query = "SELECT cash_balance FROM virtual_accounts WHERE user_id = ? FOR UPDATE";

        $stmt = $mydb->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $mydb->rollback();
            return [
            "success" => false,
            "message" => "Virtual account not found"
            ];
        }
        $account = $result->fetch_assoc();
        $cash_balance = $account['cash_balance'];
        
        if($action === "buy") {
            $execution_price = $ask;
        } else {
            $execution_price = $bid;
        }

        $total_amount = round($execution_price * $quantity, 2);

        if ($action === "buy") {
        $query = "INSERT INTO holdings (user_id, symbol, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)";
        $stmt = $mydb->prepare($query);
        $stmt->bind_param("isi", $user_id, $symbol, $quantity);
        


        $query = "UPDATE virtual_accounts SET cash_balance = cash_balance - ? WHERE user_id = ? AND cash_balance >= ?";
        $cashStmt = $mydb->prepare($query);
        $cashStmt->bind_param("did", $total_amount, $user_id, $total_amount);
        $cashStmt->execute();

        if ($cashStmt->affected_rows !== 1) {
            throw new Exception("Insufficient funds to make the purchase");
        }

        $stmt->execute();
        $query = "INSERT INTO trades (user_id, symbol, trade_type, quantity, price_per_share, total_amount) VALUES (?, ?, 'buy', ?, ?, ?)";
        $tradeStmt = $mydb->prepare($query);
        $tradeStmt->bind_param("isidd", $user_id, $symbol, $quantity, $execution_price, $total_amount);
        $tradeStmt->execute();
        } else {
            $query = "SELECT quantity FROM holdings WHERE user_id = ? AND symbol = ? FOR UPDATE";
            $holdingsStmt = $mydb->prepare($query);
            $holdingsStmt->bind_param("is", $user_id, $symbol);
            $holdingsStmt->execute();
            $myResult = $holdingsStmt->get_result();

            if ($myResult->num_rows === 0) {
                throw new Exception ("You don't own this stock");
            }
            $holdings = $myResult->fetch_assoc();
            $current_quantity = $holdings['quantity'];

            if ($current_quantity < $quantity) {
                throw new Exception("Not enough shares to sell");
            }
            $query ="UPDATE holdings SET quantity = quantity - ? WHERE user_id = ? AND symbol = ? AND quantity >= ?";
            $updateStmt = $mydb->prepare($query);;
            $updateStmt->bind_param("iisi", $quantity, $user_id, $symbol, $quantity);
            $updateStmt->execute();

            if ($updateStmt->affected_rows !== 1) {
                throw new Exception("Failed to update holdings");
            }

            $query = "UPDATE virtual_accounts SET cash_balance = cash_balance + ? WHERE user_id = ?";
            $cashStmt = $mydb->prepare($query);
            $cashStmt->bind_param("di", $total_amount, $user_id);
            $cashStmt->execute();

            if ($cashStmt->affected_rows !== 1) {
                throw new Exception("Unable to update the cash balance");
            }

            $query = "INSERT INTO trades (user_id, symbol, trade_type, quantity, price_per_share, total_amount) VALUES (?, ?, 'sell', ?, ?, ?)";
            $tradeStmt = $mydb->prepare($query);
            $tradeStmt->bind_param("isidd", $user_id, $symbol, $quantity, $execution_price, $total_amount);
            $tradeStmt->execute();

            if ($current_quantity == $quantity) {
                $query = "DELETE FROM holdings WHERE user_id = ? AND symbol = ? AND quantity = 0";
                $deleteStmt = $mydb->prepare($query);
                $deleteStmt->bind_param("is", $user_id, $symbol);
                $deleteStmt->execute();
            }
        }
        $mydb->commit();
        return [
            "success" => true,
            "message" => "Trade completed successfully"
        ];
    } catch (Throwable $e) {
        $mydb->rollback();
        return [
            "success" => false,
            "message" => $e->getMessage()
        ];
    }
}
