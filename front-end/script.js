const registerForm = document.getElementById("registerForm");

if (registerForm) {
    registerForm.addEventListener("submit", function(event) { //when form submitted --> run this code (below)
        event.preventDefault();


        const name = document.getElementById("name").value;
        const email = document.getElementById("email").value;
        const password = document.getElementById("password").value; //.value means give me whatever the user typed into the box
        const confirmPassword = document.getElementById("confirmPassword").value;

        console.log("Name:", name);
        console.log("Email:", email);
        console.log("Password:", password);
        console.log("Confirm Password", confirmPassword);

    });
}


const loginForm = document.getElementById("loginForm"); //find html element with ID loginForm and save as loginForm

if (loginForm) {
    loginForm.addEventListener("submit", function(event) { //when form submitted --> run this code (below)
        event.preventDefault();

        const email = document.getElementById("email").value;
        const password = document.getElementById("password").value; //.value means give me whatever the user typed into the box

        console.log("Email:", email);
        console.log("Password:", password);

    });

}