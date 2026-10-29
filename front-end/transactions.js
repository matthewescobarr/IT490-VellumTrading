document.addEventListener("DOMContentLoaded", function () {

    const transactionsTableBody =
        document.getElementById("transactionsTableBody");

    // only run this code on transactions.php
    if (!transactionsTableBody) {
        return;
    }

    const transactionsMessage =
        document.getElementById("transactionsMessage");

    // format stock prices
    function formatMoney(amount) {

        const price = Number(amount);

        if (!Number.isFinite(price)) {
            return "N/A";
        }

        return "$" + price.toFixed(2);
    }

    // create a table cell
    function createCell(value) {

        const cell = document.createElement("td");

        cell.textContent =
            value !== null && value !== undefined
                ? value
                : "N/A";

        return cell;
    }

    // display the user's transactions
    function displayTransactions(transactions) {

        transactionsTableBody.replaceChildren();

        // Check whether any transactions exist.
        if (transactions.length === 0) {

            transactionsMessage.textContent =
                "You have not made any transactions yet.";

            const row = document.createElement("tr");
            const cell = document.createElement("td");

            cell.colSpan = 5;

            cell.textContent =
                "Your transaction history will appear here.";

            row.appendChild(cell);
            transactionsTableBody.appendChild(row);

            return;
        }

        transactionsMessage.textContent =
            "Showing " + transactions.length + " transaction(s).";

        // add each transaction to the table
        transactions.forEach(function (transaction) {

            const row = document.createElement("tr");

            // Support common field names.
            const date =
                transaction.createdAt ||
                transaction.date ||
                transaction.timestamp ||
                "N/A";

            const symbol =
                transaction.symbol ||
                transaction.stockSymbol ||
                "N/A";

            const action =
                transaction.type ||
                transaction.action ||
                transaction.side ||
                "N/A";

            const shares =
                transaction.quantity ??
                transaction.shares ??
                transaction.qty ??
                "N/A";

            const price =
                transaction.price ??
                transaction.pricePerShare;

            // Date
            row.appendChild(createCell(date));

            // Stock symbol
            row.appendChild(createCell(symbol));

            // BUY or SELL
            row.appendChild(
                createCell(String(action).toUpperCase())
            );

            // Number of shares
            row.appendChild(createCell(shares));

            // Price per share
            row.appendChild(
                createCell(formatMoney(price))
            );

            transactionsTableBody.appendChild(row);

        });

    }

    // Request transaction history from the backend.
    async function loadTransactions() {

        transactionsMessage.textContent =
            "Loading transactions...";

        try {

            const response = await fetch(
                "transactions_handler.php",
                {
                    method: "POST",

                    headers: {
                        "Content-Type": "application/json"
                    },

                    credentials: "same-origin",

                    body: JSON.stringify({
                        action: "get_transactions"
                    })
                }
            );

            if (!response.ok) {
                throw new Error(
                    "Server returned status " + response.status
                );
            }

            const result = await response.json();

            if (result.success !== true) {
                throw new Error(
                    result.message ||
                    "Unable to retrieve transactions."
                );
            }

            if (!Array.isArray(result.transactions)) {
                throw new Error(
                    "The server returned an invalid transaction list."
                );
            }

            displayTransactions(result.transactions);

        } 
        
        catch (error) {

            console.error(
                "[Transactions] Failed to load history:",
                error
            );

            transactionsMessage.textContent =
                "Unable to load your transaction history. Please try again.";

            transactionsTableBody.replaceChildren();

            const row = document.createElement("tr");
            const cell = document.createElement("td");

            cell.colSpan = 5;

            cell.textContent =
                "Transactions could not be loaded.";

            row.appendChild(cell);
            transactionsTableBody.appendChild(row);

        }

    }

    // Load the history when the page opens.
    loadTransactions();

});