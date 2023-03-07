<?php
if (isset($_POST['signup-submit']))
{
    require 'dbh.inc.php';

    //superglobal variables. Updated to sanitize inputs for safety.
    $username = htmlspecialchars($_POST['uid']);
    $email = htmlspecialchars($_POST['mail']);
    $password = htmlspecialchars($_POST['pwd']);
    $uniquecode = htmlspecialchars($_POST['upo']);

    //Start of Input Checks
    if (empty($username) || empty($email) || empty($password))
    {
        header("Location: ../login.php?error=emptyfields&uid=" . $username . "&mail=" . $email);
        exit();
    }

    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) && !preg_match("/^[a-zA-Z0-9]*$/", $username))
    {
        header("Location: ../login.php?error=invalidmailuid");
        exit();
    }

    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
    {
        header("Location: ../login.php?error=invalidmail&uid=" . $username);
        exit();
    }

    elseif (!preg_match("/^[a-zA-Z0-9]*$/", $username))
    {
        header("Location: ../login.php?error=invaliduid");
        exit();
    }

    elseif (strlen($uniquecode) != 20)
    {
        header("Location: ../login.php?error=invaliduniquecode");
        exit();
    }

    elseif (strlen($password) < 8)
    {
        header("Location: ../login.php?error=passwordtoshort");
        exit();
    }
    //End of Input Checks
    else
    {
        $sql = "SELECT uidUsers FROM users WHERE uidUsers=?";
        $stmt = mysqli_stmt_init($conn);
        if (!mysqli_stmt_prepare($stmt, $sql))
        {
            header("Location: ../login.php?error=sqlerror");
            exit();
        }
        else
        {
            //Checks username
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            $resultCheck = mysqli_stmt_num_rows($stmt);
            if ($resultCheck > 0)
            {
                header("Location: ../login.php?error=usertaken&mail=" . $email);
                exit();
            }

            //checks email
            $sql = "SELECT emailUsers FROM users WHERE emailUsers=?";
            $stmt = mysqli_stmt_init($conn);
            mysqli_stmt_prepare($stmt, $sql);
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            $resultCheck = mysqli_stmt_num_rows($stmt);

            //checks unique code 
            $sql2 = "SELECT orderkeys FROM productkeys WHERE orderkeys=?";
            $stmt2 = mysqli_stmt_init($conn);
            mysqli_stmt_prepare($stmt2, $sql2);
            mysqli_stmt_bind_param($stmt2, "s", $uniquecode);
            mysqli_stmt_execute($stmt2);
            mysqli_stmt_store_result($stmt2);
            $resultCheck2 = mysqli_stmt_num_rows($stmt2);

            if ($resultCheck > 0)
            {
                header("Location: ../login.php?error=emailtaken");
                exit();
            }
            elseif ($resultCheck2 == 0)
            {
                header("Location: ../login.php?error=uniqueidincorrect");
                exit();
            }

            else
            {
                //if it passes all checks, make a user account.
                $sql = "INSERT INTO users (uidUsers, emailUsers,pwdUsers) VALUES (?,?,?)";

                $stmt = mysqli_stmt_init($conn);
                if (!mysqli_stmt_prepare($stmt, $sql))
                {
                    header("Location: ../login.php?error=sqlerror");
                    exit();
                }
                else
                {
                    //Finish Generating Account
                    $hashedPwd = password_hash($password, PASSWORD_DEFAULT);
                    mysqli_stmt_bind_param($stmt, "sss", $username, $email, $hashedPwd);
                    mysqli_stmt_execute($stmt);

                    // Associate unique product key with account
                    $sql = "UPDATE users SET orderkey=? WHERE emailUsers=?";
                    $stmt = mysqli_stmt_init($conn);
                    mysqli_stmt_prepare($stmt, $sql);
                    mysqli_stmt_bind_param($stmt, "ss", $uniquecode, $email);
                    mysqli_stmt_execute($stmt);

                    // Delete the unique code from the productkeys table
                    $sql = "DELETE FROM productkeys WHERE orderkeys = ?";
                    mysqli_stmt_prepare($stmt, $sql);
                    mysqli_stmt_bind_param($stmt, "s", $uniquecode);
                    mysqli_stmt_execute($stmt);

                    // Make table for user when they make an account
                    $tableName = $username;
                    $sql = "CREATE TABLE $tableName (WaypointNum INT NOT NULL PRIMARY KEY AUTO_INCREMENT, Coordinate DOUBLE NOT NULL)";
                    $stmt = mysqli_stmt_init($conn);
                    mysqli_stmt_prepare($stmt, $sql);
                    mysqli_stmt_execute($stmt);
                    
                    //Time dependent tables for updating data.
                    $tableName = $username . "_time";

                    // SQL statement to create the table
                    $sql = "CREATE TABLE $tableName (
                        DataNum INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
                        x_coord DOUBLE NOT NULL,
                        y_coord DOUBLE NOT NULL,
                        temperature DOUBLE NOT NULL,
                        humidity DOUBLE NOT NULL,
                        air_quality DOUBLE NOT NULL
                    )";
                
                    // Prepare and execute the statement
                    $stmt = mysqli_stmt_init($conn);
                    mysqli_stmt_prepare($stmt, $sql);
                    mysqli_stmt_execute($stmt);

                    session_start();
                    $_SESSION['userUid'] = $username;

                    header("Location: ../../operation/navigationpage.php");
                    exit();
                }
            }
        }
    }
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
}
else
{
    header("Location: ../login.php?");
    exit();
}

