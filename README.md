# Sistema de Login y Registro - Cooperativa Agrícola CREATE TABLE usuarios (
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
);intada

Sistema web de autenticación para la Cooperativa Agrícola La Pintada con diseño temático de agricultura.

## 🌱 Características

- **Login y Registro de Usuarios**: Sistema completo de autenticación
- **Diseño Agrícola**: Interfaz temática con colores y elementos de agricultura
- **Seguridad**: Contraseñas encriptadas con PHP password_hash()
- **Responsive**: Adaptable a dispositivos móviles
- **Validaciones**: Del lado del cliente y servidor
- **Dashboard**: Panel principal después del login
- **Roles de Usuario**: Admin, Productor, Cliente, Contador

## 📁 Estructura del Proyecto

```
cooperativa-agricola/
├── css/
│   ├── styles.css          # Estilos para login/registro
│   └── dashboard.css       # Estilos para dashboard
├── js/
│   ├── login.js           # JavaScript para login
│   ├── registro.js        # JavaScript para registro
│   └── dashboard.js       # JavaScript para dashboard
├── php/
│   ├── config.php         # Configuración de BD y funciones
│   ├── auth.php           # Clase de autenticación
│   ├── procesar_login.php # Procesamiento de login
│   ├── procesar_registro.php # Procesamiento de registro
│   └── logout.php         # Cerrar sesión
├── images/               # Imágenes (opcional)
├── index.html            # Página principal de redirección
├── login.html           # Página de login
├── registro.html        # Página de registro
├── dashboard.html       # Panel principal
└── database_setup.sql   # Script de base de datos
```

## 🚀 Instalación

### 1. Requisitos Previos

- **Servidor Web**: Apache/Nginx con PHP 7.4 o superior
- **Base de Datos**: MySQL 5.7 o MariaDB 10.2+
- **PHP**: Versión 7.4+ con extensiones:
  - PDO
  - PDO_MySQL
  - mbstring

### 2. Configuración de Base de Datos

1. **Abrir phpMyAdmin o cliente MySQL**
2. **Ejecutar el script de base de datos**:
   ```sql
   -- Copiar y ejecutar el contenido de database_setup.sql
   ```
3. **Verificar la creación**:
   - Base de datos: `cooperativa_agricola`
   - Tabla: `usuarios`
   - Usuario admin creado con email: `admin@cooperativa.com` y contraseña: `password`

### 3. Configuración del Servidor

1. **Copiar archivos** al directorio web del servidor:
   ```bash
   # Ejemplo para XAMPP
   cp -r cooperativa-agricola/ C:/xampp/htdocs/
   
   # Ejemplo para servidor Linux
   cp -r cooperativa-agricola/ /var/www/html/
   ```

2. **Configurar permisos** (Linux):
   ```bash
   chmod 755 /var/www/html/cooperativa-agricola/
   chmod 644 /var/www/html/cooperativa-agricola/php/*.php
   ```

### 4. Configuración de la Aplicación

1. **Editar configuración de base de datos** en `php/config.php`:
   ```php
   define('DB_HOST', 'localhost');     # Tu host de MySQL
   define('DB_USER', 'root');          # Tu usuario de MySQL
   define('DB_PASS', '12345678');      # Tu contraseña de MySQL
   define('DB_NAME', 'cooperativa_agricola');
   define('DB_PORT', 3306);            # Puerto de MySQL
   ```

2. **Verificar URL base** en `php/config.php`:
   ```php
   define('BASE_URL', 'http://localhost/cooperativa-agricola/');
   ```

## 🔧 Configuración con XAMPP (Windows)

### Pasos Detallados:

1. **Descargar e instalar XAMPP**
2. **Iniciar Apache y MySQL** desde el panel de control de XAMPP
3. **Copiar proyecto**:
   ```
   Copiar la carpeta 'cooperativa-agricola' a:
   C:\xampp\htdocs\cooperativa-agricola\
   ```
4. **Configurar base de datos**:
   - Abrir http://localhost/phpmyadmin
   - Crear nueva base de datos: `cooperativa_agricola`
   - Importar o ejecutar el archivo `database_setup.sql`
5. **Acceder a la aplicación**:
   - Inicio: http://localhost/cooperativa-agricola/
   - Login: http://localhost/cooperativa-agricola/login.html
   - Registro: http://localhost/cooperativa-agricola/registro.html

## 👥 Usuarios por Defecto

### Administrador Predeterminado:
- **Email**: admin@cooperativa.com
- **Contraseña**: password
- **Rol**: admin

> ⚠️ **Importante**: Cambiar la contraseña del administrador después del primer login

## 🔐 Características de Seguridad

- **Encriptación**: Contraseñas hasheadas con `password_hash()`
- **Validación**: Input sanitization y validación
- **Sesiones**: Manejo seguro de sesiones PHP
- **SQL Injection**: Protección con PDO prepared statements
- **XSS**: Escapado de datos de salida

## 📱 Características de la Interfaz

### Diseño Temático Agrícola:
- **Colores**: Verde (agricultura), amarillo (trigo), marrón (tierra)
- **Iconos**: Emojis temáticos (🌱, 🚜, 🌾, etc.)
- **Gradientes**: Efectos visuales naturales
- **Responsive**: Adaptable a móviles y tablets

### Funcionalidades JavaScript:
- **Validación en tiempo real**
- **Indicador de fortaleza de contraseña**
- **Mensajes de error/éxito animados**
- **Loading states**
- **Navegación con teclado**

## 🌟 Funcionalidades del Dashboard

- **Panel de Bienvenida**: Saludo personalizado según la hora
- **Servicios**: Tarjetas de servicios futuros
- **Noticias**: Sección de anuncios
- **Información de Contacto**: Datos de soporte
- **Logout Seguro**: Modal de confirmación

## 🔄 Flujo de Uso

1. **Nuevo Usuario**:
   - Acceder a `registro.html`
   - Completar formulario con validaciones
   - Recibir confirmación de registro
   - Redirigir a login

2. **Usuario Existente**:
   - Acceder a `login.html`
   - Ingresar credenciales
   - Validación en servidor
   - Redirigir a dashboard

3. **Dashboard**:
   - Ver información personalizada
   - Acceder a servicios (próximamente)
   - Cerrar sesión segura

## 🛠️ Personalización

### Cambiar Colores:
Editar variables CSS en `css/styles.css` y `css/dashboard.css`:
```css
:root {
    --verde-primario: #2d5016;
    --verde-secundario: #4a7c59;
    --amarillo-trigo: #ffc107;
    /* ... más colores */
}
```

### Agregar Nuevos Roles:
1. Modificar tabla en MySQL:
   ```sql
   ALTER TABLE usuarios MODIFY rol ENUM('admin', 'productor', 'cliente', 'contador', 'nuevo_rol');
   ```
2. Actualizar opciones en `registro.html`
3. Actualizar iconos en `dashboard.js`

## 📞 Soporte

- **Email**: soporte@cooperativa.com
- **Teléfono**: (123) 456-7890
- **Horario**: Lunes a Viernes, 8:00 AM - 6:00 PM

## 📝 Licencia

© 2024 Cooperativa Agrícola La Pintada. Todos los derechos reservados.

---

*"Cultivando juntos un futuro próspero" 🌱*