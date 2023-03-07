<?php
session_start(); 
?>

<!DOCTYPE html>
<!-- === Coding by CodingLab | www.codinglabweb.com === -->
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- ===== Iconscout CSS ===== -->
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">

    <!-- ===== CSS ===== -->
    <link rel="stylesheet" href="styles.css">
         
    <title>4-Sight Login And Registration</title>
    <link rel="icon" href="../logo.png">
</head>
<body>
    <div class="logincontainer">
        <div class="forms">
            <div class="form login">
                <span class="title">Login</span>
                <a href="../index.php"><button class="titlehome">Home</button></a>
                <?php
                $error_messages = [
                    "wrongpwd" => "Incorrect data entered!",
                    "emptyfields" => "Fill in all the fields!",
                ];

                if (
                    isset($_GET["error"]) &&
                    array_key_exists($_GET["error"], $error_messages)
                ) {
                    echo '<p style="color: red;">' .
                        $error_messages[$_GET["error"]] .
                        "</p>";
                }
                ?>
                <form action="includes/login.inc.php" method="post">
                    <div class="input-field">
                        <input type="text" name="mailuid" placeholder="Enter your username or email" ><i class="uil uil-user"></i>
                    </div>
                    <div class="input-field">
                        <input type="password" name="pwd" class="password" placeholder="Enter your password" ><i class="uil uil-lock icon"></i><i class="uil uil-eye-slash showHidePw"></i>
                    </div>

                    <!-- <div class="checkbox-text">
                        <a href="#" class="text">Forgot password?</a>
                    </div> -->

                    <div class="input-field button">
                        <input type="submit" name = "login-submit" value="Login">
                    </div>

                </form>

                <div class="login-signup">
                    <span class="text">Not a member?
                        <a href="" class="text signup-link">Signup Now</a>
                    </span>
                </div>
            </div>

<!-- Registration Form -->
<link rel="stylesheet" href="styles.css">

<div class="form signup">
    <span class="title">Registration</span>
        <a href="../index.php"><button class="titlehome2">Home</button></a>
        <?php
        $error_messages = [
            "emptyfields" => "Fill in all the fields!",
            "invalidmailuid" => "Invalid username e-mail!",
            "invaliduid" => "Invalid username",
            "invalidmail" => "Invalid e-mail!",
            "emailtaken" => "E-mail is already taken!",
            "passwordcheck" => "Your passwords do not match!",
            "usertaken" => "Username is already taken!",
            "passwordtoshort" => "Password is too short, minimum length 8!",
            "invaliduniquecode" => "Unique code format not correct!",
            "uniqueidincorrect" => "Unique code not valid!",
        ];

        if (
            isset($_GET["error"]) &&
            array_key_exists($_GET["error"], $error_messages)
        ) {
            echo '<p style="color: red;">' .
                $error_messages[$_GET["error"]] .
                "</p>";
        }
        ?>

    <form action="includes/signup.inc.php" method="post">
        <div class="input-field">
            <input type="text" name="uid" placeholder="Enter your username" >
            <i class="uil uil-user"></i>
        </div>
        <div class="input-field">
            <input type="text" name ="mail" placeholder="Enter your email" >
            <i class="uil uil-envelope icon"></i>
        </div>
        <div class="input-field">
            <input type="text" name="upo" placeholder="Enter your unique code" >
            <i class="uil uil-qrcode-scan"></i>
        </div>

        <div class="input-field">
            <input type="password" name="pwd" class="password" placeholder="Create a password" >
            <i class="uil uil-lock icon"></i>
            <i class="uil uil-eye-slash showHidePw"></i>
        </div>

        <div class="checkbox-text">
            <div class="checkbox-content">
                <input type="checkbox" id="termCon" required>
                <label for="termCon" class="text">I accepted all <a href = "tos.html"> terms and conditions</a></label>
            </div>
        </div>
            


        <div class="input-field button">
            <input type="submit" class="text signup-button" name="signup-submit" value="Sign Up!">
        </div>
    </form>

    <div class="login-signup">
        <span class="text">Already a member?
            <a href="login.php" class="text login-link">Login Now</a>
        </span>
    </div>
</div>
</div>


<script src="script.js"></script>
