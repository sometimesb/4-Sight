<?php
// include database connection
require_once  '../login/includes/dbh.inc.php';

function generateStaticMap($coordinates, $name, $len,$apiKey)
{
    /**
     * Generate a static map from an array of coordinates.
     *
     * @param array $coordinates The array of coordinates.
     * @param array $name The names of the coordinates.
     * @param int $len The length of the array.
     * @return string The response from the API.
     */
    $width = 1024;
    $height = 768;
    // Transform the coordinates into the format expected by the API.
    $coordinateTransform = [];

    for ($i = 0; $i < $len; $i++) {
        $latitude = $name[$i][1];
        $longitude = $name[$i][0];
        $coordinateTransform[] = "$longitude,$latitude";
    }

    // Create the marker string from the transformed coordinates.
    $markerString = '';

    for ($i = 0; $i < count($coordinateTransform); $i++) {
        $lonlat = $coordinateTransform[$i];
        $markerString .= "lonlat:{$lonlat};color:%231f63e6;text:" . ($i + 1) . '|';
    }

    $markerString = rtrim($markerString, '|');

    // Create the API URL.
    $apiUrl = "https://maps.geoapify.com/v1/staticmap?style=osm-bright-grey&width=$width&height=$height&marker=$markerString&apiKey=$apiKey";
    // Call the API using cURL.
    $curl = curl_init($apiUrl);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);
    curl_close($curl);

    return $response;
}

function generateOptimizedMap($orderkey,$apiKey)
{
    /**
     * Generate an optimized map from an order key.
     *
     * @param string $orderkey The order key.
     * @return string The response from the API.
     */
    $width = 1024;
    $height = 768;

    // Call the waypoints API.
    $url = "http://localhost/test4sight/api/waypointsV2.php?orderkey=$orderkey&mode=1";
    $response = file_get_contents($url);


    // Decode the response into an array of waypoints.
    $waypoints = json_decode($response, true);

    // Transform the waypoints into the format expected by the API.
    $transformedWaypoints = [];

    for ($i = 1; $i <= count($waypoints) / 2; $i++) {
        $latitude = $waypoints[(2 * $i) - 1];
        $longitude = $waypoints[2 * $i];
        $transformedWaypoints[] = "$longitude,$latitude";
    }

    // Create the marker string from the transformed waypoints.
    $markerString = '';

    for ($i = 0; $i < count($transformedWaypoints); $i++) {
        $lonlat = $transformedWaypoints[$i];
        $markerString .= "lonlat:{$lonlat};color:%231f63e6;text:" . ($i + 1) . '|';
    }

    $markerString = rtrim($markerString, '|');

    // Create the API URL.
    $apiUrl = "https://maps.geoapify.com/v1/staticmap?style=osm-bright-grey&width=$width&height=$height&marker=$markerString&apiKey=$apiKey";
    $curl = curl_init($apiUrl);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);
    curl_close($curl);

    // Encode the resposnse in base64 format and store it in the session
    $base64optimized = base64_encode($response);
    $_SESSION['base64optimized'] = $base64optimized;

    return $response;
}
