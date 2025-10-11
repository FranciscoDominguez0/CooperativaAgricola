-- Script para crear la base de datos y tablas
-- Ejecuta este script en tu servidor MySQL

-- 1. Crear la base de datos
CREATE DATABASE IF NOT EXISTS pagos 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 2. Usar la base de datos
USE pagos;

-- 3. Crear tabla de socios
CREATE TABLE IF NOT EXISTS socios (
    id_socio INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    cedula VARCHAR(20) UNIQUE NOT NULL,
    telefono VARCHAR(15),
    direccion TEXT,
    email VARCHAR(100),
    fecha_ingreso DATE,
    estado ENUM('activo', 'inactivo', 'suspendido') DEFAULT 'activo',
    aportes_totales DECIMAL(10,2) DEFAULT 0.00,
    deudas_pendientes DECIMAL(10,2) DEFAULT 0.00,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 4. Crear tabla de ventas
CREATE TABLE IF NOT EXISTS ventas (
    id_venta INT AUTO_INCREMENT PRIMARY KEY,
    id_socio INT NOT NULL,
    producto VARCHAR(50) NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL,
    precio_unitario DECIMAL(8,2) NOT NULL,
    total DECIMAL(10,2) GENERATED ALWAYS AS (cantidad * precio_unitario) STORED,
    cliente VARCHAR(100) NOT NULL,
    direccion_entrega TEXT,
    fecha_venta DATE NOT NULL,
    fecha_entrega DATE,
    estado ENUM('pendiente', 'entregado', 'pagado', 'cancelado') DEFAULT 'pendiente',
    metodo_pago ENUM('efectivo', 'transferencia', 'cheque', 'credito') DEFAULT 'efectivo',
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_socio) REFERENCES socios(id_socio) ON DELETE CASCADE
);

-- 5. Crear tabla de pagos
CREATE TABLE IF NOT EXISTS pagos (
    id_pago INT AUTO_INCREMENT PRIMARY KEY,
    id_socio INT NOT NULL,
    id_venta INT NULL,
    monto DECIMAL(10,2) NOT NULL,
    tipo ENUM('aporte_mensual', 'aporte_extraordinario', 'pago_venta', 'prestamo', 'devolucion') NOT NULL,
    descripcion TEXT,
    estado ENUM('pendiente', 'confirmado', 'rechazado') DEFAULT 'pendiente',
    fecha_pago DATE NOT NULL,
    metodo_pago ENUM('efectivo', 'transferencia', 'cheque', 'deposito') DEFAULT 'efectivo',
    numero_comprobante VARCHAR(50),
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_socio) REFERENCES socios(id_socio) ON DELETE CASCADE,
    FOREIGN KEY (id_venta) REFERENCES ventas(id_venta) ON DELETE SET NULL
);

-- 6. Insertar datos de ejemplo
INSERT IGNORE INTO socios (id_socio, nombre, cedula, telefono, direccion, email, fecha_ingreso, estado) VALUES
(1, 'Juan Pérez', '12345678', '3001234567', 'Calle 1 #2-3, La Pintada', 'juan@email.com', '2024-01-15', 'activo'),
(2, 'María García', '87654321', '3007654321', 'Carrera 5 #10-20, La Pintada', 'maria@email.com', '2024-02-10', 'activo'),
(3, 'Carlos López', '11223344', '3001122334', 'Calle 8 #15-30, La Pintada', 'carlos@email.com', '2024-03-05', 'activo');

INSERT IGNORE INTO ventas (id_venta, id_socio, producto, cantidad, precio_unitario, cliente, fecha_venta, estado, metodo_pago) VALUES
(1, 1, 'Café Premium', 50.00, 25000.00, 'Café La Tostadora', '2024-01-20', 'pagado', 'transferencia'),
(2, 2, 'Tomate Chonto', 100.00, 15000.00, 'Verduras Frescas', '2024-02-15', 'entregado', 'efectivo'),
(3, 3, 'Yuca Común', 75.00, 8000.00, 'Restaurante El Fogón', '2024-03-10', 'pendiente', 'cheque');

INSERT IGNORE INTO pagos (id_socio, id_venta, monto, tipo, descripcion, estado, fecha_pago, metodo_pago, numero_comprobante, observaciones) VALUES
(1, 1, 50000.00, 'aporte_mensual', 'Aporte mensual enero 2024', 'confirmado', '2024-01-15', 'transferencia', 'TRF001', 'Pago puntual'),
(1, NULL, 100000.00, 'aporte_extraordinario', 'Aporte para mejoras de infraestructura', 'confirmado', '2024-02-10', 'efectivo', 'EFE001', 'Contribución voluntaria'),
(2, 2, 75000.00, 'pago_venta', 'Pago por venta de tomates', 'confirmado', '2024-02-15', 'transferencia', 'TRF002', 'Pago completo de venta'),
(2, NULL, 45000.00, 'aporte_mensual', 'Aporte mensual febrero 2024', 'pendiente', '2024-02-20', 'cheque', 'CHQ001', 'Pendiente de cobro'),
(3, NULL, 60000.00, 'aporte_mensual', 'Aporte mensual marzo 2024', 'confirmado', '2024-03-15', 'deposito', 'DEP001', 'Depósito bancario'),
(1, NULL, 25000.00, 'prestamo', 'Préstamo para compra de semillas', 'confirmado', '2024-03-20', 'transferencia', 'TRF003', 'Préstamo aprobado'),
(3, NULL, 30000.00, 'devolucion', 'Devolución de aporte excedente', 'confirmado', '2024-03-25', 'transferencia', 'TRF004', 'Devolución procesada');

-- 7. Verificar que todo se creó correctamente
SELECT 'Base de datos y tablas creadas exitosamente' as mensaje;
SELECT COUNT(*) as total_socios FROM socios;
SELECT COUNT(*) as total_ventas FROM ventas;
SELECT COUNT(*) as total_pagos FROM pagos;
