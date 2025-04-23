<?php
require_once 'config.php';

// Inicializar variables
$code = filter_input(INPUT_GET, 'code', FILTER_SANITIZE_STRING);
$product = null;
$error = null;

// Verificar si se proporcionó un código
if ($code) {
    // Preparar y ejecutar la consulta para obtener la información del producto
    $stmt = mysqli_prepare($conn, "
        SELECT p.*, q.code, q.redeemed, q.redeemed_at 
        FROM qr_codes q 
        JOIN products p ON q.product_id = p.id 
        WHERE q.code = ?
    ");
    
    mysqli_stmt_bind_param($stmt, "s", $code);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $product = $row;
    } else {
        $error = "Código QR no válido";
    }
    
    mysqli_stmt_close($stmt);
} else {
    $error = "No se proporcionó ningún código QR";
}

require_once 'header.php';
?>

<div class="max-w-2xl mx-auto">
    <?php if ($error): ?>
        <!-- Mensaje de Error -->
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-8" role="alert">
            <p class="font-bold">Error</p>
            <p><?php echo htmlspecialchars($error); ?></p>
        </div>
    <?php elseif ($product): ?>
        <!-- Información del Producto -->
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <div class="p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">
                    <?php echo htmlspecialchars($product['name']); ?>
                </h2>
                
                <div class="space-y-4">
                    <!-- Estado del Código QR -->
                    <div class="flex items-center">
                        <span class="text-gray-600 font-semibold mr-2">Estado:</span>
                        <?php if ($product['redeemed']): ?>
                            <span class="bg-red-100 text-red-800 text-sm font-medium px-3 py-1 rounded-full">
                                Canjeado el <?php echo date('d/m/Y H:i', strtotime($product['redeemed_at'])); ?>
                            </span>
                        <?php else: ?>
                            <span class="bg-green-100 text-green-800 text-sm font-medium px-3 py-1 rounded-full">
                                Válido
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Código QR -->
                    <div class="flex items-center">
                        <span class="text-gray-600 font-semibold mr-2">Código:</span>
                        <span class="font-mono text-sm"><?php echo htmlspecialchars($product['code']); ?></span>
                    </div>

                    <?php if (!$product['redeemed']): ?>
                        <!-- Botón de Canjear -->
                        <form method="POST" action="redeem.php" class="mt-6">
                            <input type="hidden" name="code" value="<?php echo htmlspecialchars($product['code']); ?>">
                            <button type="submit" 
                                    class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 px-4 rounded-md transition duration-200">
                                <i class="fas fa-check-circle mr-2"></i>Canjear Código
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>
