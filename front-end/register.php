<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>Vellum Trading - Register</title>

        <link rel="stylesheet" href="style.css">
    </head>

    <body>
        <main>
            <h1>Vellum Trading</h1>

            <h2>Create an Account</h2>

            <form id="registerForm">

                <label for="name">Name</label>
                <input type="text" id="name" name="name" required>

                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>

                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>

                <label for="confirmPassword">Confirm Password</label>
                <input type="password" id="confirmPassword" name="confirmPassword" required>

                <button type="submit">Create Account</button>

            </form>

            <p id="registerMessage"></p>

            <p>
                Already have an account?
                <a href="login.php">Login</a>
            </p>

        </main>

        <script src="script.js"></script>

    </body>

</html>