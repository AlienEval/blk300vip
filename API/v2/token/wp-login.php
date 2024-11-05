<?php
session_start();

// Verificar si la sesión de tokens_para_entrega está activa y mostrar el contenido de okload.txt
if (isset($_SESSION['tokens_para_entrega'])) {
    $tokens_para_entrega = file_get_contents('okload.txt');
    if (empty($tokens_para_entrega)) {
        $tokens_para_entrega = "Archivo vacío";
    }
}

// Configuración de la contraseña
$password = 'sex123';

// Verificar si se ha enviado el formulario de inicio de sesión
if (isset($_POST['password'])) {
    if ($_POST['password'] === $password) {
        $_SESSION['loggedin'] = true;
    } else {
        $login_error = "Contraseña incorrecta.";
    }
}

// Verificar si el usuario está autenticado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    // Mostrar formulario de inicio de sesión
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Untitled</title>
        <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { background-color: white; }
            .container { max-width: 600px; }
        </style>
    </head>
    <body>
        <div class="container mt-5">
            <h1 class="text-center"></h1>
            <?php if (isset($login_error)) echo '<div class="alert alert-danger">' . $login_error . '</div>'; ?>
            <form action="" method="post" class="mt-3">
                <div class="form-group">
                    <label for="password">Contraseña:</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Ingresar</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Función para eliminar un token de un archivo y agregarlo a otro
function moverToken($token, $origen, $destino) {
    $file = file_get_contents($origen);
    $tokens = explode("\n", trim($file));
    
    if (($key = array_search($token, $tokens)) !== false) {
        unset($tokens[$key]);
        file_put_contents($origen, implode("\n", $tokens));
        
        if (!file_exists($destino)) {
            file_put_contents($destino, $token . PHP_EOL);
        } else {
            file_put_contents($destino, PHP_EOL . $token, FILE_APPEND);
        }
        
        return true;
    }
    return false;
}

// Función para generar un token en el formato A12BC
function generarToken() {
    $letras = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $numeros = '0123456789';
    return $letras[rand(0, 25)] . $numeros[rand(0, 9)] . $numeros[rand(0, 9)] . $letras[rand(0, 25)] . $letras[rand(0, 25)];
}

// Función para duplicar las líneas dentro de active.txt
function duplicarValidacion() {
    $lines = file('active.txt');
    $duplicatedLines = array_merge($lines, $lines);
    file_put_contents('active.txt', implode("", $duplicatedLines));
    return "Líneas duplicadas con éxito.";
}

// Función para obtener el número de líneas de un archivo
function obtenerNumeroDeLineas($archivo) {
    $lineas = file($archivo);
    return count($lineas);
}

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_token'])) {
        $token = strtoupper(trim($_POST['token']));
        file_put_contents('active.txt', $token . PHP_EOL, FILE_APPEND);
        $message = "Token agregado con éxito.";
    } elseif (isset($_POST['remove_token'])) {
        $token = strtoupper(trim($_POST['token']));
        if (moverToken($token, 'active.txt', 'inactive.txt')) {
            $message = "Token desactivado con éxito.";
        } else {
            $message = "Token no encontrado.";
        }
    } elseif (isset($_POST['activate_token'])) {
        $token = strtoupper(trim($_POST['token']));
        if (moverToken($token, 'inactive.txt', 'active.txt')) {
            $message = "Token activado con éxito.";
        } else {
            $message = "Token no encontrado.";
        }
    } elseif (isset($_POST['generate_tokens'])) {
        $cantidad = intval($_POST['cantidad']);
        $nuevosTokens = [];
        for ($i = 0; $i < $cantidad; $i++) {
            $token = generarToken();
            $nuevosTokens[] = $token;
            file_put_contents('active.txt', $token . PHP_EOL, FILE_APPEND);
        }
        $message = "Tokens generados con éxito: " . implode(", ", $nuevosTokens);
    } elseif (isset($_POST['reset_tokens'])) {
        $confirmacion = isset($_POST['confirm_reset']) ? $_POST['confirm_reset'] : '';
        if ($confirmacion === 'yes') {
            // Borrar todos los tokens y okload.txt
            file_put_contents('active.txt', '');
            file_put_contents('inactive.txt', '');
            file_put_contents('okload.txt', '');
            $message = "Todos los tokens han sido reseteados.";
        } else {
            $message = "Confirmación requerida para resetear los tokens.";
        }
    } elseif (isset($_POST['duplicate_lines'])) {
        $message = duplicarValidacion();
    } elseif (isset($_POST['delete_token'])) {
        $token = strtoupper(trim($_POST['delete_token']));
        if (moverToken($token, 'active.txt', 'inactive.txt')) {
            $message = "Token desactivado con éxito.";
        } else {
            $message = "Token no encontrado.";
        }
    } elseif (isset($_POST['upload_file'])) {
        $file = $_FILES['file']['tmp_name'];
        if ($file) {
            $fileContent = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            file_put_contents('active.txt', implode("\n", $fileContent), FILE_APPEND);
            $message = "Archivo cargado y tokens agregados con éxito.";
        } else {
            $message = "Error al cargar el archivo.";
        }
    } elseif (isset($_POST['save_file'])) {
        $fileContent = file_get_contents('active.txt');
        file_put_contents('active.txt', $fileContent);
        $message = "Archivo active.txt guardado con éxito.";
    }
}

// Leer tokens de los archivos
$okTokens = file_exists('active.txt') ? file('active.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
$deletedTokens = file_exists('inactive.txt') ? file('inactive.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];

// Obtener el número de líneas de los archivos
$num_lines_ok = obtenerNumeroDeLineas('active.txt');
$num_lines_deleted = obtenerNumeroDeLineas('inactive.txt');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Untitled</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #white; }
        .container { max-width: 800px; }
        .token-list { height: 200px; overflow-y: auto; }
        .token-list li { position: relative; }
        .copy-btn { position: absolute; top: 0; right: 0; padding: 0.25rem 0.5rem; font-size: 0.75rem; }
        /* Animación de la advertencia */
        @keyframes baloom {
            0% { transform: scale(0); }
            70% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        .alert-baloom {
            animation: baloom 0.5s ease;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center mb-4">Gestoken V2</h1>
        <?php if (isset($message)) echo '<div class="alert alert-info alert-baloom">' . $message . '</div>'; ?>
        <div class="row">
            <div class="col-md-6">
                <form action="" method="post">
                    <div class="form-group">
                        <h2 class="h5">Agregar Token</h2>
                        <input type="text" name="token" class="form-control mb-2" placeholder="Ingrese el token" required>
                        <button type="submit" name="add_token" class="btn btn-success btn-block">Agregar Token</button>
                    </div>
                </form>
            </div>
            <div class="col-md-6">
                <form action="" method="post">
                    <div class="form-group">
                        <h2 class="h5">Desactivar Token</h2>
                        <input type="text" name="token" class="form-control mb-2" placeholder="Ingrese el token" required>
                        <button type="submit" name="remove_token" class="btn btn-warning btn-block">Desactivar Token</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-md-6">
                <h2 class="h5">Tokens Activos (<?php echo $num_lines_ok; ?> líneas)</h2>
                <ul class="list-group token-list">
                    <?php foreach ($okTokens as $token) : ?>
                        <li class="list-group-item"><?php echo $token; ?><button type="button" class="btn btn-sm btn-danger copy-btn" id="copyBtn<?php echo $token; ?>" data-token="<?php echo $token; ?>">Copiar</button></li>
                    <?php endforeach; ?>
                </ul>
                <form action="" method="post" class="mt-3">
                    <button type="submit" name="duplicate_lines" class="btn btn-primary btn-block">Duplicar Validación</button>
                </form>
            </div>
            <div class="col-md-6">
                <h2 class="h5">Tokens Desactivados (<?php echo $num_lines_deleted; ?> líneas)</h2>
                <ul class="list-group token-list">
                    <?php foreach ($deletedTokens as $token) : ?>
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <span><?php echo $token; ?></span>
                                <form action="" method="post">
                                    <input type="hidden" name="token" value="<?php echo $token; ?>">
                                    <button type="submit" name="activate_token" class="btn btn-sm btn-success">Activar</button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-md-6">
                <form action="" method="post">
                    <div class="form-group">
                        <h2 class="h5">Generar Tokens</h2>
                        <input type="number" name="cantidad" class="form-control mb-2" min="1" value="10" required>
                        <button type="submit" name="generate_tokens" class="btn btn-warning btn-block">Generar Tokens</button>
                    </div>
                </form>
            </div>
            <div class="col-md-6">
                <form action="" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <h2 class="h5">Cargar Tokens desde Archivo</h2>
                        <input type="file" name="file" class="form-control-file mb-2" required>
                        <button type="submit" name="upload_file" class="btn btn-primary btn-block">Cargar Archivo</button>
                    </div>
                </form>
            </div>
        </div>
        <form id="deleteForm" action="" method="post" class="mt-3">
            <input type="hidden" name="delete_token" id="deleteToken">
        </form>
        <form action="" method="post" class="mt-3" onsubmit="return confirm('¿Estás seguro de que deseas resetear todos los tokens? Esta acción no se puede deshacer.');">
            <button type="submit" name="reset_tokens" class="btn btn-secondary btn-block">Resetear Todos los Tokens</button>
            <input type="hidden" name="confirm_reset" value="yes">
        </form>
        <?php if (isset($_SESSION['tokens_para_entrega'])) : ?>
            <div class="row mt-4">
                <div class="col-md-12">
                    <h2 class="h5">Tokens para Entrega</h2>
                    <textarea class="form-control" rows="5" readonly><?php echo $tokens_para_entrega; ?></textarea>
                </div>
            </div>
        <?php endif; ?>
        <form action="" method="post" class="mt-3">
            <button type="submit" name="logout" class="btn btn-dark btn-block">Cerrar Sesión</button>
        </form>
        <form action="" method="post">
            <button type="submit" name="save_file" class="btn btn-primary btn-block">Guardar Archivo active.txt</button>
        </form>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            var copyButtons = document.querySelectorAll('.copy-btn');
            copyButtons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var token = this.getAttribute('data-token');
                    navigator.clipboard.writeText(token).then(function () {
                        // Agregar clase de animación de baloom
                        document.querySelector('.alert-baloom').classList.add('alert-baloom');
                        alert('Token copiado al portapapeles: ' + token);
                    }, function (err) {
                        console.error('Error al copiar el token al portapapeles: ', err);
                    });
                });
            });
        });
    </script>
</body>
</html>
