<?php
// Función para verificar si un usuario está autenticado
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: login.php');
        exit;
    }
}

// Función para verificar si un usuario es administrador
function checkAdmin() {
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        header('Location: dashboard.php');
        exit;
    }
}

// Función para mostrar mensajes de alerta
function showAlert($message, $type = 'success') {
    $class = $type === 'success' ? 'bg-green-100 border-green-500 text-green-700' : 'bg-red-100 border-red-500 text-red-700';
    return "
        <div class='{$class} border-l-4 p-4 mb-6' role='alert'>
            <p>" . htmlspecialchars($message) . "</p>
        </div>
    ";
}

// Función para obtener estadísticas del usuario
function getUserStats($conn, $user_id) {
    $stats = [];
    
    // Total de NFTs del usuario
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM user_nfts WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $stats['total_nfts'] = mysqli_fetch_assoc($result)['total'];

    // Último NFT canjeado
    $stmt = mysqli_prepare($conn, "
        SELECT p.name, un.acquired_at 
        FROM user_nfts un 
        JOIN products p ON un.product_id = p.id 
        WHERE un.user_id = ? 
        ORDER BY un.acquired_at DESC 
        LIMIT 1
    ");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $stats['last_nft'] = mysqli_fetch_assoc($result);

    return $stats;
}

// Función para obtener estadísticas del sistema (admin)
function getSystemStats($conn) {
    $stats = [];
    
    // Total de NFTs generados
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM qr_codes");
    $stats['total_nfts'] = mysqli_fetch_assoc($result)['total'];
    
    // Total de NFTs canjeados
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM qr_codes WHERE redeemed = 1");
    $stats['redeemed_nfts'] = mysqli_fetch_assoc($result)['total'];
    
    // Total de usuarios
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE is_admin = 0");
    $stats['total_users'] = mysqli_fetch_assoc($result)['total'];
    
    // Últimos canjes
    $result = mysqli_query($conn, "
        SELECT u.username, p.name as product_name, qc.redeemed_at
        FROM qr_codes qc
        JOIN users u ON qc.redeemed_by = u.id
        JOIN products p ON qc.product_id = p.id
        WHERE qc.redeemed = 1
        ORDER BY qc.redeemed_at DESC
        LIMIT 5
    ");
    $stats['recent_redemptions'] = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
    return $stats;
}

// Función para generar un código QR único
function generateUniqueCode($conn) {
    do {
        $code = uniqid() . bin2hex(random_bytes(8));
        $stmt = mysqli_prepare($conn, "SELECT id FROM qr_codes WHERE code = ?");
        mysqli_stmt_bind_param($stmt, "s", $code);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
    } while (mysqli_stmt_num_rows($stmt) > 0);
    
    return $code;
}

// Función para validar y sanitizar entrada
function sanitizeInput($input) {
    return htmlspecialchars(trim($input));
}

// Función para verificar si un código QR es válido y no ha sido canjeado
function validateQRCode($conn, $code) {
    $stmt = mysqli_prepare($conn, "
        SELECT qc.*, p.name as product_name 
        FROM qr_codes qc
        JOIN products p ON qc.product_id = p.id
        WHERE qc.code = ? AND qc.redeemed = 0
    ");
    mysqli_stmt_bind_param($stmt, "s", $code);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

// Función para canjear un código QR
function redeemQRCode($conn, $code, $user_id) {
    mysqli_begin_transaction($conn);
    
    try {
        // Verificar y obtener información del código
        $stmt = mysqli_prepare($conn, "
            SELECT id, product_id FROM qr_codes 
            WHERE code = ? AND redeemed = 0 
            FOR UPDATE
        ");
        mysqli_stmt_bind_param($stmt, "s", $code);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($qr_code = mysqli_fetch_assoc($result)) {
            // Marcar como canjeado
            $update_stmt = mysqli_prepare($conn, "
                UPDATE qr_codes 
                SET redeemed = 1, redeemed_at = NOW(), redeemed_by = ?
                WHERE id = ?
            ");
            mysqli_stmt_bind_param($update_stmt, "ii", $user_id, $qr_code['id']);
            mysqli_stmt_execute($update_stmt);
            
            // Agregar a la colección del usuario
            $insert_stmt = mysqli_prepare($conn, "
                INSERT INTO user_nfts (user_id, product_id, qr_code_id)
                VALUES (?, ?, ?)
            ");
            mysqli_stmt_bind_param($insert_stmt, "iii", $user_id, $qr_code['product_id'], $qr_code['id']);
            mysqli_stmt_execute($insert_stmt);
            
            mysqli_commit($conn);
            return true;
        }
        
        mysqli_rollback($conn);
        return false;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        throw $e;
    }
}
?>
