<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Obtener el contenido del archivo desde la solicitud POST
    $tokens = $_POST['tokens'];
    
    // Establecer la ruta para guardar el archivo
    $filePath = '../active.txt';

    // Guardar el contenido en el archivo
    if (file_put_contents($filePath, $tokens)) {
        echo json_encode(["message" => "Archivo guardado correctamente"]);
    } else {
        echo json_encode(["message" => "Error al guardar el archivo"]);
    }
} else {
    echo json_encode(["message" => "Método no permitido"]);
}
?>

