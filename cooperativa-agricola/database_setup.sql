-- Script para crear la base de datos y tabla de usuarios
-- Cooperativa Agrícola La Pintada

-- Crear la base de datos (si no existe)
CREATE DATABASE IF NOT EXISTS cooperativa_agricola 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE cooperativa_agricola;

-- Crear tabla de usuarios según especificación
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(100) UNIQUE NOT NULL,
    contraseña VARCHAR(255) NOT NULL, -- Hash MD5 o SHA256
    rol ENUM('admin', 'productor', 'cliente', 'contador') DEFAULT 'productor',
    estado ENUM('activo', 'inactivo', 'suspendido') DEFAULT 'activo',
    fecha_registro DATE NOT NULL,
    ultimo_acceso DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insertar usuario administrador por defecto
INSERT IGNORE INTO usuarios (nombre, correo, contraseña, rol, estado, fecha_registro) 
VALUES ('Administrador', 'admin@cooperativa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'activo', CURDATE());

-- Comentario: La contraseña por defecto es "password" - cambiar después del primer login