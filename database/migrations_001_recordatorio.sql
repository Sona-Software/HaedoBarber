-- Para bases existentes (no ejecutar en instalaciones nuevas con schema.sql actualizado).
ALTER TABLE turnos ADD COLUMN recordatorio_enviado TINYINT(1) NOT NULL DEFAULT 0 AFTER observaciones;
