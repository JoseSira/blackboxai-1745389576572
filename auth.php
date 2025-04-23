<?php
require_once 'config.php';
require_once 'utils.php';

class Auth {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // Registrar un nuevo usuario
    public function register($username, $email, $password) {
        try {
            // Verificar si el usuario ya existe
            $stmt = mysqli_prepare($this->conn, "SELECT id FROM users WHERE username = ? OR email = ?");
            mysqli_stmt_bind_param($stmt, "ss", $username, $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) > 0) {
                return [
                    'success' => false,
                    'message' => 'El usuario o email ya está registrado'
                ];
            }

            // Crear el nuevo usuario
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($this->conn, "
                INSERT INTO users (username, email, password, is_admin) 
                VALUES (?, ?, ?, FALSE)
            ");
            mysqli_stmt_bind_param($stmt, "sss", $username, $email, $hashed_password);

            if (mysqli_stmt_execute($stmt)) {
                return [
                    'success' => true,
                    'message' => 'Usuario registrado exitosamente'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al registrar el usuario'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error en el registro: ' . $e->getMessage()
            ];
        }
    }

    // Iniciar sesión
    public function login($username, $password) {
        try {
            $stmt = mysqli_prepare($this->conn, "
                SELECT id, username, password, is_admin 
                FROM users 
                WHERE username = ?
            ");
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($user = mysqli_fetch_assoc($result)) {
                if (password_verify($password, $user['password'])) {
                    // Iniciar sesión
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['is_admin'] = $user['is_admin'];

                    return [
                        'success' => true,
                        'is_admin' => $user['is_admin'],
                        'message' => 'Inicio de sesión exitoso'
                    ];
                }
            }

            return [
                'success' => false,
                'message' => 'Usuario o contraseña incorrectos'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error en el inicio de sesión: ' . $e->getMessage()
            ];
        }
    }

    // Cerrar sesión
    public function logout() {
        session_start();
        session_destroy();
        return [
            'success' => true,
            'message' => 'Sesión cerrada exitosamente'
        ];
    }

    // Verificar si el usuario está autenticado
    public function isAuthenticated() {
        return isset($_SESSION['user_id']);
    }

    // Verificar si el usuario es administrador
    public function isAdmin() {
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
    }

    // Obtener información del usuario actual
    public function getCurrentUser() {
        if (!$this->isAuthenticated()) {
            return null;
        }

        $stmt = mysqli_prepare($this->conn, "
            SELECT id, username, email, is_admin, created_at
            FROM users
            WHERE id = ?
        ");
        mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }

    // Actualizar información del usuario
    public function updateUser($userId, $data) {
        try {
            $updates = [];
            $types = "";
            $values = [];

            if (isset($data['username'])) {
                $updates[] = "username = ?";
                $types .= "s";
                $values[] = $data['username'];
            }

            if (isset($data['email'])) {
                $updates[] = "email = ?";
                $types .= "s";
                $values[] = $data['email'];
            }

            if (isset($data['password'])) {
                $updates[] = "password = ?";
                $types .= "s";
                $values[] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            if (empty($updates)) {
                return [
                    'success' => false,
                    'message' => 'No hay datos para actualizar'
                ];
            }

            $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
            $types .= "i";
            $values[] = $userId;

            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, $types, ...$values);

            if (mysqli_stmt_execute($stmt)) {
                return [
                    'success' => true,
                    'message' => 'Información actualizada exitosamente'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al actualizar la información'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error en la actualización: ' . $e->getMessage()
            ];
        }
    }
}

// Crear instancia global de Auth
$auth = new Auth($conn);
?>
