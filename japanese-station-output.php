<?php
//Created by Raaz Acharya. You can freely modify or re-distribute this script.
// Function to fetch the coordinates of an address using Google Geocoding API (You can use other Custom API to geocode the address sent by the HTML form)
//
function getCoordinates($address, $apiKey) {
    $geocodeUrl = "https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($address) . "&key=" . $apiKey;
    $geocodeResponse = file_get_contents($geocodeUrl);
    $geocodeData = json_decode($geocodeResponse, true);

    if ($geocodeData && isset($geocodeData['results'][0]['geometry']['location'])) {
        $lat = number_format($geocodeData['results'][0]['geometry']['location']['lat'], 6); // Adjusted to show more precision
        $lng = number_format($geocodeData['results'][0]['geometry']['location']['lng'], 6); // Adjusted to show more precision
        return [
            'lat' => $lat,
            'lng' => $lng
        ];
    }
    return null;
}

// Function to fetch station information from HeartRails API (We will be using HeartRails API to fetch the nearest station details which is free with some limitations.)
function fetchStationInfo($longitude, $latitude) {
    $apiUrl = "https://express.heartrails.com/api/json?method=getStations&x={$longitude}&y={$latitude}";
    $response = file_get_contents($apiUrl);
    $data = json_decode($response, true);

    if ($data && isset($data['response']['station'])) {
        return $data['response']['station'];
    }
    return [];
}

//You can use translation function to show the station details in english if you like to change or save it to database in original Japanese format and then translate later when showing it on frontpage side.

// Function to calculate walking time in minutes based on distance in meters
function calculateWalkingTime($distance) {
    // Average walking speed assumed as 5 km/h or 1.39 m/s (You can use your custom calculation method)
    $walkingSpeed = 1.39; // meters per second
    $timeInSeconds = $distance / $walkingSpeed;
    $timeInMinutes = ceil($timeInSeconds / 60); // Round up to the nearest minute
    return $timeInMinutes;
}

$address = '';
$stations = [];
$errorMessage = '';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $address = isset($_POST['address']) ? $_POST['address'] : '';

    $googleApiKey = 'YOUR_GOOGLE_API_KEY'; // Replace with your Google API key

    // Get coordinates from Google Geocoding API
    $coordinates = getCoordinates($address, $googleApiKey);

    if ($coordinates) {
        $latitude = $coordinates['lat'];
        $longitude = $coordinates['lng'];

        // Fetch station information from HeartRails API using fetched coordinates
        $stations = fetchStationInfo($longitude, $latitude);

        if (empty($stations)) {
            $errorMessage = "No stations found nearby.";
        }
    } else {
        $errorMessage = "Failed to get coordinates for the address.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nearest Stations</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .custom-control-label {
            padding-left: 1.5rem;
        }
        .btn-float {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }
    </style>
</head>
<body>

<div class="container mt-5">
    <h2 class="mb-4">Nearest Stations Finder</h2>
    <form method="post" action="">
        <div class="form-group">
            <label for="address">Address</label>
            <input type="text" class="form-control" id="address" name="address" required>
        </div>
        <button type="submit" class="btn btn-primary">Find Stations</button>
    </form>

    <?php if (!empty($address) && !empty($stations)): ?>
        <div class="mt-5">
            <h3>Stations near: <?php echo htmlspecialchars($address); ?></h3>
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="card-title">近くの駅情報</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead class="thead-dark">
                            <tr>
                                <th></th>
                                <th>駅/路線</th>
                                <th>距離</th>
                                <th>Time (分)</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            $counter = 1; // Initialize counter for unique id
                            foreach ($stations as $station) {
                                // Calculate walking time
                                $distance = intval($station['distance']); // Convert distance string to integer
                                $walkingTime = calculateWalkingTime($distance);

                                // Format station details
                                $stationLine = "{$station['name']} / {$station['line']}";
                                ?>
                                <tr>
                                    <td>
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="station_<?php echo $counter; ?>"
                                                   name="selectedStations[]" value="<?php echo htmlspecialchars($station['name']); ?>">
                                            <label class="custom-control-label" for="station_<?php echo $counter; ?>"></label>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($stationLine); ?></td>
                                    <td><?php echo htmlspecialchars($station['distance']); ?></td>
                                    <td><?php echo htmlspecialchars($walkingTime); ?></td>
                                </tr>
                                <?php
                                $counter++; // Increment counter for next iteration
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                    
                </div>
            </div>
        </div>
    <?php elseif (!empty($errorMessage)): ?>
        <div class="alert alert-danger mt-5" role="alert">
            <?php echo htmlspecialchars($errorMessage); ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
