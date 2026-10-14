CREATE DATABASE IF NOT EXISTS haedobarber_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE haedobarber_db;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS turnos;
DROP TABLE IF EXISTS barbero_fotos;
DROP TABLE IF EXISTS sede_galeria;
DROP TABLE IF EXISTS barberos;
DROP TABLE IF EXISTS servicios;
DROP TABLE IF EXISTS sedes;
DROP TABLE IF EXISTS contactos;
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
    ultimo_acceso DATETIME NULL,
    remember_token_hash VARCHAR(255) NULL,
    remember_token_expira DATETIME NULL
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
    observaciones TEXT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

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
CREATE UNIQUE INDEX uq_turno_barbero_fecha_hora
ON turnos(barbero_id, fecha, hora);

INSERT INTO usuarios
(nombre, apellido, telefono, email, password, rol)
VALUES
('Admin', 'Sistema', '1111111111', 'admin@haedobarber.test', '$2y$12$7V/14xaIm/YnnrvzRosINu4/xk49vcvBDPdG0DIErWe0TtdqpYgLq', 'admin'),
('Usuario', 'General', '2222222222', 'usuario@haedobarber.test', '$2y$12$0LubMCbipcMq2eLuIpZNluhflVon5m/yppWssoNwE9x1f7.QSQyBC', 'general');

INSERT INTO servicios
(nombre, descripcion, precio, duracion_min, activo)
VALUES
('Corte Completo', 'Asesoramiento, corte con máquina/tijera y peinado con cera.', 17000, 30, 1),
('Corte + Barba', 'Corte completo + alineación, rebaje y perfilado de barba.', 22000, 60, 1),
('Solo Barba', 'Alineación, rebaje y perfilado.', 12000, 30, 1),
('Global', 'Decoloración global y matizado, incluye corte.', 55000, 90, 1),
('Mechas', 'Decoloración con gorro para mechas y matizado, incluye corte.', 50000, 90, 1),
('Perfilado cejas / Diseño & Freestyle', 'Diseño personalizado y perfilado.', 0, 30, 1);
