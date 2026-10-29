document.addEventListener("DOMContentLoaded", function () {

    // only run this code on portfolio.php
    const portfolioPage = document.getElementById("portfolioPage");

    if (!portfolioPage) {
        return;
    }

    // get the session key and page elements
    const sessionKey = portfolioPage.dataset.sessionKey;

    const portfolioMessage = document.getElementById("portfolioMessage");
    const cashBalance = document.getElementById("cashBalance");
    const holdingsValue = document.getElementById("holdingsValue");
    const totalPortfolioValue = document.getElementById("totalPortfolioValue");
    const holdingsTable = document.getElementById("holdingsTableBody");

    // format a number as US dollars
    function formatMoney(value) {
        return new Intl.NumberFormat("en-US", {
            style: "currency",
            currency: "USD"
        }).format(value);
    }

    // display a message on the page
    function showMessage(element, message, isError = false) {
        if (!element) {
            return;
        }

        element.textContent = message;
        element.style.color = isError ? "#b91c1c" : "";
    }

    // send a POST request and return the JSON response
    async function sendRequest(url, body) {
        const response = await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(body)
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || "Request failed.");
        }

        return data;
    }

    // get the current price of a stock from James's handler
    async function getStockPrice(symbol) {
        const data = await sendRequest(
            "http://100.110.65.121/stock_price_handler.php",
            {
                symbol: symbol
            }
        );

        if (data.success !== true) {
            throw new Error(data.message || "Could not get stock price.");
        }

        const price = Number(data.price);

        if (!Number.isFinite(price) || price < 0) {
            throw new Error("The stock price returned was invalid.");
        }

        return price;
    }

    // add a cell to a table row
    function addCell(row, value) {
        const cell = document.createElement("td");

        cell.textContent = value;

        row.appendChild(cell);
    }

    // display an empty holdings table
    function showEmptyHoldings() {
        const row = document.createElement("tr");
        const cell = document.createElement("td");

        cell.colSpan = 4;
        cell.textContent = "You don't have any stock holdings yet.";

        row.appendChild(cell);
        holdingsTable.appendChild(row);
    }

    // load the user's portfolio
    async function loadPortfolio() {

        if (!sessionKey) {
            throw new Error("Please log in again.");
        }

        showMessage(portfolioMessage, "Loading portfolio...");

        // send a request to James's portfolio handler
        const data = await sendRequest(
            "http://100.110.65.121/portfolio_handler.php",
            {
                action: "get",
                sessionKey: sessionKey
            }
        );

        // check the response from the portfolio handler
        if (
            data.success !== true ||
            !data.result ||
            data.result.success !== true
        ) {
            throw new Error(
                data.message ||
                (data.result && data.result.message) ||
                "Could not load portfolio."
            );
        }

        const portfolio = data.result;
        const cash = Number(portfolio.cash_balance);
        const holdings = portfolio.holdings || [];

        if (!Number.isFinite(cash)) {
            throw new Error("The cash balance returned was invalid.");
        }

        // display the available cash
        cashBalance.textContent = formatMoney(cash);

        // remove the previous table rows
        holdingsTable.replaceChildren();

        // handle an empty portfolio
        if (holdings.length === 0) {
            showEmptyHoldings();

            holdingsValue.textContent = formatMoney(0);
            totalPortfolioValue.textContent = formatMoney(cash);

            showMessage(
                portfolioMessage,
                "Portfolio loaded successfully."
            );

            return;
        }

        let totalHoldings = 0;
        let failedStocks = 0;

        // load the price and value of each stock holding
        for (const holding of holdings) {

            const symbol = String(holding.symbol || "").toUpperCase();
            const quantity = Number(holding.quantity);

            if (!symbol || !Number.isFinite(quantity)) {
                console.error("Invalid holding returned:", holding);
                failedStocks++;
                continue;
            }


            // calculates the total value of the stock holding and adds it to the table
            try {
                const price = await getStockPrice(symbol);
                const value = quantity * price;

                totalHoldings += value;

                const row = document.createElement("tr");

                addCell(row, symbol);
                addCell(row, quantity);
                addCell(row, formatMoney(price));
                addCell(row, formatMoney(value));

                holdingsTable.appendChild(row);

            } 
            
            catch (error) {
                console.error(
                    "Could not load stock price for " + symbol + ":",
                    error
                );

                failedStocks++;
            }
        }

        // update the portfolio totals
        holdingsValue.textContent = formatMoney(totalHoldings);

        totalPortfolioValue.textContent = formatMoney(
            cash + totalHoldings
        );

        // report whether every stock loaded successfully
        if (failedStocks > 0) {
            showMessage(
                portfolioMessage,
                "Portfolio loaded, but " + failedStocks +
                " holding(s) could not be displayed.",
                true
            );
        } 
        
        else {
            showMessage(
                portfolioMessage,
                "Portfolio loaded successfully."
            );
        }
    }

    // load the portfolio and display any errors
    loadPortfolio().catch(function (error) {
        console.error("Portfolio loading error:", error);

        showMessage(
            portfolioMessage,
            error.message || "Could not load portfolio.",
            true
        );
    });

});