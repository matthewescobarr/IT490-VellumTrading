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
                <a href="dashboard.php">Dashboard</a>
                <a href="portfolio.php">Portfolio</a>
                <a href="transactions.php">Transactions</a>
                <a href="login.php">Logout</a>
            </div>

        </nav>

    </body>
    
</html>

<form id="tradeForm">

    <label for="symbol">Stock Symbol</label>
    <input type="text" id="symbol" required>

    <label for="quantity">Quantity</label>
    <input type="number" id="quantity" min="1" required>

    <label for="action">Action</label>
    <select id="action">
        <option value="buy">Buy</option>
        <option value="sell">Sell</option>
    </select>

    <button type="submit">Trade</button>
</form>

<p id="tradeMessage"></p>

    
