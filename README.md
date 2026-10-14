# Haedo Barber

Aplicación web de turnos para barbería, desarrollada en PHP y MySQL. Este repositorio contiene el sitio público, panel de administración, gestión de usuarios, sedes, barberos, servicios, turnos y mensajes.

## Estructura

- `public/`: páginas servidas por Apache y recursos públicos (`assets/css`, `assets/js`, `assets/img`).
- `config/`: carga de entorno, conexión a la base de datos y validación de sesión.
- `controllers/`: acciones POST y controladores de autenticación/turnos.
- `services/`: correo y otros servicios de aplicación.
- `database/`: esquema inicial de MySQL. `schema.sql` borra y recrea tablas; usar sólo en una base vacía o con una copia de seguridad, nunca como migración sobre datos que se quieran conservar.
- `scripts/`: tareas manuales o programadas; no son páginas web.
- `libs/`: dependencias PHP locales (PHPMailer).
- `uploads/`: imágenes cargadas para sedes y barberos. No borrar ni reemplazar al reordenar el código.

## Desarrollo local con XAMPP

1. Ubicá el proyecto en `C:\xampp\htdocs\HaedoBarber`.
2. Creá una base MySQL vacía llamada `haedobarber_db` y, sólo para una instalación nueva, importá `database/schema.sql`.
3. Copiá `.env.example` como `.env` y configurá `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` y los datos SMTP reales. Para seguir usando una base existente llamada `barberwest_db`, conservá `DB_NAME=barberwest_db`; este cambio de marca no migra ni renombra bases existentes.
4. Encendé Apache y MySQL; abrí `http://localhost/HaedoBarber/`.

El `.htaccess` desactiva índices de directorio y bloquea archivos ocultos y carpetas internas si Apache tiene `AllowOverride` habilitado. Los datos reales de contacto, redes sociales y ubicación deben configurarse antes de publicar; no se deben reutilizar automáticamente los enlaces del negocio anterior.
