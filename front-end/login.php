<!DOCTYPE html>
<html lang="en">

    <head> 
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>Vellum Trading - Login</title>

        <link rel="stylesheet" href="style.css">
    </head>

    <body> 

        <main> 

            <h1>Vellum Trading</h1>
            <h2>Login</h2>

            <form id="loginForm">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>

                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>

                <button type="submit">Login</button>
            </form>

            <p id="loginMessage"></p>

            <p>
                Don't have an account yet?
                <a href="register.php">Create an account</a>
            </p>

       </main>

       <script src="script.js"></script>

    </body>

</html>