<?php
header("Content-Type: application/json");
require "../login/includes/dbh.inc.php";

$x_coord = $_GET["x_coord"] ?? null;
$y_coord = $_GET["y_coord"] ?? null;
$temperature = $_GET["temperature"] ?? null;
$humidity = $_GET["humidity"] ?? null;
$air_quality = $_GET["air_quality"] ?? null;
$orderkey = $_GET["orderkey"] ?? null;

if (!isset($orderkey)) {
    echo json_encode(["error" => "An orderkey must be provided."]);
    exit();
}

$required_params = array("x_coord", "y_coord", "temperature", "humidity", "air_quality");

foreach ($required_params as $param) {
    if (!isset($_GET[$param])) {
        echo json_encode(["error" => "Missing parameter: " . $param]);
        exit();
    }
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

$stmt = $conn->prepare("INSERT INTO $tableName (x_coord, y_coord, temperature, humidity, air_quality) VALUES (?,?,?,?,?)");
$stmt->bind_param("ddddd", $x_coord, $y_coord, $temperature, $humidity, $air_quality);
$stmt->execute();

$stmt = $conn->prepare("SELECT * FROM $tableName ORDER BY DataNum DESC LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();

if (!$result->num_rows) {
    echo json_encode(["error" => "The user has not sent new data yet."]);
    exit();
}

$response = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($response);
?>
