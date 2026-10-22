<?php
/**
 * Lógica compartida de turnos (horarios, disponibilidad, límites anti-abuso).
 *
 * TODO lo configurable está en este archivo (valores por defecto) y se puede
 * sobrescribir desde .env con la misma clave (ej: TURNOS_MAX_SEMANA=4, TURNOS_CANASTA_POLITICA=rechazar).
 *
 * Para cambiar los horarios de trabajo editá TURNOS_BLOQUES, TURNOS_DIAS_CERRADOS
 * y TURNOS_SLOT_MIN más abajo. Los textos visibles salen de turnos_horario_texto().
 */

require_once __DIR__ . '/env.php';

function turnos_cfg_int(string $clave, int $defecto): int
{
    $v = $_ENV[$clave] ?? getenv($clave);
    if ($v === false || $v === null || $v === '' || !is_numeric($v)) {
        return $defecto;
    }
    return (int) $v;
}

/** Valor de texto de .env restringido a una lista de opciones permitidas. */
function turnos_cfg_str(string $clave, string $defecto, array $permitidos): string
{
    $v = $_ENV[$clave] ?? getenv($clave);
    $v = is_string($v) ? strtolower(trim($v)) : '';
    return in_array($v, $permitidos, true) ? $v : $defecto;
}

function turnos_def(string $nombre, $valor): void
{
    if (!defined($nombre)) {
        define($nombre, $valor);
    }
}

/* ----------------------------- Horarios de trabajo ----------------------------- */
turnos_def('TURNOS_TZ', 'America/Argentina/Buenos_Aires');
// Bloques de atención [inicio, fin] en formato HH:MM (el turno debe terminar antes o justo al fin).
// Atención de corrido: lunes a sábado de 10:30 a 20:30.
turnos_def('TURNOS_BLOQUES', [['10:30', '20:30']]);
// Días cerrados (ISO: 1 = lunes ... 7 = domingo). Solo domingo.
turnos_def('TURNOS_DIAS_CERRADOS', [7]);
// Grilla de horarios: los turnos solo pueden empezar cada N minutos desde el inicio de cada bloque.
turnos_def('TURNOS_SLOT_MIN', turnos_cfg_int('TURNOS_SLOT_MIN', 30));

/* ----------------------------- Anticipación ----------------------------- */
turnos_def('TURNOS_ANTICIPACION_MIN_MIN', turnos_cfg_int('TURNOS_ANTICIPACION_MIN_MIN', 60)); // minutos
turnos_def('TURNOS_ANTICIPACION_MAX_DIAS', turnos_cfg_int('TURNOS_ANTICIPACION_MAX_DIAS', 30)); // días

/* ----------------------------- Límites por usuario ----------------------------- */
// Cupo semanal: cada cuenta puede crear hasta TURNOS_MAX_SEMANA turnos por semana calendario
// (lunes a domingo, hora de Buenos Aires). Se cuenta por la semana en que se CREA la reserva
// (no por la fecha del turno): eso limita la velocidad de creación de reservas, que es lo que
// importa contra el abuso, y evita que alguien bloquee agenda de muchas semanas de golpe sin pasar
// por revisión. Cuentan todos los turnos no cancelados (pendiente, confirmado, completado, ausente).
// Pasado el cupo, la reserva NO se rechaza: va a la "canasta" (estado pendiente + requiere_confirmacion=1),
// reserva el horario y espera que el admin la confirme o rechace.
turnos_def('TURNOS_MAX_SEMANA', turnos_cfg_int('TURNOS_MAX_SEMANA', 3));
// Política al exceder el cupo: 'canasta' (confirmación manual, por defecto) o 'rechazar' (se rechaza la reserva).
turnos_def('TURNOS_CANASTA_POLITICA', turnos_cfg_str('TURNOS_CANASTA_POLITICA', 'canasta', ['canasta', 'rechazar']));
// Tope de reservas de una cuenta esperando confirmación manual a la vez (evita llenar la agenda con pendientes).
turnos_def('TURNOS_MAX_CANASTA', turnos_cfg_int('TURNOS_MAX_CANASTA', 3));
turnos_def('TURNOS_MAX_POR_DIA', turnos_cfg_int('TURNOS_MAX_POR_DIA', 1));              // turnos activos el mismo día
turnos_def('TURNOS_OBS_MAX', turnos_cfg_int('TURNOS_OBS_MAX', 255));                    // largo máx. de observaciones

/* ----------------------------- Rate limiting ----------------------------- */
turnos_def('TURNOS_IP_MAX_HORA', turnos_cfg_int('TURNOS_IP_MAX_HORA', 12));             // intentos de reserva por IP por hora
turnos_def('TURNOS_USUARIO_MAX_HORA', turnos_cfg_int('TURNOS_USUARIO_MAX_HORA', 8));    // intentos de reserva por cuenta por hora
// Solo poner TURNOS_CONFIAR_PROXY=1 en .env si el sitio está detrás de un proxy/CDN propio de confianza.
turnos_def('TURNOS_CONFIAR_PROXY', turnos_cfg_int('TURNOS_CONFIAR_PROXY', 0) === 1);

/* ----------------------------- Ausencias (no-show) ----------------------------- */
turnos_def('TURNOS_NOSHOW_MAX', turnos_cfg_int('TURNOS_NOSHOW_MAX', 2));                // ausentes que bloquean
turnos_def('TURNOS_NOSHOW_DIAS', turnos_cfg_int('TURNOS_NOSHOW_DIAS', 60));             // ventana (días) para contarlos

/* ----------------------------- Cancelaciones ----------------------------- */
turnos_def('TURNOS_CANCEL_MIN_HORAS', turnos_cfg_int('TURNOS_CANCEL_MIN_HORAS', 2));    // anticipación mínima para cancelar
turnos_def('TURNOS_CANCEL_MAX', turnos_cfg_int('TURNOS_CANCEL_MAX', 3));                // cancelaciones permitidas...
turnos_def('TURNOS_CANCEL_VENTANA_DIAS', turnos_cfg_int('TURNOS_CANCEL_VENTANA_DIAS', 7)); // ...en esta ventana de días

date_default_timezone_set(TURNOS_TZ);

/* ----------------------- Textos del horario (derivados) ----------------------- */

/** Rangos de días abiertos, p. ej. "Lun a Sáb". Se deriva de TURNOS_DIAS_CERRADOS. */
function turnos_dias_texto(bool $largo = false): string
{
    $nombres = $largo
        ? [1 => 'lunes', 2 => 'martes', 3 => 'miércoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sábado', 7 => 'domingo']
        : [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'];
    $abiertos = array_values(array_diff(range(1, 7), TURNOS_DIAS_CERRADOS));
    if (!$abiertos) {
        return '';
    }
    $rangos = [];
    $ini = $prev = $abiertos[0];
    foreach (array_slice($abiertos, 1) as $d) {
        if ($d === $prev + 1) {
            $prev = $d;
            continue;
        }
        $rangos[] = [$ini, $prev];
        $ini = $prev = $d;
    }
    $rangos[] = [$ini, $prev];
    $partes = [];
    foreach ($rangos as [$a, $b]) {
        $partes[] = $a === $b ? $nombres[$a] : $nombres[$a] . ($b === $a + 1 ? ' y ' : ' a ') . $nombres[$b];
    }
    return implode(', ', $partes);
}

/** Días cerrados en plural, p. ej. "domingos". */
function turnos_cerrado_texto(): string
{
    $nombres = [1 => 'lunes', 2 => 'martes', 3 => 'miércoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sábados', 7 => 'domingos'];
    $l = [];
    foreach (TURNOS_DIAS_CERRADOS as $d) {
        $l[] = $nombres[$d];
    }
    if (count($l) > 1) {
        $ult = array_pop($l);
        return implode(', ', $l) . ' y ' . $ult;
    }
    return $l[0] ?? '';
}

/** Franjas de horario, p. ej. "10:30 a 20:30" (varias: "10:30 a 13:00 y 14:30 a 20:00"). */
function turnos_horas_texto(): string
{
    $p = [];
    foreach (TURNOS_BLOQUES as $b) {
        $p[] = $b[0] . ' a ' . $b[1];
    }
    return implode(' y ', $p);
}

/** Horario completo para mostrar, p. ej. "Lun a Sáb · 10:30 a 20:30". */
function turnos_horario_texto(): string
{
    return turnos_dias_texto() . ' · ' . turnos_horas_texto();
}

/* =============================== Lógica pura =============================== */

function turnos_ts(string $fecha, string $hora): int
{
    $dt = new DateTimeImmutable($fecha . ' ' . $hora, new DateTimeZone(TURNOS_TZ));
    return $dt->getTimestamp();
}

function turnos_fecha_valida(string $fecha): bool
{
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $fecha, $m)) {
        return false;
    }
    return checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
}

function turnos_hora_valida(string $hora): bool
{
    return (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $hora);
}

/** ID entero positivo estricto; devuelve int o null. */
function turnos_id($valor): ?int
{
    if (is_int($valor)) {
        return $valor > 0 ? $valor : null;
    }
    if (!is_string($valor) || !ctype_digit($valor) || strlen($valor) > 10) {
        return null;
    }
    $n = (int) $valor;
    return $n > 0 ? $n : null;
}

/** Valida solo el día (formato, cerrado, pasado, máximo). Devuelve código de error o null. */
function turnos_validar_dia(string $fecha, int $ahora): ?string
{
    if (!turnos_fecha_valida($fecha)) {
        return 'datos_invalidos';
    }
    $hoy = date('Y-m-d', $ahora);
    if ($fecha < $hoy) {
        return 'fecha_pasada';
    }
    $dow = (int) date('N', turnos_ts($fecha, '12:00'));
    if (in_array($dow, TURNOS_DIAS_CERRADOS, true)) {
        return 'dia_cerrado';
    }
    $maximo = date('Y-m-d', $ahora + TURNOS_ANTICIPACION_MAX_DIAS * 86400);
    if ($fecha > $maximo) {
        return 'anticipacion_maxima';
    }
    return null;
}

/**
 * Validación completa de fecha/hora/duración. Es LA ÚNICA fuente de verdad: la usan
 * tanto la grilla de disponibilidad como el controller al crear el turno.
 */
function turnos_validar_horario(string $fecha, string $hora, int $duracionMin, int $ahora): ?string
{
    if (!turnos_fecha_valida($fecha) || !turnos_hora_valida($hora) || $duracionMin <= 0 || $duracionMin > 480) {
        return 'datos_invalidos';
    }

    $errDia = turnos_validar_dia($fecha, $ahora);
    if ($errDia !== null) {
        return $errDia;
    }

    $inicio = turnos_ts($fecha, $hora);
    $fin = $inicio + $duracionMin * 60;

    if ($inicio < $ahora) {
        return 'fecha_pasada';
    }
    if ($inicio < $ahora + TURNOS_ANTICIPACION_MIN_MIN * 60) {
        return 'anticipacion_minima';
    }

    foreach (TURNOS_BLOQUES as $bloque) {
        $iniBloque = turnos_ts($fecha, $bloque[0]);
        $finBloque = turnos_ts($fecha, $bloque[1]);
        if ($inicio >= $iniBloque && $fin <= $finBloque) {
            if ((($inicio - $iniBloque) / 60) % TURNOS_SLOT_MIN !== 0) {
                return 'horario_invalido'; // no alineado a la grilla
            }
            return null;
        }
    }
    return 'horario_invalido';
}

function turnos_solapan(int $iniA, int $finA, int $iniB, int $finB): bool
{
    return $iniA < $finB && $finA > $iniB;
}

/**
 * Horarios libres (H:i) para un día. $ocupados = [['hora' => 'HH:MM[:SS]', 'duracion_min' => n], ...]
 */
function turnos_slots_disponibles(string $fecha, int $duracionMin, array $ocupados, int $ahora): array
{
    $slots = [];
    if (turnos_validar_dia($fecha, $ahora) !== null || $duracionMin <= 0) {
        return $slots;
    }
    foreach (TURNOS_BLOQUES as $bloque) {
        $iniBloque = turnos_ts($fecha, $bloque[0]);
        $finBloque = turnos_ts($fecha, $bloque[1]);
        for ($t = $iniBloque; $t + $duracionMin * 60 <= $finBloque; $t += TURNOS_SLOT_MIN * 60) {
            $hora = date('H:i', $t);
            if (turnos_validar_horario($fecha, $hora, $duracionMin, $ahora) !== null) {
                continue;
            }
            $libre = true;
            foreach ($ocupados as $o) {
                $iniO = turnos_ts($fecha, substr((string) $o['hora'], 0, 5));
                $finO = $iniO + ((int) $o['duracion_min']) * 60;
                if (turnos_solapan($t, $t + $duracionMin * 60, $iniO, $finO)) {
                    $libre = false;
                    break;
                }
            }
            if ($libre) {
                $slots[] = $hora;
            }
        }
    }
    return $slots;
}

/* =============================== Mensajes =============================== */

function turnos_mensajes_error(): array
{
    $horasCancel = TURNOS_CANCEL_MIN_HORAS;
    return [
        'campos_vacios' => 'Completá todos los datos para reservar tu turno.',
        'datos_invalidos' => 'Los datos del turno no son válidos. Probá elegir de nuevo sede, barbero, servicio y horario.',
        'fecha_pasada' => 'No podés reservar un horario que ya pasó.',
        'dia_cerrado' => 'La barbería permanece cerrada los ' . turnos_cerrado_texto() . '. Atendemos ' . turnos_horario_texto() . '.',
        'horario_invalido' => 'Ese horario está fuera del horario de atención (' . turnos_horario_texto() . '). Elegí uno de la lista.',
        'horario_ocupado' => 'Ese horario ya fue reservado. Elegí otro disponible.',
        'anticipacion_minima' => 'Los turnos se reservan con al menos ' . TURNOS_ANTICIPACION_MIN_MIN . ' minutos de anticipación.',
        'anticipacion_maxima' => 'Solo podés reservar con hasta ' . TURNOS_ANTICIPACION_MAX_DIAS . ' días de anticipación.',
        'usuario_turno_superpuesto' => 'Ya tenés un turno activo que se superpone con ese horario.',
        'usuario_turno_mismo_dia' => 'Ya tenés un turno reservado para ese día. Podés reservar un solo turno por día.',
        'limite_semanal' => 'Alcanzaste el máximo de ' . TURNOS_MAX_SEMANA . ' turnos por semana. Contactá a la barbería o probá la semana próxima.',
        'limite_canasta' => 'Ya tenés ' . TURNOS_MAX_CANASTA . ' reservas esperando confirmación de la barbería. Cuando las revisemos vas a poder reservar más.',
        'usuario_bloqueado_ausencias' => 'Por ausencias a turnos anteriores tu cuenta no puede reservar por el momento. Contactá a la barbería si fue un error.',
        'demasiados_intentos' => 'Hiciste demasiados intentos en poco tiempo. Esperá un rato y volvé a probar.',
        'observaciones_largas' => 'Las observaciones son demasiado largas (máximo ' . TURNOS_OBS_MAX . ' caracteres).',
        'cancelacion_tardia' => 'Solo podés cancelar con al menos ' . $horasCancel . ' horas de anticipación. Contactá a la barbería.',
        'limite_cancelaciones' => 'Cancelaste muchos turnos en pocos días. Para cancelar este contactá a la barbería.',
        'turno_inexistente' => 'No encontramos ese turno.',
        'turno_no_cancelable' => 'Ese turno ya no se puede cancelar.',
        'db_error' => 'Ocurrió un error al procesar la operación. Intentá nuevamente en unos minutos.',
    ];
}

/** Devuelve el texto amable para un código, o null si el código no es conocido (evita reflejar texto arbitrario). */
function turnos_mensaje_error(?string $codigo): ?string
{
    $m = turnos_mensajes_error();
    return ($codigo !== null && isset($m[$codigo])) ? $m[$codigo] : null;
}

/** URL de redirección a reservar_turno.php conservando la selección. $base = prefijo relativo. */
function turnos_url_reserva(string $base, string $error, array $sel = []): string
{
    $q = ['error' => $error];
    foreach (['sede_id', 'barbero_id', 'servicio_id', 'fecha'] as $k) {
        if (!empty($sel[$k])) {
            $q[$k] = $sel[$k];
        }
    }
    return $base . 'reservar_turno.php?' . http_build_query($q);
}

/* =============================== Acceso a BD =============================== */

function turnos_ip_cliente(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (TURNOS_CONFIAR_PROXY && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $primera = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        if (filter_var($primera, FILTER_VALIDATE_IP)) {
            $ip = $primera;
        }
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

function turnos_tabla_faltante(PDOException $e): bool
{
    return $e->getCode() === '42S02';
}

/** Devuelve 'demasiados_intentos' si la IP o la cuenta superaron el límite por hora. Fail-open si falta la tabla. */
function turnos_rate_limit(PDO $conn, string $ip, int $usuarioId, string $accion = 'crear'): ?string
{
    try {
        $s = $conn->prepare("SELECT COUNT(*) FROM turnos_intentos
                             WHERE ip = :ip AND accion = :a AND creado_en >= (NOW() - INTERVAL 1 HOUR)");
        $s->execute([':ip' => $ip, ':a' => $accion]);
        if ((int) $s->fetchColumn() >= TURNOS_IP_MAX_HORA) {
            return 'demasiados_intentos';
        }
        $s = $conn->prepare("SELECT COUNT(*) FROM turnos_intentos
                             WHERE usuario_id = :u AND accion = :a AND creado_en >= (NOW() - INTERVAL 1 HOUR)");
        $s->execute([':u' => $usuarioId, ':a' => $accion]);
        if ((int) $s->fetchColumn() >= TURNOS_USUARIO_MAX_HORA) {
            return 'demasiados_intentos';
        }
    } catch (PDOException $e) {
        if (!turnos_tabla_faltante($e)) {
            throw $e;
        }
        error_log('Haedo Barber: falta la tabla turnos_intentos (correr migrations_003_turnos_antiabuso.sql)');
    }
    return null;
}

function turnos_log_intento(PDO $conn, string $ip, int $usuarioId, string $accion, bool $exito = false): ?int
{
    try {
        $s = $conn->prepare("INSERT INTO turnos_intentos (ip, usuario_id, accion, exito) VALUES (:ip, :u, :a, :e)");
        $s->execute([':ip' => $ip, ':u' => $usuarioId, ':a' => $accion, ':e' => $exito ? 1 : 0]);
        if (random_int(1, 50) === 1) { // limpieza ocasional
            $conn->exec("DELETE FROM turnos_intentos WHERE creado_en < (NOW() - INTERVAL 14 DAY)");
        }
        return (int) $conn->lastInsertId();
    } catch (PDOException $e) {
        if (!turnos_tabla_faltante($e)) {
            throw $e;
        }
        return null;
    }
}

function turnos_marcar_exito(PDO $conn, ?int $intentoId): void
{
    if ($intentoId === null) {
        return;
    }
    try {
        $conn->prepare("UPDATE turnos_intentos SET exito = 1 WHERE id = :id")->execute([':id' => $intentoId]);
    } catch (PDOException $e) {
        // no crítico
    }
}

/** Restricciones de cuenta que impiden reservar: hoy solo el bloqueo por ausencias (una cuenta nueva puede reservar al instante). */
function turnos_restriccion_usuario(PDO $conn, int $usuarioId): ?string
{
    $s = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE id = :id");
    $s->execute([':id' => $usuarioId]);
    if ((int) $s->fetchColumn() === 0) {
        return 'datos_invalidos';
    }

    $s = $conn->prepare("SELECT COUNT(*) FROM turnos
                         WHERE usuario_id = :id AND estado = 'ausente'
                         AND fecha >= (CURDATE() - INTERVAL " . (int) TURNOS_NOSHOW_DIAS . " DAY)");
    $s->execute([':id' => $usuarioId]);
    if ((int) $s->fetchColumn() >= TURNOS_NOSHOW_MAX) {
        return 'usuario_bloqueado_ausencias';
    }
    return null;
}

/**
 * Semana calendario (lunes 00:00 a lunes siguiente 00:00, hora de Buenos Aires) que contiene $ts.
 * Devuelve ['YYYY-mm-dd HH:ii:ss' inicio, 'YYYY-mm-dd HH:ii:ss' fin exclusivo].
 */
function turnos_semana_rango(int $ts): array
{
    $tz = new DateTimeZone(TURNOS_TZ);
    $d = (new DateTimeImmutable('@' . $ts))->setTimezone($tz)->setTime(0, 0, 0);
    $lunes = $d->modify('-' . ((int) $d->format('N') - 1) . ' days');
    return [$lunes->format('Y-m-d H:i:s'), $lunes->modify('+7 days')->format('Y-m-d H:i:s')];
}

/**
 * Decisión pura según los conteos: ¿nace confirmado, va a la canasta o se rechaza?
 * $creadasSemana = turnos no cancelados que la cuenta ya creó esta semana (sin contar el nuevo).
 * $enCanasta = reservas de la cuenta esperando confirmación manual (pendiente + requiere_confirmacion).
 * Devuelve ['estado' => 'confirmado'|'pendiente', 'requiere_confirmacion' => 0|1, 'error' => ?string].
 */
function turnos_decidir_estado(int $creadasSemana, int $enCanasta): array
{
    if ($creadasSemana < TURNOS_MAX_SEMANA) {
        return ['estado' => 'confirmado', 'requiere_confirmacion' => 0, 'error' => null];
    }
    if (TURNOS_CANASTA_POLITICA === 'rechazar') {
        return ['estado' => 'pendiente', 'requiere_confirmacion' => 0, 'error' => 'limite_semanal'];
    }
    if ($enCanasta >= TURNOS_MAX_CANASTA) {
        return ['estado' => 'pendiente', 'requiere_confirmacion' => 1, 'error' => 'limite_canasta'];
    }
    return ['estado' => 'pendiente', 'requiere_confirmacion' => 1, 'error' => null];
}

/** Conteos de la cuenta para decidir el estado. Llamar dentro de la transacción con el usuario bloqueado. */
function turnos_conteos_usuario(PDO $conn, int $usuarioId, int $ahora): array
{
    [$ini, $fin] = turnos_semana_rango($ahora);
    $s = $conn->prepare("SELECT COUNT(*) FROM turnos
                         WHERE usuario_id = :id AND estado <> 'cancelado'
                         AND creado_en >= :ini AND creado_en < :fin");
    $s->execute([':id' => $usuarioId, ':ini' => $ini, ':fin' => $fin]);
    $semana = (int) $s->fetchColumn();

    $s = $conn->prepare("SELECT COUNT(*) FROM turnos
                         WHERE usuario_id = :id AND estado = 'pendiente' AND requiere_confirmacion = 1");
    $s->execute([':id' => $usuarioId]);
    return [$semana, (int) $s->fetchColumn()];
}

/** Límites por usuario al crear un turno (llamar dentro de la transacción con el usuario bloqueado). */
function turnos_validar_limites_usuario(PDO $conn, int $usuarioId, string $fecha, int $inicio, int $fin): ?string
{
    // Mismo día / solapados
    $s = $conn->prepare("SELECT t.hora, sv.duracion_min FROM turnos t
                         INNER JOIN servicios sv ON sv.id = t.servicio_id
                         WHERE t.usuario_id = :id AND t.fecha = :f AND t.estado IN ('pendiente','confirmado')");
    $s->execute([':id' => $usuarioId, ':f' => $fecha]);
    $delDia = $s->fetchAll(PDO::FETCH_ASSOC);

    foreach ($delDia as $t) {
        $ini = turnos_ts($fecha, substr($t['hora'], 0, 5));
        if (turnos_solapan($inicio, $fin, $ini, $ini + ((int) $t['duracion_min']) * 60)) {
            return 'usuario_turno_superpuesto';
        }
    }
    if (count($delDia) >= TURNOS_MAX_POR_DIA) {
        return 'usuario_turno_mismo_dia';
    }
    return null;
}

/** Turnos ocupados (hora + duración) de un barbero en una fecha. */
function turnos_ocupados_barbero(PDO $conn, int $barberoId, string $fecha): array
{
    $s = $conn->prepare("SELECT t.hora, sv.duracion_min FROM turnos t
                         INNER JOIN servicios sv ON t.servicio_id = sv.id
                         WHERE t.barbero_id = :b AND t.fecha = :f AND t.estado IN ('pendiente','confirmado')");
    $s->execute([':b' => $barberoId, ':f' => $fecha]);
    return $s->fetchAll(PDO::FETCH_ASSOC);
}

/** Cantidad de cancelaciones del usuario en la ventana configurada. */
function turnos_cancelaciones_recientes(PDO $conn, int $usuarioId): int
{
    try {
        $s = $conn->prepare("SELECT COUNT(*) FROM turnos_intentos
                             WHERE usuario_id = :u AND accion = 'cancelar' AND exito = 1
                             AND creado_en >= (NOW() - INTERVAL " . (int) TURNOS_CANCEL_VENTANA_DIAS . " DAY)");
        $s->execute([':u' => $usuarioId]);
        return (int) $s->fetchColumn();
    } catch (PDOException $e) {
        if (!turnos_tabla_faltante($e)) {
            throw $e;
        }
        return 0;
    }
}
