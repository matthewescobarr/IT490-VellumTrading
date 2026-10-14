<?php
require_once 'authCheck.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Vellum Trading - Search Stocks</title>

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
        <h1>Search Stocks</h1>

        <form id="searchForm">
            <label for="stockSymbol">Stock Symbol</label>

            <input
                type="text"
                id="stockSymbol"
                name="stockSymbol"
                placeholder="Enter stock symbol, e.g. AAPL"
                required
            >

            <button type="submit">Search</button>
        </form>

        <p id="searchMessage"></p>

        <div id="searchResults"></div>
    </main>

</body>
</html>