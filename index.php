<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="UTF-8" />
      <meta name="viewport" content="width=device-width, initial-scale=1.0" />
      <title>Project 4-Sight</title>
      <link rel="icon" href="./logo.png">
      <link rel="stylesheet" href="styles.css" />
      <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.14.0/css/all.css" integrity="sha384-HzLeBuhoNPvSl5KYnjx0BT+WB0QEEqLprO+NBkkk5gbc67FTaL7XIGa2w1L0Xbgc" crossorigin="anonymous" />
   </head>
   <body>
      <!-- Navbar Section -->
      <nav class="navbar">
         <div class="navbar__container">
            <a href="../index.php#home" class="animate-charcter">4-SIGHT</a>
            <div class="navbar__toggle" id="mobile-menu">
               <span class="bar"></span> <span class="bar"></span>
               <span class="bar"></span>
            </div>
            <ul class="navbar__menu">
               <li class="navbar__item">
                  <a href="#home" class="navbar__links" id="home-page">Home</a>
               </li>
               <li class="navbar__item">
                  <a href="#about" class="navbar__links" id="about-page">About</a>
               </li>
               <li class="navbar__item">
                  <a href="#services" class="navbar__links" id="services-page">Services</a
                     >
               </li>
               <li class="navbar__btn">    
                  <?php if (!isset($_SESSION['userUid'])) {
                     echo '<a href="./login/login.php"  class="button" id="Login">Log in</a>'; } ?>
               </li>
               <li class="navbar__btn">
                  <?php if (!isset($_SESSION[ 'userUid'])) { echo '<a href="./login/login.php" class="button" id="Register" style="max-height: 100%; white-space:nowrap;">Sign Up</a>'; } else { echo '<a href="./login/includes/logout.inc.php" class="button" id="signup">Logout</a>'; echo '<li class="navbar__btn">    
                     <a href="./operation/navigationpage.php" class="button" id="signup">Navigation</a>
                     </li>'; } ?>
               </li>
            </ul>
         </div>
      </nav>
      <!-- Hero Section -->
      <div class="hero" id="home">
         <div class="hero__container">
            <h1 class="hero__heading">Improve your <span>life</span></h1>
            <p class="hero__description">Unlimited Possibilities</p>
            <button class="main__btn"><a href="#services">Explore</a>
            </button>
         </div>
      </div>
      <!-- About Section -->
      <div class="main" id="about">
         <div class="main__container">
            <div class="main__img--container">
               <div class="main__img--card">
                  <img src="logo.png" alt="">
               </div>
            </div>
            <div class="main__content">
               <h1>What do we do?</h1>
               <h2>We help the visually impaired</h2>
               <p>Contact us to learn more about our services</p>
               <button class="main__btn"><a href="mailto:bigbob@gmail.com">Contact Us</a></button>
            </div>
         </div>
      </div>
      <!-- Services Section -->
      <div class="services" id="services">
         <h1>Our Services</h1>
         <div class="services__wrapper">
            <div class="services__card">
               <h2>Custom Navigation</h2>
               <p>Google-like based Routing</p>
               <div class="services__btn">
                  <button>
                     <a href="#sign-up" style="text-decoration: none; color: white;">Get Started</a>
               </div>
            </div>
            <div class="services__card">
             <h2>Obstacle Detection</h2>
            <p>Automatic detection of objects</p>
            <div class="services__btn">
            <button><a href="#sign-up" style="text-decoration: none; color: white;">Get Started</a>
            </div>
            </div>
            <div class="services__card">
            <h2>Air Sensors</h2>
            <p>Detects harmful gas in the air</p>
            <div class="services__btn">
            <button><a href="#sign-up" style="text-decoration: none; color: white;">Get Started</a>
            </div>
            </div>
            <div class="services__card">
            <h2>Find My Buddy</h2>
            <p>GPS Enabled Hardware</p>
            <div class="services__btn">
            <button><a href="#sign-up" style="text-decoration: none; color: white;">Get Started</a>
            </button>
            </div>
            </div>
         </div>
      </div>
      <!-- Features Section -->
      <div class="main" id="sign-up">
         <div class="main__container">
            <div class="main__content">
               <h1>Interested?</h1>
               <h2>Sign Up Today</h2>
               <p>See what makes us different</p>
               <button class="main__btn"><a href="./login/login.php">Sign Up</a>
               </button>
            </div>
            <div class="main__img--container">
               <div class="main__img--card" id="card-2">
                  <i class="fas fa-users"></i>
               </div>
            </div>
         </div>
      </div>
      <!-- Footer Section -->
      <div class="footer__container">
         <div class="footer__links">
            <div class="footer__link--wrapper">
               <div class="footer__link--items">
                  <h2>About Us</h2>
                  <a href="./login/tos.html">Terms of Service</a>
               </div>
               <div class="footer__link--items">
                  <h2>Contact Us</h2>
                  <a href="mailto:bigbob@gmail.com">Contact Us</a>
               </div>
            </div>
            <div class="footer__link--wrapper">
               <div class="footer__link--items">
                  <h2>Videos</h2>
                  <a href="/">Support Videos[WIP]</a>
               </div>
               <!---
                  <div class="footer__link--items">
                    <h2>Social Media</h2>
                    <a href="/">Instagram</a> <a href="/">Facebook</a>
                    <a href="/">Youtube</a> <a href="/">Twitter</a>
                  </div>
                  -->
            </div>
         </div>
         <section class="social__media">
            <div class="social__media--wrap">
               <div class="footer__logo">
                  <a href="/" id="footer__logo">4-SIGHT</a>
               </div>
               <p class="website__rights">© 4-SIGHT 2023. All rights reserved</p>
               <div class="social__icons">
                  <!--             
                     <a href="/" class="social__icon--link" target="_blank"
                       ><i class="fab fa-facebook"></i
                     ></a>
                     
                     <a href="/" class="social__icon--link"
                       ><i class="fab fa-instagram"></i
                     ></a>
                     
                     <a href="/" class="social__icon--link"
                       ><i class="fab fa-youtube"></i
                     ></a>
                     -->
                  <a href="https://www.linkedin.com/in/bilalfzakaria/" target="_blank" class="social__icon--link"><i class="fab fa-linkedin"></i
                     ></a>
                  <!--
                     <a href="/" class="social__icon--link"
                       ><i class="fab fa-twitter"></i
                     ></a>
                     -->
               </div>
            </div>
         </section>
      </div>
      <script type="module" src="app.js"></script>
   </body>
</html>