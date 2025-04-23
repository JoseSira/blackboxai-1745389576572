<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'utils.php';

// Verificar autenticación
if (!$auth->isAuthenticated()) {
    header('Location: login.php');
    exit;
}

// Obtener información del usuario y sus NFTs
$user = $auth->getCurrentUser();
$stats = getUserStats($conn, $user['id']);

// Obtener los NFTs del usuario
$stmt = mysqli_prepare($conn, "
    SELECT 
        un.id,
        p.name as product_name,
        p.description,
        p.image_url,
        qc.code,
        un.acquired_at
    FROM user_nfts un
    JOIN products p ON un.product_id = p.id
    JOIN qr_codes qc ON un.qr_code_id = qc.id
    WHERE un.user_id = ?
    ORDER BY un.acquired_at DESC
");
mysqli_stmt_bind_param($stmt, "i", $user['id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$nfts = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema NFT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <?php require_once 'nav.php'; ?>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Estadísticas del usuario -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white shadow-lg rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Total NFTs</h3>
                <p class="text-3xl font-bold text-blue-500"><?php echo $stats['total_nfts']; ?></p>
            </div>

            <?php if ($stats['last_nft']): ?>
            <div class="bg-white shadow-lg rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Último NFT Canjeado</h3>
                <p class="text-lg text-gray-700"><?php echo htmlspecialchars($stats['last_nft']['name']); ?></p>
                <p class="text-sm text-gray-500">
                    <?php echo date('d/m/Y H:i', strtotime($stats['last_nft']['acquired_at'])); ?>
                </p>
            </div>
            <?php endif; ?>

            <div class="bg-white shadow-lg rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Acciones Rápidas</h3>
                <a href="redeem.php" 
                   class="inline-block bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-md transition duration-200">
                    <i class="fas fa-plus mr-2"></i>Canjear Nuevo NFT
                </a>
            </div>
        </div>

        <!-- Lista de NFTs -->
        <div class="bg-white shadow-lg rounded-lg p-6">
            <h2 class="text-2xl font-bold mb-6">Mis NFTs</h2>

            <?php if (empty($nfts)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-box-open text-6xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500 text-lg mb-4">Aún no tienes NFTs en tu colección</p>
                    <a href="redeem.php" class="text-blue-500 hover:text-blue-600 font-semibold">
                        <i class="fas fa-plus mr-2"></i>Canjear mi primer NFT
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($nfts as $nft): ?>
                        <div class="bg-white border rounded-lg overflow-hidden hover:shadow-lg transition-shadow">
                            <?php if ($nft['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($nft['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($nft['product_name']); ?>"
                                     class="w-full h-48 object-cover">
                            <?php else: ?>
                                <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                                    <i class="fas fa-image text-4xl text-gray-400"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="p-6">
                                <h3 class="text-xl font-bold text-gray-800 mb-2">
                                    <?php echo htmlspecialchars($nft['product_name']); ?>
                                </h3>
                                
                                <?php if ($nft['description']): ?>
                                    <p class="text-gray-600 mb-4">
                                        <?php echo htmlspecialchars($nft['description']); ?>
                                    </p>
                                <?php endif; ?>

                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-gray-500">
                                        ID: <?php echo substr($nft['code'], 0, 8); ?>...
                                    </span>
                                    <span class="text-gray-500">
                                        <?php echo date('d/m/Y', strtotime($nft['acquired_at'])); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
