<?php
// Set the response content type to JSON
header("Content-Type: application/json");

//initial require database file.
require "../login/includes/dbh.inc.php";

// Initialize variables
$haversine = 0;
$orderkey = $_GET["orderkey"] ?? null;
$mode = isset($_GET['mode']) ? $_GET['mode'] : 0;
define('MAXIMUM_SIZE', 2000);

// Function to get everything setup.
function setup(){
    global $haversine, $orderkey, $mode;

    if (!isset($orderkey)) {
        echo json_encode(["error" => "An orderkey must be provided."]);
        exit();
    }
}

// Function to calculate the distance between two points on Earth using the Haversine formula
function haversineDistance($lat1, $lon1, $lat2, $lon2) {
    /**
     * Calculate the distance in meters between two points on Earth using the Haversine formula.
     *
     * @param float $lat1 The latitude of the first point in degrees.
     * @param float $lon1 The longitude of the first point in degrees.
     * @param float $lat2 The latitude of the second point in degrees.
     * @param float $lon2 The longitude of the second point in degrees.
     * @return float The distance between the two points in meters.
     */
    $earthRadius = 6371000; // Radius of the earth in meters
    $latFrom = deg2rad($lat1); // Convert latitude of point 1 from degrees to radians
    $lonFrom = deg2rad($lon1); // Convert longitude of point 1 from degrees to radians
    $latTo = deg2rad($lat2); // Convert latitude of point 2 from degrees to radians
    $lonTo = deg2rad($lon2); // Convert longitude of point 2 from degrees to radians
    $latDelta = $latTo - $latFrom; // Calculate the difference in latitude between the two points
    $lonDelta = $lonTo - $lonFrom; // Calculate the difference in longitude between the two points
    $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2))); // Calculate the central angle between the two points using the Haversine formula
    return $angle * $earthRadius; // Return the distance between the two points
}

// Function to calculate the size of an array
function getSize($arr) {
    /**
    * Calculates the size of an array in bytes.
    * 
    * @param array $arr The array to calculate the size of.
    * @return int The total size of the array in bytes.
    */
    $totalSize = 0; // Initialize the total size to 0
    foreach($arr as $element) { // Loop through each element of the array
        if (is_array($element)) { // If the element is an array, recursively call calculateArraySize() on it and add the result to the total size
            $totalSize += calculateArraySize($element);
        }
        if (is_string($element)) { // If the element is a string, add its length to the total size
            $totalSize += strlen($element);
        }
        if (is_int($element)) { // If the element is an integer, add the size of an integer in bytes to the total size
            $totalSize += PHP_INT_SIZE;
        }
    }
    return $totalSize; // Return the total size
}

 // Function to get uidUsers from an OrderKey
function fetchUserIdFromOrderKey($orderkey, $conn) {
    /**
     * Fetches the user ID associated with the given order key.
     *
     * @param string $orderkey The order key.
     * @param mysqli $conn The database connection object.
     * @return int|string The user ID associated with the order key.
     * @throws Exception If no match is found.
     */
    $stmt = $conn->prepare("SELECT uidUsers FROM users WHERE orderkey = ?");
    // Bind the order key parameter to the prepared statement
    $stmt->bind_param("s", $orderkey);
    // Execute the prepared statement
    $stmt->execute();
    // Get the result of the executed statement
    $result = $stmt->get_result();
    // Fetch the first row of the result as an associative array
    $row = mysqli_fetch_assoc($result);

    //Error Checking
    if (!$row) {
        $error = array("error" => "No match found.");
        echo json_encode($error);
        exit();
    }

    // Return the user ID associated with the order key
    return $row['uidUsers'];
}

 // Function to prepare routes from the user table
function selectFromUserTable($uid, $conn) {
    /**
     * Selects all rows from the table associated with the given user ID.
     *
     * @param string $uid The user ID associated with the table to select from.
     * @param mysqli $conn The database connection object.
     * @return mysqli_result The result of the query.
     * @throws Exception If the query fails.
     */
    // Prepare the query
    $stmt = $conn->prepare("SELECT * FROM " . $uid);
    if (!$stmt) {
        throw new Exception("Query preparation failed: " . $conn->error);
    }

    // Execute the query
    if (!$stmt->execute()) {
        throw new Exception("Query execution failed: " . $stmt->error);
    }

    // Get the result set
    $result = $stmt->get_result();

    if (!$result->num_rows) {
        echo json_encode(["error" => "The user has not begun a route yet."]);
        exit();
    }

    // Return the result set
    return $result;
}

// Function to remove coordinates that fall below the haversine.
function getNormalData($orderkey, $conn) { // Define a function named getNormalData that takes an order key and database connection object as parameters.
    /**
     * Fetches the normal data associated with the given order key.
     *
     * @param string $orderkey The order key.
     * @param mysqli $conn The database connection object.
     * @return array The normal data associated with the order key.
     */
    $row = fetchUserIdFromOrderKey($orderkey, $conn); // Call the fetchUserIdFromOrderKey() function with the order key and database connection object, and assign the resulting row to $row.
    $result = selectFromUserTable($row, $conn); // Call the selectFromUserTable() function with the $row and database connection object, and assign the resulting query result to $result.

    $normal_data = []; // Initialize an empty array named $normal_data.
    $waypoint_num = 1; // Initialize the waypoint number to 1.
    $to_remove = []; // Initialize an empty array named $to_remove.
    $haversine = 0.1; // Initialize the haversine distance to 0.1.

    while ($row = $result->fetch_assoc()) { // Start a while loop that iterates through each row in the query result.
        $normal_data[$waypoint_num] = strval($row["Coordinate"]); // Get the "Coordinate" value from the current row, convert it to a string, and store it in the $normal_data array at the current waypoint number.

        if ($waypoint_num % 4 == 0) { // If the current waypoint number is a multiple of 4...
            $lat1 = $normal_data[$waypoint_num - 3]; // Get the latitude of the first coordinate in the segment.
            $lon1 = $normal_data[$waypoint_num - 2]; // Get the longitude of the first coordinate in the segment.
            $lat2 = $normal_data[$waypoint_num - 1]; // Get the latitude of the second coordinate in the segment.
            $lon2 = $normal_data[$waypoint_num]; // Get the longitude of the second coordinate in the segment.

            $haversine_distance = haversineDistance($lat1, $lon1, $lat2, $lon2); // Calculate the haversine distance between the two coordinates.

            if ($haversine_distance < $haversine) { // If the haversine distance is less than the threshold...
                $to_remove = array_merge($to_remove, [$waypoint_num - 3, $waypoint_num - 2, $waypoint_num - 1, $waypoint_num]); // Add the indexes of the coordinates in the segment to the $to_remove array.
            }
        }

        $waypoint_num++; // Increment the waypoint number.
    }

    foreach ($to_remove as $index) { // Start a foreach loop that iterates through each index in the $to_remove array.
        unset($normal_data[$index]); // Remove the coordinate at the current index from the $normal_data array.
    }

    $normal_data = array_values($normal_data); // Re-index the $normal_data array so that the indexes are consecutive integers.

    return $normal_data; // Return the $normal_data array.
}

// Function to formats the code, if mode is 1, format with decimals, otherwise multiply by 10^7
function getFormattedData($normal_data, $mode) {
    /**
     * Formats the normal data into a key-value array with keys as
     * indices and values as normal data points in the desired format.
     *
     * @param array $normal_data The normal data to format.
     * @param int $mode The mode to use for formatting.
     * @return array The formatted data.
     */
    $data = []; // create an empty array to hold the formatted data

    // loop through each item in the normal_data array
    foreach ($normal_data as $key => $value) {
        $data[] = '"' . ($key+1) . '": "' . $value . '"';
    }

    return $data; // return the formatted data array
}

// Function that ultimately reduces the coordinates until the coordinates are below a certain size.
function reduceHaversineDistance($normal_data, $jsonSizedata) {
    /**
     * Reduces haversine distance between waypoints to reduce JSON size.
     * The function takes an array of coordinates and a float indicating the size of the JSON data,
     * and returns the array of coordinates with reduced haversine distance between waypoints.
     *
     * @param array $normal_data The normal data to format.
     * @param float $jsonSizedata The size of the JSON data.
     * @return array The normal data with reduced haversine distance.
     */
    $haversine = 0.1; // initialize haversine distance threshold

    // loop until the JSON data size is less than or equal to MAXIMUM_SIZE
    while ($jsonSizedata > MAXIMUM_SIZE) {
        $haversine += 0.1; // increment haversine distance threshold by 0.1
        $to_remove = []; // initialize array to store indices to remove

        // loop through the coordinates in groups of 4 (lat1, lon1, lat2, lon2)
        for ($i = 0; $i < count($normal_data); $i += 4) {
            $lat1 = $normal_data[$i] ?? null; // get the first latitude
            $lon1 = $normal_data[$i + 1] ?? null; // get the first longitude
            $lat2 = $normal_data[$i + 2] ?? null; // get the second latitude
            $lon2 = $normal_data[$i + 3] ?? null; // get the second longitude

            // calculate the haversine distance between the two coordinates
            $haversine_distance = haversineDistance($lat1, $lon1, $lat2, $lon2);

            // if the haversine distance is less than the threshold, add the indices to $to_remove
            if ($haversine_distance < $haversine) {
                $to_remove = array_merge($to_remove, [$i, $i + 1, $i + 2, $i + 3]);
            }
        }

        // remove the coordinates with indices in $to_remove
        foreach ($to_remove as $index) {
            unset($normal_data[$index]);
        }

        // reset the indices of the remaining coordinates
        $normal_data = array_values($normal_data);

        // format the remaining coordinates into an array of key-value pairs
        $data = [];
        foreach ($normal_data as $key => $value) {
            $data[] = '"' . ($key + 1) . '": "' . (float)$value . '"';
        }

        // calculate the size of the new JSON data
        $jsonSizedata = getSize($data);
    }

    return $normal_data; // return the array of coordinates with reduced haversine distance
}

// Final function that will return JSON for output data.
function formatWaypoints($orderkey, $mode, $conn) {
    /**
     * Formats waypoints for a given order key and mode, returning a JSON-encoded string of the formatted data.
     *
     * @param string $orderkey The order key to use for formatting waypoints.
     * @param int $mode The mode to use for formatting waypoints (0 for high-precision, 1 for low-precision).
     * @param mysqli $conn The database connection to use for retrieving waypoint data.
     * @return string The formatted waypoints in JSON format.
     */
    $row = fetchUserIdFromOrderKey($orderkey, $conn); // Get row from user table by orderkey
    $result = selectFromUserTable($row, $conn); // Get user data from row
    $normal_data = getNormalData($orderkey, $conn); // Get the normal data for the order
    $formatted_data = getFormattedData($normal_data, $mode); // Format the normal data based on mode
    $jsonSizedata = getSize($formatted_data); // Get the size of the formatted data
    $reduced_normal_data = reduceHaversineDistance($normal_data, $jsonSizedata); // Reduce data based on size

    $data = []; // Initialize data array to hold formatted waypoints

    if ($mode == 0) { // If mode is 0, format data to include 7 decimal places
        foreach ($reduced_normal_data as $key => $value) {
            $data[] = '"' . ($key + 1) . '": "' . (float)$value * 10 ** 7 . '"';
        }
    } else { // Otherwise, format data to include default decimal places
        foreach ($reduced_normal_data as $key => $value) {
            $data[] = '"' . ($key + 1) . '": "' . (float)$value . '"';
        }
    }

    return '{' . implode(',', $data) . '}'; // Return formatted data as a JSON string
}

// Initialize the environment
setup();
// Fetch the user ID from the order key
$row = fetchUserIdFromOrderKey($orderkey, $conn);
// Select user data from the database
$result = selectFromUserTable($row, $conn);
// Get the normal data
$normal_data = getNormalData($orderkey, $conn);
// Format the data according to the specified mode
$formatted_data = getFormattedData($normal_data, $mode);
// Get the size of the JSON data
$jsonSizedata = getSize($formatted_data);
// Reduce the haversine distance between waypoints to reduce JSON size
$reduced_normal_data = reduceHaversineDistance($normal_data, $jsonSizedata);
// Format the waypoints according to the specified mode
$result = formatWaypoints($orderkey, $mode, $conn);
// Echo the formatted waypoints
echo $result;