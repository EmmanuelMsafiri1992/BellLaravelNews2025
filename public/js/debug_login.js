console.log("DEBUG: Checking login form...");
setTimeout(() => {
    const loginForm = document.getElementById('loginForm');
    console.log("DEBUG: loginForm element:", loginForm);
    if (loginForm) {
        console.log("DEBUG: Adding test event listener");
        loginForm.addEventListener('submit', (e) => {
            console.log("DEBUG: Form submit event captured!");
        });
    } else {
        console.log("DEBUG: loginForm NOT FOUND!");
    }
}, 2000);
