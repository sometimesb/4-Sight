const logincontainer = document.querySelector(".logincontainer"),
    pwShowHide = document.querySelectorAll(".showHidePw"),
    pwFields = document.querySelectorAll(".password"),
    signUp = document.querySelector(".signup-link"),
    signUpButton = document.querySelector(".signup-button"),
    homeButton = document.querySelector(".titlehome"),
    login = document.querySelector(".login-link");

//   js code to show/hide password and change icon
pwShowHide.forEach((eyeIcon) => {
    eyeIcon.addEventListener("click", () => {
        pwFields.forEach((pwField) => {
            if (pwField.type === "password") {
                pwField.type = "text";

                pwShowHide.forEach((icon) => {
                    icon.classList.replace("uil-eye-slash", "uil-eye");
                });
            } else {
                pwField.type = "password";

                pwShowHide.forEach((icon) => {
                    icon.classList.replace("uil-eye", "uil-eye-slash");
                });
            }
        });
    });
});

// key = localStorage.getItem("key");
// console.log("Received from local: ", key);

// // js code to appear signup and login form
// signUp.addEventListener("click", () => {
//     logincontainer.classList.add("active");
//     localStorage.setItem("key", "active");
// });

// signUpButton.addEventListener("click", () => {
//     localStorage.setItem("key", "active");
//     logincontainer.classList.add("active");
//     console.log("Button Hit, active stored");
// });

// login.addEventListener("click", () => {
//     logincontainer.classList.remove("active");
//     localStorage.setItem("key", "unactive");
//     console.log("Active Removed1");
// });

// if (key == "active") {
//     logincontainer.classList.add("active");
// }

// document.querySelector(".titlehome").addEventListener("click", function () {
//     logincontainer.classList.remove("active");
//     localStorage.setItem("key", "unactive");
//     console.log("Active Removed2");
//     this.style.backgroundColor = "red";
// });

// document.querySelector(".titlehome2").addEventListener("click", function () {
//     logincontainer.classList.remove("active");
//     localStorage.setItem("key", "unactive");
//     console.log("Active Removed3");
//     this.style.backgroundColor = "red";
// });

//optimized version.
const setActive = () => {
    logincontainer.classList.add("active");
    localStorage.setItem("key", "active");
};

const setUnactive = () => {
    logincontainer.classList.remove("active");
    localStorage.setItem("key", "unactive");
};

const updateContainer = () => {
    key = localStorage.getItem("key");
    if (key === "active") {
        setActive();
    } else {
        setUnactive();
    }
};

signUp.addEventListener("click", setActive);
signUpButton.addEventListener("click", setActive);
login.addEventListener("click", setUnactive);

document.querySelector(".titlehome").addEventListener("click", setUnactive);
document.querySelector(".titlehome2").addEventListener("click", setUnactive);

updateContainer();