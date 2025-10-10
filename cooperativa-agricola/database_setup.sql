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

-- Crear tabla de insumos
CREATE TABLE insumos (
    id_insumo INT AUTO_INCREMENT PRIMARY KEY,
    nombre_insumo VARCHAR(100) NOT NULL,
    tipo ENUM('semillas', 'fertilizantes', 'pesticidas', 'herramientas', 'maquinaria') NOT NULL,
    descripcion TEXT,
    cantidad_disponible INT DEFAULT 0,
    cantidad_minima INT DEFAULT 0,
    precio_unitario DECIMAL(8,2) NOT NULL,
    proveedor VARCHAR(100),
    fecha_registro DATE NOT NULL,
    ubicacion_almacen VARCHAR(50),
    estado ENUM('disponible', 'agotado', 'descontinuado') DEFAULT 'disponible',
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

-- Crear tabla de producción
CREATE TABLE produccion (
    id_produccion INT AUTO_INCREMENT PRIMARY KEY,
    id_socio INT NOT NULL,
    cultivo VARCHAR(50) NOT NULL,
    variedad VARCHAR(100),
    cantidad DECIMAL(10,2) NOT NULL,
    unidad ENUM('quintales', 'toneladas', 'libras', 'sacos') DEFAULT 'quintales',
    area_cultivada DECIMAL(8,2),
    fecha_siembra DATE,
    fecha_recoleccion DATE NOT NULL,
    calidad ENUM('premium', 'buena', 'regular', 'baja') DEFAULT 'buena',
    precio_estimado DECIMAL(8,2),
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_socio) REFERENCES socios(id_socio) ON DELETE CASCADE
);

-- Insertar algunos insumos de ejemplo
INSERT IGNORE INTO insumos (nombre_insumo, tipo, descripcion, cantidad_disponible, cantidad_minima, precio_unitario, proveedor, fecha_registro, ubicacion_almacen, estado) VALUES
('Semillas de Café Caturra', 'semillas', 'Semillas de café de alta calidad para siembra', 50, 10, 15000.00, 'AgroSemillas S.A.S', '2024-01-15', 'Almacén A - Estante 1', 'disponible'),
('Fertilizante NPK 15-15-15', 'fertilizantes', 'Fertilizante balanceado para cultivos', 100, 20, 8500.00, 'Fertilizantes del Valle', '2024-02-10', 'Almacén B - Estante 3', 'disponible'),
('Herbicida Glifosato', 'pesticidas', 'Herbicida para control de malezas', 20, 5, 25000.00, 'Agroquímicos del Sur', '2024-01-20', 'Almacén C - Estante 2', 'disponible'),
('Azadón de Mano', 'herramientas', 'Azadón de acero para labranza manual', 15, 3, 45000.00, 'Herramientas Agrícolas Ltda', '2024-03-05', 'Almacén D - Estante 1', 'disponible'),
('Tractor John Deere', 'maquinaria', 'Tractor para labranza y transporte', 2, 1, 45000000.00, 'Maquinaria Agrícola S.A', '2024-02-28', 'Patio de Maquinaria', 'disponible'),
('Semillas de Maíz Híbrido', 'semillas', 'Semillas de maíz de alto rendimiento', 25, 5, 12000.00, 'Semillas del Campo', '2024-03-15', 'Almacén A - Estante 2', 'disponible'),
('Fungicida Cobre', 'pesticidas', 'Fungicida para control de enfermedades', 10, 2, 18000.00, 'Agroquímicos del Sur', '2024-02-20', 'Almacén C - Estante 1', 'disponible'),
('Machete Agrícola', 'herramientas', 'Machete para limpieza de cultivos', 20, 5, 35000.00, 'Herramientas Agrícolas Ltda', '2024-03-10', 'Almacén D - Estante 2', 'disponible');

-- Insertar algunos registros de producción de ejemplo
INSERT IGNORE INTO produccion (id_socio, cultivo, variedad, cantidad, unidad, area_cultivada, fecha_siembra, fecha_recoleccion, calidad, precio_estimado, observaciones) VALUES
(1, 'Café', 'Caturra', 25.50, 'quintales', 2.5, '2024-01-15', '2024-06-20', 'premium', 850000.00, 'Cosecha de excelente calidad, granos grandes y uniformes'),
(2, 'Café', 'Bourbon', 18.75, 'quintales', 1.8, '2024-02-01', '2024-07-15', 'buena', 650000.00, 'Buena producción, algunos granos con defectos menores'),
(3, 'Maíz', 'Híbrido', 45.00, 'quintales', 3.2, '2024-03-10', '2024-08-25', 'regular', 320000.00, 'Cosecha promedio, afectada por lluvias excesivas'),
(1, 'Plátano', 'Dominico', 12.30, 'quintales', 1.5, '2024-01-20', '2024-05-30', 'buena', 180000.00, 'Plátanos de buen tamaño y sabor'),
(4, 'Yuca', 'Común', 8.75, 'quintales', 1.2, '2024-02-15', '2024-07-10', 'regular', 95000.00, 'Producción menor a la esperada'),
(2, 'Café', 'Geisha', 5.25, 'quintales', 0.8, '2024-01-10', '2024-06-05', 'premium', 450000.00, 'Café especial de alta calidad, granos seleccionados'),
(3, 'Frijol', 'Cargamanto', 15.60, 'quintales', 2.0, '2024-03-05', '2024-08-15', 'buena', 280000.00, 'Frijoles de buena calidad, sin plagas'),
(1, 'Tomate', 'Chonto', 22.40, 'quintales', 1.8, '2024-02-20', '2024-06-30', 'regular', 340000.00, 'Tomates de tamaño mediano, algunos con grietas');

-- Comentario: La contraseña por defecto es "password" - cambiar después del primer login