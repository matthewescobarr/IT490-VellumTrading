document.addEventListener("DOMContentLoaded", function () {

    // ==========================================
    // LOGIN FORM
    // ==========================================

    const loginForm = document.getElementById("loginForm");

    if (loginForm) {
        loginForm.addEventListener("submit", function (event) {
            event.preventDefault();

            const email = document.getElementById("email").value.trim();
            const password = document.getElementById("password").value;
            const loginMessage = document.getElementById("loginMessage");

            // Clear previous messages
            if (loginMessage) {
                loginMessage.textContent = "";
            }

            // Validate input
            if (!email || !password) {
                if (loginMessage) {
                    loginMessage.textContent =
                        "Please enter your email and password.";
                }
                return;
            }


            //TROUBLESHOOTING
            console.log("Sending login request to backend...");
            // Send login information to the backend
            fetch("http://100.110.65.121/login_handler.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    email: email,
                    password: password
                })
            })

            .then(function (response) {
                return response.text().then(function (text) {
                    let result;

                    try {
                        result = JSON.parse(text);
                    } catch (error) {
                        console.error("Invalid login response:", text);
                        throw new Error(
                            "The server returned an invalid login response."
                        );
                    }

                    if (!response.ok) {
                        throw new Error(
                            result.message || "The login request failed."
                        );
                    }

                    return result;
                });
            })

            .then(function (result) {

                // Check whether login succeeded
                if (!result.success || !result.sessionKey) {
                    throw new Error(
                        result.message || "Login failed. Check your credentials."
                    );
                }



                //TROUBLESHOOTING
                console.log("Backend login succeeded. Creating PHP session...");
                
                // Create the local PHP session
                return fetch("setSession.php", {
                    method: "POST",
                    credentials: "same-origin",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        sessionKey: result.sessionKey
                    })
                });
            })

            .then(function (response) {
                return response.text().then(function (text) {
                    let sessionResult;

                    try {
                        sessionResult = JSON.parse(text);
                    } catch (error) {
                        console.error("Invalid session response:", text);
                        throw new Error(
                            "The session server returned an invalid response."
                        );
                    }

                    if (!response.ok || !sessionResult.success) {
                        throw new Error(
                            sessionResult.message ||
                            "Could not establish your session."
                        );
                    }

                    return sessionResult;
                });
            })

            .then(function () {
                // Redirect after successful login and session creation
                window.location.href = "index.php";
            })

            .catch(function (error) {
                console.error("Login error:", error);

                if (loginMessage) {
                    loginMessage.textContent =
                        error.message || "Unable to log in. Please try again.";
                }
            });
        });
    }


    // ==========================================
    // REGISTRATION FORM
    // ==========================================

    const registerForm = document.getElementById("registerForm");

    if (registerForm) {
        registerForm.addEventListener("submit", function (event) {
            event.preventDefault();

            const name = document.getElementById("name").value.trim();
            const email = document.getElementById("email").value.trim();
            const password = document.getElementById("password").value;
            const confirmPassword =
                document.getElementById("confirmPassword").value;

            const registerMessage =
                document.getElementById("registerMessage");

            // Clear previous messages
            if (registerMessage) {
                registerMessage.textContent = "";
            }

            // Validate input
            if (!name || !email || !password || !confirmPassword) {
                if (registerMessage) {
                    registerMessage.textContent =
                        "Please complete all fields.";
                }
                return;
            }

            // Ensure passwords match
            if (password !== confirmPassword) {
                if (registerMessage) {
                    registerMessage.textContent =
                        "Passwords do not match.";
                }
                return;
            }

            //TROUBLESHOOTING
            console.log("Sending login request to backend...");

            // Send registration information to the backend
            fetch("http://100.110.65.121/register_handler.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    name: name,
                    email: email,
                    password: password
                })
            })

            .then(function (response) {
                return response.text().then(function (text) {
                    let result;

                    try {
                        result = JSON.parse(text);
                    } catch (error) {
                        console.error(
                            "Invalid registration response:",
                            text
                        );

                        throw new Error(
                            "The server returned an invalid registration response."
                        );
                    }

                    if (!response.ok) {
                        throw new Error(
                            result.message ||
                            "The registration request failed."
                        );
                    }

                    return result;
                });
            })

            .then(function (result) {

                if (result.success) {

                    if (registerMessage) {
                        registerMessage.textContent =
                            "Registration successful! Redirecting to login...";
                    }

                    // Redirect to login after successful registration
                    setTimeout(function () {
                        window.location.href = "login.php";
                    }, 1500);

                } else {

                    if (registerMessage) {
                        registerMessage.textContent =
                            result.message ||
                            "User already exists. Please login.";
                    }
                }
            })

            .catch(function (error) {
                console.error("Registration error:", error);

                if (registerMessage) {
                    registerMessage.textContent =
                        error.message ||
                        "Unable to register. Please try again.";
                }
            });
        });
    }

});