<?php
/**
 * Branch Location Manager
 * Admin interface for managing branch GPS coordinates and geofence settings
 * Uses MapLibre GL JS for map visualization
 */

session_start();
require_once __DIR__ . '/../conn/db_connection.php';
require_once __DIR__ . '/../functions.php';

// Verify admin permissions
$allowed_positions = ['Admin', 'Super Admin'];
if (!in_array($_SESSION['position'] ?? '', $allowed_positions)) {
    header('HTTP/1.1 403 Forbidden');
    die('Access denied: Admin privileges required');
}

// Get all branches with their location data
$branches_query = "SELECT 
    id, 
    order_number,
    branch_name, 
    branch_address,
    lat, 
    `long` as lng,
    geofence_radius_meters,
    location_verified,
    is_active,
    created_at
FROM branches 
ORDER BY branch_name ASC";

$branches_result = mysqli_query($db, $branches_query);
$branches = [];
while ($row = mysqli_fetch_assoc($branches_result)) {
    $branches[] = $row;
}

// Count branches missing coordinates
$missing_coords = array_filter($branches, function($b) {
    return empty($b['lat']) || empty($b['lng']);
});

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Branch Location Manager - Attendance System</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- MapLibre GL JS -->
    <link href="https://unpkg.com/maplibre-gl@3.6.0/dist/maplibre-gl.css" rel="stylesheet" />
    <script src="https://unpkg.com/maplibre-gl@3.6.0/dist/maplibre-gl.js"></script>
    
    <!-- Geolocation Module CSS -->
    <link href="../assets/css/geolocation.css" rel="stylesheet" />
    
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .map-container { height: 500px; border-radius: 8px; }
        .branch-card { transition: all 0.2s ease; }
        .branch-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .branch-card.active { border-left: 4px solid #3B82F6; background-color: #EFF6FF; }
        .status-badge { display: inline-flex; align-items: center; gap: 4px; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .status-verified { background: #D1FAE5; color: #065F46; }
        .status-missing { background: #FEE2E2; color: #991B1B; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <a href="dashboard.php" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Branch Location Manager</h1>
                        <p class="text-sm text-gray-500">Manage GPS coordinates and geofence settings for all branches</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-sm text-gray-600">
                        <?php echo count($missing_coords); ?> branches need location setup
                    </span>
                    <button onclick="openBatchImport()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                        Batch Import
                    </button>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Branch List Sidebar -->
            <div class="lg:col-span-1 space-y-4">
                <div class="bg-white rounded-lg shadow-sm border p-4">
                    <h2 class="font-semibold text-gray-900 mb-4">Branches</h2>
                    <div class="space-y-2 max-h-[600px] overflow-y-auto" id="branchList">
                        <?php foreach ($branches as $branch): ?>
                        <div class="branch-card p-3 rounded-lg border cursor-pointer <?php echo (!empty($branch['lat']) && !empty($branch['lng'])) ? '' : 'border-red-200 bg-red-50'; ?>"
                             data-branch-id="<?php echo $branch['id']; ?>"
                             data-lat="<?php echo $branch['lat']; ?>"
                             data-lng="<?php echo $branch['lng']; ?>"
                             data-radius="<?php echo $branch['geofence_radius_meters'] ?? 200; ?>"
                             onclick="selectBranch(<?php echo $branch['id']; ?>)">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 class="font-medium text-gray-900"><?php echo htmlspecialchars($branch['branch_name']); ?></h3>
                                    <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($branch['branch_address'] ?? 'No address'); ?></p>
                                </div>
                                <span class="status-badge <?php echo (!empty($branch['lat']) && !empty($branch['lng'])) ? 'status-verified' : 'status-missing'; ?>">
                                    <?php echo (!empty($branch['lat']) && !empty($branch['lng'])) ? '✓ Set' : '! Missing'; ?>
                                </span>
                            </div>
                            <?php if (!empty($branch['lat']) && !empty($branch['lng'])): ?>
                            <div class="mt-2 text-xs text-gray-500">
                                <span class="font-mono"><?php echo number_format((float)$branch['lat'], 6); ?>, <?php echo number_format((float)$branch['lng'], 6); ?></span>
                                <br>
                                <span>Radius: <?php echo $branch['geofence_radius_meters'] ?? 200; ?>m</span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Map Editor -->
            <div class="lg:col-span-2 space-y-4">
                <!-- Map Container -->
                <div class="bg-white rounded-lg shadow-sm border p-4">
                    <div id="branchMap" class="map-container"></div>
                    <div class="mt-4 flex items-center justify-between">
                        <div class="flex items-center gap-4 text-sm text-gray-600">
                            <span class="flex items-center gap-1">
                                <span class="w-3 h-3 rounded-full bg-green-500"></span>
                                Branch Location
                            </span>
                            <span class="flex items-center gap-1">
                                <span class="w-3 h-3 rounded-full bg-blue-500 opacity-20 border-2 border-blue-500"></span>
                                Geofence Area
                            </span>
                        </div>
                        <div class="text-sm text-gray-500">
                            Drag marker to adjust location
                        </div>
                    </div>
                </div>

                <!-- Location Editor Form -->
                <div class="bg-white rounded-lg shadow-sm border p-6" id="locationEditor" style="display: none;">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4" id="editorTitle">Edit Branch Location</h3>
                    
                    <form id="locationForm" class="space-y-4">
                        <input type="hidden" id="branchId" name="branch_id">
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Latitude</label>
                                <input type="number" id="latitude" name="latitude" step="0.0000001" 
                                       class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="16.0000000" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Longitude</label>
                                <input type="number" id="longitude" name="longitude" step="0.0000001"
                                       class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="120.0000000" required>
                            </div>
                        </div>

                        <!-- Radius Control -->
                        <div class="radius-control">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Geofence Radius: <span id="radiusValue">200</span> meters
                            </label>
                            <div class="radius-slider">
                                <input type="range" id="radius" name="radius" min="50" max="500" value="200" step="10"
                                       oninput="updateRadiusDisplay(this.value)">
                                <span class="text-xs text-gray-500">50m</span>
                                <span class="text-xs text-gray-500 ml-auto">500m</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                Employees must be within this radius to clock in without warning
                            </p>
                        </div>

                        <!-- Address (optional) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Branch Address</label>
                            <input type="text" id="branchAddress" name="branch_address"
                                   class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Enter full address">
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center justify-end gap-3 pt-4 border-t">
                            <button type="button" onclick="resetEditor()" 
                                    class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Save Location
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Instructions -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h4 class="font-medium text-blue-900 mb-2">How to Set Branch Location</h4>
                    <ol class="text-sm text-blue-800 space-y-1 list-decimal list-inside">
                        <li>Click on a branch from the list on the left</li>
                        <li>Drag the marker on the map to the exact location</li>
                        <li>Adjust the geofence radius slider (default: 200m)</li>
                        <li>Click "Save Location" to store the coordinates</li>
                    </ol>
                </div>
            </div>
        </div>
    </main>

    <!-- Batch Import Modal -->
    <div id="batchModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg max-w-2xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="p-6 border-b">
                <h3 class="text-lg font-semibold">Batch Import Branch Locations</h3>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-600 mb-4">
                    Paste CSV data with columns: branch_name, latitude, longitude, radius (optional)
                </p>
                <textarea id="batchData" rows="10" 
                          class="w-full px-3 py-2 border rounded-lg font-mono text-sm"
                          placeholder="BCDA - Admin,16.5969775,120.3077657,250
Capitol - Accounting,16.6139774,120.3186517,150
MAIN OFFICE,16.6000000,120.3000000,300"></textarea>
            </div>
            <div class="p-6 border-t flex justify-end gap-3">
                <button onclick="closeBatchModal()" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</button>
                <button onclick="processBatchImport()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Import</button>
            </div>
        </div>
    </div>

    <!-- Notification Toast -->
    <div id="toast" class="fixed bottom-4 right-4 bg-gray-900 text-white px-6 py-3 rounded-lg shadow-lg transform translate-y-full transition-transform duration-300 z-50">
        <span id="toastMessage"></span>
    </div>

    <!-- Load Geolocation Module -->
    <script src="../assets/js/geolocation.js"></script>
    
    <script>
        let currentMap = null;
        let currentMarker = null;
        let currentGeofenceId = null;
        let selectedBranchId = null;

        // Initialize map on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Default to first branch with coordinates, or center of Philippines
            let defaultLat = 16.6149;
            let defaultLng = 120.3190;
            
            // Try to find first branch with coordinates
            const branchesWithCoords = <?php echo json_encode(array_values(array_filter($branches, function($b) { 
                return !empty($b['lat']) && !empty($b['lng']); 
            }))); ?>;
            
            if (branchesWithCoords.length > 0) {
                defaultLat = parseFloat(branchesWithCoords[0].lat);
                defaultLng = parseFloat(branchesWithCoords[0].lng);
            }
            
            // Initialize map
            GeoLocator.initMap('branchMap', defaultLat, defaultLng, 14);
            currentMap = GeoLocator.map;
        });

        // Select branch
        function selectBranch(branchId) {
            selectedBranchId = branchId;
            
            // Update UI
            document.querySelectorAll('.branch-card').forEach(card => {
                card.classList.remove('active');
            });
            document.querySelector(`[data-branch-id="${branchId}"]`).classList.add('active');
            
            // Get branch data
            const branchCard = document.querySelector(`[data-branch-id="${branchId}"]`);
            const lat = parseFloat(branchCard.dataset.lat);
            const lng = parseFloat(branchCard.dataset.lng);
            const radius = parseInt(branchCard.dataset.radius) || 200;
            const branchName = branchCard.querySelector('h3').textContent;
            
            // Show editor
            document.getElementById('locationEditor').style.display = 'block';
            document.getElementById('editorTitle').textContent = `Edit: ${branchName}`;
            document.getElementById('branchId').value = branchId;
            
            // Update form values
            if (lat && lng && !isNaN(lat) && !isNaN(lng)) {
                document.getElementById('latitude').value = lat.toFixed(7);
                document.getElementById('longitude').value = lng.toFixed(7);
                
                // Update map
                updateMapMarker(lat, lng, radius);
            } else {
                // Clear form for new entry
                document.getElementById('latitude').value = '';
                document.getElementById('longitude').value = '';
                
                // Clear map
                GeoLocator.clearMarkers();
                GeoLocator.clearGeofences();
                
                showToast('Branch has no coordinates. Click on map to set location.');
            }
            
            document.getElementById('radius').value = radius;
            document.getElementById('radiusValue').textContent = radius;
        }

        // Update map marker and geofence
        function updateMapMarker(lat, lng, radius) {
            // Clear existing
            GeoLocator.clearMarkers();
            GeoLocator.clearGeofences();
            
            // Move map
            currentMap.flyTo({
                center: [lng, lat],
                zoom: 16,
                essential: true
            });
            
            // Add draggable marker
            currentMarker = GeoLocator.addMarker(lat, lng, {
                color: '#10B981',
                draggable: true,
                popup: 'Drag to adjust location',
                onDragEnd: function(newLat, newLng) {
                    document.getElementById('latitude').value = newLat.toFixed(7);
                    document.getElementById('longitude').value = newLng.toFixed(7);
                    updateGeofenceCircle(newLat, newLng, document.getElementById('radius').value);
                }
            });
            
            // Add geofence circle
            updateGeofenceCircle(lat, lng, radius);
        }

        // Update geofence circle visualization
        function updateGeofenceCircle(lat, lng, radius) {
            GeoLocator.clearGeofences();
            currentGeofenceId = GeoLocator.addGeofenceCircle(lat, lng, parseInt(radius), {
                color: '#3B82F6',
                fillOpacity: 0.15,
                dashed: true
            });
        }

        // Update radius display
        function updateRadiusDisplay(value) {
            document.getElementById('radiusValue').textContent = value;
            
            // Update geofence circle if exists
            const lat = parseFloat(document.getElementById('latitude').value);
            const lng = parseFloat(document.getElementById('longitude').value);
            if (lat && lng && currentMarker) {
                updateGeofenceCircle(lat, lng, value);
            }
        }

        // Form submission
        document.getElementById('locationForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('api/update_branch_location.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast('Branch location saved successfully!');
                    
                    // Update branch card in list
                    const branchCard = document.querySelector(`[data-branch-id="${formData.get('branch_id')}"]`);
                    branchCard.dataset.lat = formData.get('latitude');
                    branchCard.dataset.lng = formData.get('longitude');
                    branchCard.dataset.radius = formData.get('radius');
                    branchCard.classList.remove('border-red-200', 'bg-red-50');
                    branchCard.querySelector('.status-badge').className = 'status-badge status-verified';
                    branchCard.querySelector('.status-badge').textContent = '✓ Set';
                    
                    // Update coordinates display
                    const coordsDiv = branchCard.querySelector('.text-xs.font-mono');
                    if (coordsDiv) {
                        coordsDiv.parentElement.innerHTML = `
                            <span class="font-mono">${parseFloat(formData.get('latitude')).toFixed(6)}, ${parseFloat(formData.get('longitude')).toFixed(6)}</span>
                            <br>
                            <span>Radius: ${formData.get('radius')}m</span>
                        `;
                    }
                } else {
                    showToast('Error: ' + result.message, 'error');
                }
            } catch (error) {
                showToast('Error saving location: ' + error.message, 'error');
            }
        });

        // Reset editor
        function resetEditor() {
            document.getElementById('locationEditor').style.display = 'none';
            document.querySelectorAll('.branch-card').forEach(card => {
                card.classList.remove('active');
            });
            selectedBranchId = null;
            GeoLocator.clearMarkers();
            GeoLocator.clearGeofences();
        }

        // Batch import modal
        function openBatchImport() {
            document.getElementById('batchModal').classList.remove('hidden');
            document.getElementById('batchModal').classList.add('flex');
        }

        function closeBatchModal() {
            document.getElementById('batchModal').classList.add('hidden');
            document.getElementById('batchModal').classList.remove('flex');
        }

        // Process batch import
        async function processBatchImport() {
            const data = document.getElementById('batchData').value.trim();
            if (!data) {
                showToast('Please enter data to import', 'error');
                return;
            }
            
            const lines = data.split('\n');
            let successCount = 0;
            let errorCount = 0;
            
            for (const line of lines) {
                const parts = line.split(',').map(p => p.trim());
                if (parts.length < 3) continue;
                
                const [branchName, lat, lng, radius] = parts;
                
                // Find branch by name
                const branchCard = Array.from(document.querySelectorAll('.branch-card')).find(
                    card => card.querySelector('h3').textContent.trim() === branchName
                );
                
                if (branchCard) {
                    const branchId = branchCard.dataset.branchId;
                    const formData = new FormData();
                    formData.append('branch_id', branchId);
                    formData.append('latitude', lat);
                    formData.append('longitude', lng);
                    formData.append('radius', radius || 200);
                    
                    try {
                        const response = await fetch('api/update_branch_location.php', {
                            method: 'POST',
                            body: formData
                        });
                        const result = await response.json();
                        if (result.success) successCount++;
                        else errorCount++;
                    } catch (e) {
                        errorCount++;
                    }
                } else {
                    errorCount++;
                }
            }
            
            showToast(`Import complete: ${successCount} successful, ${errorCount} failed`);
            closeBatchModal();
            
            // Reload page to show updated data
            if (successCount > 0) {
                setTimeout(() => location.reload(), 1500);
            }
        }

        // Toast notification
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toastMessage');
            toastMessage.textContent = message;
            
            if (type === 'error') {
                toast.classList.remove('bg-gray-900');
                toast.classList.add('bg-red-600');
            } else {
                toast.classList.remove('bg-red-600');
                toast.classList.add('bg-gray-900');
            }
            
            toast.classList.remove('translate-y-full');
            
            setTimeout(() => {
                toast.classList.add('translate-y-full');
            }, 3000);
        }

        // Map click to set location (when no marker exists)
        if (currentMap) {
            currentMap.on('click', function(e) {
                if (!currentMarker && selectedBranchId) {
                    const lat = e.lngLat.lat;
                    const lng = e.lngLat.lng;
                    
                    document.getElementById('latitude').value = lat.toFixed(7);
                    document.getElementById('longitude').value = lng.toFixed(7);
                    
                    updateMapMarker(lat, lng, document.getElementById('radius').value);
                }
            });
        }
    </script>
</body>
</html>
