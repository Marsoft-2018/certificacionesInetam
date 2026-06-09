# Instalación en XAMPP para macOS – Guía paso a paso

## Paso 1 – Copiar el proyecto

Copia la carpeta `certificados` dentro de:
```
/Applications/XAMPP/xamppfiles/htdocs/
```
Debe quedar así:
```
/Applications/XAMPP/xamppfiles/htdocs/certificados/
```

## Paso 2 – Habilitar mod_rewrite y AllowOverride en Apache

Abre el archivo de configuración de Apache:
```
/Applications/XAMPP/xamppfiles/etc/httpd.conf
```
**Busca esta sección** (aproximadamente en la línea 220):
```apache
<Directory "/Applications/XAMPP/xamppfiles/htdocs">
    Options Indexes FollowSymLinks ExecCGI Includes
    AllowOverride None        ← CAMBIAR ESTO
    Require all granted
</Directory>
```
**Cámbiala por:**
```apache
<Directory "/Applications/XAMPP/xamppfiles/htdocs">
    Options Indexes FollowSymLinks ExecCGI Includes
    AllowOverride All          ← DEBE DECIR All
    Require all granted
</Directory>
```

**También busca la línea del módulo rewrite** y asegúrate que NO esté comentada:
```apache
LoadModule rewrite_module modules/mod_rewrite.so
```
(Quita el `#` del inicio si lo tiene)

## Paso 3 – Instalar dependencias (DOMPDF)

Abre la Terminal y ejecuta:
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/certificados
/Applications/XAMPP/xamppfiles/bin/php /usr/local/bin/composer install
```

Si no tienes Composer instalado globalmente:
```bash
# Instalar Composer en macOS
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Luego instalar dependencias
cd /Applications/XAMPP/xamppfiles/htdocs/certificados
composer install
```

## Paso 4 – Configurar la base de datos

1. Abre **phpMyAdmin**: http://localhost/phpmyadmin
2. Crea una base de datos llamada `inetam_certificados`
3. Importa el archivo: `certificados/database/schema.sql`

**O por terminal:**
```bash
/Applications/XAMPP/xamppfiles/bin/mysql -u root -p < /Applications/XAMPP/xamppfiles/htdocs/certificados/database/schema.sql
```

## Paso 5 – Configurar el proyecto

Edita `config/config.php`:
```php
define('BASE_URL', 'http://localhost/certificados/public');
define('DB_HOST',  'localhost');
define('DB_NAME',  'inetam_certificados');
define('DB_USER',  'root');
define('DB_PASS',  '');   // En XAMPP macOS suele estar vacío por defecto
```

## Paso 6 – Reiniciar Apache

En el panel de XAMPP, haz clic en **Stop** y luego **Start** en Apache.

## Paso 7 – Verificar instalación

Abre en el navegador:
```
http://localhost/certificados/public/test.php
```
Debe mostrar si la BD está conectada y si Composer fue ejecutado.

Luego accede a la aplicación:
```
http://localhost/certificados/public/
```

---

## Problemas comunes en macOS

| Error | Solución |
|-------|----------|
| 403 Forbidden | `AllowOverride All` no configurado en httpd.conf |
| "Ruta no encontrada" | mod_rewrite no habilitado o `AllowOverride None` |
| Error de BD | Importar schema.sql / verificar contraseña root |
| PDFs en blanco | Ejecutar `composer install` para instalar DOMPDF |
| Error vendor | Ejecutar `composer install` dentro de la carpeta del proyecto |

---

## Ruta completa de acceso (XAMPP macOS)

```
Proyecto:   /Applications/XAMPP/xamppfiles/htdocs/certificados/
App URL:    http://localhost/certificados/public/
Test URL:   http://localhost/certificados/public/test.php
phpMyAdmin: http://localhost/phpmyadmin
```
