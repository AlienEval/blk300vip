<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generador de Tokens</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    <style>
        @media (max-width: 768px) {
            .flex-col {
                flex-direction: column;
            }
            .w-1/2 {
                width: 100%;
            }
            .h-96 {
                height: auto;
            }
        }
    </style>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">
    <div class="max-w-4xl w-full mx-auto bg-white p-6 rounded-lg shadow-md flex flex-col md:flex-row">
        <!-- Opciones de generación de tokens -->
        <div class="w-1/2 p-4 border-r border-gray-300">
            <h1 class="text-2xl font-semibold mb-4">Generador de Tokens</h1>
            <form method="post">
                <div class="mb-3">
                    <label for="letra" class="block text-sm font-medium text-gray-700 mb-1">Letra:</label>
                    <input type="text" name="letra" id="letra" placeholder="Aleatorio si se deja en blanco" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-300">
                </div>
                <div class="mb-3">
                    <label for="numero1" class="block text-sm font-medium text-gray-700 mb-1">Número 1:</label>
                    <input type="number" name="numero1" id="numero1" placeholder="Aleatorio si se deja en blanco" min="0" max="9" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-300">
                </div>
                <div class="mb-3">
                    <label for="numero2" class="block text-sm font-medium text-gray-700 mb-1">Número 2:</label>
                    <input type="number" name="numero2" id="numero2" placeholder="Aleatorio si se deja en blanco" min="0" max="9" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-300">
                </div>
                <div class="mb-3">
                    <label for="letra_consecutiva1" class="block text-sm font-medium text-gray-700 mb-1">Letra Consecutiva 1:</label>
                    <input type="text" name="letra_consecutiva1" id="letra_consecutiva1" placeholder="Aleatorio si se deja en blanco" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-300">
                </div>
                <div class="mb-3">
                    <label for="letra_consecutiva2" class="block text-sm font-medium text-gray-700 mb-1">Letra Consecutiva 2:</label>
                    <input type="text" name="letra_consecutiva2" id="letra_consecutiva2" placeholder="Aleatorio si se deja en blanco" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-300">
                </div>
                <div class="mb-3">
                    <label for="numTokens" class="block text-sm font-medium text-gray-700 mb-1">Número de Tokens:</label>
                    <input type="number" name="numTokens" id="numTokens" value="10" min="1" max="1000" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-300">
                </div>
                <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold px-4 py-2 rounded mt-2">
                    Generar Tokens
                </button>
            </form>

            <!-- Formulario para agregar URL -->
            <div class="mt-6">
                <h2 class="text-xl font-medium mb-4">Agregar URL a Tokens:</h2>
                <div class="mb-3">
                    <label for="urlToAdd" class="block text-sm font-medium text-gray-700 mb-1">URL:</label>
                    <input type="url" id="urlToAdd" placeholder="URL para agregar a los tokens" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-300">
                </div>
                <button onclick="agregarURL()" class="w-full bg-green-500 hover:bg-green-600 text-white font-semibold px-4 py-2 rounded">
                    Agregar URL
                </button>
            </div>
        </div>

        <!-- Tokens generados -->
        <div class="w-1/2 p-4">
            <h2 class="text-2xl font-medium mb-4">Tokens Generados:</h2>
            <textarea id="tokens" class="w-full h-96 px-3 py-2 border rounded bg-gray-100 focus:outline-none focus:ring focus:border-blue-300 mb-4" readonly><?php
                function generarToken($letra, $numero1, $numero2, $letra_consecutiva1, $letra_consecutiva2) {
                    return $letra . $numero1 . $numero2 . $letra_consecutiva1 . $letra_consecutiva2;
                }

                function valorAleatorio($campo, $caracteres) {
                    return empty($campo) ? $caracteres[rand(0, strlen($caracteres) - 1)] : $campo;
                }

                if ($_SERVER["REQUEST_METHOD"] == "POST") {
                    $numTokens = $_POST['numTokens'];
                    $tokens = [];
                    for ($i = 0; $i < $numTokens; $i++) {
                        $letra = valorAleatorio($_POST['letra'], 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
                        $numero1 = empty($_POST['numero1']) ? rand(0, 9) : $_POST['numero1'];
                        $numero2 = empty($_POST['numero2']) ? rand(0, 9) : $_POST['numero2'];
                        $letra_consecutiva1 = valorAleatorio($_POST['letra_consecutiva1'], 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
                        $letra_consecutiva2 = valorAleatorio($_POST['letra_consecutiva2'], 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');

                        $token = generarToken($letra, $numero1, $numero2, $letra_consecutiva1, $letra_consecutiva2);
                        $tokens[] = $token;
                        echo $token . "\n";
                    }
                }
            ?></textarea>
            <button onclick="guardarArchivo()" class="w-full bg-green-500 hover:bg-green-600 text-white font-semibold px-4 py-2 rounded mb-4">
                Guardar Tokens en Archivo
            </button>
        </div>
    </div>

    <script>
        function guardarArchivo() {
            const tokens = document.getElementById('tokens').value;
            const password = prompt('Ingrese la contraseña para guardar el archivo:');
            
            if (password === 'sex123') {
                fetch('guardar_tokens.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        'tokens': tokens
                    })
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message); // Mostrar mensaje al usuario
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            } else {
                alert('Contraseña incorrecta.');
            }
        }

        function agregarURL() {
            const url = document.getElementById('urlToAdd').value;
            if (url) {
                const tokensTextarea = document.getElementById('tokens');
                const tokens = tokensTextarea.value.trim().split('\n');
                const tokensWithURL = tokens.map(token => url + token);
                tokensTextarea.value = tokensWithURL.join('\n');
            }
        }
    </script>
</body>
</html>

