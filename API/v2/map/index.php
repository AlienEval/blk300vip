<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Define the base directory for token files
$base_path = '../users/files/';

// Get the map number
$map_number = '1'; // Default map number

// Define the directory where map files are stored
$map_dir = './';

// Function to validate token format (basic check)
function isValidToken($token) {
    // Basic validation: check if the token is a valid base64 string
    return base64_encode(base64_decode($token, true)) === $token && strlen($token) >= 16;
}

// Function to load the map file based on the map number
function loadMap($map_number, $map_dir) {
    $map_file = $map_dir . 'map' . $map_number . '.json';
    if (file_exists($map_file)) {
        $map_content = file_get_contents($map_file);
        return json_decode($map_content, true);
    }
    return null;
}

// Default response
$response = ['status' => 'error', 'message' => 'Invalid request'];

if (isset($_GET['track'])) {
    $token = $_GET['track'];
    $file_path = $base_path . $token . '.json';

    // Check if the file exists
    if (file_exists($file_path)) {
        // Attempt to read the file
        $file_content = file_get_contents($file_path);
        if ($file_content === false) {
            $response = [
                'status' => '500 Internal Server Error',
                'message' => 'Unable to read the file'
            ];
        } else {
            // Attempt to decode JSON
            $token_data = json_decode($file_content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $response = [
                    'status' => '500 Internal Server Error',
                    'message' => 'Invalid JSON format'
                ];
            } else {
                $current_status = $token_data['status'];
                $mfastatus = $token_data['mfastatus'];
                $map_number = $token_data['map'] ?? '1'; // Default to map 1 if not set

                // Load the appropriate map file
                $map = loadMap($map_number, $map_dir);
                if (!$map) {
                    $response = [
                        'status' => '500 Internal Server Error',
                        'message' => 'Unable to load map file'
                    ];
                } else {
                    $status_groups = $map['status_groups'];
                    $status_mappings = $map['status_mappings'];
                    $mfastatus_mapping = $map['mfastatus_mapping'];

                    // Check if the current status is in the denied group
                    if (in_array($current_status, $status_groups['denied'])) {
                        $response = [
                            'status' => '200 OK',
                            'result' => [
                                'status' => $current_status,
                                'denied' => true,
                                'next' => 'unload'
                            ]
                        ];
                    } elseif ($current_status === 'init') {
                        $response = [
                            'status' => '200 OK',
                            'result' => [
                                'status' => $current_status,
                                'aesstatus' => 'none',
                                'next' => $status_mappings[$current_status] ?? 'unload'
                            ]
                        ];
                    } elseif ($current_status === 'ctoken') {
                        $ctoken = $token_data['ctoken'];
                        if ($ctoken !== 'undefined') {
                            if (isValidToken($ctoken)) {
                                $response = [
                                    'status' => '200 OK',
                                    'result' => [
                                        'status' => $current_status,
                                        'aesstatus' => 'ok',
                                        'next' => $status_mappings[$current_status] ?? 'unload' // Cambiado a 'mtoken'
                                    ]
                                ];
                            } else {
                                $response = [
                                    'status' => '200 OK',
                                    'result' => [
                                        'status' => $current_status,
                                        'aesstatus' => 'faked',
                                        'next' => 'unload'
                                    ]
                                ];
                            }
                        } else {
                            $response = [
                                'status' => '200 OK',
                                'result' => [
                                    'status' => $current_status,
                                    'aesstatus' => 'none',
                                    'next' => 'ltoken'
                                ]
                            ];
                        }
                    } elseif ($current_status === 'mtoken') {
                        $mtoken = $token_data['mtoken'];
                        if ($mtoken !== 'undefined') {
                            if (isValidToken($mtoken)) {
                                if ($mfastatus === 'on') {
                                    $response = [
                                        'status' => '200 OK',
                                        'result' => [
                                            'status' => $current_status,
                                            'aesstatus' => 'ok',
                                            'next' => 'mfa'
                                        ]
                                    ];
                                } else {
                                    $response = [
                                        'status' => '200 OK',
                                        'result' => [
                                            'status' => $current_status,
                                            'aesstatus' => 'ok',
                                            'next' => 'finish'
                                        ]
                                    ];
                                }
                            } else {
                                $response = [
                                    'status' => '200 OK',
                                    'result' => [
                                        'status' => $current_status,
                                        'aesstatus' => 'faked',
                                        'next' => 'unload'
                                    ]
                                ];
                            }
                        } else {
                            $response = [
                                'status' => '200 OK',
                                'result' => [
                                    'status' => $current_status,
                                    'aesstatus' => 'none',
                                    'next' => 'mtoken'
                                ]
                            ];
                        }
                    } elseif ($current_status === 'mfa') {
                        $response = [
                            'status' => '200 OK',
                            'result' => [
                                'status' => $current_status,
                                'next' => 'finish'
                            ]
                        ];
                    } else {
                        $response = [
                            'status' => '200 OK',
                            'result' => [
                                'status' => $current_status,
                                'next' => $status_mappings[$current_status] ?? 'unload'
                            ]
                        ];
                    }
                }
            }
        }
    } else {
        $response = [
            'status' => '404 Not Found',
            'message' => 'Token file not found'
        ];
    }
} else {
    $response = [
        'status' => '400 Bad Request',
        'message' => 'No track parameter provided'
    ];
}

echo json_encode($response);
?>

