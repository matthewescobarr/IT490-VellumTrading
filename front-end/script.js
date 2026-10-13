// ==========================================
// REGISTRATION
// ==========================================

const registerForm = document.getElementById("registerForm");

if (registerForm) {
    registerForm.addEventListener("submit", function (event) {

        // Prevent the page from reloading
        event.preventDefault();

        // Get the values entered in the form
        const name = document.getElementById("name").value.trim();
        const email = document.getElementById("email").value.trim();
        const password = document.getElementById("password").value;
        const confirmPassword = document.getElementById("confirmPassword").value;

        const registerMessage = document.getElementById("registerMessage");

        // Check that the passwords match
        if (password !== confirmPassword) {
            registerMessage.textContent =
                "Passwords do not match. Please try again.";
            return;
        }

        // Check that the fields are not empty
        if (!name || !email || !password) {
            registerMessage.textContent =
                "Please fill in all required fields.";
            return;
        }

        // Prepare the data to send to the backend
        const data = {
            name: name,
            email: email,
            password: password
        };

        // Send the registration request to James's backend
        fetch("http://100.110.65.121/register_handler.php", {
            method: "POST",

            headers: {
                "Content-Type": "application/json"
            },

            body: JSON.stringify(data)
        })

        // Check the server response
        .then(function (response) {

            if (!response.ok) {
                throw new Error("HTTP error: " + response.status);
            }

            return response.json();

        })

        // Process the backend response
        .then(function (result) {

            registerMessage.textContent =
                result.message || "User already has an account. Please log in.";

            if (result.success) {

                console.log("Registration successful");

                // Send the user to the login page
                window.location.href = "login.php";

            } else {

                console.log("Registration failed");

            }

        })

        // Handle connection or response errors
        .catch(function (error) {

            console.error("Registration error:", error);

            registerMessage.textContent =
                "Could not connect to the registration server. Please try again.";

        });

    });
}


// ==========================================
// LOGIN
// ==========================================

const loginForm = document.getElementById("loginForm");

if (loginForm) {
    loginForm.addEventListener("submit", function (event) {

        // Prevent the page from reloading
        event.preventDefault();

        // Get the values entered in the form
        const email = document.getElementById("email").value.trim();
        const password = document.getElementById("password").value;

        const loginMessage = document.getElementById("loginMessage");

        // Check that the fields are not empty
        if (!email || !password) {
            loginMessage.textContent =
                "Please enter your email and password.";
            return;
        }

        // Prepare the data to send to the backend
        const data = {
            email: email,
            password: password
        };

        // Send the login request to James's backend
        fetch("http://100.110.65.121/login_handler.php", {
            method: "POST",

            headers: {
                "Content-Type": "application/json"
            },

            body: JSON.stringify(data)
        })

        // Check the server response
        .then(function (response) {

            if (!response.ok) {
                throw new Error("HTTP error: " + response.status);
            }

            return response.json();

        })

        // Process the backend response
        .then(function (result) {

            //loginMessage.textContent =
                //result.message || "Login request completed.";

            if (result.success) {

                console.log("Login successful");

                // Redirect to the dashboard
                window.location.href = "dashboard.php";

            } else {

                console.log("Login failed");

            }

        })

        // Handle connection or response errors
        .catch(function (error) {

            console.error("Login error:", error);

            loginMessage.textContent =
                "Could not connect to the login server. Please try again.";

        });

    });
}