<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'utils.php';

// Verificar autenticación
if (!$auth->isAuthenticated()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';
$code = filter_input(INPUT_GET, 'code', FILTER_SANITIZE_STRING);

if ($code) {
    try {
        // Verificar y canjear el código QR
        if (redeemQRCode($conn, $code, $_SESSION['user_id'])) {
            // Obtener el nombre del producto
            $stmt = mysqli_prepare($conn, "
                SELECT p.name 
                FROM qr_codes qc
                JOIN products p ON qc.product_id = p.id
                WHERE qc.code = ?
            ");
            mysqli_stmt_bind_param($stmt, "s", $code);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $product = mysqli_fetch_assoc($result);

            $success = "¡Felicitaciones! Has canjeado exitosamente el NFT: " . $product['name'];
            // Redirigir al dashboard después de 2 segundos
            header("refresh:2;url=dashboard.php");
        } else {
            $error = "El código QR no es válido o ya ha sido canjeado.";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Canjear NFT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <?php require_once 'nav.php'; ?>

    <div class="max-w-2xl mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Canjear NFT</h1>

            <?php if ($error): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                    <p><?php echo htmlspecialchars($success); ?></p>
                    <p class="mt-2 text-sm">Redirigiendo al dashboard...</p>
                </div>
            <?php endif; ?>

            <?php if (!$code): ?>
                <div class="space-y-8">
                    <!-- Formulario de ingreso manual -->
                    <div>
                        <h2 class="text-lg font-semibold mb-4">Ingresar Código Manualmente</h2>
                        <form method="GET" class="space-y-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="code">
                                    Código QR
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                        <i class="fas fa-qrcode"></i>
                                    </span>
                                    <input type="text" name="code" id="code" required
                                           class="w-full pl-10 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                           placeholder="Ingrese el código QR">
                                </div>
                            </div>

                            <button type="submit" 
                                    class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 px-4 rounded-md transition duration-200">
                                <i class="fas fa-check-circle mr-2"></i>Canjear NFT
                            </button>
                        </form>
                    </div>

                    <!-- Separador -->
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white text-gray-500">O</span>
                        </div>
                    </div>

                    <!-- Escanear QR -->
                    <div>
                        <h2 class="text-lg font-semibold mb-4">Escanear Código QR</h2>
                        <div id="reader" class="bg-gray-100 p-8 rounded-lg">
                            <div class="text-center">
                                <i class="fas fa-camera text-6xl text-gray-400 mb-4"></i>
                                <p class="text-gray-600">Haz clic para activar la cámara</p>
                                <button id="startScanner" 
                                        class="mt-4 bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-md transition duration-200">
                                    <i class="fas fa-camera mr-2"></i>Iniciar Escáner
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="mt-6 text-center">
                <a href="dashboard.php" class="text-blue-500 hover:text-blue-600">
                    <i class="fas fa-arrow-left mr-2"></i>Volver al Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Script para el escáner QR -->
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script>
    document.getElementById('startScanner').addEventListener('click', function() {
        const html5QrCode = new Html5Qrcode("reader");
        const config = { fps: 10, qrbox: { width: 250, height: 250 } };

        html5QrCode.start({ facingMode: "environment" }, config, (decodedText) => {
            // Detener el escáner
            html5QrCode.stop();
            
            // Redirigir a la URL con el código
            window.location.href = `redeem.php?code=${decodedText}`;
        });
    });
    </script>
</body>
</html>
