<?php

// Include the database connection file.
require "../login/includes/dbh.inc.php";

// Retrieve the orderkey from the request parameters.
$orderkey = $_GET["orderkey"] ?? null;

function get_user_time_table(mysqli $conn, string $orderkey): void
{
    /**
     * Retrieves a user's time table from the database based on the provided orderkey.
     *
     * @param mysqli $conn The mysqli connection to the database.
     * @param string $orderkey The orderkey of the user whose time table will be retrieved.
     *
     * @return void
     */
    // Set the response header to indicate that the response will be in JSON format.
    header("Content-Type: application/json");

    // Check if an orderkey was provided.
    if (empty($orderkey)) {
        echo json_encode(["error" => "An orderkey must be provided."]);
        exit();
    }

    // Prepare and execute the SQL query to retrieve the user's uidUsers from the database.
    $stmt = $conn->prepare("SELECT uidUsers FROM users WHERE orderkey = ?");
    $stmt->bind_param("s", $orderkey);
    $stmt->execute();

    // Get the result of the query and fetch the first row.
    $result = $stmt->get_result();
    $row = mysqli_fetch_assoc($result);

    // If no matching row was found, return an error.
    if (!$row) {
        echo json_encode(["error" => "No match found."]);
        exit();
    }

    // Get the user's table name and prepare and execute the SQL query to retrieve the table.
    $tableName = $row["uidUsers"] . "_time";
    $stmt = $conn->prepare("SELECT * FROM $tableName");
    $stmt->execute();
    $result = $stmt->get_result();

    // If no rows were returned, return an error.
    if (!$result->num_rows) {
        echo json_encode(["error" => "The user has not sent new data yet."]);
        exit();
    }

    // Fetch all rows and return them as a JSON response.
    $response = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($response);
}

// Call the get_user_time_table function with the provided orderkey and the database connection.
get_user_time_table($conn, $orderkey);

?>
