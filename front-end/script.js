const registerForm = document.getElementById("registerForm");

if (registerForm) {

    registerForm.addEventListener("submit", function(event) {

        // when form is submitted, run this code
        event.preventDefault();

        const name = document.getElementById("name").value;
        const email = document.getElementById("email").value;
        const password = document.getElementById("password").value;
        const confirmPassword = document.getElementById("confirmPassword").value;

        // Check that the passwords match
        if (password !== confirmPassword) {

            document.getElementById("registerMessage").textContent =
                "Passwords do not match. Please try again.";

            return;
        }

        // Data we are sending to the backend
        const data = {

            name: name,
            email: email,
            password: password

        };

        // Send data to the backend
        fetch("/register.php", {

            method: "POST",

            headers: {

                "Content-Type": "application/json"

            },

            body: JSON.stringify(data)

        })

        // Receive the backend response
        .then(function(response) {

            return response.json();

        })

        // Use the backend response
        .then(function(result) {

            document.getElementById("registerMessage").textContent =
                result.message;

            if (result.success) {

                console.log("Registration successful");

            } else {

                console.log("Registration failed");

            }

        })

        // Handle connection errors
        .catch(function(error) {

            console.error("Error:", error);

            document.getElementById("registerMessage").textContent =
                "Could not connect to server.";

        });

    });

}