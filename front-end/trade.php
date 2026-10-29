<?php
require_once 'authCheck.php';

$pageTitle = 'Trade Stocks';

// get the session key from the authenticated session
$sessionKey = $_SESSION['sessionKey'];

// get the stock symbol from the URL, if one was provided
$symbol = isset($_GET['symbol'])
    ? strtoupper(preg_replace('/[^A-Z]/i', '', $_GET['symbol']))
    : '';
?>

<!-- navigation -->
<nav class="navbar">
    <div class="navbar-brand">
        <a href="index.php">Vellum Trading</a>
    </div>

    <div class="navbar-links">
        <a href="index.php">Dashboard</a>
        <a href="search.php">Search Stocks</a>
        <a href="trade.php" class="active">Trade Stocks</a>
        <a href="portfolio.php">Portfolio</a>
        <a href="transactions.php">Transactions</a>
        <a href="login.php">Logout</a>
    </div>
        <link rel="stylesheet" href="style.css">
</nav>

<!-- main Content -->
<main
    class="container"
    id="tradePage"
    data-session-key="<?= htmlspecialchars(
        $sessionKey,
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
>

    <section class="card">
        <h1>Trade Stocks</h1>

        <p>
            Place a simulated buy or sell order using Vellum Trading.
        </p>

        <!-- trade Form -->
        <form id="tradeForm">

            <!-- stock symbol -->
            <label for="tradeSymbol">Stock Ticker</label>

            <input
                type="text"
                id="tradeSymbol"
                name="symbol"
                placeholder="e.g. EZRA"
                value="<?= htmlspecialchars($symbol, ENT_QUOTES, 'UTF-8') ?>"
                maxlength="12"
                required
            >

            <!-- share quantity -->
            <label for="tradeQuantity">Number of Shares</label>

            <input
                type="number"
                id="tradeQuantity"
                name="quantity"
                min="1"
                step="1"
                placeholder="e.g. 3"
                required
            >

            <!-- buy or sell -->
            <label for="tradeAction">Order Type</label>

            <select
                id="tradeAction"
                name="action"
                required
            >
                <option value="buy">Buy</option>
                <option value="sell">Sell</option>
            </select>

            <!-- retrieve stock price -->
            <button
                type="button"
                id="getTradeQuote"
            >
                Get Latest Price
            </button>

            <!-- estimated order value -->
            <div
                id="tradeQuote"
                class="card"
                hidden
            >
                <h2>Estimated Order Value</h2>

                <p>
                    Latest available price per share:
                    <strong id="tradePrice">$0.00</strong>
                </p>

                <p>
                    Estimated total:
                    <strong id="tradeTotal">$0.00</strong>
                </p>

                <p id="tradeQuoteNote">
                    This is an estimate, not a confirmed execution price.
                </p>
            </div>

            <!-- submit trade -->
            <button
                type="submit"
                id="submitTrade"
            >
                Submit Simulated Trade
            </button>

        </form>

        <!-- success and error messages -->
        <p
            id="tradeMessage"
            role="status"
            aria-live="polite"
        ></p>

    </section>

</main>


<script src="trade.js"></script>