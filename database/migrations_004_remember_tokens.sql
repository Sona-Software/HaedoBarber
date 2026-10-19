-- Migración 004: "recordarme" con varios dispositivos por usuario.
-- Ejecutar UNA vez sobre la base existente:
--   mysql -u root -p haedobarber_db < database/migrations_004_remember_tokens.sql
-- Es idempotente. (Si no se corre, la app crea la tabla sola en el primer uso.)

CREATE TABLE IF NOT EXISTS remember_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    selector CHAR(18) NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expira DATETIME NOT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultimo_uso DATETIME NULL,
    UNIQUE KEY uq_remember_selector (selector),
    INDEX idx_remember_usuario (usuario_id, expira),
    CONSTRAINT fk_remember_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Las columnas usuarios.remember_* (un solo token por usuario) ya no se usan.
-- Los usuarios con "recordarme" activo deberán iniciar sesión una vez más.
SET @existe := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND INDEX_NAME = 'uq_usuarios_remember_selector'
);
SET @sql := IF(@existe > 0, 'ALTER TABLE usuarios DROP INDEX uq_usuarios_remember_selector', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'remember_selector'
);
SET @sql := IF(@existe > 0,
    'ALTER TABLE usuarios DROP COLUMN remember_selector, DROP COLUMN remember_token_hash, DROP COLUMN remember_token_expira',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
