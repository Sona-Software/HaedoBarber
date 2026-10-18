-- Migración 002: seguridad (fuerza bruta + "recordarme" con selector/validator).
-- Ejecutar UNA vez sobre la base existente (haedobarber_db):
--   mysql -u root -p <nombre_base> < database/migrations_002_security.sql
-- Es idempotente: se puede volver a correr sin romper nada.

-- 1) Tabla de intentos (login, registro, contacto). Guarda IP y hash SHA-256 del email, nunca el email.
CREATE TABLE IF NOT EXISTS intentos_acceso (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(20) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    ident CHAR(64) NOT NULL DEFAULT '',
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_intentos_tipo_ip (tipo, ip, creado_en),
    INDEX idx_intentos_tipo_ident (tipo, ident, creado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) Columna remember_selector en usuarios (si no existe).
SET @existe := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'remember_selector'
);
SET @sql := IF(@existe = 0,
    'ALTER TABLE usuarios ADD COLUMN remember_selector CHAR(18) NULL AFTER ultimo_acceso',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3) Índice único por selector (si no existe).
SET @existe := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND INDEX_NAME = 'uq_usuarios_remember_selector'
);
SET @sql := IF(@existe = 0,
    'ALTER TABLE usuarios ADD UNIQUE INDEX uq_usuarios_remember_selector (remember_selector)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4) Invalidar los tokens "recordarme" del esquema anterior (id suelto + token).
UPDATE usuarios
SET remember_selector = NULL, remember_token_hash = NULL, remember_token_expira = NULL;

-- 5) Eliminar las cuentas de prueba con contraseña conocida que traía el schema viejo
--    (sólo si no tienen turnos asociados; si tienen, borrarlas/cambiarles la clave a mano).
DELETE u FROM usuarios u
LEFT JOIN turnos t ON t.usuario_id = u.id
WHERE u.email IN ('admin@haedobarber.test', 'usuario@haedobarber.test')
  AND t.id IS NULL;

-- Luego creá tu administrador real desde la consola:
--   php scripts/crear_admin.php tu@correo.com "UnaClaveLarga123"
