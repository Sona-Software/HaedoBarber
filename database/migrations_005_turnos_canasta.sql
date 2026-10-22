-- Migración 005: "canasta" de confirmación manual de turnos.
-- Los primeros TURNOS_MAX_SEMANA (3) turnos de una cuenta por semana nacen 'confirmado'; los siguientes
-- nacen 'pendiente' con requiere_confirmacion = 1 y el admin debe aprobarlos o rechazarlos.
-- Idempotente: se puede correr más de una vez (compatible con MySQL 5.7+ y MariaDB 10.x).

-- 1) Marca de revisión manual
SET @existe := (SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'turnos' AND COLUMN_NAME = 'requiere_confirmacion');
SET @sql := IF(@existe = 0,
    'ALTER TABLE turnos ADD COLUMN requiere_confirmacion TINYINT(1) NOT NULL DEFAULT 0 AFTER estado',
    'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 2) Índice para contar turnos por cuenta y semana de creación
SET @existe := (SELECT COUNT(*) FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'turnos' AND INDEX_NAME = 'idx_turnos_usuario_creado');
SET @sql := IF(@existe = 0,
    'CREATE INDEX idx_turnos_usuario_creado ON turnos(usuario_id, creado_en)',
    'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 3) Índice para listar la canasta rápido
SET @existe := (SELECT COUNT(*) FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'turnos' AND INDEX_NAME = 'idx_turnos_canasta');
SET @sql := IF(@existe = 0,
    'CREATE INDEX idx_turnos_canasta ON turnos(requiere_confirmacion, estado)',
    'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
