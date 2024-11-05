<?php

// Funciones de encryptación y desencryptación con AES

// Clave para el cifrado (debe tener 16, 24 o 32 caracteres para AES-128, AES-192 o AES-256 respectivamente)
$encryptionKey = "7b3d4e5f1f2c3a4b5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2c3d4e5f6a7b8";

// Variables para almacenar resultados
$encryptedTexts = [];
$decryptedTexts = [];

// Función para cifrar datos con AES
function encryptAES($data) {
    global $encryptionKey;
    // Obtener el vector de inicialización
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    // Cifrar los datos usando AES-256-CBC
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', $encryptionKey, 0, $iv);
    // Concatenar el IV al inicio del texto cifrado para poder desencriptar más adelante
    return base64_encode($iv . $encrypted);
}

// Función para descifrar datos con AES
function decryptAES($data) {
    global $encryptionKey;
    // Decodificar los datos de base64
    $data = base64_decode($data);
    // Extraer el IV del texto cifrado
    $iv = substr($data, 0, openssl_cipher_iv_length('aes-256-cbc'));
    // Extraer el texto cifrado sin el IV
    $encrypted = substr($data, openssl_cipher_iv_length('aes-256-cbc'));
    // Descifrar los datos usando AES-256-CBC
    return openssl_decrypt($encrypted, 'aes-256-cbc', $encryptionKey, 0, $iv);
}

// Procesamiento del formulario si se envió
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["encrypt"])) {
        // Verificar si texts está definida y no es nula
        if (!empty($_POST["texts"])) {
            // Obtener las líneas de texto a cifrar
            $texts = explode("\n", $_POST["texts"]);
            foreach ($texts as $text) {
                if (!empty($text)) {
                    // Cifrar cada línea de texto
                    $encryptedTexts[] = encryptAES(trim($text));
                }
            }
        }
    } elseif (isset($_POST["decrypt"])) {
        // Verificar si encryptedTexts está definida y no es nula
        if (!empty($_POST["encryptedTexts"])) {
            // Obtener las líneas de texto cifrado a descifrar
            $encryptedTexts = explode("\n", $_POST["encryptedTexts"]);
            foreach ($encryptedTexts as $encryptedText) {
                if (!empty($encryptedText)) {
                    // Descifrar cada línea de texto cifrado
                    $decryptedTexts[] = decryptAES(trim($encryptedText));
                }
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Encryptación/Desencryptación AES en PHP</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            padding: 10px;
        }
        h2 {
            text-align: center;
            margin-top: 0;
        }
        form {
            background-color: #fff;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }
        textarea {
            width: calc(100% - 20px);
            padding: 8px;
            margin-bottom: 10px;
            box-sizing: border-box;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
        }
        button:hover {
            background-color: #45a049;
        }
        .result {
            background-color: #f9f9f9;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <h2>Encryptación/Desencryptación con AES en PHP</h2>

    <div class="encryption-form">
        <h3>Cifrar Texto</h3>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <label for="texts">Texto(s) a Cifrar (una línea por textbox):</label>
            <textarea id="texts" name="texts" rows="4" required></textarea>
            <button type="submit" name="encrypt">Cifrar</button>
        </form>
        <?php if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["encrypt"]) && !empty($encryptedTexts)): ?>
            <div class="result">
                <h3>Texto(s) Cifrado(s):</h3>
                <?php foreach ($encryptedTexts as $encryptedText): ?>
                    <p><?php echo htmlspecialchars($encryptedText); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="decryption-form">
        <h3>Desencriptar Texto(s) Cifrado(s)</h3>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <label for="encryptedTexts">Texto(s) Cifrado(s) (una línea por textbox):</label>
            <textarea id="encryptedTexts" name="encryptedTexts" rows="4" required></textarea>
            <button type="submit" name="decrypt">Desencriptar</button>
        </form>
        <?php if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["decrypt"]) && !empty($decryptedTexts)): ?>
            <div class="result">
                <h3>Texto(s) Desencriptado(s):</h3>
                <?php foreach ($decryptedTexts as $decryptedText): ?>
                    <p><?php echo htmlspecialchars($decryptedText); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

