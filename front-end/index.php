<?php
// check that user is logged in, and has valid session key, otherwise redirect to login page
require_once 'authCheck.php';
?>

<!DOCTYPE html>
<html lang="en">
    

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">  <!-- responsive design -->

        <title>Vellum Trading</title>

        <link rel="stylesheet" href="style.css">  <!-- styling -->
    </head>

    <body>

            <nav> 

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
            <h1>Welcome to Vellum Trading</h1> 


            <p> 
                Practice buying and seling stocks here with virtual money.
            </p>

        </main>

    </body>

</html>