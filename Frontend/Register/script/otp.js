import config from '../../config.js';
const notyf = new Notyf();


function digitValidate(ele) {
    ele.value = ele.value.replace(/[^0-9]/g, '');
}


function tabChange(val) {
    let ele = document.querySelectorAll('.otp');
    if (ele[val - 1].value !== '' && val < ele.length) {
        ele[val].focus();
    } else if (ele[val - 1].value === '' && val > 1) {
        ele[val - 2].focus();
    }
}

function verifyOTP() {
    const email = localStorage.getItem("email") || "";
    if (!email) {
        notyf.error("Email not found in localStorage!");
        return;
    }

    // Get OTP input
    const otpInputs = document.querySelectorAll('.otp');
    let otp = "";
    otpInputs.forEach(input => otp += input.value);

   

    const requestData = {
        register: true,
        email: email,
        otp: otp
    };

    fetch("http://localhost/skillSwap/skill-swap/register.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(requestData)
    })
    .then(response => response.json())
    .then(data => {
        console.log("API Response:", data);
        if (data.success) {
            notyf.success("OTP verified successfully!");
            loginUser(email);
        } else {
            notyf.error(data.message || "OTP verification failed!");
        }
    })
    .catch(error => {
        console.error("Error:", error);
        notyf.error("An error occurred while verifying OTP.");
    });
}

function loginUser(email) {
const loginData = {
username: email,
password: localStorage.getItem("password"),
login: true
};

fetch("http://localhost/skillSwap/skill-swap/login_and_logout.php", {
method: "POST",
headers: {
    "Content-Type": "application/json"
},
body: JSON.stringify(loginData)
})
.then(response => response.json())
.then(data => {
console.log("Login Response:", data);
if (data.status === "success") {
    notyf.success(data.message);
    localStorage.setItem("JWT",data.token);

    
    window.location.href = "/dashboard.html"; // Redirect to dashboard or home
} else {
    notyf.error(data.message || "Login failed!");
    alert(localStorage.getItem("password"))
}
})
.catch(error => {
console.error("Error:", error);
notyf.error("An error occurred while logging in.");
});

}



function resendOTP() {
alert('hi')
const email = localStorage.getItem("email") || "";
if (!email) {
    notyf.error("Email not found in localStorage!");
    return;
}

const requestData = {
    resend_otp: true,
    email: email
};

fetch("http://localhost/skillSwap/skill-swap/register.php", {
    method: "POST",
    headers: {
        "Content-Type": "application/json"
    },
    body: JSON.stringify(requestData)
})
.then(response => response.json())
.then(data => {
    console.log("Resend OTP Response:", data);
    if (data.success) {
        notyf.success("OTP resent successfully!");
    } else {
        notyf.error(data.message || "Failed to resend OTP!");
    }
})
.catch(error => {
    console.error("Error:", error);
    notyf.error("An error occurred while resending OTP.");
});
}