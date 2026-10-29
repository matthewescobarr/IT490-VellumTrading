<?php
require_once 'authCheck.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Vellum Trading - Transactions</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-brand">
            <a href="index.php">Vellum Trading</a>
        </div>

        <div class="navbar-links">
            <a href="index.php">Dashboard</a>
            <a href="search.php">Search Stocks</a>
            <a href="trade.php">Trade Stocks</a>
            <a href="portfolio.php">Portfolio</a>
            <a href="transactions.php" class="active">Transactions</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container">

        <section class="card">
            <h1>Transaction History</h1>

            <p>
                View your previous simulated stock purchases and sales.
            </p>

            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Stock</th>
                        <th>Action</th>
                        <th>Shares</th>
                        <th>Price</th>
                    </tr>
                </thead>

                <tbody id="transactionsTableBody">
                    <tr>
                        <td colspan="5">
                            Transaction history has not been loaded yet.
                        </td>
                    </tr>
                </tbody>
            </table>

            <p
                id="transactionsMessage"
                role="status"
                aria-live="polite"
            ></p>
        </section>

    </main>

    <script src="transactions.js"></script>

</body>
</html>