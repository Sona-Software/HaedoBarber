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
3. Copiá `.env.example` como `.env` y configurá `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` y los datos SMTP reales. La base por defecto es `haedobarber_db`.
4. Encendé Apache y MySQL; abrí `http://localhost/HaedoBarber/`.

El `.htaccess` desactiva índices de directorio y bloquea archivos ocultos y carpetas internas si Apache tiene `AllowOverride` habilitado. Los datos reales de contacto, redes sociales y ubicación deben configurarse antes de publicar; no se deben reutilizar automáticamente los enlaces del negocio anterior.

## Primer uso (instalación vacía)

`database/schema.sql` crea todas las tablas vacías: no incluye usuarios, sedes, barberos ni servicios.

1. Creá el administrador desde la consola (no hay usuarios ni claves de prueba): `php scripts/crear_admin.php tu@correo.com "UnaClaveLarga123" [Nombre] [Apellido]`. La clave debe tener 10 a 72 caracteres, con letras y números. Si el email ya existe, agregá `--forzar` para cambiarle la clave y hacerlo admin.
2. Ingresá en `public/login.php` y abrí el panel de administración.
3. Cargá, en este orden: **Sedes** (con su galería), **Barberos** (asignados a una sede, con fotos) y **Servicios** (precio y duración).
4. Completá en `.env` los datos públicos (correo de contacto, Instagram, TikTok, WhatsApp, mapa).

Hasta que haya sedes, barberos y servicios cargados, el sitio muestra estados vacíos y no permite reservar turnos.

## Seguridad

- **Capa común**: `config/security.php` (se incluye al inicio de toda página/controlador). Provee sesión endurecida (`HttpOnly`, `SameSite=Lax`, `Secure` bajo HTTPS, `use_strict_mode`, regeneración de id al iniciar sesión, expiración por inactividad de 2 h y máximo de 12 h), CSRF (`csrf_field()` en cada formulario POST y `csrf_verify()` en cada controlador), escape de salida (`e()`), rate limiting y subida segura de imágenes.
- **Fuerza bruta / spam**: tabla `intentos_acceso`. Login: 5 fallos por IP+email, 20 por IP o 30 por email en 15 min bloquean temporalmente. Registro y contacto: 5 por hora por IP.
- **Recordarme**: cookie `remember` = `selector:validator`; en la base sólo se guarda el hash SHA-256 del validator y se compara con `hash_equals`. Se revoca al cerrar sesión o ante un validator incorrecto.
- **Contraseñas**: 10 a 72 caracteres, con letras y números.
- **Uploads**: sólo JPG/PNG/WebP validados por contenido (`finfo` + `getimagesize`), máx. 5 MB, nombre aleatorio; `uploads/.htaccess` impide ejecutar código.
- **Mapas**: `sedes.mapa_embed` acepta únicamente una URL `https://www.google.com/maps/embed...` (o un `<iframe>` viejo del que se extrae el `src`); se renderiza siempre como `<iframe>` propio.
- **Apache**: el `.htaccess` raíz agrega cabeceras de seguridad (CSP, X-Frame-Options, etc.), bloquea `.env`, `.sql`, `.md`, `.git` y las carpetas internas. HSTS y la redirección a HTTPS están comentados: activarlos cuando haya certificado. Requiere `AllowOverride All`. Lo ideal en producción es apuntar el DocumentRoot a una carpeta que no exponga el código interno.
- **Migraciones**: en una base existente correr `database/migrations_002_security.sql` (crea `intentos_acceso`, agrega `usuarios.remember_selector`, invalida tokens viejos y borra las cuentas de prueba `*.test` sin turnos).
- Si el sitio está detrás de un proxy/balanceador que termina HTTPS, definir `TRUST_PROXY=1` en `.env` para respetar `X-Forwarded-Proto` / `X-Forwarded-For`.
