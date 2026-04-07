<?php
// check_branch_location.php - Diagnostic tool for branch geofence settings
require_once __DIR__ . '/conn/db_connection.php';

header('Content-Type: text/html');
echo "<h1>Branch Location Diagnostic</h1>";

// Get all branches with their coordinates
$sql = "SELECT id, branch_name, branch_address, lat, `long`, geofence_radius_meters 
        FROM branches 
        WHERE is_active = 1 
        ORDER BY branch_name";

$result = mysqli_query($db, $sql);

if (!$result) {
    die("Error: " . mysqli_error($db));
}

echo "<table border='1' cellpadding='10'>";
echo "<tr>
    <th>ID</th>
    <th>Branch Name</th>
    <th>Address</th>
    <th>Latitude (lat)</th>
    <th>Longitude (long)</th>
    <th>Geofence Radius</th>
    <th>Status</th>
</tr>";

while ($row = mysqli_fetch_assoc($result)) {
    $lat = $row['lat'];
    $lng = $row['long'];
    $radius = $row['geofence_radius_meters'] ?: 1000;
    
    // Check if coordinates are valid
    $hasValidCoords = ($lat && $lng && $lat != 0 && $lng != 0);
    $status = $hasValidCoords ? 
        "<span style='color:green'>OK</span>" : 
        "<span style='color:red'>NO COORDINATES SET</span>";
    
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td><strong>" . htmlspecialchars($row['branch_name']) . "</strong></td>";
    echo "<td>" . htmlspecialchars($row['branch_address'] ?? 'N/A') . "</td>";
    echo "<td>" . ($lat ?: "<em>NULL/0</em>") . "</td>";
    echo "<td>" . ($lng ?: "<em>NULL/0</em>") . "</td>";
    echo "<td>" . $radius . " meters</td>";
    echo "<td>" . $status . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h2>How to Fix:</h2>";
echo "<ol>";
echo "<li>If 'Main Office' (or 'Main Branch') has NO COORDINATES SET, you need to set them via the <strong>Branch Location Manager</strong>:</li>";
echo "<ul>";
echo "<li>Go to: <code>employee/branch_location_manager.php</code></li>";
echo "<li>Search for 'Main Office' or 'Main Branch'</li>";
echo "<li>Click 'Set Geofence' to set the location on the map</li>";
echo "<li>Set geofence radius (recommended: 100-500 meters depending on site size)</li>";
echo "</ul>";
echo "<li>If coordinates ARE set but you're still getting 'not in location' errors:</li>";
echo "<ul>";
echo "<li>The geofence radius may be too small - increase it to 200-500 meters</li>";
echo "<li>GPS on your device may be inaccurate - try enabling high accuracy mode</li>";
echo "<li>You may be physically outside the geofenced area</li>";
echo "</ul>";
echo "</ol>";

// Also show a map link for the user to check their current location
echo "<h2>Test Your Current Location:</h2>";
echo "<button onclick='getLocation()'>Get My GPS Coordinates</button>";
echo "<p id='locationResult'></p>";
echo "<script>
function getLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;
                const accuracy = pos.coords.accuracy;
                document.getElementById('locationResult').innerHTML = 
                    '<strong>Your GPS:</strong><br>' +
                    'Latitude: ' + lat + '<br>' +
                    'Longitude: ' + lng + '<br>' +
                    'Accuracy: ' + Math.round(accuracy) + ' meters<br>' +
                    '<a href=\"https://www.google.com/maps?q=' + lat + ',' + lng + '\" target=\"_blank\">View on Google Maps</a>';
            },
            (err) => {
                document.getElementById('locationResult').innerHTML = 
                    '<span style=\"color:red\">Error: ' + err.message + '</span>';
            },
            { enableHighAccuracy: true }
        );
    } else {
        document.getElementById('locationResult').innerHTML = 'Geolocation not supported';
    }
}
</script>";

mysqli_close($db);
?>
