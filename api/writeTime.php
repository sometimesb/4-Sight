<?php
header("Content-Type: application/json");
require "../login/includes/dbh.inc.php";

// Function to check if all parameters are sent
function check_required_params() {
    /**
     * Check if all required parameters are present in the $_GET array.
     * 
     * @return array|null An error message if a required parameter is missing, otherwise null.
     */
    // Define an array of required parameters.
    $required_params = array("x_coord", "y_coord", "temperature", "humidity", "air_quality");

    // Check if each required parameter is present in the $_GET array.
    foreach ($required_params as $param) {
        if (!isset($_GET[$param])) {
            // If a required parameter is missing, return an error message.
            return ["error" => "Missing parameter: " . $param];
        }
    }

    // If all required parameters are present, return null.
    return null;
}

// Function to get the userID given orderkey
function get_user_id($conn, $orderkey) {
    /**
     * Get the user ID associated with the provided order key
     *
     * @param mysqli $conn The MySQL connection object
     * @param string $orderkey The order key for the user
     *
     * @return array|string Error message if no matching user found, or the user ID if a match is found
     */
    // Prepare a query to retrieve the user ID associated with the provided order key
    $stmt = $conn->prepare("SELECT uidUsers FROM users WHERE orderkey = ?");
    
    // Bind the order key parameter to the query
    $stmt->bind_param("s", $orderkey);
    
    // Execute the query and get the result set
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Fetch the first row of the result set as an associative array
    $row = mysqli_fetch_assoc($result);
    
    // If no row is returned, return an error message
    if (!$row) {
        return ["error" => "No match found."];
    }
    
    // Return the user ID associated with the provided order key
    return $row["uidUsers"];
}

// Function to insert data given 5 float parameters
function insert_data($conn, $tableName, $x_coord, $y_coord, $temperature, $humidity, $air_quality) {
    /**
     * Insert new data into the specified table
     *
     * @param mysqli $conn The MySQL connection object
     * @param string $tableName The name of the table to insert data into
     * @param float $x_coord The x-coordinate to insert
     * @param float $y_coord The y-coordinate to insert
     * @param float $temperature The temperature to insert
     * @param float $humidity The humidity to insert
     * @param float $air_quality The air quality to insert
     *
     * @return void
     */
    
    // prepare a SQL statement to insert data into the specified table
    $stmt = $conn->prepare("INSERT INTO $tableName (x_coord, y_coord, temperature, humidity, air_quality) VALUES (?,?,?,?,?)");

    // bind the parameters to the statement
    // "ddddd" indicates that all parameters are double/float values
    $stmt->bind_param("ddddd", $x_coord, $y_coord, $temperature, $humidity, $air_quality);

    // execute the statement to insert the data into the database
    $stmt->execute();
}

// Function to get latest data for outputting purposes
function get_latest_data($conn, $tableName) {
    /**
     * Get the latest data row from a specified table
     *
     * @param mysqli $conn The MySQL connection object
     * @param string $tableName The name of the table to select data from
     *
     * @return array An array of associative arrays representing the latest data row
     */
    // prepare SQL statement to select the latest data row from the specified table
    $stmt = $conn->prepare("SELECT * FROM $tableName ORDER BY DataNum DESC LIMIT 1");

    // execute the SQL statement
    $stmt->execute();

    // get the result set as an array of associative arrays
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}


// Function to process data received via HTTP GET request, insert it into database and return latest data
function main($conn) {
    /**
     * Process data received via HTTP GET request, insert it into database and return latest data
     *
     * @param object $conn A database connection object
     *
     * @return void
     */

    // Get data from the HTTP GET request, or set to null if not provided
    $x_coord = $_GET["x_coord"] ?? null;
    $y_coord = $_GET["y_coord"] ?? null;
    $temperature = $_GET["temperature"] ?? null;
    $humidity = $_GET["humidity"] ?? null;
    $air_quality = $_GET["air_quality"] ?? null;
    $orderkey = $_GET["orderkey"] ?? null;

    // Check if the required orderkey parameter was provided, exit if not
    if (!isset($orderkey)) {
        echo json_encode(["error" => "An orderkey must be provided."]);
        exit();
    }

    // Check if any other required parameters are missing, exit if so
    $error = check_required_params();
    if ($error) {
        echo json_encode($error);
        exit();
    }

    // Get the user ID associated with the provided orderkey, exit if there's an error
    $uidUsers = get_user_id($conn, $orderkey);
    if (isset($uidUsers["error"])) {
        echo json_encode($uidUsers);
        exit();
    }

    // Set the table name to the user ID plus "_time"
    $tableName = $uidUsers . "_time";

    // Insert the data into the database table
    insert_data($conn, $tableName, $x_coord, $y_coord, $temperature, $humidity, $air_quality);

    // Get the latest data from the database table
    $response = get_latest_data($conn, $tableName);

    // Return the latest data as a JSON object
    echo json_encode($response);
}

main($conn);
?>
