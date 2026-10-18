<?php
/**
 * Capa de seguridad común de Haedo Barber.
 *
 * Incluir al principio de TODA página o controlador (reemplaza a session_start()):
 *   require_once __DIR__ . '/../config/security.php';
 *
 * Provee: sesión endurecida, CSRF, escape de salida, rate limiting (tabla
 * intentos_acceso), cookie "recordarme" segura (selector + validator),
 * subida segura de imágenes y validación de URLs de mapas.
 */

if (defined('HB_SECURITY_LOADED')) {
    return;
}
define('HB_SECURITY_LOADED', true);

require_once __DIR__ . '/env.php';

/* ------------------------------------------------------------------ */
/* Errores: nunca mostrarlos al público                                 */
/* ------------------------------------------------------------------ */
@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
@ini_set('log_errors', '1');
error_reporting(E_ALL);

set_exception_handler(static function (Throwable $e): void {
    error_log('Haedo Barber: excepción no controlada: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
    if (PHP_SAPI !== 'cli' && !headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
    }
    echo 'Ocurrió un error inesperado. Intentá nuevamente más tarde.';
});

/* ------------------------------------------------------------------ */
/* Utilidades básicas                                                    */
/* ------------------------------------------------------------------ */
const HB_SESSION_IDLE = 7200;      // 2 h sin actividad
const HB_SESSION_ABSOLUTE = 43200; // 12 h máximo por sesión
const HB_REMEMBER_DAYS = 30;
const HB_DUMMY_HASH = '$2y$10$iMIRH/0niK2Pcuf2gMCTUu80dNrMBj.DGzPTslElnG3/FIeBCgmOy';

/** Escapa una salida para HTML (contenido y atributos). */
function e($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function es_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    if ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443) {
        return true;
    }
    // Sólo confiar en el proxy si se configura explícitamente (TRUST_PROXY=1).
    if (($_ENV['TRUST_PROXY'] ?? '') === '1'
        && strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https') {
        return true;
    }
    return false;
}

function ip_cliente(): string
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    if (($_ENV['TRUST_PROXY'] ?? '') === '1' && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $candidato = trim(explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        if (filter_var($candidato, FILTER_VALIDATE_IP)) {
            $ip = $candidato;
        }
    }
    return substr($ip, 0, 45);
}

/** Texto de una sola línea: sin caracteres de control (evita header injection), recortado. */
function limpiar_linea($valor, int $max): string
{
    $valor = (string) $valor;
    $valor = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $valor) ?? '';
    $valor = trim(preg_replace('/\s{2,}/u', ' ', $valor) ?? '');
    return mb_substr($valor, 0, $max, 'UTF-8');
}

/** Texto multilínea: conserva saltos de línea, elimina otros caracteres de control. */
function limpiar_texto($valor, int $max): string
{
    $valor = (string) $valor;
    $valor = str_replace("\r\n", "\n", $valor);
    $valor = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $valor) ?? '';
    return mb_substr(trim($valor), 0, $max, 'UTF-8');
}

function largo_valido($valor, int $max): bool
{
    return mb_strlen((string) $valor, 'UTF-8') <= $max;
}

/* ------------------------------------------------------------------ */
/* Sesión endurecida                                                     */
/* ------------------------------------------------------------------ */
if (PHP_SAPI !== 'cli') {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.sid_length', '48');
        session_name('HBSESSID');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => es_https(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    $ahoraSeg = time();
    if (!isset($_SESSION['_born'])) {
        $_SESSION['_born'] = $ahoraSeg;
    }
    if (
        isset($_SESSION['_last'])
        && ($ahoraSeg - (int) $_SESSION['_last'] > HB_SESSION_IDLE
            || $ahoraSeg - (int) $_SESSION['_born'] > HB_SESSION_ABSOLUTE)
    ) {
        $_SESSION = [];
        session_destroy();
        session_start();
        session_regenerate_id(true);
        $_SESSION['_born'] = $ahoraSeg;
    }
    $_SESSION['_last'] = $ahoraSeg;

    if (!headers_sent() && !empty($_SESSION['usuario_id'])) {
        header('Cache-Control: no-store, max-age=0');
        header('Pragma: no-cache');
    }
} elseif (!isset($_SESSION)) {
    $_SESSION = [];
}

/** Inicia la sesión de un usuario (siempre regenera el id para evitar fijación de sesión). */
function iniciar_sesion_usuario(array $usuario): void
{
    session_regenerate_id(true);
    $_SESSION['usuario_id'] = (int) $usuario['id'];
    $_SESSION['usuario_nombre'] = $usuario['nombre'];
    $_SESSION['usuario_email'] = $usuario['email'];
    $_SESSION['usuario_rol'] = $usuario['rol'];
    $_SESSION['_born'] = time();
    $_SESSION['_rv'] = time();
    unset($_SESSION['csrf_token']);
}

/** Cierra la sesión actual por completo (datos + cookie). */
function destruir_sesion(): void
{
    $_SESSION = [];
    if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE) {
        if (!headers_sent()) {
            setcookie(session_name(), '', [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => es_https(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
        session_destroy();
    }
}

/* ------------------------------------------------------------------ */
/* CSRF                                                                  */
/* ------------------------------------------------------------------ */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Campo oculto para todos los formularios POST. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Verifica el token CSRF (y Origin/Referer si vienen). Corta la ejecución si falla. */
function csrf_verify(): void
{
    $enviado = $_POST['csrf_token'] ?? '';
    $valido = is_string($enviado)
        && $enviado !== ''
        && !empty($_SESSION['csrf_token'])
        && hash_equals((string) $_SESSION['csrf_token'], $enviado);

    if ($valido) {
        $origen = $_SERVER['HTTP_ORIGIN'] ?? ($_SERVER['HTTP_REFERER'] ?? '');
        if ($origen !== '' && $origen !== 'null') {
            $hostOrigen = parse_url((string) $origen, PHP_URL_HOST);
            $portOrigen = parse_url((string) $origen, PHP_URL_PORT);
            $hostPropio = (string) ($_SERVER['HTTP_HOST'] ?? '');
            $hostOrigenCompleto = $hostOrigen . ($portOrigen ? ':' . $portOrigen : '');
            if (!$hostOrigen || (strcasecmp($hostOrigenCompleto, $hostPropio) !== 0 && strcasecmp((string) $hostOrigen, $hostPropio) !== 0)) {
                $valido = false;
            }
        }
    }

    if (!$valido) {
        http_response_code(403);
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Solicitud no válida</title></head><body style="font-family:sans-serif;background:#0a0a0a;color:#f0ede8;padding:2rem">'
            . '<h1>Solicitud no válida</h1><p>La sesión expiró o el formulario no es válido. Volvé atrás, recargá la página e intentá de nuevo.</p>'
            . '<p><a style="color:#c9a24b" href="javascript:history.back()">Volver</a></p></body></html>';
        exit;
    }
}

/* ------------------------------------------------------------------ */
/* Autorización                                                          */
/* ------------------------------------------------------------------ */
function usuario_logueado(): bool
{
    return !empty($_SESSION['usuario_id']);
}

function es_admin(): bool
{
    return usuario_logueado() && ($_SESSION['usuario_rol'] ?? '') === 'admin';
}

/** $rutaLogin: URL relativa al login desde la página que llama. */
function requerir_admin(string $rutaLogin): void
{
    if (!es_admin()) {
        header('Location: ' . $rutaLogin);
        exit;
    }
}

/**
 * Revalida contra la base (cada 30 s) que el usuario siga existiendo y su rol actual,
 * para que degradar/borrar a alguien surta efecto sin esperar a que expire su sesión.
 */
function refrescar_usuario_sesion(PDO $conn): void
{
    if (empty($_SESSION['usuario_id'])) {
        return;
    }
    if (time() - (int) ($_SESSION['_rv'] ?? 0) < 30) {
        return;
    }
    try {
        $stmt = $conn->prepare('SELECT id, nombre, email, rol FROM usuarios WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', (int) $_SESSION['usuario_id'], PDO::PARAM_INT);
        $stmt->execute();
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $ex) {
        error_log('Haedo Barber: no se pudo revalidar el usuario: ' . $ex->getMessage());
        return;
    }
    if (!$u) {
        destruir_sesion();
        return;
    }
    $_SESSION['usuario_nombre'] = $u['nombre'];
    $_SESSION['usuario_email'] = $u['email'];
    $_SESSION['usuario_rol'] = $u['rol'];
    $_SESSION['_rv'] = time();
}

if (PHP_SAPI !== 'cli' && !empty($_SESSION['usuario_id']) && time() - (int) ($_SESSION['_rv'] ?? 0) >= 30) {
    require_once __DIR__ . '/database.php';
    refrescar_usuario_sesion($conn);
    if (empty($_SESSION['usuario_id']) && session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

/* ------------------------------------------------------------------ */
/* Contraseñas                                                         */
/* ------------------------------------------------------------------ */
/** Devuelve un código de error o null si la contraseña es aceptable. */
function validar_password(string $password, string $email = ''): ?string
{
    if (strlen($password) < 10) {
        return 'password_corta';
    }
    if (strlen($password) > 72) { // límite real de bcrypt
        return 'password_larga';
    }
    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        return 'password_debil';
    }
    $comunes = ['password12', 'contrasena1', 'contraseña1', '1234567890', 'qwerty1234', 'abcdefg123', 'password123', 'haedobarber1'];
    if (in_array(strtolower($password), $comunes, true)) {
        return 'password_debil';
    }
    if ($email !== '' && strtolower($password) === strtolower($email)) {
        return 'password_debil';
    }
    return null;
}

/* ------------------------------------------------------------------ */
/* Rate limiting / fuerza bruta (tabla intentos_acceso)                  */
/* ------------------------------------------------------------------ */
function rl_ident(string $valor): string
{
    return hash('sha256', mb_strtolower(trim($valor), 'UTF-8'));
}

/**
 * True si hay que bloquear. Cuenta intentos recientes del tipo dado:
 *  - misma IP + mismo identificador  >= $maxPar
 *  - misma IP                        >= $maxIp
 *  - mismo identificador (cualquier IP) >= $maxIdent (defensa ante ataques distribuidos)
 * Si la tabla todavía no existe (migración sin correr) NO bloquea ni rompe la app.
 */
function rl_bloqueado(PDO $conn, string $tipo, string $ident, int $maxPar, int $maxIp, int $maxIdent, int $ventanaSeg): bool
{
    try {
        $sql = 'SELECT
                    SUM(ip = :ip AND ident = :ident) AS par,
                    SUM(ip = :ip2) AS por_ip,
                    SUM(ident = :ident2) AS por_ident
                FROM intentos_acceso
                WHERE tipo = :tipo
                  AND creado_en > DATE_SUB(NOW(), INTERVAL :ventana SECOND)';
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':ip', ip_cliente());
        $stmt->bindValue(':ip2', ip_cliente());
        $stmt->bindValue(':ident', $ident);
        $stmt->bindValue(':ident2', $ident);
        $stmt->bindValue(':tipo', $tipo);
        $stmt->bindValue(':ventana', $ventanaSeg, PDO::PARAM_INT);
        $stmt->execute();
        $r = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return (int) ($r['par'] ?? 0) >= $maxPar
            || (int) ($r['por_ip'] ?? 0) >= $maxIp
            || ($ident !== '' && (int) ($r['por_ident'] ?? 0) >= $maxIdent);
    } catch (PDOException $ex) {
        error_log('Haedo Barber: rate limit no disponible: ' . $ex->getMessage());
        return false;
    }
}

function rl_registrar(PDO $conn, string $tipo, string $ident = ''): void
{
    try {
        $stmt = $conn->prepare('INSERT INTO intentos_acceso (tipo, ip, ident) VALUES (:tipo, :ip, :ident)');
        $stmt->bindValue(':tipo', $tipo);
        $stmt->bindValue(':ip', ip_cliente());
        $stmt->bindValue(':ident', $ident);
        $stmt->execute();

        if (random_int(1, 100) === 1) { // limpieza ocasional
            $conn->exec('DELETE FROM intentos_acceso WHERE creado_en < DATE_SUB(NOW(), INTERVAL 2 DAY)');
        }
    } catch (PDOException $ex) {
        error_log('Haedo Barber: no se pudo registrar el intento: ' . $ex->getMessage());
    }
}

/** Borra los intentos de la misma IP+identificador (login exitoso). */
function rl_limpiar(PDO $conn, string $tipo, string $ident): void
{
    try {
        $stmt = $conn->prepare('DELETE FROM intentos_acceso WHERE tipo = :tipo AND ip = :ip AND ident = :ident');
        $stmt->bindValue(':tipo', $tipo);
        $stmt->bindValue(':ip', ip_cliente());
        $stmt->bindValue(':ident', $ident);
        $stmt->execute();
    } catch (PDOException $ex) {
        error_log('Haedo Barber: no se pudo limpiar intentos: ' . $ex->getMessage());
    }
}

/* ------------------------------------------------------------------ */
/* Cookie "recordarme": selector + validator                             */
/* ------------------------------------------------------------------ */
function recordar_cookie_opciones(int $expira): array
{
    return [
        'expires' => $expira,
        'path' => '/',
        'domain' => '',
        'secure' => es_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function recordar_borrar_cookies(): void
{
    if (headers_sent()) {
        return;
    }
    setcookie('remember', '', recordar_cookie_opciones(time() - 3600));
    // Cookies del esquema anterior (inseguro): id suelto + token.
    setcookie('remember_user', '', recordar_cookie_opciones(time() - 3600));
    setcookie('remember_token', '', recordar_cookie_opciones(time() - 3600));
}

/** Genera un token nuevo para el usuario y lo guarda hasheado. Reemplaza al anterior. */
function recordar_emitir(PDO $conn, int $usuarioId): void
{
    $selector = bin2hex(random_bytes(9));
    $validator = bin2hex(random_bytes(32));
    $expira = time() + HB_REMEMBER_DAYS * 86400;

    $stmt = $conn->prepare(
        'UPDATE usuarios
         SET remember_selector = :selector,
             remember_token_hash = :hash,
             remember_token_expira = FROM_UNIXTIME(:expira)
         WHERE id = :id'
    );
    $stmt->bindValue(':selector', $selector);
    $stmt->bindValue(':hash', hash('sha256', $validator));
    $stmt->bindValue(':expira', $expira, PDO::PARAM_INT);
    $stmt->bindValue(':id', $usuarioId, PDO::PARAM_INT);
    $stmt->execute();

    if (!headers_sent()) {
        setcookie('remember', $selector . ':' . $validator, recordar_cookie_opciones($expira));
    }
}

function recordar_revocar(PDO $conn, int $usuarioId): void
{
    $stmt = $conn->prepare(
        'UPDATE usuarios
         SET remember_selector = NULL, remember_token_hash = NULL, remember_token_expira = NULL
         WHERE id = :id'
    );
    $stmt->bindValue(':id', $usuarioId, PDO::PARAM_INT);
    $stmt->execute();
}

/** Intenta iniciar sesión desde la cookie "remember" (selector + validator hasheado). */
function recordar_login(PDO $conn): void
{
    if (!empty($_SESSION['usuario_id'])) {
        return;
    }
    if (isset($_COOKIE['remember_user']) || isset($_COOKIE['remember_token'])) {
        recordar_borrar_cookies(); // esquema viejo: ya no se acepta
    }
    $cookie = $_COOKIE['remember'] ?? '';
    if (!is_string($cookie) || !preg_match('/^([a-f0-9]{18}):([a-f0-9]{64})$/', $cookie, $m)) {
        if ($cookie !== '') {
            recordar_borrar_cookies();
        }
        return;
    }
    [, $selector, $validator] = $m;

    try {
        $stmt = $conn->prepare(
            'SELECT id, nombre, email, rol, remember_token_hash
             FROM usuarios
             WHERE remember_selector = :selector
               AND remember_token_expira > NOW()
             LIMIT 1'
        );
        $stmt->bindValue(':selector', $selector);
        $stmt->execute();
        $u = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$u) {
            recordar_borrar_cookies();
            return;
        }
        if (!hash_equals((string) $u['remember_token_hash'], hash('sha256', $validator))) {
            // Selector válido con validator incorrecto: posible robo. Se invalida el token.
            recordar_revocar($conn, (int) $u['id']);
            recordar_borrar_cookies();
            return;
        }

        // No se rota el token en cada uso para no invalidar pedidos paralelos del navegador;
        // el token vive 30 días, se revoca al cerrar sesión y ante un validator incorrecto.
        iniciar_sesion_usuario($u);
    } catch (PDOException $ex) {
        error_log('Haedo Barber: error en recordarme: ' . $ex->getMessage());
        recordar_borrar_cookies();
    }
}

/* ------------------------------------------------------------------ */
/* Subida segura de imágenes                                             */
/* ------------------------------------------------------------------ */
const HB_UPLOAD_MAX_BYTES = 5 * 1024 * 1024;

/**
 * @return string|false|null  ruta relativa "uploads/<subdir>/<archivo>", false si es inválida,
 *                            null si no se envió archivo.
 */
function subir_imagen(string $inputName, string $subdir, string $prefijo)
{
    if (!in_array($subdir, ['barberos', 'sedes'], true)) {
        return false;
    }
    if (!isset($_FILES[$inputName]) || !is_array($_FILES[$inputName]) || is_array($_FILES[$inputName]['error'] ?? null)) {
        return ($_FILES[$inputName]['error'] ?? null) === null ? null : false;
    }
    $f = $_FILES[$inputName];
    if ($f['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
        return false;
    }
    if ($f['size'] <= 0 || $f['size'] > HB_UPLOAD_MAX_BYTES) {
        return false;
    }

    // Tipo real por contenido; la extensión original se ignora por completo.
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($f['tmp_name']);
    $extensiones = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensiones[$mime])) {
        return false;
    }
    $info = @getimagesize($f['tmp_name']);
    if ($info === false || ($info['mime'] ?? '') !== $mime) {
        return false;
    }
    if ($info[0] < 1 || $info[1] < 1 || $info[0] > 8000 || $info[1] > 8000) {
        return false;
    }

    $carpeta = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $subdir;
    if (!is_dir($carpeta) && !mkdir($carpeta, 0755, true) && !is_dir($carpeta)) {
        return false;
    }

    $prefijo = preg_replace('/[^a-z_]/', '', strtolower($prefijo)) ?: 'img';
    $nombre = $prefijo . '_' . bin2hex(random_bytes(16)) . '.' . $extensiones[$mime];
    if (!move_uploaded_file($f['tmp_name'], $carpeta . DIRECTORY_SEPARATOR . $nombre)) {
        return false;
    }
    @chmod($carpeta . DIRECTORY_SEPARATOR . $nombre, 0644);

    return 'uploads/' . $subdir . '/' . $nombre;
}

/** True si la ruta guardada tiene la forma exacta uploads/(barberos|sedes)/<archivo-seguro>. */
function ruta_upload_valida($ruta): bool
{
    return is_string($ruta)
        && preg_match('#^uploads/(barberos|sedes)/[A-Za-z0-9_][A-Za-z0-9_.-]*\.(jpg|jpeg|png|webp)$#', $ruta) === 1
        && !str_contains($ruta, '..');
}

/** Borra un archivo subido sólo si la ruta es válida y queda dentro de uploads/. */
function borrar_upload($ruta): void
{
    if (!ruta_upload_valida($ruta)) {
        return;
    }
    $base = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads');
    $archivo = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . $ruta);
    if ($base !== false && $archivo !== false && str_starts_with($archivo, $base . DIRECTORY_SEPARATOR) && is_file($archivo)) {
        @unlink($archivo);
    }
}

/* ------------------------------------------------------------------ */
/* Mapas                                                                 */
/* ------------------------------------------------------------------ */
/**
 * Acepta una URL https de embed de Google Maps (o, por compatibilidad con datos viejos,
 * un <iframe ... src="..."> del que sólo se extrae el src). Devuelve la URL o null.
 * Nunca se debe imprimir HTML crudo de la base: usar siempre esta función + iframe propio.
 */
function mapa_embed_url($valor): ?string
{
    $valor = trim((string) $valor);
    if ($valor === '') {
        return null;
    }
    if (stripos($valor, '<iframe') !== false) {
        if (!preg_match('/\ssrc\s*=\s*(["\'])(.*?)\1/is', $valor, $m)) {
            return null;
        }
        $valor = html_entity_decode(trim($m[2]), ENT_QUOTES, 'UTF-8');
    }
    if (strlen($valor) > 2000 || preg_match('/[\x00-\x20"\'<>\\\\]/', $valor)) {
        return null;
    }
    $p = parse_url($valor);
    if ($p === false || ($p['scheme'] ?? '') !== 'https' || isset($p['user']) || isset($p['port'])) {
        return null;
    }
    $host = strtolower($p['host'] ?? '');
    if (!in_array($host, ['www.google.com', 'google.com', 'maps.google.com'], true)) {
        return null;
    }
    if (!str_starts_with($p['path'] ?? '', '/maps/embed')) {
        return null;
    }
    return $valor;
}

/** HTML seguro del mapa (iframe propio con src escapado) o cadena vacía. */
function mapa_embed_html($valor): string
{
    $url = mapa_embed_url($valor);
    if ($url === null) {
        return '';
    }
    return '<iframe src="' . e($url) . '" width="600" height="450" style="border:0" loading="lazy" '
        . 'allowfullscreen referrerpolicy="no-referrer-when-downgrade" '
        . 'sandbox="allow-scripts allow-same-origin allow-popups" title="Mapa de la sede"></iframe>';
}

/* ------------------------------------------------------------------ */
/* Restauración de sesión por cookie "remember" (cualquier página)       */
/* ------------------------------------------------------------------ */
if (PHP_SAPI !== 'cli' && empty($_SESSION['usuario_id']) && !empty($_COOKIE['remember']) && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    require_once __DIR__ . '/database.php';
    recordar_login($conn);
}
