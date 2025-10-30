-- Crear tabla de usuarios para el sistema
-- Cooperativa Agrícola La Pintada

-- Tabla principal de usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    telefono VARCHAR(20),
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    rol ENUM('administrador', 'productor', 'cliente', 'contador') NOT NULL DEFAULT 'cliente',
    estado ENUM('activo', 'inactivo', 'suspendido') NOT NULL DEFAULT 'activo',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso TIMESTAMP NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    notas TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de permisos específicos
CREATE TABLE IF NOT EXISTS permisos_usuarios (
    id_permiso INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    modulo VARCHAR(50) NOT NULL,
    permiso VARCHAR(50) NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
);

-- Tabla de sesiones de usuarios
CREATE TABLE IF NOT EXISTS sesiones_usuarios (
    id_sesion INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    token_sesion VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    fecha_inicio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_ultima_actividad TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    activa BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
);

-- Insertar usuario administrador por defecto
INSERT INTO usuarios (nombre, apellido, email, username, password, rol, estado) 
VALUES (
    'Administrador', 
    'Sistema', 
    'admin@cooperativa.com', 
    'admin', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: password
    'administrador', 
    'activo'
);

-- Insertar algunos usuarios de ejemplo
INSERT INTO usuarios (nombre, apellido, email, username, password, rol, estado) 
VALUES 
('Juan', 'Pérez', 'juan.perez@email.com', 'jperez', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'productor', 'activo'),
('María', 'González', 'maria.gonzalez@email.com', 'mgonzalez', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'productor', 'activo'),
('Carlos', 'López', 'carlos.lopez@email.com', 'clopez', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cliente', 'activo'),
('Ana', 'Martínez', 'ana.martinez@email.com', 'amartinez', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'contador', 'activo');

-- Insertar permisos por defecto
INSERT INTO permisos_usuarios (id_usuario, modulo, permiso) 
SELECT u.id_usuario, 'usuarios', 'gestion_completa' 
FROM usuarios u WHERE u.rol = 'administrador';

INSERT INTO permisos_usuarios (id_usuario, modulo, permiso) 
SELECT u.id_usuario, 'produccion', 'ver_propia' 
FROM usuarios u WHERE u.rol = 'productor';

INSERT INTO permisos_usuarios (id_usuario, modulo, permiso) 
SELECT u.id_usuario, 'ventas', 'ver_catalogo' 
FROM usuarios u WHERE u.rol = 'cliente';

INSERT INTO permisos_usuarios (id_usuario, modulo, permiso) 
SELECT u.id_usuario, 'reportes', 'ver_financieros' 
FROM usuarios u WHERE u.rol = 'contador';

-- Crear índices para optimizar consultas
CREATE INDEX idx_usuarios_rol ON usuarios(rol);
CREATE INDEX idx_usuarios_estado ON usuarios(estado);
CREATE INDEX idx_usuarios_email ON usuarios(email);
CREATE INDEX idx_usuarios_username ON usuarios(username);
CREATE INDEX idx_permisos_usuario ON permisos_usuarios(id_usuario);
CREATE INDEX idx_sesiones_usuario ON sesiones_usuarios(id_usuario);
CREATE INDEX idx_sesiones_activa ON sesiones_usuarios(activa);

-- Comentarios sobre la estructura
-- La tabla usuarios maneja la información básica de cada usuario
-- La tabla permisos_usuarios permite permisos granulares por módulo
-- La tabla sesiones_usuarios rastrea las sesiones activas
-- Los roles predefinidos son: administrador, productor, cliente, contador
