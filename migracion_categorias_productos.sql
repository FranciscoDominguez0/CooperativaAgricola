-- =====================================================
-- MIGRACIÓN: AGREGAR CATEGORÍAS DE PRODUCTOS A VENTAS
-- Cooperativa Agrícola La Pintada
-- =====================================================
-- Este script solo modifica las tablas relacionadas con
-- categorías de productos y ventas. No toca otras tablas.

USE cooperativa_agricola;

-- =====================================================
-- PASO 1: Eliminar tabla ventas (si existe)
-- =====================================================
-- Primero eliminamos las foreign keys que referencian a ventas
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS ventas;

-- =====================================================
-- PASO 2: Crear tabla de categorías de productos
-- =====================================================
CREATE TABLE categorias_productos (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre_categoria VARCHAR(50) NOT NULL UNIQUE,
    descripcion TEXT,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PASO 3: Insertar categorías predefinidas
-- =====================================================
INSERT INTO categorias_productos (nombre_categoria, descripcion) VALUES
('Granos', 'Cereales y granos básicos (Maíz, Arroz, Trigo)'),
('Leguminosas', 'Legumbres y frijoles (Frijoles, Lentejas, Garbanzos)'),
('Café', 'Café y productos relacionados'),
('Tubérculos', 'Raíces y tubérculos (Yuca, Papa, Ñame)'),
('Hortalizas', 'Verduras y hortalizas frescas'),
('Frutas', 'Frutas frescas y procesadas'),
('Frutos Secos', 'Nueces, almendras y frutos secos'),
('Semillas', 'Semillas para siembra o consumo'),
('Otros', 'Otros productos agrícolas no categorizados');

-- =====================================================
-- PASO 4: Recrear tabla ventas con id_categoria
-- =====================================================
CREATE TABLE ventas (
    id_venta INT AUTO_INCREMENT PRIMARY KEY,
    id_socio INT NOT NULL,
    id_categoria INT NOT NULL,
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
    FOREIGN KEY (id_socio) REFERENCES socios(id_socio) ON DELETE CASCADE,
    FOREIGN KEY (id_categoria) REFERENCES categorias_productos(id_categoria) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PASO 5: Restaurar foreign key checks
-- =====================================================
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- PASO 6: Insertar datos de ejemplo (opcional)
-- =====================================================
-- Nota: Ajusta los id_socio según tus datos reales
-- Las categorías se asignan automáticamente según el tipo de producto

-- Ejemplo de inserción de ventas con categorías
-- INSERT INTO ventas (id_socio, id_categoria, producto, cantidad, precio_unitario, cliente, fecha_venta, estado) VALUES
-- (1, 1, 'Maíz', 50.00, 45.00, 'Molinos de Panamá', '2024-06-20', 'pagado'),
-- (2, 1, 'Arroz', 30.00, 55.00, 'Supermercados Rey', '2024-06-18', 'pendiente'),
-- (3, 3, 'Café', 20.00, 180.00, 'Café Durán', '2024-06-15', 'pagado'),
-- (1, 2, 'Frijoles', 25.00, 85.00, 'Mercado de Mariscos', '2024-06-10', 'pagado'),
-- (4, 4, 'Yuca', 40.00, 25.00, 'Distribuidora Central', '2024-06-05', 'entregado');

-- =====================================================
-- ÍNDICES PARA OPTIMIZACIÓN
-- =====================================================
CREATE INDEX idx_ventas_categoria ON ventas(id_categoria);
CREATE INDEX idx_ventas_fecha ON ventas(fecha_venta);
CREATE INDEX idx_ventas_estado ON ventas(estado);
CREATE INDEX idx_categorias_estado ON categorias_productos(estado);

-- =====================================================
-- CONSULTAS ÚTILES PARA VERIFICAR
-- =====================================================
-- Ver todas las categorías
-- SELECT * FROM categorias_productos WHERE estado = 'activo';

-- Ver ventas con categorías
-- SELECT v.*, c.nombre_categoria 
-- FROM ventas v 
-- JOIN categorias_productos c ON v.id_categoria = c.id_categoria;

-- Ver ventas agrupadas por categoría
-- SELECT c.nombre_categoria, COUNT(*) as total_ventas, SUM(v.total) as total_ingresos
-- FROM ventas v
-- JOIN categorias_productos c ON v.id_categoria = c.id_categoria
-- WHERE v.estado IN ('pagado', 'entregado')
-- GROUP BY c.nombre_categoria;
