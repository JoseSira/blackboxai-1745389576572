<?php
require_once 'config.php';
session_start();

// Verificar si el usuario está logueado y es administrador
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Inicializar variables
$codes = [];
$product_name = '';
$alert_message = '';
$alert_type = '';

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_SANITIZE_NUMBER_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_SANITIZE_NUMBER_INT);
    
    if ($product_id && $quantity > 0) {
        // Obtener información del producto
        $stmt = mysqli_prepare($conn, "SELECT name FROM products WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $product_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($product_row = mysqli_fetch_assoc($result)) {
            $product_name = $product_row['name'];
            $success_count = 0;
            
            // Generar códigos QR
            for ($i = 0; $i < $quantity; $i++) {
                $code = uniqid() . bin2hex(random_bytes(8));
                
                // Insertar código en la base de datos
                $insert_stmt = mysqli_prepare($conn, "INSERT INTO qr_codes (code, product_id) VALUES (?, ?)");
                mysqli_stmt_bind_param($insert_stmt, "si", $code, $product_id);
                
                if (mysqli_stmt_execute($insert_stmt)) {
                    $success_count++;
                    $codes[] = $code;
                }
                mysqli_stmt_close($insert_stmt);
            }
            
            if ($success_count > 0) {
                $alert_message = "Se generaron $success_count códigos QR exitosamente.";
                $alert_type = 'success';
            }
        } else {
            $alert_message = "Producto no encontrado.";
            $alert_type = 'error';
        }
        mysqli_stmt_close($stmt);
    } else {
        $alert_message = "Por favor, seleccione un producto y una cantidad válida.";
        $alert_type = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Generador de Códigos QR</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg mb-8">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <span class="text-xl font-bold text-gray-800">Panel de Administración</span>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600">
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </span>
                    <a href="logout.php" class="text-red-500 hover:text-red-700">
                        <i class="fas fa-sign-out-alt"></i> Salir
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4">
        <?php if ($alert_message): ?>
            <div class="<?php echo $alert_type === 'success' ? 'bg-green-100 border-green-500 text-green-700' : 'bg-red-100 border-red-500 text-red-700'; ?> border-l-4 p-4 mb-8" role="alert">
                <p><?php echo htmlspecialchars($alert_message); ?></p>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Estadísticas -->
            <div class="bg-white shadow-lg rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Total NFTs</h3>
                <?php
                $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM qr_codes");
                $total = mysqli_fetch_assoc($result)['total'];
                ?>
                <p class="text-3xl font-bold text-blue-500"><?php echo $total; ?></p>
            </div>

            <div class="bg-white shadow-lg rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">NFTs Canjeados</h3>
                <?php
                $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM qr_codes WHERE redeemed = 1");
                $redeemed = mysqli_fetch_assoc($result)['total'];
                ?>
                <p class="text-3xl font-bold text-green-500"><?php echo $redeemed; ?></p>
            </div>

            <div class="bg-white shadow-lg rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Usuarios Registrados</h3>
                <?php
                $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM users");
                $users = mysqli_fetch_assoc($result)['total'];
                ?>
                <p class="text-3xl font-bold text-purple-500"><?php echo $users; ?></p>
            </div>
        </div>

        <div class="bg-white shadow-lg rounded-lg p-6 mb-8">
            <h2 class="text-2xl font-bold mb-6">Generar Códigos QR</h2>
            
            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="product_id">
                        Seleccionar Producto
                    </label>
                    <select name="product_id" id="product_id" required 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Seleccione un producto</option>
                        <?php
                        $products_query = "SELECT * FROM products ORDER BY name";
                        $products_result = mysqli_query($conn, $products_query);
                        while($product = mysqli_fetch_assoc($products_result)): ?>
                            <option value="<?php echo htmlspecialchars($product['id']); ?>">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="quantity">
                        Cantidad de Códigos QR
                    </label>
                    <input type="number" name="quantity" id="quantity" min="1" value="1" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <button type="submit" 
                        class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 px-4 rounded-md transition duration-200">
                    <i class="fas fa-qrcode mr-2"></i>Generar Códigos QR
                </button>
            </form>
        </div>

        <?php if (!empty($codes)): ?>
        <div class="bg-white shadow-lg rounded-lg p-6 mb-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold">Códigos QR Generados</h2>
                <button onclick="downloadAllQR()" 
                        class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-md transition duration-200">
                    <i class="fas fa-download mr-2"></i>Descargar Todos
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($codes as $code): ?>
                    <div class="text-center p-4 border rounded-lg hover:shadow-lg transition-shadow">
                        <div id="qr-<?php echo htmlspecialchars($code); ?>" class="mb-4"></div>
                        <button onclick="downloadQR('<?php echo htmlspecialchars($code); ?>')" 
                                class="bg-blue-500 hover:bg-blue-600 text-white text-sm py-2 px-4 rounded-md transition duration-200">
                            <i class="fas fa-download mr-2"></i>Descargar
                        </button>
                        <div class="mt-2 text-xs text-gray-600 break-all">
                            <?php echo htmlspecialchars($code); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="hidden">
                <canvas id="printable-canvas"></canvas>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const codes = <?php echo json_encode($codes ?? []); ?>;
        const productName = <?php echo json_encode($product_name ?? ''); ?>;
        const baseUrl = window.location.protocol + "//" + window.location.host;

        codes.forEach(code => {
            const qrUrl = `${baseUrl}/redeem.php?code=${code}`;
            new QRCode(document.getElementById(`qr-${code}`), {
                text: qrUrl,
                width: 128,
                height: 128
            });
        });
    });

    async function downloadQR(code) {
        const qrContainer = document.querySelector(`#qr-${code}`);
        const canvas = qrContainer.querySelector('canvas');
        
        if (!canvas) {
            showAlert('Error al generar el código QR', 'error');
            return;
        }

        const downloadCanvas = document.createElement('canvas');
        const ctx = downloadCanvas.getContext('2d');
        const padding = 20;
        
        downloadCanvas.width = canvas.width + (padding * 2);
        downloadCanvas.height = canvas.height + (padding * 2) + 30;
        
        ctx.fillStyle = 'white';
        ctx.fillRect(0, 0, downloadCanvas.width, downloadCanvas.height);
        ctx.drawImage(canvas, padding, padding);
        
        ctx.fillStyle = 'black';
        ctx.font = '12px Arial';
        ctx.textAlign = 'center';
        ctx.fillText(code, downloadCanvas.width / 2, canvas.height + padding + 20);

        const link = document.createElement('a');
        link.download = `qr-${code}.png`;
        link.href = downloadCanvas.toDataURL('image/png');
        link.click();
    }

    async function downloadAllQR() {
        const codes = <?php echo json_encode($codes ?? []); ?>;
        const productName = <?php echo json_encode($product_name ?? ''); ?>;
        
        if (!codes.length) {
            alert('No hay códigos QR para descargar');
            return;
        }

        const canvas = document.getElementById('printable-canvas');
        const ctx = canvas.getContext('2d');
        const qrSize = 200;
        const margin = 20;
        const codesPerRow = 3;
        
        const totalRows = Math.ceil(codes.length / codesPerRow);
        canvas.width = (qrSize + margin * 2) * codesPerRow + margin;
        canvas.height = (qrSize + margin * 2) * totalRows + margin;

        ctx.fillStyle = 'white';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        codes.forEach((code, index) => {
            const row = Math.floor(index / codesPerRow);
            const col = index % codesPerRow;
            const x = margin + col * (qrSize + margin * 2);
            const y = margin + row * (qrSize + margin * 2);
            
            const qrCanvas = document.querySelector(`#qr-${code} canvas`);
            if (qrCanvas) {
                ctx.strokeStyle = '#ccc';
                ctx.setLineDash([5, 5]);
                ctx.strokeRect(x - margin/2, y - margin/2, qrSize + margin, qrSize + margin);
                ctx.setLineDash([]);

                ctx.drawImage(qrCanvas, x, y, qrSize, qrSize);

                ctx.fillStyle = 'black';
                ctx.font = '12px Arial';
                ctx.textAlign = 'center';
                ctx.fillText(productName, x + qrSize/2, y + qrSize + 15);
                ctx.font = '10px Arial';
                ctx.fillText(code, x + qrSize/2, y + qrSize + 30);
            }
        });

        const link = document.createElement('a');
        link.download = 'qr-codes-sheet.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
    }
    </script>
</body>
</html>
