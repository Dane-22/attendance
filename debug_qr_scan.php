<?php
/**
 * QR Scan Debug Tool
 * Helps diagnose QR scanning and geofence issues
 */

require_once __DIR__ . '/conn/db_connection.php';

// Get all branches for testing
$branches_query = "SELECT id, branch_name, lat, `long`, geofence_radius_meters FROM branches WHERE is_active = 1 ORDER BY branch_name";
$branches_result = mysqli_query($db, $branches_query);
$branches = [];
while ($row = mysqli_fetch_assoc($branches_result)) {
    $branches[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Scan Debug Tool</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .gps-coords { font-family: monospace; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen p-4">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">QR Scan Debug Tool</h1>
        
        <!-- GPS Status -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Your GPS Location</h2>
            <div id="gpsStatus" class="text-gray-600">
                <p>Click the button below to get your current GPS coordinates:</p>
                <button onclick="getLocation()" class="mt-3 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Get My Location
                </button>
            </div>
            <div id="gpsResult" class="mt-4 hidden">
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 p-3 rounded">
                        <span class="text-sm text-gray-500">Latitude:</span>
                        <div id="latValue" class="gps-coords text-lg font-semibold text-blue-600">--</div>
                    </div>
                    <div class="bg-gray-50 p-3 rounded">
                        <span class="text-sm text-gray-500">Longitude:</span>
                        <div id="lngValue" class="gps-coords text-lg font-semibold text-blue-600">--</div>
                    </div>
                    <div class="bg-gray-50 p-3 rounded">
                        <span class="text-sm text-gray-500">Accuracy:</span>
                        <div id="accuracyValue" class="gps-coords text-lg font-semibold text-green-600">--</div>
                    </div>
                    <div class="bg-gray-50 p-3 rounded">
                        <span class="text-sm text-gray-500">Source:</span>
                        <div id="sourceValue" class="text-lg font-semibold text-gray-700">--</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Branch Geofence Test -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Test Geofence by Branch</h2>
            <p class="text-gray-600 mb-4">Select a branch to test if your current location is within its geofence:</p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($branches as $branch): ?>
                <div class="border rounded-lg p-4 hover:shadow-md transition cursor-pointer branch-card" 
                     onclick="testBranch(<?php echo $branch['id']; ?>, '<?php echo htmlspecialchars($branch['branch_name']); ?>', 
                     <?php echo $branch['lat'] ?: 'null'; ?>, <?php echo $branch['long'] ?: 'null'; ?>, 
                     <?php echo $branch['geofence_radius_meters'] ?: 1000; ?>)"
                     data-branch-id="<?php echo $branch['id']; ?>">
                    <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($branch['branch_name']); ?></h3>
                    <div class="text-sm text-gray-500 mt-2">
                        <?php if ($branch['lat'] && $branch['long']): ?>
                            <div>Lat: <?php echo number_format($branch['lat'], 6); ?></div>
                            <div>Lng: <?php echo number_format($branch['long'], 6); ?></div>
                            <div class="mt-1 font-medium text-blue-600">Radius: <?php echo $branch['geofence_radius_meters'] ?: 1000; ?>m</div>
                        <?php else: ?>
                            <div class="text-red-500">No coordinates set!</div>
                        <?php endif; ?>
                    </div>
                    <div class="test-result mt-3 hidden">
                        <div class="distance text-sm"></div>
                        <div class="status text-sm font-semibold mt-1"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Test Results -->
        <div id="testResults" class="bg-white rounded-lg shadow-md p-6 hidden">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Test Result</h2>
            <div id="resultContent" class="space-y-2"></div>
        </div>

        <!-- Instructions -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="font-semibold text-blue-900 mb-2">How to Use This Tool</h3>
            <ol class="text-sm text-blue-800 space-y-1 list-decimal list-inside">
                <li>Click "Get My Location" to fetch your GPS coordinates</li>
                <li>Click on any branch card to test if you're within its geofence</li>
                <li>Check the distance and status shown on each card</li>
                <li>If you're outside the radius, the distance will be shown in red</li>
            </ol>
            <div class="mt-4 text-sm text-blue-700">
                <strong>Note:</strong> Default geofence radius is now <strong>1000 meters</strong> for all branches.
            </div>
        </div>
    </div>

    <script>
        let currentPosition = null;

        function getLocation() {
            const statusDiv = document.getElementById('gpsStatus');
            const resultDiv = document.getElementById('gpsResult');
            
            if (!navigator.geolocation) {
                statusDiv.innerHTML = '<p class="text-red-600">Geolocation is not supported by this browser.</p>';
                return;
            }

            statusDiv.innerHTML = '<p class="text-blue-600"><i class="fas fa-spinner fa-spin"></i> Getting location...</p>';

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    currentPosition = {
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy,
                        source: position.coords.accuracy < 20 ? 'GPS' : 'Network/WiFi'
                    };

                    document.getElementById('latValue').textContent = currentPosition.latitude.toFixed(7);
                    document.getElementById('lngValue').textContent = currentPosition.longitude.toFixed(7);
                    document.getElementById('accuracyValue').textContent = Math.round(currentPosition.accuracy) + ' meters';
                    document.getElementById('sourceValue').textContent = currentPosition.source;

                    resultDiv.classList.remove('hidden');
                    statusDiv.innerHTML = '<p class="text-green-600">Location retrieved successfully!</p>';
                },
                (error) => {
                    let message = 'Unable to retrieve location.';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            message = "Location permission denied. Please enable location services.";
                            break;
                        case error.POSITION_UNAVAILABLE:
                            message = "Location information unavailable.";
                            break;
                        case error.TIMEOUT:
                            message = "Location request timed out.";
                            break;
                    }
                    statusDiv.innerHTML = `<p class="text-red-600">${message}</p>`;
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        }

        function calculateDistance(lat1, lng1, lat2, lng2) {
            const R = 6371000; // Earth's radius in meters
            const phi1 = lat1 * Math.PI / 180;
            const phi2 = lat2 * Math.PI / 180;
            const deltaPhi = (lat2 - lat1) * Math.PI / 180;
            const deltaLambda = (lng2 - lng1) * Math.PI / 180;

            const a = Math.sin(deltaPhi / 2) * Math.sin(deltaPhi / 2) +
                      Math.cos(phi1) * Math.cos(phi2) *
                      Math.sin(deltaLambda / 2) * Math.sin(deltaLambda / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

            return R * c;
        }

        function testBranch(branchId, branchName, branchLat, branchLng, radius) {
            if (!currentPosition) {
                alert('Please get your location first by clicking "Get My Location"');
                return;
            }

            if (!branchLat || !branchLng) {
                alert('This branch has no coordinates set!');
                return;
            }

            const distance = calculateDistance(
                currentPosition.latitude,
                currentPosition.longitude,
                branchLat,
                branchLng
            );

            const isValid = distance <= radius;
            const card = document.querySelector(`[data-branch-id="${branchId}"]`);
            const resultDiv = card.querySelector('.test-result');
            const distanceDiv = resultDiv.querySelector('.distance');
            const statusDiv = resultDiv.querySelector('.status');

            resultDiv.classList.remove('hidden');
            distanceDiv.textContent = `Distance: ${Math.round(distance)}m (Radius: ${radius}m)`;
            
            if (isValid) {
                statusDiv.textContent = 'INSIDE GEOFENCE';
                statusDiv.className = 'status text-sm font-semibold mt-1 text-green-600';
                card.classList.add('border-green-500', 'bg-green-50');
                card.classList.remove('border-red-500', 'bg-red-50');
            } else {
                statusDiv.textContent = `OUTSIDE by ${Math.round(distance - radius)}m`;
                statusDiv.className = 'status text-sm font-semibold mt-1 text-red-600';
                card.classList.add('border-red-500', 'bg-red-50');
                card.classList.remove('border-green-500', 'bg-green-50');
            }

            // Show detailed results
            const testResults = document.getElementById('testResults');
            const resultContent = document.getElementById('resultContent');
            
            testResults.classList.remove('hidden');
            resultContent.innerHTML = `
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div><strong>Branch:</strong> ${branchName}</div>
                    <div><strong>Radius:</strong> ${radius} meters</div>
                    <div><strong>Your Location:</strong> ${currentPosition.latitude.toFixed(6)}, ${currentPosition.longitude.toFixed(6)}</div>
                    <div><strong>Branch Location:</strong> ${branchLat.toFixed(6)}, ${branchLng.toFixed(6)}</div>
                    <div><strong>Distance:</strong> ${Math.round(distance)} meters</div>
                    <div><strong>Status:</strong> <span class="${isValid ? 'text-green-600' : 'text-red-600'} font-bold">${isValid ? 'INSIDE' : 'OUTSIDE'}</span></div>
                </div>
            `;
        }
    </script>
</body>
</html>
