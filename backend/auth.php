<?php
require_once 'config.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = conectarDB();
    }
    
    // Función para registrar nuevo usuario
    public function registrar($nombre, $correo, $contraseña, $rol = 'productor') {
        try {
            // Verificar si el correo ya existe
            $stmt = $this->db->prepare("SELECT id_usuario FROM usuarios WHERE correo = ?");
            $stmt->execute([$correo]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'El correo electrónico ya está registrado'];
            }
            
            // Validar datos
            if (empty($nombre) || empty($correo) || empty($contraseña)) {
                return ['success' => false, 'message' => 'Todos los campos son obligatorios'];
            }
            
            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'El formato del correo electrónico no es válido'];
            }
            
            if (strlen($contraseña) < 6) {
                return ['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres'];
            }
            
            // Encriptar contraseña
            $contraseña_hash = password_hash($contraseña, PASSWORD_DEFAULT);
            
            // Insertar usuario
            $stmt = $this->db->prepare("
                INSERT INTO usuarios (nombre, correo, contraseña, rol, estado, fecha_registro) 
                VALUES (?, ?, ?, ?, 'activo', CURDATE())
            ");
            
            $result = $stmt->execute([$nombre, $correo, $contraseña_hash, $rol]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Usuario registrado exitosamente'];
            } else {
                return ['success' => false, 'message' => 'Error al registrar usuario'];
            }
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()];
        }
    }
    
    // Función para iniciar sesión
    public function login($correo, $contraseña) {
        try {
            // Verificar datos
            if (empty($correo) || empty($contraseña)) {
                return ['success' => false, 'message' => 'Correo y contraseña son obligatorios'];
            }
            
            // Buscar usuario
            $stmt = $this->db->prepare("
                SELECT id_usuario, nombre, correo, contraseña, rol, estado 
                FROM usuarios 
                WHERE correo = ?
            ");
            $stmt->execute([$correo]);
            $usuario = $stmt->fetch();
            
            if (!$usuario) {
                return ['success' => false, 'message' => 'Credenciales incorrectas'];
            }
            
            // Verificar estado del usuario
            if ($usuario['estado'] !== 'activo') {
                return ['success' => false, 'message' => 'Cuenta inactiva. Contacte al administrador'];
            }
            
            // Verificar contraseña
            if (password_verify($contraseña, $usuario['contraseña'])) {
                // Actualizar último acceso
                $this->actualizarUltimoAcceso($usuario['id_usuario']);
                
                // Crear sesión
                $_SESSION['user_id'] = $usuario['id_usuario'];
                $_SESSION['user_name'] = $usuario['nombre'];
                $_SESSION['user_email'] = $usuario['correo'];
                $_SESSION['user_role'] = $usuario['rol'];
                $_SESSION['login_time'] = time();
                
                return [
                    'success' => true, 
                    'message' => 'Bienvenido/a ' . $usuario['nombre'],
                    'user' => [
                        'id' => $usuario['id_usuario'],
                        'nombre' => $usuario['nombre'],
                        'correo' => $usuario['correo'],
                        'rol' => $usuario['rol']
                    ]
                ];
            } else {
                return ['success' => false, 'message' => 'Credenciales incorrectas'];
            }
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()];
        }
    }
    
    // Función para cerrar sesión
    public function logout() {
        session_destroy();
        return ['success' => true, 'message' => 'Sesión cerrada exitosamente'];
    }
    
    // Función para actualizar último acceso
    private function actualizarUltimoAcceso($user_id) {
        try {
            $stmt = $this->db->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id_usuario = ?");
            $stmt->execute([$user_id]);
        } catch (PDOException $e) {
            // Log error but don't fail login
            error_log("Error actualizando último acceso: " . $e->getMessage());
        }
    }
    
    // Función para obtener información del usuario actual
    public function getUsuarioActual() {
        if (!verificarLogin()) {
            return null;
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT id_usuario, nombre, correo, rol, estado, fecha_registro, ultimo_acceso
                FROM usuarios 
                WHERE id_usuario = ?
            ");
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }
}
?>