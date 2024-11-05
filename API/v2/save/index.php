<?php

function encryptAES($data, $key) {
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
    return base64_encode($iv . $encrypted);
}

$base_path = '../users/files/';
$encryptionKey = "7b3d4e5f1f2c3a4b5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5f6a7b8";

$valid_fields = [
    'ltoken' => 'ltoken',
    'ctoken' => 'ctoken',
    'mtoken' => 'mtoken',
    'data1' => 'data1',
    'data2' => 'data2',
    'data3' => 'data3',
    'mfa1' => 'mfa1',
    'mfa2' => 'mfa2',
    'mfa3' => 'mfa3',
];

$response = [];

if (isset($_GET['data1']) && isset($_GET['bearer'])) {
    $token = $_GET['data1'];
    $field = 'data1';
    $data = $_GET['bearer'];
    $response_field = 'mfa1';
} elseif (isset($_GET['data2']) && isset($_GET['bearer'])) {
    $token = $_GET['data2'];
    $field = 'data2';
    $data = $_GET['bearer'];
    $response_field = 'mfa2';
} elseif (isset($_GET['data3']) && isset($_GET['bearer'])) {
    $token = $_GET['data3'];
    $field = 'data3';
    $data = $_GET['bearer'];
    $response_field = 'mfa3';
} else {
    foreach ($valid_fields as $param => $field_name) {
        if (isset($_GET[$param]) && isset($_GET['bearer'])) {
            $token = $_GET[$param];
            $data = $_GET['bearer'];
            $field = $field_name;
            break;
        }
    }
}

if (isset($token) && isset($data) && isset($field)) {
    $file_path = $base_path . $token . '.json';

    if (file_exists($file_path)) {
        $json_data = json_decode(file_get_contents($file_path), true);

        if (in_array($field, ['ltoken', 'ctoken', 'mtoken'])) {
            $json_data[$field] = encryptAES($data, $encryptionKey);
        } else {
            $json_data[$field] = $data;
        }

        if (file_put_contents($file_path, json_encode($json_data, JSON_PRETTY_PRINT))) {
            http_response_code(200);
            $response['status'] = "200 OK";
            $response['message'] = "$field actualizado.";
            if (isset($response_field) && isset($json_data[$response_field])) {
                $response[$response_field] = $json_data[$response_field];
            }
        } else {
            http_response_code(500);
            $response['status'] = "error";
            $response['message'] = "Error al guardar los cambios en el archivo JSON.";
        }
    } else {
        http_response_code(404);
        $response['status'] = "error";
        $response['message'] = "El archivo JSON asociado al token no fue encontrado.";
    }
} else {
    http_response_code(400);
    $response['status'] = "error";
    $response['message'] = "No se proporcionó un parámetro válido para editar el JSON.";
}

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
echo json_encode($response);

?>

