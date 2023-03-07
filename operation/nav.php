<?php
session_start();

function beginNavigation(){
    // Include required files
    require_once 'mapper.php';

    // Get form data
    $startx = $_POST["coordinateonex"] ?? null;
    $starty = $_POST["coordinateoney"] ?? null;
    $endx = $_POST["coordinatetwox"] ?? null;
    $endy = $_POST["coordinatetwoy"] ?? null;
    $currentUser = $_SESSION["userUid"] ?? null;

    // Define constants for maximum and minimum waypoints
    // These are defined to comply with Arduino memory constraints
    define("MAXIMUM_WAYPOINTS", 400);
    define("MINIMUM_WAYPOINTS", 5);

    // Check if any of the coordinate values are null or empty
    if (!($startx ?? false) || !($starty ?? false) || !($endx ?? false) || !($endy ?? false)) {
        // If any of the coordinate values are null or empty, redirect back to the navigation page with an error message
        header("Location: ./navigationpage.php?error=emptyfields");
        exit();
    }

    // Set up API call using cURL
    $url = "https://api.geoapify.com/v1/routing?waypoints={$startx},{$starty}%7C{$endx},{$endy}&mode=walk&apiKey={$apiKey}";
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_CONNECTTIMEOUT => 6,
        CURLOPT_TIMEOUT => 6,
    ));
    $response = curl_exec($curl);
    curl_close($curl);

    // Parse JSON response
    $json_array = json_decode($response, true);
    // Set up MySQLi database connection
    $mysqli = new mysqli($servername, $dbUsername, $dbPassword, $dbName);

    try {
        if (!in_array("400", $json_array)) {
            // Retrieve the coordinates of the route
            $name = $json_array["features"]["0"]["geometry"]["coordinates"]["0"];

            // Calculate the number of waypoints in the route
            $len = sizeof($name);

            // Check if the number of waypoints exceeds the maximum limit or is below the minimum limit
            if ($len * 2 > MAXIMUM_WAYPOINTS || $len * 2 < MINIMUM_WAYPOINTS) {

                // Determine the appropriate error message based on the condition
                $error = $len * 2 > MAXIMUM_WAYPOINTS ? "waypointsexceed" : "routetooclose";
                
                // Redirect the user to the navigation page with the appropriate error message
                header("Location: ./navigationpage.php?error=$error");
                exit();
            }

            //start transaction
            $mysqli->begin_transaction();

            // Prepare the data
            $coordinates = [];
            for ($x = 0; $x <= $len - 1; $x++) {
                for ($y = 1; $y >= 0; $y--) {
                    $coordinates[] = "('" . $name[$x][$y] . "')";
                }
            }
            
            // Generate static map and save to session
            $originalRoute = generateStaticMap($coordinates,$name,$len,$apiKey);
            $base64 = base64_encode($originalRoute);
            $_SESSION['base64'] =  $base64;

            // SQL query to retrieve orderkey for specified user
            $sql = "SELECT orderkey FROM users WHERE uidUsers = '$currentUser'";

            // Execute query
            $result = mysqli_query($conn, $sql);

            // Check if any rows were returned
            if (mysqli_num_rows($result) > 0) {
                // Get the orderkey value from the first row returned
                $row = mysqli_fetch_assoc($result);
                $orderkey = $row["orderkey"];
                // echo "The orderkey for user '$currentUser' is '$orderkey'.";
            } 

            // Delete everything from table and insert new coordinates
            $stmt = $mysqli->prepare("TRUNCATE TABLE `{$currentUser}`");
            $stmt->execute();

            $stmt = $mysqli->prepare(
                "INSERT INTO `{$currentUser}` (Coordinate) VALUES " .
                implode(",", $coordinates)
            );
            $stmt->execute();

            // End transaction
            $mysqli->commit();

            // Generate optimized map
            $optimizedRoute = generateOptimizedMap($orderkey,$apiKey);

            // Redirect to success page
            header("Location: ./navigationpage.php?error=success");
            exit();

        } else {
            header("Location: ./navigationpage.php?error=noroute");
            exit();
        }
    } catch (TypeError $e) {
        echo($e);
        exit();
        $mysqli->rollback(); // undo changes in case of error
        header("Location: ./navigationpage.php?error=apidownerror");
    }
}

function deleteNavigation() {
    // Require the database connection file
    require_once '../login/includes/dbh.inc.php';

    // Get the current user's ID or set it to null if not logged in
    $currentUser = $_SESSION['userUid'] ?? null;

    // Create a new mysqli object to connect to the database
    $mysqli = new mysqli($servername, $dbUsername, $dbPassword, $dbName);

    // Check if the connection was successful
    if ($mysqli->connect_errno) {
        // If the connection failed, redirect back to the navigation page with an error message
        header('Location: ./navigationpage.php?error=dbconnection');
        exit();
    }

    // Prepare a SELECT statement to count the number of rows in the current user's table
    $stmt = $mysqli->prepare("SELECT COUNT(*) FROM `$currentUser`");

    // Check if the statement was prepared successfully
    if (!$stmt) {
        // If the statement failed, redirect back to the navigation page with an error message
        header('Location: ./navigationpage.php?error=dbstatement');
        exit();
    }

    // Execute the statement
    $stmt->execute();

    // Bind the result to the $count variable
    $stmt->bind_result($count);

    // Fetch the result
    $stmt->fetch();

    // Close the statement
    $stmt->close();

    // If there are no rows in the current user's table, redirect back to the navigation page with an error message
    if (!$count) {
        header('Location: ./navigationpage.php?error=norouteinprogress');
        exit();
    }

    // Prepare a TRUNCATE statement to delete all rows from the current user's table
    $stmt = $mysqli->prepare("TRUNCATE TABLE `$currentUser`");

    // Check if the statement was prepared successfully
    if (!$stmt) {
        // If the statement failed, redirect back to the navigation page with an error message
        header('Location: ./navigationpage.php?error=dbstatement');
        exit();
    }

    // Execute the statement
    $stmt->execute();

    // If no rows were affected (i.e. no rows were deleted), redirect back to the navigation page with an error message
    if ($stmt->affected_rows === 0) {
        header('Location: ./navigationpage.php?error=emptytable');
        exit();
    }

    // Close the statement
    $stmt->close();

    // Close the database connection
    $mysqli->close();

    // Redirect back to the navigation page
    header('Location: ./navigationpage.php');
    exit();
}

// Check if the "navigation-submit" button has been pressed
if (isset($_POST["navigation-submit"])) {
    beginNavigation();
} 

// Check if the "navigation-delete" button has been pressed
if (isset($_POST['navigation-delete'])) {
    deleteNavigation();
}

