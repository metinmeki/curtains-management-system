// Wait for the page to load
document.addEventListener("DOMContentLoaded", function() {
    
    const loginForm = document.getElementById("loginForm");
    const errorMessage = document.getElementById("errorMessage");

    // When the user clicks the Login button
    if(loginForm) {
        loginForm.addEventListener("submit", function(event) {
            // Prevent the page from refreshing
            event.preventDefault(); 

            // Get the values typed by the user
            const email = document.getElementById("email").value;
            const password = document.getElementById("password").value;

            // --- TEMPORARY TEST (Since Blnd's API might not be ready yet) ---
            if (email === "admin@admin.com" && password === "123456") {
                // Success! Save fake token and redirect
                localStorage.setItem("user_role", "owner");
                localStorage.setItem("user_token", "fake_token_123");
                
                alert("Login Successful! Redirecting to Dashboard...");
                // Redirect to the main selection page
                window.location.href = "../../index.html"; 
            } else {
                // Fail! Show error
                errorMessage.style.display = "block";
                errorMessage.innerText = "Wrong email or password!";
            }
        });
    }
});