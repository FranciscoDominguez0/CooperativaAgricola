-- Script para crear la base de datos y tablas
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

-- Crear tabla de socios
CREATE TABLE socios (
    id_socio INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    cedula VARCHAR(20) UNIQUE NOT NULL,
    telefono VARCHAR(15),
    direccion TEXT,
    email VARCHAR(100),
    fecha_ingreso DATE NOT NULL,
    estado ENUM('activo', 'inactivo', 'suspendido') DEFAULT 'activo',
    aportes_totales DECIMAL(10,2) DEFAULT 0.00,
    deudas_pendientes DECIMAL(10,2) DEFAULT 0.00,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insertar usuario administrador por defecto
INSERT IGNORE INTO usuarios (nombre, correo, contraseña, rol, estado, fecha_registro) 
VALUES ('Administrador', 'admin@cooperativa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'activo', CURDATE());

-- Insertar algunos socios de ejemplo
INSERT IGNORE INTO socios (nombre, cedula, telefono, direccion, email, fecha_ingreso, estado, aportes_totales, deudas_pendientes, observaciones) VALUES
('Juan Pérez García', '12345678', '3001234567', 'Calle 15 #25-30, La Pintada', 'juan.perez@email.com', '2024-01-15', 'activo', 150000.00, 0.00, 'Socio fundador, muy comprometido'),
('María Rodríguez López', '87654321', '3007654321', 'Carrera 8 #12-45, La Pintada', 'maria.rodriguez@email.com', '2024-02-20', 'activo', 200000.00, 50000.00, 'Excelente productora de café'),
('Carlos Mendoza Silva', '11223344', '3001122334', 'Vereda El Roble, La Pintada', 'carlos.mendoza@email.com', '2024-03-10', 'activo', 75000.00, 0.00, 'Especialista en cultivos orgánicos'),
('Ana Gutiérrez Torres', '55667788', '3005566778', 'Finca La Esperanza, La Pintada', 'ana.gutierrez@email.com', '2024-01-30', 'inactivo', 100000.00, 25000.00, 'Temporalmente inactivo por motivos familiares'),
('Roberto Castro Jiménez', '99887766', '3009988776', 'Calle 20 #5-10, La Pintada', 'roberto.castro@email.com', '2024-04-05', 'activo', 300000.00, 0.00, 'Mayor aportante del año');

-- Comentario: La contraseña por defecto es "password" - cambiar después del primer login