// Selecting the mobile menu, the menu links, and the navbar logo
const menu = document.querySelector('#mobile-menu');
const menuLinks = document.querySelector('.navbar__menu');
const navLogo = document.querySelector('#navbar__logo');

// Selecting the Login and SignUp buttons
const login = document.querySelectorAll(".button");
const LoginButton = login[0];
const SignUpButton = login[1];

// Function to display the mobile menu
const mobileMenu = () => {
  menu.classList.toggle('is-active');
  menuLinks.classList.toggle('active');
};

// Adding an event listener to toggle the mobile menu on click
menu.addEventListener('click', mobileMenu);

// Adding an event listener to set localStorage to "active" on SignUpButton click
SignUpButton.addEventListener("click", function() {
  localStorage.setItem("key","active");
});

// Adding an event listener to set localStorage to "unactive" on LoginButton click
LoginButton.addEventListener("click", function() {
  localStorage.setItem("key","unactive");
});

// Function to highlight the active menu item when scrolling
const highlightMenu = () => {
  const elem = document.querySelector('.highlight');
  const homeMenu = document.querySelector('#home-page');
  const aboutMenu = document.querySelector('#about-page');
  const servicesMenu = document.querySelector('#services-page');
  let scrollPos = window.scrollY;

  // Conditionals to add the "highlight" class to the active menu item based on scroll position
  if (window.innerWidth > 960 && scrollPos < 600) {
    homeMenu.classList.add('highlight');
    aboutMenu.classList.remove('highlight');
    return;
  } else if (window.innerWidth > 960 && scrollPos < 1400) {
    aboutMenu.classList.add('highlight');
    homeMenu.classList.remove('highlight');
    servicesMenu.classList.remove('highlight');
    return;
  } else if (window.innerWidth > 960 && scrollPos < 2345) {
    servicesMenu.classList.add('highlight');
    aboutMenu.classList.remove('highlight');
    return;
  }

  // Removing the "highlight" class from the menu item when the user scrolls back up
  if ((elem && window.innerWIdth < 960 && scrollPos < 600) || elem) {
    elem.classList.remove('highlight');
  }
};

// Adding event listeners to highlight the active menu item when scrolling and clicking
window.addEventListener('scroll', highlightMenu);
window.addEventListener('click', highlightMenu);

// Function to close the mobile menu when clicking on a menu item
const hideMobileMenu = () => {
  const menuBars = document.querySelector('.is-active');

  // Conditionals to close the mobile menu if it's open and the window is less than or equal to 768px
  if (window.innerWidth <= 768 && menuBars) {
    menu.classList.toggle('is-active');
    menuLinks.classList.remove('active');
  }
};

// Adding an event listener to close the mobile menu when clicking on a menu item
menuLinks.addEventListener('click', hideMobileMenu);
navLogo.addEventListener('click', hideMobileMenu);
