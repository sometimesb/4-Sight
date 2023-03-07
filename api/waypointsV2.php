<?php
header("Content-Type: application/json");
require "../login/includes/dbh.inc.php";

$haversine = 0;
$orderkey = $_GET["orderkey"] ?? null;
$mode = isset($_GET['mode']) ? $_GET['mode'] : 0;

function haversineDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000;
    $latFrom = deg2rad($lat1);
    $lonFrom = deg2rad($lon1);
    $latTo = deg2rad($lat2);
    $lonTo = deg2rad($lon2);
    $latDelta = $latTo - $latFrom;
    $lonDelta = $lonTo - $lonFrom;
    $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
        cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
    return $angle * $earthRadius;
}

function getSize($arr) {
    $tot = 0;
    foreach($arr as $a) {
        if (is_array($a)) {
            $tot += getSize($a);
        }
        if (is_string($a)) {
            $tot += strlen($a);
        }
        if (is_int($a)) {
            $tot += PHP_INT_SIZE;
        }
    }
    return $tot;
}



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

$stmt = $conn->prepare("SELECT * FROM " . $row["uidUsers"]);
$stmt->execute();
$result = $stmt->get_result();

if (!$result->num_rows) {
    echo json_encode(["error" => "The user has not begun a route yet."]);
    exit();
}

$normal_data = [];
$waypoint_num = 1;
$to_remove = [];
$haversine = 0.1;

while ($row = $result->fetch_assoc()) {
    $normal_data[$waypoint_num] = strval($row["Coordinate"]);

    if ($waypoint_num % 4 == 0) {
        $lat1 = $normal_data[$waypoint_num - 3];
        $lon1 = $normal_data[$waypoint_num - 2];
        $lat2 = $normal_data[$waypoint_num - 1];
        $lon2 = $normal_data[$waypoint_num];

        $haversine_distance = haversineDistance($lat1, $lon1, $lat2, $lon2);

        if ($haversine_distance < $haversine) {
            $to_remove = array_merge($to_remove, [$waypoint_num - 3, $waypoint_num - 2, $waypoint_num - 1, $waypoint_num]);
        }
    }

    $waypoint_num++;
}

foreach ($to_remove as $index) {
    unset($normal_data[$index]);
}

$normal_data = array_values($normal_data);

$data = [];
foreach ($normal_data as $key => $value) {
    if($mode == 1){
        $data[] = '"' . ($key+1) . '": "' . $value . '"';

    }
    else{
        $data[] = '"' . ($key+1) . '": "' . strval(floor(($value)*10**7)) . '"';
    }
}

$jsonSizedata = getSize($data);

while ($jsonSizedata > 2000) {
    $haversine += 0.1;
    $to_remove = [];

    for ($i = 0; $i < count($normal_data); $i += 4) {
        $lat1 = $normal_data[$i] ?? null;
        $lon1 = $normal_data[$i + 1] ?? null;
        $lat2 = $normal_data[$i + 2] ?? null;
        $lon2 = $normal_data[$i + 3] ?? null;

        $haversine_distance = haversineDistance($lat1, $lon1, $lat2, $lon2);

        if ($haversine_distance < $haversine) {
            $to_remove = array_merge($to_remove, [$i, $i + 1, $i + 2, $i + 3]);
        }
    }

    foreach ($to_remove as $index) {
        unset($normal_data[$index]);
    }

    $normal_data = array_values($normal_data);

    $data = [];
    foreach ($normal_data as $key => $value) {
        if($mode==1){
            $data[] = '"' . ($key + 1) . '": "' . $value . '"';
        } else {
            $data[] = '"' . ($key + 1) . '": "' . ($value*10**5) . '"';
        }
    }
    

    $jsonSizedata = getSize($data);
}

echo '{' . implode(',', $data) . '}';


