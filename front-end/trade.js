function initTrade() {

    const tradeForm = document.getElementById("tradeForm");

    // only run on the trade page
    if (!tradeForm) {
        return;
    }

    const tradePage = document.getElementById("tradePage");

    const symbolInput = document.getElementById("tradeSymbol");
    const quantityInput = document.getElementById("tradeQuantity");
    const actionInput = document.getElementById("tradeAction");

    const quoteButton = document.getElementById("getTradeQuote");
    const submitButton = document.getElementById("submitTrade");

    const tradeQuote = document.getElementById("tradeQuote");
    const tradePrice = document.getElementById("tradePrice");
    const tradeTotal = document.getElementById("tradeTotal");
    const tradeMessage = document.getElementById("tradeMessage");

    const API_URL = "http://100.110.65.121/";

    let latestPrice = null;
    let latestSymbol = "";

    // display messages
    function displayMessage(message, isError = false) {
        tradeMessage.textContent = message;
        tradeMessage.style.color = isError ? "#b91c1c" : "#166534";
    }

    // format dollar amounts
    function formatMoney(amount) {
        return Number(amount).toLocaleString("en-US", {
            style: "currency",
            currency: "USD"
        });
    }

    // validate the stock symbol and quantity
    function validateTrade() {
        const symbol = symbolInput.value.trim().toUpperCase();
        const quantity = Number(quantityInput.value);

        if (!/^[A-Z]{1,5}$/.test(symbol)) {
            throw new Error("Enter a valid stock symbol.");
        }

        if (!Number.isSafeInteger(quantity) || quantity < 1) {
            throw new Error(
                "Enter a whole number of shares greater than zero."
            );
        }

        return { symbol, quantity };
    }

    // send requests for stock prices and portfolio information
    async function sendRequest(endpoint, requestData) {
        const response = await fetch(API_URL + endpoint, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(requestData)
        });

        const responseText = await response.text();

        let result;

        try {
            result = JSON.parse(responseText);
        } 
        
        catch (error) {
            throw new Error("The backend returned an invalid response.");
        }

        if (!response.ok || result.success !== true) {
            throw new Error(
                result.message || "The backend request failed."
            );
        }

        return result;
    }

    // clear the previous stock quote
    function clearQuote() {
        latestPrice = null;
        latestSymbol = "";
        tradeQuote.hidden = true;
    }

    // clear the quote when the symbol changes
    symbolInput.addEventListener("input", clearQuote);

    // Update the estimated total when quantity changes.
    quantityInput.addEventListener("input", function () {
        const quantity = Number(quantityInput.value);
        const symbol = symbolInput.value.trim().toUpperCase();

        if (
            latestPrice !== null &&
            latestSymbol === symbol &&
            Number.isSafeInteger(quantity) &&
            quantity > 0
        ) 
        
        {
            tradeTotal.textContent = formatMoney(
                latestPrice * quantity
            );
        }
    });

    
    // get latest stock price
    quoteButton.addEventListener("click", async function () {
        try {
            const { symbol, quantity } = validateTrade();

            tradeQuote.hidden = true;

            const result = await sendRequest(
                "stock_price_handler.php",
                { symbol: symbol }
            );

            const price = Number(result.price);

            if (!Number.isFinite(price) || price <= 0) {
                throw new Error("The backend returned an invalid stock price.");
            }

            latestPrice = price;
            latestSymbol = symbol;

            tradePrice.textContent = formatMoney(price);
            tradeTotal.textContent = formatMoney(price * quantity);

            tradeQuote.hidden = false;

            displayMessage(
                "Price retrieved. Review your order before submitting."
            );

        } 
        
        catch (error) {
            console.error("[Trade] Quote request failed:", error);
            clearQuote();
            displayMessage(error.message, true);
        }
    });


    // submit buy and sell orders to the backend
    tradeForm.addEventListener("submit", async function (event) {
        event.preventDefault();

        try {
            const {symbol, quantity} = validateTrade();
            const action = actionInput.value.toLowerCase();
            const sessionKey = tradePage?.dataset.sessionKey;

            if (!sessionKey) {
                throw new Error("Your session was not found. Please log in again.");
            }

            if (action !== "buy" && action !== "sell") {
                throw new Error("Choose Buy or Sell.");
            }

            if (latestPrice === null || latestSymbol !== symbol) {
                throw new Error("Get the latest price before submitting your order.");
            }

            // check available cash before a buy order
            if (action === "buy") {
                displayMessage("Checking your available cash...");

                const portfolio = await sendRequest(
                    "portfolio_handler.php",
                    {
                        action: "get",
                        sessionKey: sessionKey
                    }
                );

                const portfolioData = portfolio.result ?? portfolio;
                const availableCash = Number(portfolioData.cash_balance);
                const tradeCost = latestPrice * quantity;

                if (!Number.isFinite(availableCash)) {
                    throw new Error("Could not verify your available cash.");
                }

                if (
                    Math.round(tradeCost * 100) >
                    Math.round(availableCash * 100)
                ) {
                    throw new Error(
                        "Insufficient funds! Your order costs " +
                        formatMoney(tradeCost) +
                        ", but you only have " +
                        formatMoney(availableCash) +
                        " available."
                    );
                }
            }

            // send the trade directly to the backend
            submitButton.disabled = true;
            quoteButton.disabled = true;

            displayMessage("Submitting your simulated " + action + " order...");

            const tradeData = {
                sessionKey: sessionKey,
                action: action,
                symbol: symbol,
                quantity: quantity
            };


            const response = await fetch(
                API_URL + "trade_handler.php",
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify(tradeData)
                }
            );


            const responseText = await response.text();

            let result;

            try {
                result = JSON.parse(responseText);
            } 
            
            catch (error) {
                throw new Error("The trade backend returned invalid JSON.");
            }

            if (!response.ok || result.success !== true) {
                throw new Error(
                    result.message || "The trade could not be completed."
                );
            }

            let message = result.message || "Trade processed successfully.";
        

            if (
                result.portfolio_snapshot &&
                result.portfolio_snapshot.success === false
            ) 
            
            {
                message += " Warning: portfolio history was not saved.";
            }


        } 
        
        catch (error) {
            console.error("[Trade] Trade request failed:", error);
            displayMessage(error.message, true);

        } 
        
        finally {
            submitButton.disabled = false;
            quoteButton.disabled = false;
        }
    });

}

// initialize after the page loads
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initTrade, {
        once: true
    });
} 

else {
    initTrade();
}