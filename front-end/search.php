<?php

// check that user is logged in, and has valid session key, otherwise redirect to login page
require_once 'authCheck.php';
$pageTitle = 'Explore Stocks';
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vellum Trading - <?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <nav>
        <h2>Vellum Trading</h2>
        <div>
            <a href="index.php">Home</a>
            <a href="search.php">Search Stocks</a>
            <a href="trade.php">Trade Stocks</a>
            <a href="portfolio.php">Portfolio</a>
            <a href="transactions.php">Transactions</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <main>
        <section class="card">
            <h1>Explore Stocks</h1>
            <h2>Search for a Stock</h2>
            <p>Enter a symbol such as AAPL or MSFT.</p>

            <!-- stock search form -->
            <form id="stockSearchForm">
                <label for="stockSearchInput">Stock Symbol</label>

                <input
                    type="text"
                    id="stockSearchInput"
                    name="symbol"
                    placeholder="Enter stock symbol, e.g. AAPL"
                    required
                >
                
                <button type="submit" id="searchButton">Search</button>
                
                <p id="stockSearchMessage"></p>
            </form>

            <div id="stockSearchResult" class="card" hidden>
                <h3 id="stockSymbol"></h3>
                <p>Latest available price:</p>
                <h2 id="stockPrice"></h2>
                <p id="stockSource"></p>
                <p id="stockUpdated"></p>

                <a id="tradeLink" href="trade.php">
                    Trade this stock
                </a>
            </div>
        </section>
    </main>

    <script src="search.js" defer></script>
</body>
</html>