<?php

$orderkey = $_GET["orderkey"] ?? '';
require "../login/includes/dbh.inc.php";

// Function to wipe data from time table.
function truncate_user_table(mysqli $conn, string $orderkey): void
{
    /**
     * This function truncates the specified user's time table in the database.
     *
     * @param mysqli $conn The mysqli connection to the database.
     * @param string $orderkey The orderkey of the user whose table will be truncated.
     *
     * @return void
     */
    header("Content-Type: application/json");

    try {
        // Check if an orderkey was provided in the header.
        if (empty($orderkey)) {
            throw new Exception("An orderkey must be provided.");
        }

        // Prepare and execute the SQL query to retrieve the user's uidUsers from the database.
        $stmt = $conn->prepare("SELECT uidUsers FROM users WHERE orderkey = ?");
        $stmt->bind_param("s", $orderkey);
        $stmt->execute();

        // Get the result of the query and fetch the first row.
        $result = $stmt->get_result();
        $row = $result->fetch_array(MYSQLI_ASSOC);

        // If no matching row was found, return an error.
        if (!$row) {
            throw new Exception("No match found.");
        }

        // Get the user's table name and prepare and execute the SQL query to truncate the table.
        $tableName = $row["uidUsers"] . "_time";
        $stmt = $conn->prepare("TRUNCATE TABLE $tableName");
        $stmt->execute();

        // Return a success message.
        echo json_encode(["success" => "Data truncated successfully."]);
    } catch (Exception $e) {
        // Return an error message.
        echo json_encode(["error" => $e->getMessage()]);
    }
}

// Call the function.
truncate_user_table($conn, $orderkey);
?>