-- =============================================
-- SISTEMA DE GESTIÓN DE NEWSLETTERS
-- Modelo físico MySQL compatible con phpMyAdmin
-- =============================================

CREATE DATABASE IF NOT EXISTS newsletter_system
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE newsletter_system;

-- =============================================
-- ACCESO Y SEGURIDAD
-- =============================================

CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion VARCHAR(255)
) ENGINE=InnoDB;


CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rol_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    intentos_fallidos INT DEFAULT 0,
    bloqueo_hasta DATETIME NULL,
    activo BOOLEAN DEFAULT TRUE,
    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_usuarios_roles
        FOREIGN KEY (rol_id)
        REFERENCES roles(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;


CREATE TABLE logs_login (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    email_ingresado VARCHAR(150),
    ip VARCHAR(45),
    exitoso BOOLEAN,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_logs_login_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB;


-- =============================================
-- CONTENIDO
-- =============================================

CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT
) ENGINE=InnoDB;


CREATE TABLE newsletters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    autor_id INT NOT NULL,
    categoria_id INT NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    contenido TEXT NOT NULL,

    estado ENUM(
        'borrador',
        'publicado',
        'archivado'
    ) NOT NULL DEFAULT 'borrador',

    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_newsletters_autor
        FOREIGN KEY (autor_id)
        REFERENCES usuarios(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_newsletters_categoria
        FOREIGN KEY (categoria_id)
        REFERENCES categorias(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;


CREATE TABLE etiquetas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;


CREATE TABLE newsletter_etiquetas (
    newsletter_id INT NOT NULL,
    etiqueta_id INT NOT NULL,

    PRIMARY KEY (newsletter_id, etiqueta_id),

    CONSTRAINT fk_newsletter_etiquetas_newsletter
        FOREIGN KEY (newsletter_id)
        REFERENCES newsletters(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_newsletter_etiquetas_etiqueta
        FOREIGN KEY (etiqueta_id)
        REFERENCES etiquetas(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;


-- =============================================
-- PLANTILLAS Y CAMPAÑAS
-- =============================================

CREATE TABLE plantillas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    html_contenido TEXT NOT NULL,
    creado_por INT NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_plantillas_usuario
        FOREIGN KEY (creado_por)
        REFERENCES usuarios(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;


CREATE TABLE campañas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    newsletter_id INT NOT NULL,
    plantilla_id INT NULL,
    creado_por INT NOT NULL,
    asunto VARCHAR(200) NOT NULL,
    fecha_programada DATETIME NULL,
    fecha_envio_real DATETIME NULL,

    estado ENUM(
        'programada',
        'enviando',
        'enviada',
        'cancelada'
    ) NOT NULL DEFAULT 'programada',

    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_campanas_newsletter
        FOREIGN KEY (newsletter_id)
        REFERENCES newsletters(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_campanas_plantilla
        FOREIGN KEY (plantilla_id)
        REFERENCES plantillas(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    CONSTRAINT fk_campanas_usuario
        FOREIGN KEY (creado_por)
        REFERENCES usuarios(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;


-- =============================================
-- SUSCRIPTORES
-- =============================================

CREATE TABLE suscriptores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NULL,

    email VARCHAR(150) NOT NULL UNIQUE,

    estado ENUM(
        'pendiente',
        'activo',
        'inactivo',
        'baja'
    ) NOT NULL DEFAULT 'pendiente',

    token_verificacion VARCHAR(255) NULL,
    token_baja VARCHAR(255) NULL,

    fecha_suscripcion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_baja DATETIME NULL
) ENGINE=InnoDB;


CREATE TABLE suscriptor_categorias (
    suscriptor_id INT NOT NULL,
    categoria_id INT NOT NULL,

    PRIMARY KEY (suscriptor_id, categoria_id),

    CONSTRAINT fk_suscriptor_categoria_suscriptor
        FOREIGN KEY (suscriptor_id)
        REFERENCES suscriptores(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_suscriptor_categoria_categoria
        FOREIGN KEY (categoria_id)
        REFERENCES categorias(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;


-- =============================================
-- ENVÍOS Y TRACKING
-- =============================================

CREATE TABLE envios (
    id INT AUTO_INCREMENT PRIMARY KEY,

    campaña_id INT NOT NULL,
    suscriptor_id INT NOT NULL,

    enviado_en DATETIME DEFAULT CURRENT_TIMESTAMP,

    abierto BOOLEAN DEFAULT FALSE,
    abierto_en DATETIME NULL,

    error VARCHAR(255) NULL,

    CONSTRAINT uq_envio_unico
        UNIQUE (campaña_id, suscriptor_id),

    CONSTRAINT fk_envios_campana
        FOREIGN KEY (campaña_id)
        REFERENCES campañas(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_envios_suscriptor
        FOREIGN KEY (suscriptor_id)
        REFERENCES suscriptores(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;


CREATE TABLE clics (
    id INT AUTO_INCREMENT PRIMARY KEY,

    envio_id INT NOT NULL,
    url VARCHAR(500) NOT NULL,

    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_clics_envio
        FOREIGN KEY (envio_id)
        REFERENCES envios(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;


-- =============================================
-- AUDITORÍA
-- =============================================

CREATE TABLE auditoria (
    id INT AUTO_INCREMENT PRIMARY KEY,

    usuario_id INT NULL,

    accion VARCHAR(100) NOT NULL,
    tabla_afectada VARCHAR(100) NOT NULL,
    registro_id INT NULL,

    valores_anteriores JSON NULL,
    valores_nuevos JSON NULL,

    ip VARCHAR(45) NULL,

    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_auditoria_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB;


-- =============================================
-- ÍNDICES EXTRA PARA RENDIMIENTO
-- =============================================

CREATE INDEX idx_usuarios_email
ON usuarios(email);

CREATE INDEX idx_newsletters_estado
ON newsletters(estado);

CREATE INDEX idx_campanas_estado
ON campañas(estado);

CREATE INDEX idx_suscriptores_estado
ON suscriptores(estado);

CREATE INDEX idx_envios_abierto
ON envios(abierto);

CREATE INDEX idx_logs_login_fecha
ON logs_login(fecha);

CREATE INDEX idx_auditoria_fecha
ON auditoria(fecha);


-- =============================================
-- DATOS INICIALES
-- =============================================

INSERT INTO roles (nombre, descripcion)
VALUES
('admin', 'Administrador del sistema'),
('editor', 'Editor de newsletters'),
('lector', 'Usuario con acceso de lectura');

INSERT INTO categorias (nombre, descripcion)
VALUES
(
    'Ciberseguridad',
    'Noticias y análisis de seguridad informática'
),
(
    'Linux',
    'Administración, servidores y software libre'
),
(
    'Inteligencia Artificial',
    'IA aplicada a tecnología y desarrollo'
);

INSERT INTO usuarios
(
    rol_id,
    nombre,
    email,
    password_hash
)
VALUES
(
    2,
    'Editor Principal',
    'editor@newsletter.com',
    '$2y$10$qVYyHYfoIBbtdFU8yUPs5.XWPzUz7Y.qZzkFJ9w5ZAjvE/Bu5DqJa'
);

INSERT INTO newsletters
(
    autor_id,
    categoria_id,
    titulo,
    contenido,
    estado
)
VALUES
(
    1,
    1,
    'Análisis de vulnerabilidades destacadas de la semana',
    'Durante esta semana se publicaron múltiples vulnerabilidades críticas que afectan sistemas empresariales. En esta edición analizamos su impacto, posibles mitigaciones y recomendaciones para administradores.',
    'publicado'
);

INSERT INTO newsletters
(
    autor_id,
    categoria_id,
    titulo,
    contenido,
    estado
)
VALUES
(
    1,
    2,
    'Hardening básico para servidores Linux',
    'Repasamos prácticas esenciales para asegurar servidores Linux, incluyendo configuración de SSH, gestión de usuarios, firewall y actualizaciones automáticas.',
    'publicado'
);

INSERT INTO newsletters
(
    autor_id,
    categoria_id,
    titulo,
    contenido,
    estado
)
VALUES
(
    1,
    3,
    'Cómo la IA está transformando la ciberseguridad',
    'La inteligencia artificial se está utilizando tanto para defensa como para detección de amenazas. Exploramos herramientas modernas y casos de uso reales en empresas.',
    'publicado'
);

INSERT INTO etiquetas (nombre)
VALUES
('CVE'),
('Linux'),
('IA'),
('Seguridad'),
('Servidores');

INSERT INTO newsletter_etiquetas
(newsletter_id, etiqueta_id)
VALUES
(1,1),
(1,4),

(2,2),
(2,5),

(3,3),
(3,4);

INSERT INTO suscriptores
(nombre,email,estado)
VALUES
(
    'Juan Pérez',
    'juan@example.com',
    'activo'
),
(
    'Ana Gómez',
    'ana@example.com',
    'activo'
),
(
    'Carlos Ruiz',
    'carlos@example.com',
    'activo'
);
