-- Migración 003: protecciones anti-abuso de turnos.
-- Para bases existentes (en instalaciones nuevas ya está incluido en schema.sql).

-- 1) Registro de intentos para rate limiting por IP / cuenta y conteo de cancelaciones.
CREATE TABLE IF NOT EXISTS turnos_intentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45) NOT NULL,
    usuario_id INT NULL,
    accion VARCHAR(20) NOT NULL,          -- 'crear' | 'cancelar'
    exito TINYINT(1) NOT NULL DEFAULT 0,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_intentos_ip (ip, accion, creado_en),
    INDEX idx_intentos_usuario (usuario_id, accion, creado_en)
);

-- 2) El índice único anterior incluía turnos cancelados, por lo que un horario cancelado
--    no se podía volver a reservar (error de base de datos). Se reemplaza por uno que solo
--    considera turnos activos (pendiente/confirmado). Requiere MySQL 5.7+ / MariaDB 10.2+.
ALTER TABLE turnos DROP INDEX uq_turno_barbero_fecha_hora;

ALTER TABLE turnos
    ADD COLUMN hora_activa TIME
        GENERATED ALWAYS AS (IF(estado IN ('pendiente','confirmado'), hora, NULL)) VIRTUAL;

CREATE UNIQUE INDEX uq_turno_activo_barbero_fecha_hora
ON turnos(barbero_id, fecha, hora_activa);
