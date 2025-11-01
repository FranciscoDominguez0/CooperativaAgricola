# Instrucciones para Configurar la Base de Datos de Pagos

## 🔧 Pasos para Configurar la Base de Datos

### 1. **Crear la Base de Datos**
```sql
CREATE DATABASE pagos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. **Ejecutar el Script de Creación**
Ejecuta el archivo `create_pagos_table.sql` en tu base de datos MySQL:

```bash
mysql -u root -p pagos < create_pagos_table.sql
```

O desde phpMyAdmin:
1. Selecciona la base de datos `pagos`
2. Ve a la pestaña "SQL"
3. Copia y pega el contenido de `create_pagos_table.sql`
4. Ejecuta el script

### 3. **Verificar la Conexión**
Abre en tu navegador: `http://localhost/tu-proyecto/debug_pagos.php`

Deberías ver una respuesta JSON con los datos de la base de datos.

### 4. **Verificar la Configuración**
Si hay problemas, verifica:

#### **En `php/conexion.php`:**
```php
$host = 'localhost';        // Tu servidor MySQL
$dbname = 'pagos';          // Nombre de tu base de datos
$username = 'root';         // Tu usuario MySQL
$password = '';             // Tu contraseña MySQL
```

#### **Configuración Alternativa:**
Si tu base de datos tiene otro nombre o configuración, modifica `php/conexion.php`:

```php
$host = 'localhost';        // Cambia si tu servidor es diferente
$dbname = 'tu_base_datos';  // Cambia por el nombre real
$username = 'tu_usuario';   // Cambia por tu usuario
$password = 'tu_password';  // Cambia por tu contraseña
```

### 5. **Probar el Módulo de Pagos**
1. Abre `dashboard.html` en tu navegador
2. Haz clic en "Pagos" en el menú lateral
3. Deberías ver las estadísticas y la tabla con datos

## 🚨 Solución de Problemas

### **Error: "Base de datos no existe"**
- Ejecuta: `CREATE DATABASE pagos;`
- Luego ejecuta el script `create_pagos_table.sql`

### **Error: "Tabla no existe"**
- Ejecuta el script `create_pagos_table.sql` en tu base de datos

### **Error: "Acceso denegado"**
- Verifica el usuario y contraseña en `php/conexion.php`
- Asegúrate de que el usuario tenga permisos en la base de datos

### **Error: "Conexión rechazada"**
- Verifica que MySQL esté ejecutándose
- Verifica el puerto (por defecto 3306)
- Verifica la configuración de `$host`

## 📋 Verificación Final

Después de seguir estos pasos, deberías poder:

1. ✅ Ver las estadísticas de pagos en el dashboard
2. ✅ Ver la tabla con datos de pagos
3. ✅ Crear, editar y eliminar pagos
4. ✅ Ver los datos actualizarse en tiempo real

## 🔍 Archivos de Prueba

- `debug_pagos.php` - Para verificar la conexión
- `test_pagos_connection.php` - Para probar la conexión
- `create_pagos_table.sql` - Script para crear las tablas

Si sigues teniendo problemas, revisa los archivos de prueba para identificar el error específico.


