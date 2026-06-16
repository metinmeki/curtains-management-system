// Variables to remember which store the user clicked
let targetStoreId = null;
let targetStoreType = null;

// 1. When a user clicks a store card
function selectStore(storeId, type) {
    console.log("Store clicked! ID:", storeId, "Type:", type);
    targetStoreId = storeId;
    targetStoreType = type;
    
    let title = storeId === 5 ? "Main Warehouse Login" : "Store " + storeId + " Login";
    document.getElementById("modalTitle").innerText = title;
    document.getElementById("loginModal").style.display = "flex";
}

// 2. Close modal
function closeModal() {
    document.getElementById("loginModal").style.display = "none";
    document.getElementById("loginError").style.display = "none";
    document.getElementById("storeLoginForm").reset();
}

// 3. Handle the Login attempt
document.addEventListener("DOMContentLoaded", function() {
    console.log("JavaScript is loaded and ready!");
    
    const loginForm = document.getElementById("storeLoginForm");
    const loginError = document.getElementById("loginError");

    if (loginForm) {
        loginForm.addEventListener("submit", function(event) {
            event.preventDefault(); // يمنع الصفحة من عمل Refresh
            
            // قراءة الإيميل والباسوورد (مع مسح أي مسافات زائدة)
            const email = document.getElementById("loginEmail").value.trim();
            const password = document.getElementById("loginPassword").value.trim();

            console.log("Attempting login with:", email, password);
            console.log("Target Store ID is:", targetStoreId);

            // إخفاء رسالة الخطأ أولاً
            loginError.style.display = "none";

            // --- الفحص ---
            if (email === "admin@admin.com" && password === "123456") {
                console.log("Owner success!");
                grantAccess("owner");
            } 
            else if (email === "emp1@curtains.com" && password === "123456" && targetStoreId === 1) {
                console.log("Employee success!");
                grantAccess("store_user");
            } 
            else {
                console.log("Login failed! Condition not met.");
                loginError.style.display = "block";
                loginError.innerText = "Access Denied. Wrong email/password or wrong store.";
            }
        });
    } else {
        console.error("CRITICAL ERROR: Could not find the form with id 'storeLoginForm'. Make sure you added the Modal HTML to index.html!");
    }
});

function grantAccess(role) {
    console.log("Granting access... Redirecting...");
    localStorage.setItem("user_token", "fake_token_123");
    localStorage.setItem("active_store_id", targetStoreId);
    localStorage.setItem("user_role", role);

    if (targetStoreType === 'retail') {
        window.location.href = "pages/retail/dashboard.html";
    } else {
        window.location.href = "pages/inventory/dashboard.html";
    }
}