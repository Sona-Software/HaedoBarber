CREATE DATABASE IF NOT EXISTS haedobarber_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE haedobarber_db;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS turnos_intentos;
DROP TABLE IF EXISTS turnos;
DROP TABLE IF EXISTS barbero_fotos;
DROP TABLE IF EXISTS sede_galeria;
DROP TABLE IF EXISTS barberos;
DROP TABLE IF EXISTS servicios;
DROP TABLE IF EXISTS sedes;
DROP TABLE IF EXISTS contactos;
DROP TABLE IF EXISTS intentos_acceso;
DROP TABLE IF EXISTS remember_tokens;
DROP TABLE IF EXISTS usuarios;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL,
    apellido VARCHAR(80) NOT NULL,
    telefono VARCHAR(30),
    email VARCHAR(120) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin','general') NOT NULL DEFAULT 'general',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso DATETIME NULL
);

-- "Recordar inicio de sesion": un token (selector + hash SHA-256 del validator) por dispositivo.
CREATE TABLE remember_tokens (
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
);

-- Intentos de login/registro/contacto para bloqueo temporal (fuerza bruta / spam).
CREATE TABLE intentos_acceso (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(20) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    ident CHAR(64) NOT NULL DEFAULT '',
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_intentos_tipo_ip (tipo, ip, creado_en),
    INDEX idx_intentos_tipo_ident (tipo, ident, creado_en)
);

CREATE TABLE contactos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    email VARCHAR(120) NOT NULL,
    asunto VARCHAR(150),
    mensaje TEXT NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE sedes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL,
    direccion VARCHAR(150) NOT NULL,
    mapa_embed TEXT NULL,
    foto VARCHAR(255) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE sede_galeria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sede_id INT NOT NULL,
    foto VARCHAR(255) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sede_galeria_sede
        FOREIGN KEY (sede_id) REFERENCES sedes(id)
        ON DELETE CASCADE
);

CREATE TABLE barberos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    especialidad VARCHAR(120),
    foto VARCHAR(255) NULL,
    sede_id INT NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_barberos_sede
        FOREIGN KEY (sede_id) REFERENCES sedes(id)
);

CREATE TABLE barbero_fotos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barbero_id INT NOT NULL,
    foto VARCHAR(255) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_barbero_fotos_barbero
        FOREIGN KEY (barbero_id) REFERENCES barberos(id)
        ON DELETE CASCADE
);

CREATE TABLE servicios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    duracion_min INT NOT NULL DEFAULT 30,
    activo TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE turnos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    sede_id INT NOT NULL,
    barbero_id INT NOT NULL,
    servicio_id INT NOT NULL,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    estado ENUM('pendiente','confirmado','cancelado','completado','ausente') NOT NULL DEFAULT 'pendiente',
    -- 1 = reserva de la "canasta": excedió el cupo semanal y el admin debe confirmarla o rechazarla.
    requiere_confirmacion TINYINT(1) NOT NULL DEFAULT 0,
    observaciones TEXT NULL,
    recordatorio_enviado TINYINT(1) NOT NULL DEFAULT 0,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    hora_activa TIME GENERATED ALWAYS AS (IF(estado IN ('pendiente','confirmado'), hora, NULL)) VIRTUAL,

    CONSTRAINT fk_turnos_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id),

    CONSTRAINT fk_turnos_sede
        FOREIGN KEY (sede_id) REFERENCES sedes(id),

    CONSTRAINT fk_turnos_barbero
        FOREIGN KEY (barbero_id) REFERENCES barberos(id),

    CONSTRAINT fk_turnos_servicio
        FOREIGN KEY (servicio_id) REFERENCES servicios(id)
);

CREATE INDEX idx_turnos_usuario ON turnos(usuario_id);
CREATE INDEX idx_turnos_sede ON turnos(sede_id);
CREATE INDEX idx_turnos_barbero ON turnos(barbero_id);
CREATE INDEX idx_turnos_servicio ON turnos(servicio_id);
CREATE INDEX idx_turnos_fecha ON turnos(fecha);
CREATE INDEX idx_turnos_usuario_creado ON turnos(usuario_id, creado_en);
CREATE INDEX idx_turnos_canasta ON turnos(requiere_confirmacion, estado);
-- Solo turnos activos ocupan el horario (un turno cancelado libera el slot).
CREATE UNIQUE INDEX uq_turno_activo_barbero_fecha_hora
ON turnos(barbero_id, fecha, hora_activa);

-- Registro de intentos de reserva/cancelación (rate limiting por IP y cuenta).
CREATE TABLE turnos_intentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45) NOT NULL,
    usuario_id INT NULL,
    accion VARCHAR(20) NOT NULL,
    exito TINYINT(1) NOT NULL DEFAULT 0,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_intentos_ip (ip, accion, creado_en),
    INDEX idx_intentos_usuario (usuario_id, accion, creado_en)
);

-- Instalación vacía: sin usuarios, sedes, barberos ni servicios. Ver README (primer uso).
