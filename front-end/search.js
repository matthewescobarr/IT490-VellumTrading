const stockSearchForm = document.getElementById("stockSearchForm");

if (stockSearchForm) {
    stockSearchForm.addEventListener("submit", async function (event) {
        event.preventDefault();

        const input = document.getElementById("stockSearchInput");
        const message = document.getElementById("stockSearchMessage");
        const result = document.getElementById("stockSearchResult");

        const symbol = input.value.trim().toUpperCase();

        // validate the symbol before sending the request
        if (!/^[A-Z]{1,5}$/.test(symbol)) { 
            // letters A-Z, length between 1 and 5 characters
            message.textContent = "Please enter a valid stock symbol.";
            result.hidden = true;
            return;
        }

        message.textContent = "Loading stock price...";
        result.hidden = true;

        try {
            const response = await fetch(
                "http://100.110.65.121/stock_price_handler.php",
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({ symbol: symbol })
                }
            )

            const data = await response.json();

            if (!response.ok || data.success !== true) {
                throw new Error(
                    data.message || "Could not retrieve stock information." 
                    // data.message is stock symbol not being found in the database.
                );
            }

            if (data.price === undefined || data.price === null) {
                throw new Error("The server did not return a stock price.");
            }

            // display the actual response from the stock-price service.
            document.getElementById("stockSymbol").textContent =
                data.symbol || symbol;
                // use data.symbol if available, otherwise use the input symbol

            document.getElementById("stockPrice").textContent =
                new Intl.NumberFormat("en-US", {
                    style: "currency",
                    currency: "USD"
                }).format(Number(data.price));

            document.getElementById("stockSource").textContent =
                "Source: " + (data.source || "Market data service");
                // source: alpaca or market data service if not available

            document.getElementById("stockUpdated").textContent =
                data.retrieved_at
                    ? "Updated: " + new Date(data.retrieved_at).toLocaleString()
                    : "Update time unavailable";

            document.getElementById("tradeLink").href =
                "trade.php?symbol=" +
                encodeURIComponent(data.symbol || symbol);

            result.hidden = false;
            message.textContent = "Stock price loaded successfully.";

        } 
        
        catch (error) {
            message.textContent =
                error.message || "An error occurred while searching.";
        } 
    });
}