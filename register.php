<?php
require_once 'config.php';
require_once 'auth.php';
session_start();

// Si el usuario ya está logueado, redirigir según su rol
if ($auth->isAuthenticated()) {
    header('Location: ' . ($auth->isAdmin() ? 'admin.php' : 'dashboard.php'));
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = 'Las contraseñas no coinciden';
    } else {
        $result = $auth->register($username, $email, $password);
        if ($result['success']) {
            $success = $result['message'];
            // Redirigir después de 2 segundos
            header("refresh:2;url=login.php");
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Sistema NFT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <?php require_once 'nav.php'; ?>

    <div class="max-w-md mx-auto px-4 py-8">
        <div class="bg-white shadow-lg rounded-lg p-8">
            <h2 class="text-2xl font-bold text-center mb-8">Crear Cuenta</h2>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                    <p><?php echo htmlspecialchars($success); ?></p>
                    <p class="mt-2 text-sm">Redirigiendo al login...</p>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="username">
                        Usuario
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" name="username" id="username" required
                               class="w-full pl-10 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Elija un nombre de usuario">
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                        Email
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="email" name="email" id="email" required
                               class="w-full pl-10 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Ingrese su email">
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                        Contraseña
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" name="password" id="password" required
                               class="w-full pl-10 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Elija una contraseña">
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="confirm_password">
                        Confirmar Contraseña
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" name="confirm_password" id="confirm_password" required
                               class="w-full pl-10 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Confirme su contraseña">
                    </div>
                </div>

                <button type="submit" 
                        class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 px-4 rounded-md transition duration-200">
                    <i class="fas fa-user-plus mr-2"></i>Registrarse
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-gray-600">¿Ya tienes una cuenta?</p>
                <a href="login.php" class="text-blue-500 hover:text-blue-600 font-semibold">
                    Iniciar Sesión
                </a>
            </div>
        </div>
    </div>
</body>
</html>
