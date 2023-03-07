<?php
header("Content-Type: application/json");
require "../login/includes/dbh.inc.php";

$orderkey = $_GET["orderkey"] ?? null;

if (!isset($orderkey)) {
    echo json_encode(["error" => "An orderkey must be provided."]);
    exit();
}

$stmt = $conn->prepare("SELECT uidUsers FROM users WHERE orderkey = ?");
$stmt->bind_param("s", $orderkey);
$stmt->execute();
$result = $stmt->get_result();
$row = mysqli_fetch_assoc($result);

if (!$row) {
    echo json_encode(["error" => "No match found."]);
    exit();
}

$tableName = $row["uidUsers"] . "_time";

$stmt = $conn->prepare("SELECT * FROM $tableName");
$stmt->execute();
$result = $stmt->get_result();

if (!$result->num_rows) {
    echo json_encode(["error" => "The user has not sent new data yet."]);
    exit();
}

$response = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($response);
?>
