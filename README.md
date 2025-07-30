# Sistema de Gestión de Contratos Empresarial

Un sistema completo y profesional para la gestión de contratos de clientes y proveedores, desarrollado en PHP 8.0 con MySQL, Bootstrap 5 y JavaScript.

## 🚀 Características Principales

### ✨ Funcionalidades Core
- **Dashboard Ejecutivo** con métricas en tiempo real y gráficos interactivos
- **Gestión Completa de Contratos** (clientes y proveedores)
- **Sistema de Roles y Permisos** granular (Super Admin, Admin, Manager, Usuario)
- **Categorías Personalizables** para organizar contratos
- **Alertas Automáticas** de vencimiento con notificaciones por email
- **Sistema de Notificaciones** integrado
- **Reportes Avanzados** con exportación PDF/Excel
- **Auditoría Completa** de actividades

### 🎨 Diseño y UX
- **Diseño Responsive** mobile-first
- **Bootstrap 5.3** con tema personalizado
- **Sidebar Colapsible** con navegación intuitiva
- **Gráficos Interactivos** con Chart.js
- **DataTables** para manejo eficiente de datos
- **SweetAlert2** para alertas elegantes

### 🔐 Seguridad
- **Autenticación Segura** con hash de contraseñas
- **Control de Sesiones** robusto
- **Prevención SQL Injection** con PDO
- **Validación de Datos** en frontend y backend
- **Logs de Auditoría** completos

## 📋 Requisitos del Sistema

### Servidor Web
- **Ubuntu Server 20.04+** (recomendado)
- **Apache 2.4+** con mod_rewrite habilitado
- **PHP 8.0+** con extensiones:
  - `php-mysql`
  - `php-pdo`
  - `php-json`
  - `php-mbstring`
  - `php-curl`
  - `php-gd`
  - `php-zip`

### Base de Datos
- **MySQL 8.0+** o **MariaDB 10.5+**

### Navegadores Soportados
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## 🛠️ Instalación en Ubuntu

### 1. Actualizar el Sistema
```bash
sudo apt update && sudo apt upgrade -y
```

### 2. Instalar Apache, PHP y MySQL
```bash
# Instalar Apache
sudo apt install apache2 -y

# Instalar PHP 8.0 y extensiones necesarias
sudo apt install php8.1 php8.1-mysql php8.1-pdo php8.1-json php8.1-mbstring php8.1-curl php8.1-gd php8.1-zip libapache2-mod-php8.1 -y
# Instalar MySQL
sudo apt install mysql-server -y

# Habilitar módulos de Apache
sudo a2enmod rewrite
sudo a2enmod headers
```

### 3. Configurar MySQL
```bash
# Configuración segura de MySQL
sudo mysql_secure_installation

# Crear base de datos y usuario
sudo mysql -u root -p
```

```sql
CREATE DATABASE contract_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'contract_user'@'localhost' IDENTIFIED BY 'secure_password_here';
GRANT ALL PRIVILEGES ON contract_management.* TO 'contract_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 4. Descargar e Instalar el Sistema
```bash
# Ir al directorio web
cd /var/www/html

# Clonar o copiar los archivos del sistema
sudo cp -r /path/to/contract-system/* .

# Configurar permisos
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html
sudo chmod -R 777 /var/www/html/uploads
sudo chmod -R 777 /var/www/html/logs
```

### 5. Configurar Apache
```bash
sudo nano /etc/apache2/sites-available/contract-system.conf
```

```apache
<VirtualHost *:80>
    ServerName tu-dominio.com
    DocumentRoot /var/www/html
    
    <Directory /var/www/html>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/contract_error.log
    CustomLog ${APACHE_LOG_DIR}/contract_access.log combined
</VirtualHost>
```

```bash
# Habilitar el sitio
sudo a2ensite contract-system.conf
sudo a2dissite 000-default.conf
sudo systemctl reload apache2
```

### 6. Configurar la Base de Datos
```bash
# Importar el esquema de base de datos
mysql -u contract_user -p contract_management < database/schema.sql
```

### 7. Configurar el Sistema
```bash
# Editar configuración de base de datos
sudo nano config/database.php
```

Actualizar las credenciales de la base de datos:
```php
private $host = 'localhost';
private $database = 'contract_management';
private $username = 'contract_user';
private $password = 'secure_password_here';
```

### 8. Configurar .htaccess (Opcional)
```bash
sudo nano /var/www/html/.htaccess
```

```apache
RewriteEngine On

# Redireccionar a HTTPS (si está disponible)
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Proteger archivos sensibles
<Files ~ "^(config|includes|database)">
    Order allow,deny
    Deny from all
</Files>

# Configuración de seguridad
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
Header always set Strict-Transport-Security "max-age=63072000; includeSubDomains; preload"
Header always set Content-Security-Policy "default-src 'self' https:; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://code.jquery.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; img-src 'self' data: https:; font-src 'self' https://cdnjs.cloudflare.com"
```

## 🔑 Credenciales por Defecto

- **Super Admin**: `admin` / `password`
- **Manager**: `manager` / `password`
- **Usuario**: `user` / `password`

**⚠️ IMPORTANTE**: Cambiar estas contraseñas inmediatamente después de la instalación.

## 📊 Estructura del Proyecto

```
contract-management/
├── config/
│   ├── database.php      # Configuración de BD
│   └── config.php        # Configuración general
├── includes/
│   ├── auth.php          # Sistema de autenticación
│   ├── header.php        # Header común
│   └── footer.php        # Footer común
├── assets/
│   ├── css/style.css     # Estilos personalizados
│   └── js/app.js         # JavaScript principal
├── database/
│   └── schema.sql        # Esquema de BD
├── uploads/              # Archivos subidos
├── logs/                 # Logs del sistema
├── api/                  # Endpoints API
├── modules/              # Módulos del sistema
├── index.php             # Dashboard principal
├── login.php             # Página de login
├── contracts.php         # Gestión de contratos
└── README.md             # Este archivo
```

## 🎯 Uso del Sistema

### Dashboard
- **Métricas principales**: Total de contratos, activos, próximos a vencer, valor total
- **Gráficos**: Contratos por categoría y estado
- **Alertas**: Contratos próximos a vencer
- **Actividad reciente**: Log de acciones del sistema

### Gestión de Contratos
- **Crear/Editar**: Formularios completos con validación
- **Filtros avanzados**: Por categoría, estado, tipo, fechas
- **Exportación**: PDF y Excel
- **Documentos adjuntos**: Subida y gestión de archivos

### Sistema de Usuarios
- **Roles jerárquicos**: Control granular de permisos
- **Perfiles completos**: Información detallada de usuarios
- **Auditoría**: Tracking completo de actividades

### Notificaciones
- **Alertas automáticas**: Contratos próximos a vencer
- **Centro de notificaciones**: Gestión centralizada
- **Email notifications**: Sistema de envío automático

## 🔧 Configuración Avanzada

### Email Notifications
Editar `config/config.php`:
```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'tu-email@gmail.com');
define('SMTP_PASSWORD', 'tu-password');
```

### Personalización
- **Colores**: Modificar variables CSS en `assets/css/style.css`
- **Logo**: Reemplazar en `includes/header.php`
- **Configuraciones**: Tabla `system_settings` en BD

### Backups Automáticos
```bash
# Crear script de backup
sudo nano /usr/local/bin/backup-contracts.sh
```

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/contract-system"
mkdir -p $BACKUP_DIR

# Backup de base de datos
mysqldump -u contract_user -p'password' contract_management > $BACKUP_DIR/db_$DATE.sql

# Backup de archivos
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/html/uploads

# Eliminar backups antiguos (más de 30 días)
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete
```

```bash
# Hacer ejecutable y programar
sudo chmod +x /usr/local/bin/backup-contracts.sh
sudo crontab -e
# Agregar: 0 2 * * * /usr/local/bin/backup-contracts.sh
```

## 🚨 Solución de Problemas

### Problemas Comunes

1. **Error 500**: Verificar logs de Apache en `/var/log/apache2/error.log`
2. **No se cargan estilos**: Verificar permisos y mod_rewrite
3. **Error de BD**: Verificar credenciales en `config/database.php`
4. **Archivos no se suben**: Verificar permisos en `uploads/`

### Logs del Sistema
- **Apache**: `/var/log/apache2/`
- **PHP**: `/var/www/html/logs/php_errors.log`
- **Sistema**: Tabla `audit_logs` en BD

## 📈 Monitoreo y Mantenimiento

### Tareas Regulares
- **Backups diarios**: Base de datos y archivos
- **Limpieza de logs**: Rotar y comprimir logs antiguos
- **Actualizaciones**: Mantener PHP y MySQL actualizados
- **Revisión de seguridad**: Auditar permisos y accesos

### Métricas a Monitorear
- **Uso de disco**: Especialmente directorio `uploads/`
- **Rendimiento de BD**: Queries lentas
- **Sesiones activas**: Usuarios concurrentes
- **Errores**: Logs de PHP y Apache

## 🤝 Contribución

Para contribuir al proyecto:

1. Fork del repositorio
2. Crear rama feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Crear Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver `LICENSE` para más detalles.

## 📞 Soporte
+53 53274074
Para soporte técnico:
- **Email**: appscuba@gmail.com
- **Documentación**: [Wiki del proyecto]
- **Issues**: [GitHub Issues]

---

**Desarrollado con ❤️ para la gestión eficiente de contratos empresariales**
