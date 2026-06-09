# INETAM – Sistema de Certificados y Diplomas
**Institución Educativa Técnica Agropecuaria y Minera**  
San Martín de Loba, Bolívar

---

## Requisitos

- PHP 8.0 o superior
- MySQL 5.7+ / MariaDB 10.4+
- Apache 2.4+ con `mod_rewrite` habilitado
- Composer

---

## Instalación

### 1. Clonar / copiar el proyecto
```bash
# Copiar la carpeta "certificados" dentro de tu DocumentRoot
# Ejemplo en XAMPP/WAMP:
cp -r certificados/ C:/xampp/htdocs/
```

### 2. Instalar dependencias (DOMPDF)
```bash
cd certificados/
composer install
```

### 3. Configurar la base de datos
```bash
# Desde MySQL/phpMyAdmin ejecutar:
mysql -u root -p < database/schema.sql
```

### 4. Ajustar configuración
Editar `config/config.php`:
```php
define('BASE_URL', 'http://localhost/certificados');  // ← Tu URL base
define('DB_HOST',  'localhost');
define('DB_NAME',  'inetam_certificados');
define('DB_USER',  'root');
define('DB_PASS',  '');   // ← Tu contraseña MySQL
```

### 5. Configurar Apache
Asegúrate de tener `AllowOverride All` en tu `httpd.conf` o `apache2.conf`.

El archivo `.htaccess` ya está incluido y configurado.

### 6. Subir el Logo / Escudo Institucional
- En la aplicación web, al crear/editar un estudiante, usa el botón **"Subir Logo"**
- O copia manualmente el escudo PNG a: `public/assets/img/escudo.png`

---

## Estructura del Proyecto

```
certificados/
├── app/
│   ├── Controllers/
│   │   ├── EstudianteController.php  ← CRUD API REST
│   │   ├── PdfController.php         ← Generación de PDFs
│   │   └── UploadController.php      ← Subida de logo
│   ├── Core/
│   │   ├── Controller.php            ← Clase base
│   │   ├── Database.php              ← Conexión PDO singleton
│   │   └── Router.php                ← Front Controller
│   ├── Models/
│   │   └── EstudianteModel.php       ← CRUD + validaciones
│   └── Views/
│       └── layouts/
│           └── main.php              ← Vista principal SPA
├── config/
│   └── config.php                    ← Configuración global
├── database/
│   └── schema.sql                    ← Esquema MySQL
├── public/
│   ├── assets/img/                   ← Logo/escudo institucional
│   ├── css/app.css                   ← Estilos Bootstrap5 + custom
│   ├── js/app.js                     ← Axios + DataTables + SweetAlert2
│   ├── index.php                     ← Front Controller
│   └── .htaccess                     ← URL rewriting
├── vendor/                           ← Dependencias Composer (DOMPDF)
├── .htaccess
└── composer.json
```

---

## Funcionalidades

### Gestión de Estudiantes (CRUD completo)
| Campo | Tipo | Requerido |
|-------|------|-----------|
| Primer nombre | Texto | ✅ |
| Segundo nombre | Texto | ❌ |
| Primer apellido | Texto | ✅ |
| Segundo apellido | Texto | ❌ |
| Número de documento | Texto único | ✅ |
| Título | Académico / Técnico / TIC | ✅ |
| Especialidad | Sin esp. / Agropecuario / Programación | ✅ |
| Día, Mes, Año del acta | Numérico | ✅ |
| Libro, Folio, Nº Diploma | Texto | ✅ |

### Documentos PDF generados
1. **Diploma** – Formato certificado con escudo, colores patrios, nombre en cursiva
2. **Acta Individual de Grado** – Documento oficial por estudiante
3. **Acta General de Graduación** – Lista completa en formato tabla (orientación landscape)
4. **Mención de Honor** – Reconocimiento académico individual

### Tecnologías
- **Backend**: PHP 8 puro, arquitectura MVC
- **Base de datos**: MySQL con PDO
- **PDF**: DOMPDF v2
- **Frontend**: Bootstrap 5.3 + DataTables + Axios + SweetAlert2

---

## Configuración Institucional
Para cambiar rector, secretaria, número de acta, etc., editar la tabla `configuracion` en MySQL:
```sql
UPDATE configuracion SET valor = 'Nuevo Rector' WHERE clave = 'rector';
```

---

## Soporte
Proyecto desarrollado para INETAM – San Martín de Loba, Bolívar.
