<?php
session_start();


if (!isset($_SESSION["userUid"])) 
{
    header("Location: ../index.php");
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>4-Sight Navigation</title>
    <link rel="icon" href="../logo.png">
    <link rel="stylesheet" href="./styles/nav.css" />
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.14.0/css/all.css" integrity="sha384-HzLeBuhoNPvSl5KYnjx0BT+WB0QEEqLprO+NBkkk5gbc67FTaL7XIGa2w1L0Xbgc" crossorigin="anonymous" />
    <link rel="stylesheet" href="./styles/autocomplete.css">
    <link rel="stylesheet" href="./styles/mapstyles.css">
  </head>
  <body>
    <!-- Navbar Section -->
    <nav class="navbar">
      <div class="navbar__container">
        <a href="../index.php#home" class="animate-charcter">4-SIGHT</a>
        <!-- <a href="../index.php#home" id="navbar__logo">4-SIGHT</a> -->
        <div class="navbar__toggle" id="mobile-menu">
          <span class="bar"></span>
          <span class="bar"></span>
          <span class="bar"></span>
        </div>
        <ul class="navbar__menu">
          <li class="navbar__item">
            <a href="../index.php#home" class="navbar__links" id="home-page">Home</a>
          </li>
          <li class="navbar__item">
            <a href="../index.php#about" class="navbar__links" id="about-page">About</a>
          </li>
          <li class="navbar__item">
            <a href="../index.php#services" class="navbar__links" id="services-page">Services</a>
          </li>
          <li class="navbar__btn"> 
            <?php if (!isset($_SESSION["userUid"])) {
              echo '
            <a href="./login/login.php"  class="buttonnav" id="Login">Login</a>';
          } ?> 
          </li>
          <li class="navbar__btn"> 
            <?php if (!isset($_SESSION["userUid"])) {
              echo '
            <a href="./login/login.php" class="buttonnav" id="Register" style="max-height: 100%; white-space:nowrap;">Sign Up</a>';
          } else {
              echo '
            <a href="../login/includes/logout.inc.php" class="button" id="signup">Logout</a>';
          } ?> 
          </li>
        </ul>
      </div>
    </nav>
    <?php 
    $error_messages = [
      'noroute' => '<div class="errorstyle">No route found!</div>',
      'waypointsexceed' => '<div class="errorstyle">Route too long!</div>',
      'success' => '<div class="successstyle">Route saved to jacket!</div>',
      'emptyfields' => '<div class="errorstyle">Please fill in all fields!</div>',
      'apidownerror' => '<div class="errorstyle">Main API Error!</div>',
      'norouteinprogress' => '<div class="errorstyle">No Route To Cancel!</div>',
      'emptytable' => '<div class="successstyle">Route Canceled!</div>',
      'routetooclose' => '<div class="errorstyle">Route too Close!</div>'
    ];


    $error = @$_GET["error"];
    if (array_key_exists($error, $error_messages)) {
        echo $error_messages[$error];
        if ($error === 'norouteinprogress' || $error === 'emptytable') {
            unset($_SESSION['base64']);
            unset($_SESSION['base64optimized']);
        }
    } else {
        echo '<div class="successstyle">Enter starting and ending locations</div>';
    }
?>

<form action="nav.php" method="post">
    <div class="autocomplete-container" id="autocomplete-container"></div>
    <div class="autocomplete2-container2" id="autocomplete2-container2"></div>
    <Input type="hidden" name="coordinateonex" id="coordinateonex" value="If your reading this, help me pls.">
    <Input type="hidden" name="coordinateoney" id="coordinateoney" value="">
    <Input type="hidden" name="coordinatetwox" id="coordinatetwox" value="">
    <Input type="hidden" name="coordinatetwoy" id="coordinatetwoy" value="">
    <div class="button-container">
        <div class="input-field-button-delete">
            <input type="submit" class="submitnav" name="navigation-delete" value="Cancel">
        </div>
        <div class="input-field-button">
            <input type="submit" class="submitnav" name="navigation-submit" value="Begin">
        </div>
    </div>
</form>

<?php 
 if (isset($_SESSION["base64"]) || isset($_SESSION["base64optimized"])) : ?>
  <div class="image-container">
      <?php if (isset($_SESSION["base64"])) : ?>
          <div class="image-wrapper">
              <img src="data:image/png;base64,<?= $_SESSION["base64"] ?>" alt="Static Map" onclick="expandImage(this)">
              <div class="image-text">Original</div>
          </div>
      <?php endif; ?>
      <?php if (isset($_SESSION["base64optimized"])) : ?>
          <div class="image-wrapper">
              <img src="data:image/png;base64,<?= $_SESSION["base64optimized"] ?>" alt="Optimized Map" onclick="expandImage(this)">
              <div class="image-text">Optimized</div>
          </div>
      <?php endif; ?>
  </div>

  <div id="image-modal" onclick="closeModal(event)">
      <span class="close-button" onclick="closeModal(event)">&times;</span>
      <img id="modal-image" src="">
  </div>
  <script src="./scripts/maphelper.js"></script>
<?php endif; ?>


<script src="./scripts/script.js"></script>
<script src="./scripts/script2.js"></script>
<script src="../app.js"></script>
<style></style>
</body>
</html>
