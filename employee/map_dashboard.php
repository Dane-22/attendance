<?php
/**
 * Admin Map Dashboard - Geolocation & Geofence Monitoring
 * Features: Real-time employee locations, branch geofences, violation tracking
 * Technology: MapLibre GL JS, PHP, MySQL
 */

session_start();
require_once __DIR__ . '/../conn/db_connection.php';
require_once __DIR__ . '/../functions.php';

// Check if user is admin
$userRole = isset($_SESSION['position']) ? $_SESSION['position'] : '';
if (!in_array($userRole, ['Admin', 'Super Admin', 'Manager'])) {
    header('Location: dashboard.php');
    exit();
}

// Get current user info
$currentUserId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$currentUserName = isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin';

// Initialize variables
$branches = [];
$todayAttendance = [];
$violations = [];
$stats = [
    'total_branches' => 0,
    'active_branches' => 0,
    'employees_today' => 0,
    'violations_today' => 0,
    'accuracy_issues' => 0
];

try {
    // Get all branches with locations
    $branchSql = "SELECT id, branch_name, lat, `long`, geofence_radius_meters, is_active, location_verified
                  FROM branches 
                  WHERE lat IS NOT NULL AND lat != '' 
                  AND `long` IS NOT NULL AND `long` != ''
                  ORDER BY branch_name";
    $branchResult = mysqli_query($db, $branchSql);
    while ($branch = mysqli_fetch_assoc($branchResult)) {
        $branches[] = $branch;
    }
    
    // Get today's attendance with locations
    $today = date('Y-m-d');
    $attendanceSql = "SELECT a.id, a.employee_id, a.attendance_date, a.time_in, a.time_out,
                             a.clock_in_lat, a.clock_in_lng, a.clock_out_lat, a.clock_out_lng,
                             a.location_accuracy, a.flagged_accuracy, a.geofence_violation_count,
                             e.first_name, e.last_name, e.employee_code, e.position,
                             b.branch_name, b.id as branch_id
                      FROM attendance a
                      JOIN employees e ON a.employee_id = e.id
                      JOIN branches b ON a.branch_name = b.branch_name
                      WHERE a.attendance_date = ? 
                      AND (a.clock_in_lat IS NOT NULL OR a.clock_out_lat IS NOT NULL)
                      ORDER BY a.time_in DESC";
    
    $attStmt = mysqli_prepare($db, $attendanceSql);
    if ($attStmt) {
        mysqli_stmt_bind_param($attStmt, 's', $today);
        mysqli_stmt_execute($attStmt);
        $attResult = mysqli_stmt_get_result($attStmt);
        while ($attendance = mysqli_fetch_assoc($attResult)) {
            $todayAttendance[] = $attendance;
        }
        mysqli_stmt_close($attStmt);
    }
    
    // Get today's geofence violations
    $violationSql = "SELECT gv.*, e.first_name, e.last_name, e.employee_code, b.branch_name
                     FROM geofence_violations gv
                     JOIN employees e ON gv.employee_id = e.id
                     JOIN branches b ON gv.branch_id = b.id
                     WHERE gv.violation_date = ? AND gv.status = 'active'
                     ORDER BY gv.created_at DESC";
    
    $violStmt = mysqli_prepare($db, $violationSql);
    if ($violStmt) {
        mysqli_stmt_bind_param($violStmt, 's', $today);
        mysqli_stmt_execute($violStmt);
        $violResult = mysqli_stmt_get_result($violStmt);
        while ($violation = mysqli_fetch_assoc($violResult)) {
            $violations[] = $violation;
        }
        mysqli_stmt_close($violStmt);
    }
    
    // Calculate statistics
    $stats['total_branches'] = count($branches);
    $stats['active_branches'] = count(array_filter($branches, fn($b) => $b['is_active']));
    $stats['employees_today'] = count($todayAttendance);
    $stats['violations_today'] = count($violations);
    $stats['accuracy_issues'] = count(array_filter($todayAttendance, fn($a) => $a['flagged_accuracy']));
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Geolocation Dashboard - JAJR Attendance System</title>
    
    <!-- MapLibre GL JS -->
    <script src="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.js"></script>
    <link href="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.css" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="../assets/css/tailwind.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Styles -->
    <style>
        #map { 
            height: calc(100vh - 200px); 
            min-height: 500px;
            border-radius: 0.5rem;
        }
        
        .map-control-panel {
            backdrop-filter: blur(10px);
            background: rgba(15, 23, 42, 0.9);
        }
        
        .branch-marker {
            width: 12px;
            height: 12px;
            background: #3b82f6;
            border: 2px solid white;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .employee-marker {
            width: 8px;
            height: 8px;
            background: #10b981;
            border: 1px solid white;
            border-radius: 50%;
        }
        
        .violation-marker {
            width: 10px;
            height: 10px;
            background: #ef4444;
            border: 2px solid white;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }
        
        .geofence-circle {
            fill-opacity: 0.1;
            stroke-opacity: 0.3;
        }
        
        .accuracy-poor { background: #f59e0b; }
        .accuracy-good { background: #10b981; }
        .violation-high { background: #ef4444; }
    </style>
</head>
<body class="bg-gray-900 text-gray-100">
    <!-- Header -->
    <header class="bg-gray-800 border-b border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <i class="fas fa-map-marked-alt text-blue-500 mr-3"></i>
                    <h1 class="text-xl font-semibold">Geolocation Dashboard</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-400">Live Monitoring</span>
                    <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
            <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">Total Branches</p>
                        <p class="text-2xl font-bold"><?php echo $stats['total_branches']; ?></p>
                    </div>
                    <i class="fas fa-building text-blue-500 text-2xl"></i>
                </div>
            </div>
            
            <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">Active Branches</p>
                        <p class="text-2xl font-bold text-green-500"><?php echo $stats['active_branches']; ?></p>
                    </div>
                    <i class="fas fa-check-circle text-green-500 text-2xl"></i>
                </div>
            </div>
            
            <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">Employees Today</p>
                        <p class="text-2xl font-bold"><?php echo $stats['employees_today']; ?></p>
                    </div>
                    <i class="fas fa-users text-purple-500 text-2xl"></i>
                </div>
            </div>
            
            <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">Violations</p>
                        <p class="text-2xl font-bold text-red-500"><?php echo $stats['violations_today']; ?></p>
                    </div>
                    <i class="fas fa-exclamation-triangle text-red-500 text-2xl"></i>
                </div>
            </div>
            
            <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">Accuracy Issues</p>
                        <p class="text-2xl font-bold text-yellow-500"><?php echo $stats['accuracy_issues']; ?></p>
                    </div>
                    <i class="fas fa-crosshairs text-yellow-500 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Map and Controls -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Map Container -->
            <div class="lg:col-span-3">
                <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
                    <div class="p-4 border-b border-gray-700">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold">Live Location Map</h2>
                            <div class="flex items-center space-x-2">
                                <button onclick="toggleGeofences()" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 rounded text-sm">
                                    <i class="fas fa-draw-polygon mr-1"></i> Geofences
                                </button>
                                <button onclick="toggleEmployees()" class="px-3 py-1 bg-green-600 hover:bg-green-700 rounded text-sm">
                                    <i class="fas fa-user mr-1"></i> Employees
                                </button>
                                <button onclick="toggleViolations()" class="px-3 py-1 bg-red-600 hover:bg-red-700 rounded text-sm">
                                    <i class="fas fa-exclamation-triangle mr-1"></i> Violations
                                </button>
                                <button onclick="refreshMap()" class="px-3 py-1 bg-gray-600 hover:bg-gray-700 rounded text-sm">
                                    <i class="fas fa-sync-alt mr-1"></i> Refresh
                                </button>
                            </div>
                        </div>
                    </div>
                    <div id="map"></div>
                </div>
            </div>

            <!-- Side Panel -->
            <div class="space-y-6">
                <!-- Legend -->
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <h3 class="font-semibold mb-3">Legend</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-blue-500 rounded-full mr-2"></div>
                            <span>Branch Location</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                            <span>Employee (Good Accuracy)</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-yellow-500 rounded-full mr-2"></div>
                            <span>Employee (Poor Accuracy)</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-red-500 rounded-full mr-2"></div>
                            <span>Geofence Violation</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-8 h-8 border-2 border-blue-300 rounded-full opacity-30 mr-2"></div>
                            <span>Geofence Radius</span>
                        </div>
                    </div>
                </div>

                <!-- Recent Violations -->
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <h3 class="font-semibold mb-3">Recent Violations</h3>
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        <?php if (empty($violations)): ?>
                            <p class="text-gray-400 text-sm">No violations today</p>
                        <?php else: ?>
                            <?php foreach (array_slice($violations, 0, 5) as $violation): ?>
                                <div class="bg-gray-700 rounded p-2 text-sm">
                                    <div class="flex items-center justify-between">
                                        <span class="font-medium"><?php echo htmlspecialchars($violation['first_name'] . ' ' . $violation['last_name']); ?></span>
                                        <span class="text-red-400"><?php echo $violation['distance_from_branch']; ?>m</span>
                                    </div>
                                    <div class="text-gray-400 text-xs">
                                        <?php echo htmlspecialchars($violation['branch_name']); ?> • <?php echo date('H:i', strtotime($violation['violation_time'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Filter Controls -->
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <h3 class="font-semibold mb-3">Filters</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="text-sm text-gray-400">Branch</label>
                            <select id="branchFilter" class="w-full bg-gray-700 rounded px-3 py-2 text-sm">
                                <option value="">All Branches</option>
                                <?php foreach ($branches as $branch): ?>
                                    <option value="<?php echo $branch['id']; ?>"><?php echo htmlspecialchars($branch['branch_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="text-sm text-gray-400">Show Only</label>
                            <div class="space-y-1">
                                <label class="flex items-center text-sm">
                                    <input type="checkbox" id="showViolationsOnly" class="mr-2">
                                    <span>Violations Only</span>
                                </label>
                                <label class="flex items-center text-sm">
                                    <input type="checkbox" id="showPoorAccuracy" class="mr-2">
                                    <span>Poor Accuracy Only</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- JavaScript -->
    <script>
        // Map initialization
        let map;
        let branchesLayer;
        let employeesLayer;
        let violationsLayer;
        let geofencesLayer;
        
        // Data from PHP
        const branches = <?php echo json_encode($branches); ?>;
        const attendance = <?php echo json_encode($todayAttendance); ?>;
        const violations = <?php echo json_encode($violations); ?>;
        
        // Initialize map with free CartoDB Dark Matter tiles (no API key needed)
        map = new maplibregl.Map({
            container: 'map',
            style: {
                version: 8,
                sources: {
                    'carto-dark': {
                        type: 'raster',
                        tiles: ['https://a.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png'],
                        tileSize: 256,
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>'
                    }
                },
                layers: [{
                    id: 'carto-dark-layer',
                    type: 'raster',
                    source: 'carto-dark',
                    minzoom: 0,
                    maxzoom: 22
                }]
            },
            center: [120.5, 16.5], // Center on La Union area
            zoom: 10
        });
            
            // Add navigation control
            map.addControl(new maplibregl.NavigationControl());
            
            // Handle missing images gracefully
            map.on('styleimagemissing', function(e) {
                // Prevent errors for missing sprite images
                console.warn('Missing map image:', e.id);
            });
            
            // Wait for map to load
            map.on('load', function() {
                addBranches();
                addEmployees();
                addViolations();
                addGeofences();
                fitMapToBounds();
            });
        }
        
        // Add branch markers
        function addBranches() {
            const branchFeatures = branches.map(branch => ({
                type: 'Feature',
                geometry: {
                    type: 'Point',
                    coordinates: [parseFloat(branch.long), parseFloat(branch.lat)]
                },
                properties: {
                    id: branch.id,
                    name: branch.branch_name,
                    radius: branch.geofence_radius_meters,
                    active: branch.is_active,
                    verified: branch.location_verified
                }
            }));
            
            map.addSource('branches', {
                type: 'geojson',
                data: {
                    type: 'FeatureCollection',
                    features: branchFeatures
                }
            });
            
            map.addLayer({
                id: 'branches',
                type: 'circle',
                source: 'branches',
                paint: {
                    'circle-radius': 6,
                    'circle-color': '#3b82f6',
                    'circle-stroke-color': '#ffffff',
                    'circle-stroke-width': 2
                }
            });
            
            // Add popup for branches
            map.on('click', 'branches', function(e) {
                const props = e.features[0].properties;
                new maplibregl.Popup()
                    .setHTML(`
                        <div class="p-2">
                            <h4 class="font-bold">${props.name}</h4>
                            <p class="text-sm">Radius: ${props.radius}m</p>
                            <p class="text-sm">Status: ${props.active ? 'Active' : 'Inactive'}</p>
                            <p class="text-sm">Verified: ${props.verified ? 'Yes' : 'No'}</p>
                        </div>
                    `)
                    .setLngLat(e.lngLat)
                    .addTo(map);
            });
        }
        
        // Add employee markers
        function addEmployees() {
            const employeeFeatures = attendance.map(att => {
                const lat = att.clock_in_lat || att.clock_out_lat;
                const lng = att.clock_in_lng || att.clock_out_lng;
                const accuracy = att.location_accuracy;
                const isPoorAccuracy = att.flagged_accuracy;
                
                return {
                    type: 'Feature',
                    geometry: {
                        type: 'Point',
                        coordinates: [parseFloat(lng), parseFloat(lat)]
                    },
                    properties: {
                        id: att.id,
                        employee_id: att.employee_id,
                        name: `${att.first_name} ${att.last_name}`,
                        code: att.employee_code,
                        position: att.position,
                        branch: att.branch_name,
                        time_in: att.time_in,
                        time_out: att.time_out,
                        accuracy: accuracy,
                        flagged_accuracy: isPoorAccuracy,
                        violations: att.geofence_violation_count
                    }
                };
            });
            
            map.addSource('employees', {
                type: 'geojson',
                data: {
                    type: 'FeatureCollection',
                    features: employeeFeatures
                }
            });
            
            map.addLayer({
                id: 'employees',
                type: 'circle',
                source: 'employees',
                paint: {
                    'circle-radius': 4,
                    'circle-color': [
                        'case',
                        ['get', 'flagged_accuracy'],
                        '#f59e0b', // Yellow for poor accuracy
                        '#10b981'  // Green for good accuracy
                    ],
                    'circle-stroke-color': '#ffffff',
                    'circle-stroke-width': 1
                }
            });
            
            // Add popup for employees
            map.on('click', 'employees', function(e) {
                const props = e.features[0].properties;
                new maplibregl.Popup()
                    .setHTML(`
                        <div class="p-2">
                            <h4 class="font-bold">${props.name}</h4>
                            <p class="text-sm">${props.code} • ${props.position}</p>
                            <p class="text-sm">Branch: ${props.branch}</p>
                            <p class="text-sm">Time In: ${props.time_in}</p>
                            <p class="text-sm">Accuracy: ${props.accuracy}m</p>
                            <p class="text-sm">Violations: ${props.violations}</p>
                        </div>
                    `)
                    .setLngLat(e.lngLat)
                    .addTo(map);
            });
        }
        
        // Add violation markers
        function addViolations() {
            const violationFeatures = violations.map(violation => ({
                type: 'Feature',
                geometry: {
                    type: 'Point',
                    coordinates: [parseFloat(violation.longitude), parseFloat(violation.latitude)]
                },
                properties: {
                    id: violation.id,
                    employee_id: violation.employee_id,
                    name: `${violation.first_name} ${violation.last_name}`,
                    branch: violation.branch_name,
                    distance: violation.distance_from_branch,
                    radius: violation.geofence_radius,
                    time: violation.violation_time,
                    accuracy: violation.accuracy_meters
                }
            }));
            
            map.addSource('violations', {
                type: 'geojson',
                data: {
                    type: 'FeatureCollection',
                    features: violationFeatures
                }
            });
            
            map.addLayer({
                id: 'violations',
                type: 'circle',
                source: 'violations',
                paint: {
                    'circle-radius': 6,
                    'circle-color': '#ef4444',
                    'circle-stroke-color': '#ffffff',
                    'circle-stroke-width': 2
                }
            });
            
            // Add popup for violations
            map.on('click', 'violations', function(e) {
                const props = e.features[0].properties;
                new maplibregl.Popup()
                    .setHTML(`
                        <div class="p-2">
                            <h4 class="font-bold text-red-400">Geofence Violation</h4>
                            <p class="text-sm">${props.name}</p>
                            <p class="text-sm">Branch: ${props.branch}</p>
                            <p class="text-sm">Distance: ${props.distance}m (Radius: ${props.radius}m)</p>
                            <p class="text-sm">Time: ${props.time}</p>
                            <p class="text-sm">Accuracy: ${props.accuracy}m</p>
                        </div>
                    `)
                    .setLngLat(e.lngLat)
                    .addTo(map);
            });
        }
        
        // Add geofence circles
        function addGeofences() {
            const geofenceFeatures = branches.map(branch => {
                const center = [parseFloat(branch.long), parseFloat(branch.lat)];
                const radius = branch.geofence_radius_meters;
                
                // Create circle points (approximation)
                const points = [];
                const numPoints = 64;
                for (let i = 0; i <= numPoints; i++) {
                    const angle = (i / numPoints) * 2 * Math.PI;
                    const offset = radius / 111320; // Rough conversion to degrees
                    points.push([
                        center[0] + offset * Math.cos(angle),
                        center[1] + offset * Math.sin(angle)
                    ]);
                }
                
                return {
                    type: 'Feature',
                    geometry: {
                        type: 'Polygon',
                        coordinates: [points]
                    },
                    properties: {
                        branch_id: branch.id,
                        branch_name: branch.branch_name,
                        radius: radius
                    }
                };
            });
            
            map.addSource('geofences', {
                type: 'geojson',
                data: {
                    type: 'FeatureCollection',
                    features: geofenceFeatures
                }
            });
            
            map.addLayer({
                id: 'geofences',
                type: 'fill',
                source: 'geofences',
                paint: {
                    'fill-color': '#3b82f6',
                    'fill-opacity': 0.1
                }
            });
            
            map.addLayer({
                id: 'geofences-outline',
                type: 'line',
                source: 'geofences',
                paint: {
                    'line-color': '#3b82f6',
                    'line-width': 2,
                    'line-opacity': 0.3
                }
            });
        }
        
        // Fit map to show all points
        function fitMapToBounds() {
            const allPoints = [
                ...branches.map(b => [parseFloat(b.long), parseFloat(b.lat)]),
                ...attendance.map(a => [parseFloat(a.clock_in_lng || a.clock_out_lng), parseFloat(a.clock_in_lat || a.clock_out_lat)]),
                ...violations.map(v => [parseFloat(v.longitude), parseFloat(v.latitude)])
            ].filter(coord => coord[0] && coord[1]);
            
            if (allPoints.length > 0) {
                const bounds = new maplibregl.LngLatBounds();
                allPoints.forEach(coord => bounds.extend(coord));
                map.fitBounds(bounds, { padding: 50 });
            }
        }
        
        // Toggle functions
        function toggleGeofences() {
            const visibility = map.getLayoutProperty('geofences', 'visibility');
            map.setLayoutProperty('geofences', 'visibility', 
                visibility === 'visible' ? 'none' : 'visible');
            map.setLayoutProperty('geofences-outline', 'visibility', 
                visibility === 'visible' ? 'none' : 'visible');
        }
        
        function toggleEmployees() {
            const visibility = map.getLayoutProperty('employees', 'visibility');
            map.setLayoutProperty('employees', 'visibility', 
                visibility === 'visible' ? 'none' : 'visible');
        }
        
        function toggleViolations() {
            const visibility = map.getLayoutProperty('violations', 'visibility');
            map.setLayoutProperty('violations', 'visibility', 
                visibility === 'visible' ? 'none' : 'visible');
        }
        
        function refreshMap() {
            location.reload();
        }
        
        // Initialize map when page loads
        document.addEventListener('DOMContentLoaded', initMap);
        
        // Auto-refresh every 5 minutes
        setInterval(refreshMap, 300000);
    </script>
</body>
</html>
