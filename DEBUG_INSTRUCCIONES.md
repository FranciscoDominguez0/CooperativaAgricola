# 🔍 Debug - Reportes en Blanco

## 📋 **Pasos para Debuggear**

### 1. **Verificar Datos en Base de Datos**
```
http://localhost/cooperativa-agricola/debug_datos.php
```
Este archivo te mostrará:
- Qué tablas existen
- Cuántos registros hay en cada tabla
- Ejemplos de datos
- Resultados de consultas específicas

### 2. **Probar Reportes Sin Sesión**
```
http://localhost/cooperativa-agricola/test_reportes_debug.php
```
Este archivo prueba:
- Conexión a la base de datos
- Consultas de KPIs sin verificación de sesión
- Resultados detallados con debug

### 3. **Verificar Logs de Error**
Revisa los logs de PHP para ver errores:
- En XAMPP: `xampp/apache/logs/error.log`
- En WAMP: `wamp/logs/php_error.log`

### 4. **Probar Consultas Directas**
Abre phpMyAdmin y ejecuta estas consultas:

```sql
-- Verificar datos en ventas
SELECT COUNT(*) as total_ventas, SUM(total) as total_ingresos 
FROM ventas WHERE estado = 'pagado';

-- Verificar datos en pagos
SELECT COUNT(*) as total_pagos, SUM(monto) as total_aportes 
FROM pagos WHERE estado = 'confirmado';

-- Verificar datos en socios
SELECT COUNT(*) as total_socios 
FROM socios WHERE estado = 'activo';

-- Verificar datos en insumos
SELECT COUNT(*) as total_insumos, SUM(cantidad_disponible * precio_unitario) as valor_inventario 
FROM insumos WHERE estado = 'disponible';
```

## 🎯 **Posibles Problemas**

1. **Datos en formato incorrecto**: Las fechas o estados no coinciden
2. **Problema de conexión**: La base de datos no se conecta
3. **Problema de sesión**: El sistema requiere sesión válida
4. **Problema de consultas**: Las consultas SQL tienen errores

## 🔧 **Soluciones**

### Si no hay datos:
```sql
-- Insertar datos de prueba
INSERT INTO socios (nombre, cedula, telefono, email, fecha_ingreso, estado) 
VALUES ('Juan Pérez', '12345678', '555-1234', 'juan@test.com', CURDATE(), 'activo');

INSERT INTO ventas (id_socio, producto, cantidad, precio_unitario, cliente, fecha_venta, estado) 
VALUES (1, 'Maíz', 50, 30.00, 'Cliente Test', CURDATE(), 'pagado');

INSERT INTO pagos (id_socio, monto, tipo, estado, fecha_pago) 
VALUES (1, 500.00, 'aporte_mensual', 'confirmado', CURDATE());
```

### Si hay problema de sesión:
El archivo `test_reportes_debug.php` no requiere sesión.

### Si hay problema de consultas:
Revisa los logs de error para ver qué consulta está fallando.

## 📞 **Siguiente Paso**

1. Ejecuta `debug_datos.php` y comparte el resultado
2. Ejecuta `test_reportes_debug.php` y comparte el resultado
3. Revisa los logs de error de PHP

Con esa información podremos identificar exactamente qué está pasando.
