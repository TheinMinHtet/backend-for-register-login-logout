import config from '../../config.js';
        const notyf = new Notyf();

        const phoneInput = document.querySelector("#phone");
        const iti = window.intlTelInput(phoneInput, {
            initialCountry: "mm", // Default country set to Myanmar (+95)
            separateDialCode: true,
            utilsScript: "./build/js/utils.js",
        });

        document.addEventListener("DOMContentLoaded", () => {
            const submitButton = document.querySelector(".submit-btn");
            const backButton = document.querySelector("#back");
        
            if (submitButton) {
                submitButton.addEventListener("click", nextPage);
            }
            backButton.addEventListener("click",goBack);
        });

    function nextPage(event) {
    event.preventDefault(); // Prevent default form submission

    const fullPhoneNumber = iti.getNumber(); // Get the full number with country code
    const countryCode = iti.getSelectedCountryData().dialCode; // Get country code (e.g., "95")
    const phoneNumberWithoutCode = fullPhoneNumber.replace(`+${countryCode}`, "").trim(); // Remove country code
    const telegramName = document.getElementById("telegram").value.trim();

    // Validate phone number
    if (!iti.isValidNumber()) {
        notyf.error("Invalid phone number!");
        return;
    }

    // Validate Telegram username
    if (!telegramName.startsWith("@") || telegramName.length < 2) {
        notyf.error("Telegram username must start with '@' and have at least 2 characters!");
        return;
    }

    // Store values in localStorage
    localStorage.setItem("countryCode", `+${countryCode}`);
    localStorage.setItem("phoneNumber", phoneNumberWithoutCode);
    localStorage.setItem("telegramName", telegramName);

    notyf.success("Credentials saved successfully!");

    const username = localStorage.getItem("username") || "" ;
    const email = localStorage.getItem("email") || "" ;
    const password =  localStorage.getItem("password") || "" ;

    // Create JSON data for API request
    const requestData = {
        username: username, // Replace with actual username if available
        email: email, // Replace with actual email if available
        password: password, // Replace with actual password if available
        confirm_psw: password,
        country_code: `+${countryCode}`,
        phone: phoneNumberWithoutCode,
        tele_user_name: telegramName,
        send_otp: true
    };

    





    // Send data to API
    fetch(config.register, {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
    username: username.replace(/_/g, " "), 
    email: email, 
    password: password,  
    confirm_psw: password,
    country_code: `+${countryCode}`,
    phone: phoneNumberWithoutCode,
    tele_user_name: telegramName,
    send_otp: true
})

    })
    .then(response => response.json()) // Convert response to JSON
    .then(data => {
        console.log("API Response:", data);
        if (data.success) {
            notyf.success(data.success);
            setTimeout(() => {
                window.location.href = "./OTP-Code.html"; // Change this to your next page
            }, 1500);
        } else {
            notyf.error(data.error || "Registration failed!");
        }
    })
    .catch(error => {
        console.error("Error:", error);
        notyf.error("An error occurred while registering!");
    });
}

function goBack() {
            window.history.back();
        }