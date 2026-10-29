<?php
// check that user is logged in, and has valid session key, otherwise redirect to login page

require_once 'authCheck.php';

// in case there is no session key, make a new one
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pass the logged-in user's session key to JavaScript, session_key used as a backup if sessionKey does not exist
$sessionKey = $_SESSION['sessionKey']
    ?? $_SESSION['session_key']
    ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Vellum Trading - Portfolio</title>

    <link rel="stylesheet" href="style.css">

    <script src="portfolio.js"></script>
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

    <!-- retrieve portfolio page, store session key in html, -->
    <main
        id="portfolioPage"
        data-session-key="<?= htmlspecialchars($sessionKey, ENT_QUOTES, 'UTF-8') ?>" 
    >
        <h1>My Portfolio</h1>

        <p id="portfolioMessage" role="status" aria-live="polite">
            Loading your portfolio...
        </p>

        <!-- portfolio summary -->
        <section class="card">
            <h2>Portfolio Summary</h2>

            <p>Available Cash</p>
            <h3 id="cashBalance">—</h3>

            <p>Holdings Value</p>
            <h3 id="holdingsValue">—</h3>

            <p>Total Portfolio Value</p>
            <h2 id="totalPortfolioValue">—</h2>
        </section>

        <!-- individual stock holdings -->
        <section class="card">
            <h2>My Holdings</h2>

            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Stock</th>
                            <th>Shares</th>
                            <th>Latest Price</th>
                            <th>Market Value</th>
                        </tr>
                    </thead>

                    <tbody id="holdingsTableBody">
                        <tr>
                            <td colspan="4">Loading holdings...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>