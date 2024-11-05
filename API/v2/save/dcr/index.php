<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

function decryptAES($data, $encryptionKey) {
    $data = base64_decode($data);
    $iv = substr($data, 0, openssl_cipher_iv_length('aes-256-cbc'));
    if (strlen($iv) !== 16) {
        return 'error';
    }
    $encrypted = substr($data, openssl_cipher_iv_length('aes-256-cbc'));
    $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $encryptionKey, 0, $iv);
    return $decrypted === false ? 'error' : $decrypted;
}

if (isset($_GET['token']) && isset($_GET['key'])) {
    $fileName = $_GET['token'];
    $encryptionKey = $_GET['key'];
    $filePath = "../../users/$fileName.json";

    if (file_exists($filePath)) {
        $jsonContent = file_get_contents($filePath);
        $jsonData = json_decode($jsonContent, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            $fieldsToDecrypt = ['ltoken', 'ctoken', 'mtoken', 'data1', 'data2', 'data3'];

            foreach ($fieldsToDecrypt as $field) {
                if (isset($jsonData[$field])) {
                    $jsonData[$field] = decryptAES($jsonData[$field], $encryptionKey);
                }
            }

            header('Content-Type: application/json');
            echo json_encode($jsonData);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON format in the file.']);
        }
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'File not found.']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters.']);
}

?>

