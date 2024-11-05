<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

function isValidAES($string) {
    // Verifica si la longitud del string es 64 caracteres (256 bits en hexadecimal)
    // y que el string solo contiene caracteres hexadecimales.
    return preg_match('/^[a-f0-9]{64}$/', $string);
}

if (isset($_GET['token']) || isset($_GET['clr']) || isset($_GET['addban'])) {
    $token = isset($_GET['token']) ? $_GET['token'] : (isset($_GET['clr']) ? $_GET['clr'] : $_GET['addban']);
    $filePath = "../users/files/{$token}.json";
    
    if (isset($_GET['clr'])) {
        // Eliminar el archivo asociado al token
        if (file_exists($filePath)) {
            if (unlink($filePath)) {
                echo json_encode(["success" => "File deleted successfully"]);
            } else {
                echo json_encode(["error" => "Failed to delete file"]);
            }
        } else {
            echo json_encode(["error" => "File not found"]);
        }
    } elseif (isset($_GET['addban'])) {
        // Cambiar el estado a "ban"
        if (file_exists($filePath)) {
            $jsonContent = file_get_contents($filePath);
            $data = json_decode($jsonContent, true);

            if ($data !== null) {
                $data["status"] = "ban";
                file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
                echo json_encode(["success" => "Status updated to 'ban'"]);
            } else {
                echo json_encode(["error" => "Error decoding JSON"]);
            }
        } else {
            echo json_encode(["error" => "File not found"]);
        }
    } else {
        // Obtener información del archivo asociado al token
        if (file_exists($filePath)) {
            $jsonContent = file_get_contents($filePath);
            $data = json_decode($jsonContent, true);

            if ($data !== null) {
                $response = [
                    "token" => $data["token"],
                    "ip" => $data["ip"],
                    "strikes" => $data["strikes"],
                    "ltoken" => ($data["ltoken"] !== "undefined" && isValidAES($data["ltoken"])) ? "VALID" : ($data["ltoken"] === "undefined" ? "no" : $data["ltoken"]),
                    "ctoken" => ($data["ctoken"] !== "undefined" && isValidAES($data["ctoken"])) ? "VALID" : ($data["ctoken"] === "undefined" ? "no" : $data["ctoken"]),
                    "mtoken" => ($data["mtoken"] !== "undefined" && isValidAES($data["mtoken"])) ? "VALID" : ($data["mtoken"] === "undefined" ? "no" : $data["mtoken"]),
                    "data1" => ($data["data1"] !== "undefined" && isValidAES($data["data1"])) ? "VALID" : ($data["data1"] === "undefined" ? "no" : $data["data1"]),
                    "data2" => ($data["data2"] !== "undefined" && isValidAES($data["data2"])) ? "VALID" : ($data["data2"] === "undefined" ? "no" : $data["data2"]),
                    "data3" => ($data["data3"] !== "undefined" && isValidAES($data["data3"])) ? "VALID" : ($data["data3"] === "undefined" ? "no" : $data["data3"])
                ];

                echo json_encode($response);
            } else {
                echo json_encode(["error" => "Error decoding JSON"]);
            }
        } else {
            echo json_encode(["error" => "File not found"]);
        }
    }
} else {
    echo json_encode(["error" => "No token provided"]);
}
?>

