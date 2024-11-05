<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json");

$directory = '../users/files/';
$blacklistFile = '../token/blacklist.txt';

function updateStatus($token, $statusKey, $statusValue, $incrementStrikes = false) {
    global $directory, $blacklistFile;
    $filename = $directory . $token . '.json';

    if (file_exists($filename)) {
        $json = file_get_contents($filename);
        $data = json_decode($json, true);

        // Update status key
        $data[$statusKey] = $statusValue;

        if ($incrementStrikes) {
            // Initialize strikes if not set
            if (!isset($data['strikes'])) {
                $data['strikes'] = 0;
            }

            // Increment strikes
            $data['strikes'] = intval($data['strikes']) + 1;

            if ($data['strikes'] >= 3) {
                // Obtain user's IP
                $ip = $data['ip'];

                // Add IP to blacklist
                file_put_contents($blacklistFile, $ip . PHP_EOL, FILE_APPEND);

                // Set status to "banned"
                $data['status'] = 'banned';
            }
        }

        $updatedJson = json_encode($data, JSON_PRETTY_PRINT);
        file_put_contents($filename, $updatedJson);
        echo json_encode(array(
            'status' => '200 OK',
            'strikes' => $data['strikes']
        ));
    } else {
        echo json_encode(array(
            'status' => '404 Not Found'
        ));
    }
}

function addBan($token) {
    global $directory, $blacklistFile;
    $filename = $directory . $token . '.json';

    if (file_exists($filename)) {
        $json = file_get_contents($filename);
        $data = json_decode($json, true);
        $ip = $data['ip'];
        file_put_contents($blacklistFile, $ip . PHP_EOL, FILE_APPEND);
        
        // Set status to "banned"
        $data['status'] = 'banned';

        $updatedJson = json_encode($data, JSON_PRETTY_PRINT);
        file_put_contents($filename, $updatedJson);

        echo json_encode(array(
            'status' => '200 OK',
            'message' => 'IP added to blacklist and status set to "banned"'
        ));
    } else {
        echo json_encode(array(
            'status' => '404 Not Found',
            'message' => 'Token not found'
        ));
    }
}

if (isset($_GET['ltoken'])) {
    $token = $_GET['ltoken'];
    updateStatus($token, 'status', 'ltoken');
} elseif (isset($_GET['ctoken'])) {
    $token = $_GET['ctoken'];
    updateStatus($token, 'status', 'ctoken');
} elseif (isset($_GET['mtoken'])) {
    $token = $_GET['mtoken'];
    updateStatus($token, 'status', 'mtoken');
} elseif (isset($_GET['ban'])) {
    $token = $_GET['ban'];
    updateStatus($token, 'status', 'banned');
} elseif (isset($_GET['leave'])) {
    $token = $_GET['leave'];
    updateStatus($token, 'status', 'leave');
} elseif (isset($_GET['finish'])) {
    $token = $_GET['finish'];
    updateStatus($token, 'status', 'finished');
} elseif (isset($_GET['mfa'])) {
    $token = $_GET['mfa'];
    updateStatus($token, 'status', 'mfa');
} elseif (isset($_GET['stk'])) {
    $token = $_GET['stk'];
    updateStatus($token, 'status', 'striked', true);
} elseif (isset($_GET['addban'])) {
    $token = $_GET['addban'];
    addBan($token);
} else {
    echo json_encode(array(
        'status' => '400 Bad Request'
    ));
}
?>

