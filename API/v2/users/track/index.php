<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$logFile = 'last_execution.log';
$statusFile = 'status_record.json';

$jsonFiles = glob('../files/*.json');

$now = new DateTime();
$nowTimestamp = $now->getTimestamp();

$lastLogTime = null;
if (file_exists($logFile)) {
    $lastLogTime = new DateTime(file_get_contents($logFile));
}

file_put_contents($logFile, $now->format('Y-m-d H:i:s'));

$previousStatuses = [];
if (file_exists($statusFile)) {
    $previousStatuses = json_decode(file_get_contents($statusFile), true) ?? [];
}

$statuses = [
    'init' => [],
    'ltoken' => [],
    'ctoken' => [],
    'mtoken' => [],
    'dtoken' => [],
    'mfa' => [],
    'finish' => [],
    'leave' => [],
    'ban' => [],
    'striked' => [],
    'timeout' => []
];

$tokens = [];

function getMinutesDifference($timestamp1, $timestamp2) {
    return round(($timestamp2 - $timestamp1) / 60);
}

// Handle ?status=!
if (isset($_GET['status']) && $_GET['status'] === '!') {
    foreach ($jsonFiles as $file) {
        $content = file_get_contents($file);
        if ($content === false) {
            continue;
        }

        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            continue;
        }

        $fileModTime = new DateTime('@' . filemtime($file));
        $fileModTimestamp = $fileModTime->getTimestamp();
        $timeDifference = getMinutesDifference($fileModTimestamp, $nowTimestamp);

        if (isset($data['token']) && isset($data['status'])) {
            $currentStatus = strtolower($data['status']);
            if (in_array($currentStatus, ['leave', 'timeout', 'striked'])) {
                continue; // Exclude files with these statuses from being changed to "ban"
            }

            if ($currentStatus === 'ltoken' && $timeDifference > 3) {
                $data['status'] = 'ban';
                file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
            } elseif ($currentStatus === 'ctoken' && $timeDifference > 10) {
                $data['status'] = 'ban';
                file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
            } elseif ($currentStatus === 'mtoken' && $timeDifference > 5) {
                $data['status'] = 'ban';
                file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
            }
        }

        if ($lastLogTime && ($nowTimestamp - $fileModTimestamp) > 600) { // 600 seconds = 10 minutes
            if (isset($data['status'])) {
                $data['status'] = 'timeout';
                file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
            }
        }
    }
    // Respond with "200 OK" JSON
    echo json_encode(['status' => '200 OK'], JSON_PRETTY_PRINT);
    exit; // Exit to prevent further execution
}

// Process regular requests
if (!isset($_GET['status'])) {
    foreach ($jsonFiles as $file) {
        $fileModTime = new DateTime('@' . filemtime($file));
        $fileModTimestamp = $fileModTime->getTimestamp();
        $statusUpdated = false;

        $content = file_get_contents($file);
        if ($content === false) {
            continue;
        }

        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            continue;
        }

        $timeDifference = getMinutesDifference($fileModTimestamp, $nowTimestamp);
        if (isset($data['token'])) {
            $tokens[$data['token']] = $timeDifference;
        }

        if ($lastLogTime && $fileModTimestamp > $lastLogTime->getTimestamp()) {
            if (isset($data['status'])) {
                $currentStatus = $data['status'];
                $fileKey = md5($file);
                $previousStatus = isset($previousStatuses[$fileKey]) ? $previousStatuses[$fileKey] : null;

                if ($currentStatus !== $previousStatus) {
                    $statusUpdated = true;
                }

                $previousStatuses[$fileKey] = $currentStatus;
            }
        }

        if (isset($data['status'])) {
            $status = strtolower($data['status']);
            if ($status === 'init' && $timeDifference > 1) {
                $data['status'] = 'ban';
                file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
                $statusUpdated = true;
            } elseif (array_key_exists($status, $statuses)) {
                array_unshift($statuses[$status], [
                    'filename' => pathinfo($file, PATHINFO_FILENAME),
                    'mod_time' => $fileModTimestamp
                ]);
            } else {
                array_unshift($statuses['timeout'], [
                    'filename' => pathinfo($file, PATHINFO_FILENAME),
                    'mod_time' => $fileModTimestamp
                ]);
            }
        } elseif ($statusUpdated) {
            array_unshift($statuses['timeout'], [
                'filename' => pathinfo($file, PATHINFO_FILENAME),
                'mod_time' => $fileModTimestamp
            ]);
        }
    }

    file_put_contents($statusFile, json_encode($previousStatuses, JSON_PRETTY_PRINT));

    $response = [];

    if (isset($_GET['mfa']) && $_GET['mfa'] === '!') {
        if (count($statuses['mfa']) > 0) {
            $response['mfa'] = [
                '#' => count($statuses['mfa']),
                'u' => []
            ];
            foreach ($statuses['mfa'] as $file) {
                $token = $file['filename'];
                $timeDifference = isset($tokens[$token]) ? $tokens[$token] : 'N/A';
                $response['mfa']['u'][$token] = $timeDifference;
            }
        }
    } else {
        foreach ($statuses as $status => $files) {
            if (count($files) > 0) {
                if (in_array($status, ['leave', 'ban', 'striked'])) {
                    $response[$status] = [
                        '#' => count($files)
                    ];
                } else {
                    usort($files, function ($a, $b) {
                        return $b['mod_time'] <=> $a['mod_time'];
                    });

                    $response[$status] = [
                        '#' => count($files),
                        'u' => []
                    ];
                    foreach ($files as $file) {
                        if ($status !== 'timeout') {
                            $token = $file['filename'];
                            $timeDifference = isset($tokens[$token]) ? $tokens[$token] : 'N/A';
                            $response[$status]['u'][$token] = $timeDifference;
                        }
                    }
                }
            }
        }
    }

    echo json_encode($response, JSON_PRETTY_PRINT);
}

?>

