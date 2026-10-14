<?php
require_once 'authCheck.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Vellum Trading - Trade</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

    <nav>
        <h2>Vellum Trading</h2>

        <div>
            <a href="index.php">Home</a>
            <a href="dashboard.php">Dashboard</a>
            <a href="search.php">Search Stocks</a>
            <a href="portfolio.php">Portfolio</a>
            <a href="transactions.php">Transactions</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <main>
        <h1>Buy or Sell Stocks</h1>

        <form id="tradeForm">

            <label for="symbol">Stock Symbol</label>
            <input
                type="text"
                id="symbol"
                name="symbol"
                required
            >

            <label for="quantity">Quantity</label>
            <input
                type="number"
                id="quantity"
                name="quantity"
                min="1"
                step="1"
                required
            >

            <label for="action">Action</label>
            <select id="action" name="action">
                <option value="buy">Buy</option>
                <option value="sell">Sell</option>
            </select>

            <button type="submit">Submit Trade</button>

        </form>

        <p id="tradeMessage"></p>
    </main>

</body>
</html>