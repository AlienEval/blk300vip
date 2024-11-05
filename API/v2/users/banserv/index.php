<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$configFile = 'config.json'; // Archivo de configuración para tiempos de cambio
$logFile = 'last_execution.log';
$timeoutLogFile = 'timeout.log';
$statusFile = 'status_record.json';

// Cargar configuración de tiempos de cambio
$configContent = file_get_contents($configFile);
if ($configContent === false) {
    die(json_encode(['status' => '500 Internal Server Error', 'message' => 'Error loading configuration.']));
}

$config = json_decode($configContent, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    die(json_encode(['status' => '500 Internal Server Error', 'message' => 'Invalid JSON format in configuration.']));
}

$now = new DateTime();
$nowTimestamp = $now->getTimestamp();

$lastLogTime = null;
if (file_exists($logFile)) {
    $lastLogTime = new DateTime(file_get_contents($logFile));
}

file_put_contents($logFile, $now->format('Y-m-d H:i:s'));

$statuses = [
    'timeout' => [],
    'remaining' => []
];

function getMinutesDifference($timestamp1, $timestamp2) {
    return round(($timestamp2 - $timestamp1) / 60);
}

function handleTimeout($file, &$data, $nowTimestamp, $config, &$statuses, $timeoutLogFile) {
    if (isset($data['status']) && isset($config[$data['status']])) {
        if (isset($data['time'])) {
            $statusTime = DateTime::createFromFormat('H:i', $data['time']);
            if ($statusTime !== false) {
                $statusTimestamp = $statusTime->getTimestamp();
                $timeLimit = $config[$data['status']] * 60; // Convertir minutos a segundos
                if (($nowTimestamp - $statusTimestamp) > $timeLimit) {
                    $data['status'] = 'timeout';
                    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
                    array_unshift($statuses['timeout'], [
                        'filename' => pathinfo($file, PATHINFO_FILENAME),
                        'mod_time' => filemtime($file)
                    ]);
                    // Registrar el cambio a "timeout"
                    file_put_contents($timeoutLogFile, "Timeout set for: " . pathinfo($file, PATHINFO_FILENAME) . "\n", FILE_APPEND);
                    return true;
                }
            }
        }
    }
    return false;
}

$jsonFiles = glob('../files/*.json');
$infoRequested = isset($_GET['info']) && $_GET['info'] === '!';

if ($infoRequested) {
    $response = [];
    foreach ($jsonFiles as $file) {
        $content = file_get_contents($file);
        if ($content === false) {
            continue;
        }

        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            continue;
        }

        if (isset($data['status']) && in_array($data['status'], array_keys($config))) {
            $status = $data['status'];
            $fileModTime = new DateTime('@' . filemtime($file));
            $fileModTimestamp = $fileModTime->getTimestamp();
            $timeDifference = getMinutesDifference($fileModTimestamp, $nowTimestamp);

            if (handleTimeout($file, $data, $nowTimestamp, $config, $statuses, $timeoutLogFile)) {
                continue;
            }

            $timeLimit = $config[$status] * 60; // Convertir minutos a segundos
            $remainingTime = $timeLimit - ($nowTimestamp - $fileModTimestamp);

            if ($remainingTime > 0) {
                $remainingMinutes = round($remainingTime / 60); // Convertir segundos a minutos
                $response[pathinfo($file, PATHINFO_FILENAME)] = $remainingMinutes;
            }
        }
    }

    echo json_encode($response, JSON_PRETTY_PRINT);
} else {
    echo json_encode(['status' => '200 OK']);
}

