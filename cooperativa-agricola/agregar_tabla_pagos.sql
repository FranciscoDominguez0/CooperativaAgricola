-- Script para agregar la tabla pagos a la base de datos cooperativa_agricola existente
-- Ejecuta este script en phpMyAdmin

USE cooperativa_agricola;

-- Crear tabla de pagos
CREATE TABLE IF NOT EXISTS pagos (
    id_pago INT AUTO_INCREMENT PRIMARY KEY,
    id_socio INT NOT NULL,
    id_venta INT NULL, -- Solo si es pago de una venta específica
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

-- Insertar datos de ejemplo para pagos
INSERT IGNORE INTO pagos (id_socio, id_venta, monto, tipo, descripcion, estado, fecha_pago, metodo_pago, numero_comprobante, observaciones) VALUES
(1, 1, 50000.00, 'aporte_mensual', 'Aporte mensual enero 2024', 'confirmado', '2024-01-15', 'transferencia', 'TRF001', 'Pago puntual'),
(1, NULL, 100000.00, 'aporte_extraordinario', 'Aporte para mejoras de infraestructura', 'confirmado', '2024-02-10', 'efectivo', 'EFE001', 'Contribución voluntaria'),
(2, 2, 75000.00, 'pago_venta', 'Pago por venta de tomates', 'confirmado', '2024-02-15', 'transferencia', 'TRF002', 'Pago completo de venta'),
(2, NULL, 45000.00, 'aporte_mensual', 'Aporte mensual febrero 2024', 'pendiente', '2024-02-20', 'cheque', 'CHQ001', 'Pendiente de cobro'),
(3, NULL, 60000.00, 'aporte_mensual', 'Aporte mensual marzo 2024', 'confirmado', '2024-03-15', 'deposito', 'DEP001', 'Depósito bancario'),
(1, NULL, 25000.00, 'prestamo', 'Préstamo para compra de semillas', 'confirmado', '2024-03-20', 'transferencia', 'TRF003', 'Préstamo aprobado'),
(3, NULL, 30000.00, 'devolucion', 'Devolución de aporte excedente', 'confirmado', '2024-03-25', 'transferencia', 'TRF004', 'Devolución procesada');

-- Verificar que la tabla se creó correctamente
SELECT 'Tabla pagos creada exitosamente' as mensaje;
SELECT COUNT(*) as total_pagos FROM pagos;
