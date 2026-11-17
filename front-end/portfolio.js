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

// ==========================================
// PORTFOLIO HISTORICAL PERFORMANCE GRAPH
// ==========================================

const portfolioPage = document.getElementById("portfolioPage");

if (portfolioPage) {

    const historyMessage = document.getElementById("historyMessage");
    const portfolioHistoryCanvas = document.getElementById("portfolioHistoryChart");

    const sessionKey = portfolioPage.dataset.sessionKey;

    let portfolioHistoryChart = null;

    async function loadPortfolioHistory() {

        if (!portfolioHistoryCanvas) {
            console.error("[Portfolio History] Chart canvas not found.");
            return;
        }

        if (!sessionKey) {
            historyMessage.textContent = "Session information is missing.";
            return;
        }

        historyMessage.textContent = "Loading portfolio history...";

        try {

            const response = await fetch(
                "http://100.110.65.121/historical_chart_handler.php",
                {
                    method: "POST",

                    headers: {
                        "Content-Type": "application/json"
                    },

                    body: JSON.stringify({
                        session_key: sessionKey
                    })
                }
            );

            if (!response.ok) {
                throw new Error(
                    "Server returned status " + response.status
                );
            }

            const data = await response.json();

            if (!data.success) {
                throw new Error(
                    data.message || "Unable to retrieve portfolio history."
                );
            }

            if (!data.history || data.history.length === 0) {
                historyMessage.textContent =
                    "No portfolio history available yet.";

                return;
            }

            // Extract dates and portfolio values
            const labels = data.history.map(item => {
                return new Date(item.recorded_at).toLocaleString();
            });

            const values = data.history.map(item => {
                return Number(item.total_value);
            });

            // Remove previous chart before creating a new one
            if (portfolioHistoryChart) {
                portfolioHistoryChart.destroy();
            }

            // Create historical portfolio line graph
            portfolioHistoryChart = new Chart(
                portfolioHistoryCanvas,
                {
                    type: "line",

                    data: {
                        labels: labels,

                        datasets: [
                            {
                                label: "Portfolio Value ($)",

                                data: values,

                                borderWidth: 2,

                                tension: 0.3,

                                fill: false,

                                pointRadius: 3,

                                pointHoverRadius: 6
                            }
                        ]
                    },

                    options: {
                        responsive: true,

                        maintainAspectRatio: false,

                        plugins: {
                            legend: {
                                display: true
                            },

                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return "$" +
                                            Number(context.parsed.y).toFixed(2);
                                    }
                                }
                            }
                        },

                        scales: {
                            y: {
                                beginAtZero: false,

                                ticks: {
                                    callback: function(value) {
                                        return "$" + value;
                                    }
                                },

                                title: {
                                    display: true,
                                    text: "Portfolio Value"
                                }
                            },

                            x: {
                                title: {
                                    display: true,
                                    text: "Recorded Date"
                                }
                            }
                        }
                    }
                }
            );

            historyMessage.textContent = "";

        } catch (error) {

            console.error(
                "[Portfolio History] Failed to load history:",
                error
            );

            historyMessage.textContent =
                "Unable to load portfolio history.";
        }
    }

    // Load the graph
    loadPortfolioHistory();

}