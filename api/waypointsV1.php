<?php

// Get the orderkey parameter from the URL query string, if it is not present, set it to null.
$orderkey = $_GET["orderkey"] ?? null;

// Function to connect to database
function connectToDatabase() {
    /**
     * Connect to the database.
     *
     * @return mixed Database connection object.
     */
    require "../login/includes/dbh.inc.php"; // Require the database connection file.
    return $conn; // Return the connection object.
}

// Function to get waypoints from database
function getWaypoints($orderkey) {
    /**
     * Get the waypoints of a user.
     *
     * @param string $orderkey The orderkey of the user.
     * @return array|mixed[] An array of the waypoints of the user, or an error message.
     */
    $conn = connectToDatabase(); // Connect to the database.

    // Prepare and execute a statement to get the uidUsers of the user with the given orderkey.
    $stmt = $conn->prepare("SELECT uidUsers FROM users WHERE orderkey = ?");
    $stmt->bind_param("s", $orderkey);
    $stmt->execute();

    // Get the result of the statement and fetch the first row.
    $result = $stmt->get_result();
    $row = mysqli_fetch_assoc($result);

    // If the row is empty, return an error message.
    if (!$row) {
        return ["error" => "No match found."];
    }

    // Prepare and execute a statement to get all of the waypoints for the user with the given uidUsers.
    $stmt = $conn->prepare("SELECT * FROM " . $row["uidUsers"]);
    $stmt->execute();

    // Get the result of the statement.
    $result = $stmt->get_result();

    // If the result has no rows, return an error message.
    if ($result->num_rows === 0) {
        return ["error" => "The user has not begun a route yet."];
    }

    $response = []; // Initialize an empty array to hold the waypoints.

    // Loop through the result and add each row to the response array.
    while ($row = mysqli_fetch_assoc($result)) {
        $response[$row["WaypointNum"]] = strval($row["Coordinate"]);
    }

    return $response; // Return the response array.
}

// Function to output JSON response.
function outputJSON($response) {
    /**
     * Output a response as JSON.
     *
     * @param array $response The response array to output.
     * @return void
     */
    header("Content-Type: application/json"); // Set the content type to JSON.
    echo json_encode($response); // Output the response as JSON.
}

// Function to get script setup.
function setup(){
    /**
     * Setup the script.
     *
     * @return void
     */
    global $orderkey; // Access the global orderkey variable.

    // Check if the orderkey is not set, if so output an error message and return.
    if (!$orderkey) {
        outputJSON(["error" => "An orderkey must be provided."]);
        return;
    }

    $response = getWaypoints($orderkey); // Get the waypoints for the user.
    outputJSON($response); // Output the response as JSON.
}

// Call the setup function to run the script.
setup(); 
