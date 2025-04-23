<?php
if (!isset($_SESSION)) {
    session_start();
}

// Determinar la página actual
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Navbar -->
<nav class="bg-white shadow-lg">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <a href="<?php echo isset($_SESSION['user_id']) ? 'dashboard.php' : 'index.php'; ?>" 
                   class="text-xl font-bold text-gray-800">
                    Sistema NFT
                </a>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- Menú de navegación para usuarios autenticados -->
                    <div class="hidden md:flex md:ml-10 space-x-8">
                        <a href="dashboard.php" 
                           class="<?php echo $current_page === 'dashboard.php' ? 'text-blue-500' : 'text-gray-500 hover:text-gray-700'; ?> px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-home mr-2"></i>Dashboard
                        </a>
                        
                        <a href="redeem.php" 
                           class="<?php echo $current_page === 'redeem.php' ? 'text-blue-500' : 'text-gray-500 hover:text-gray-700'; ?> px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-qrcode mr-2"></i>Canjear NFT
                        </a>
                        
                        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                            <a href="admin.php" 
                               class="<?php echo $current_page === 'admin.php' ? 'text-blue-500' : 'text-gray-500 hover:text-gray-700'; ?> px-3 py-2 rounded-md text-sm font-medium">
                                <i class="fas fa-cog mr-2"></i>Administración
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="flex items-center">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- Menú de usuario -->
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-600">
                            <i class="fas fa-user mr-2"></i><?php echo htmlspecialchars($_SESSION['username']); ?>
                        </span>
                        <a href="logout.php" class="text-red-500 hover:text-red-700">
                            <i class="fas fa-sign-out-alt mr-2"></i>Salir
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Botones de login/registro -->
                    <div class="flex items-center space-x-4">
                        <a href="login.php" class="text-gray-500 hover:text-gray-700">
                            <i class="fas fa-sign-in-alt mr-2"></i>Iniciar Sesión
                        </a>
                        <a href="register.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md">
                            <i class="fas fa-user-plus mr-2"></i>Registrarse
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Contenedor principal -->
<div class="max-w-7xl mx-auto px-4 py-8">
