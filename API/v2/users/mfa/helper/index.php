<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Función para desencriptar una cadena base64
function decryptBase64($encoded) {
    return base64_decode($encoded);
}

// Verifica si la longitud del string es 64 caracteres (256 bits en hexadecimal)
function isValidAES($string) {
    return preg_match('/^[a-f0-9]{64}$/', $string);
}

// Verificar si se proporciona el parámetro action1
if (isset($_GET['action1'])) {
    $token = $_GET['action1'];
    $filePath = "../../files/{$token}.json";
    
    if (file_exists($filePath)) {
        $jsonContent = file_get_contents($filePath);
        $data = json_decode($jsonContent, true);

        if ($data !== null) {
            if (isset($data["data1"])) {
                // Desencriptar el valor de data1
                $data1Decoded = decryptBase64($data["data1"]);
                
                // Modificar solo el campo data1 en el archivo JSON
                $data["data1"] = $data1Decoded;
                $newJsonContent = json_encode($data, JSON_PRETTY_PRINT);

                // Guardar el archivo modificado en la misma ubicación
                if (file_put_contents($filePath, $newJsonContent)) {
                    echo json_encode(["success" => "File updated successfully", "data1" => $data1Decoded]);
                } else {
                    echo json_encode(["error" => "Failed to update file"]);
                }
            } else {
                echo json_encode(["error" => "data1 not found"]);
            }
        } else {
            echo json_encode(["error" => "Error decoding JSON"]);
        }
    } else {
        echo json_encode(["error" => "File not found"]);
    }
} elseif (isset($_GET['token'])) {
    $token = $_GET['token'];
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
    } else {
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
    echo json_encode(["error" => "No token or action1 parameter provided"]);
}
?>

