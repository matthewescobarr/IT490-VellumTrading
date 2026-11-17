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
            <h1>Welcome to Vellum Trading</h1> 


            <p> 
                Practice buying and seling stocks here with virtual money.
            </p>

	    <div id="stock-news-widget">
                <h3>Latest Market News</h3>
                <ul id="news-list">Loading news...</ul>
            </div>
            <script>
                const rssUrl = 'https://cnbc.com';
                const apiUrl = `https://rss2json.com{encodeURIComponent(rssUrl)}`;

                fetch(apiUrl).then(response => response.json()).then(data => {
                    const list = document.getElementById('news-list');
                    list.innerHTML = ''; //"Loading..." text

                // Displays the top 5 articles
                    data.items.slice(0, 5).forEach(article => {
                        const li = document.createElement('li');
                        const date = new Date(article.pubDate).toLocaleDateString();
        
                        li.innerHTML = `<a href="${article.link}" target="_blank" rel="noopener noreferrer">${article.title}</a>
                        <span class="news-date">${date}</span>`;
                        list.appendChild(li);
                    });
                }).catch(error => {
                    document.getElementById('news-list').innerHTML = 'Unable to load stock news.';
                    console.error('Error fetching RSS feed:', error);
                });
            </script>

        </main>

    </body>

</html>
