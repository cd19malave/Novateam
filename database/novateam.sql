-- ============================================================================
--  NOVATEAM / EDUNOVA — MySQL / MariaDB para phpMyAdmin
--  Charset: utf8mb4  |  Motor: InnoDB
--
--  Importar en phpMyAdmin:
--    1. Inicia XAMPP/WAMP (Apache + MySQL)
--    2. Abre http://localhost/phpmyadmin
--    3. Importar > elegir este archivo > Continuar
--
--  Usuarios de prueba (contraseña de todos: EduNova2026!)
--    admin@novateam.edu.co          administrador
--    jenniffer.yepes@colegio.edu.co profesor (matemáticas)
--    carlos.malave@colegio.edu.co   profesor (inglés)
--    ana.torres@colegio.edu.co      estudiante 4°
--    luis.rodriguez@colegio.edu.co  estudiante 4°
-- ============================================================================

CREATE DATABASE IF NOT EXISTS novateam_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE novateam_db;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP VIEW IF EXISTS vw_leaderboard;
DROP TABLE IF EXISTS mensajes_archivos;
DROP TABLE IF EXISTS mensajes;
DROP TABLE IF EXISTS archivos_adjuntos;
DROP TABLE IF EXISTS matriculas;
DROP TABLE IF EXISTS solicitudes_contacto;
DROP TABLE IF EXISTS intentos_acceso;
DROP TABLE IF EXISTS respuestas;
DROP TABLE IF EXISTS intentos;
DROP TABLE IF EXISTS ejercicios;
DROP TABLE IF EXISTS guias;
DROP TABLE IF EXISTS usuario_insignias;
DROP TABLE IF EXISTS insignias;
DROP TABLE IF EXISTS notificaciones;
DROP TABLE IF EXISTS usuarios;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE usuarios (
    id_usuario           INT AUTO_INCREMENT PRIMARY KEY,
    nombre               VARCHAR(120)        NOT NULL,
    correo               VARCHAR(150)        NOT NULL UNIQUE,
    contrasena_hash      VARCHAR(255)        NOT NULL,
    rol                  ENUM('estudiante','profesor','administrador') NOT NULL DEFAULT 'estudiante',
    grado                TINYINT             NULL COMMENT 'Solo estudiantes (1 a 5)',
    materia              ENUM('matematicas','ingles') NULL COMMENT 'Solo profesores',
    puntos               INT                 NOT NULL DEFAULT 0,
    ejercicios_resueltos INT                 NOT NULL DEFAULT 0,
    activo               TINYINT(1)          NOT NULL DEFAULT 1,
    ultimo_acceso        DATETIME            NULL,
    fecha_registro       DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    foto_perfil          VARCHAR(255)        NULL COMMENT 'Ruta imagen de perfil',
    marco_perfil         VARCHAR(50)         NULL COMMENT 'Estilo de marco: dorado,plateado,arcoiris,fuego,estrella,ninguno',
    bio                  TEXT                NULL COMMENT 'Biografia del usuario',
    fondo_perfil         VARCHAR(255)        NULL COMMENT 'Ruta imagen de fondo de perfil',
    tema_color           VARCHAR(20)         NOT NULL DEFAULT 'default' COMMENT 'Tema de color: default,oscuro,verde,rosa,purpura',
    remember_token       VARCHAR(64)         NULL COMMENT 'Token para recuperación de contraseña',
    token_expires        DATETIME            NULL COMMENT 'Expiración del token de recuperación',
    CONSTRAINT chk_grado CHECK (rol <> 'estudiante' OR (grado BETWEEN 1 AND 5)),
    CONSTRAINT chk_materia CHECK (rol <> 'profesor' OR materia IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE insignias (
    id_insignia       INT AUTO_INCREMENT PRIMARY KEY,
    codigo            VARCHAR(30)  NOT NULL UNIQUE,
    nombre            VARCHAR(100) NOT NULL,
    icono             VARCHAR(10)  NULL,
    puntos_requeridos INT          NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO insignias (codigo, nombre, icono, puntos_requeridos) VALUES
    ('mat_jr',   'Matemático Junior', '🧮', 30),
    ('eng_star', 'English Star',      '⭐', 50),
    ('genio',    'Genio Lógico',      '🧠', 100),
    ('maestro',  'Maestro de Retos',  '🏆', 200);

CREATE TABLE usuario_insignias (
    id_usuario     INT      NOT NULL,
    id_insignia    INT      NOT NULL,
    fecha_obtenida DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_usuario, id_insignia),
    CONSTRAINT fk_ui_usuario  FOREIGN KEY (id_usuario)  REFERENCES usuarios(id_usuario)  ON DELETE CASCADE,
    CONSTRAINT fk_ui_insignia FOREIGN KEY (id_insignia) REFERENCES insignias(id_insignia) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE matriculas (
    id_matricula   INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario     INT NOT NULL,
    materia        ENUM('matematicas','ingles') NOT NULL,
    fecha_matricula DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mat_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    UNIQUE KEY uq_usuario_materia (id_usuario, materia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE guias (
    id_guia           INT AUTO_INCREMENT PRIMARY KEY,
    titulo            VARCHAR(150) NOT NULL,
    categoria         ENUM('matematicas','ingles') NOT NULL,
    dificultad        ENUM('facil','medio','dificil') NOT NULL,
    modo_creacion     ENUM('ia','manual') NOT NULL DEFAULT 'manual',
    estado            ENUM('borrador','publicada') NOT NULL DEFAULT 'borrador',
    pdf_url           VARCHAR(255) NULL,
    id_profesor       INT NOT NULL,
    fecha_creacion    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_publicacion DATETIME NULL,
    fecha_expiracion  DATETIME NOT NULL,
    CONSTRAINT fk_guia_profesor FOREIGN KEY (id_profesor) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ejercicios (
    id_ejercicio       INT AUTO_INCREMENT PRIMARY KEY,
    id_guia            INT  NOT NULL,
    numero_orden       INT  NOT NULL,
    pregunta           TEXT NOT NULL,
    opcion_1           VARCHAR(255) NOT NULL,
    opcion_2           VARCHAR(255) NOT NULL,
    opcion_3           VARCHAR(255) NULL,
    opcion_4           VARCHAR(255) NULL,
    respuesta_correcta TINYINT NOT NULL,
    CONSTRAINT fk_ejercicio_guia FOREIGN KEY (id_guia) REFERENCES guias(id_guia) ON DELETE CASCADE,
    UNIQUE KEY uq_guia_orden (id_guia, numero_orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE archivos_adjuntos (
    id_archivo       INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario       INT NOT NULL,
    id_guia          INT NULL,
    id_ejercicio     INT NULL,
    nombre_original  VARCHAR(255) NOT NULL,
    nombre_guardado  VARCHAR(255) NOT NULL,
    tipo_mime        VARCHAR(100) NOT NULL,
    tamanio          INT NOT NULL DEFAULT 0,
    fecha_subida     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_arch_usuario   FOREIGN KEY (id_usuario)    REFERENCES usuarios(id_usuario)    ON DELETE CASCADE,
    CONSTRAINT fk_arch_guia      FOREIGN KEY (id_guia)       REFERENCES guias(id_guia)          ON DELETE SET NULL,
    CONSTRAINT fk_arch_ejercicio FOREIGN KEY (id_ejercicio)  REFERENCES ejercicios(id_ejercicio) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE intentos (
    id_intento       INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario       INT NOT NULL,
    id_guia          INT NOT NULL,
    completado       TINYINT(1) NOT NULL DEFAULT 0,
    puntaje          INT NOT NULL DEFAULT 0,
    total_ejercicios INT NOT NULL DEFAULT 0,
    fecha_inicio     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_completado DATETIME NULL,
    CONSTRAINT fk_intento_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    CONSTRAINT fk_intento_guia    FOREIGN KEY (id_guia)    REFERENCES guias(id_guia)       ON DELETE CASCADE,
    UNIQUE KEY uq_usuario_guia (id_usuario, id_guia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE respuestas (
    id_respuesta        INT AUTO_INCREMENT PRIMARY KEY,
    id_intento          INT NOT NULL,
    id_ejercicio        INT NOT NULL,
    opcion_seleccionada TINYINT NOT NULL,
    es_correcta         TINYINT(1) NOT NULL,
    puntos_obtenidos    INT NOT NULL DEFAULT 0,
    fecha_respuesta     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_respuesta_intento   FOREIGN KEY (id_intento)   REFERENCES intentos(id_intento)     ON DELETE CASCADE,
    CONSTRAINT fk_respuesta_ejercicio FOREIGN KEY (id_ejercicio) REFERENCES ejercicios(id_ejercicio) ON DELETE CASCADE,
    UNIQUE KEY uq_intento_ejercicio (id_intento, id_ejercicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notificaciones (
    id_notificacion INT AUTO_INCREMENT PRIMARY KEY,
    titulo          VARCHAR(150) NOT NULL,
    mensaje         TEXT NOT NULL,
    id_profesor     INT NULL,
    fecha_creacion  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notif_profesor FOREIGN KEY (id_profesor) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mensajes (
    id_mensaje   INT AUTO_INCREMENT PRIMARY KEY,
    id_emisor    INT NOT NULL,
    id_receptor  INT NOT NULL,
    id_guia      INT NULL,
    contenido    TEXT NOT NULL,
    leido        TINYINT(1) NOT NULL DEFAULT 0,
    fecha_envio  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_msg_emisor   FOREIGN KEY (id_emisor)   REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    CONSTRAINT fk_msg_receptor FOREIGN KEY (id_receptor) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    CONSTRAINT fk_msg_guia     FOREIGN KEY (id_guia)     REFERENCES guias(id_guia)       ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mensajes_archivos (
    id_mensaje INT NOT NULL,
    id_archivo INT NOT NULL,
    PRIMARY KEY (id_mensaje, id_archivo),
    CONSTRAINT fk_ma_mensaje FOREIGN KEY (id_mensaje) REFERENCES mensajes(id_mensaje)         ON DELETE CASCADE,
    CONSTRAINT fk_ma_archivo FOREIGN KEY (id_archivo) REFERENCES archivos_adjuntos(id_archivo) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE solicitudes_contacto (
    id_solicitud   INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario     INT NULL COMMENT 'Si el remitente esta logueado',
    nombre         VARCHAR(120) NOT NULL,
    correo         VARCHAR(150) NOT NULL,
    institucion    VARCHAR(150) NULL,
    mensaje        TEXT NOT NULL,
    estado         ENUM('pendiente','revisada','cerrada') NOT NULL DEFAULT 'pendiente',
    respondido_por INT NULL,
    respuesta      TEXT NULL,
    fecha          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sol_usuario     FOREIGN KEY (id_usuario)     REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
    CONSTRAINT fk_sol_respondido  FOREIGN KEY (respondido_por) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE intentos_acceso (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NULL,
    correo     VARCHAR(150) NOT NULL,
    ip         VARCHAR(45)  NOT NULL,
    exitoso    TINYINT(1)  NOT NULL DEFAULT 0,
    fecha      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_acceso_correo_fecha (correo, fecha),
    INDEX idx_acceso_ip_fecha (ip, fecha),
    CONSTRAINT fk_ia_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_guias_estado_categoria ON guias (estado, categoria, dificultad);
CREATE INDEX idx_guias_profesor         ON guias (id_profesor);
CREATE INDEX idx_intentos_usuario       ON intentos (id_usuario);
CREATE INDEX idx_usuarios_rol           ON usuarios (rol);
CREATE INDEX idx_mensajes_emisor        ON mensajes (id_emisor, fecha_envio);
CREATE INDEX idx_mensajes_receptor      ON mensajes (id_receptor, leido);
CREATE INDEX idx_archivos_guia          ON archivos_adjuntos (id_guia);
CREATE INDEX idx_matriculas_usuario     ON matriculas (id_usuario);

CREATE VIEW vw_leaderboard AS
SELECT
    u.id_usuario,
    u.nombre,
    u.grado,
    u.puntos,
    u.ejercicios_resueltos,
    u.foto_perfil,
    u.marco_perfil,
    COUNT(ui.id_insignia) AS total_insignias
FROM usuarios u
LEFT JOIN usuario_insignias ui ON ui.id_usuario = u.id_usuario
WHERE u.rol = 'estudiante' AND u.activo = 1
GROUP BY u.id_usuario, u.nombre, u.grado, u.puntos, u.ejercicios_resueltos, u.foto_perfil, u.marco_perfil;

-- Hash bcrypt de EduNova2026!  (PASSWORD_DEFAULT de PHP)
INSERT INTO usuarios (nombre, correo, contrasena_hash, rol, grado, materia) VALUES
    ('Administrador NovaTeam', 'admin@novateam.edu.co',          '$2y$10$DBKPP9me3lfvh94H2b7NU.S5UXdqtIDKewEIzSgsb9v7MR5ZFpwOm', 'administrador', NULL, NULL),
    ('Jenniffer Yepes',        'jenniffer.yepes@colegio.edu.co', '$2y$10$DBKPP9me3lfvh94H2b7NU.S5UXdqtIDKewEIzSgsb9v7MR5ZFpwOm', 'profesor', NULL, 'matematicas'),
    ('Carlos Malave',          'carlos.malave@colegio.edu.co',   '$2y$10$DBKPP9me3lfvh94H2b7NU.S5UXdqtIDKewEIzSgsb9v7MR5ZFpwOm', 'profesor', NULL, 'ingles'),
    ('Ana Torres',             'ana.torres@colegio.edu.co',      '$2y$10$DBKPP9me3lfvh94H2b7NU.S5UXdqtIDKewEIzSgsb9v7MR5ZFpwOm', 'estudiante', 4, NULL),
    ('Luis Rodriguez',         'luis.rodriguez@colegio.edu.co',  '$2y$10$DBKPP9me3lfvh94H2b7NU.S5UXdqtIDKewEIzSgsb9v7MR5ZFpwOm', 'estudiante', 4, NULL);

INSERT INTO matriculas (id_usuario, materia) VALUES
    (4, 'matematicas'),
    (4, 'ingles'),
    (5, 'matematicas'),
    (5, 'ingles');

INSERT INTO guias (titulo, categoria, dificultad, modo_creacion, estado, id_profesor, fecha_publicacion, fecha_expiracion)
VALUES
    ('Sumas y restas basicas', 'matematicas', 'facil', 'manual', 'publicada', 2, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY)),
    ('Vocabulario: animales',  'ingles',      'facil', 'manual', 'publicada', 3, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY));

INSERT INTO ejercicios (id_guia, numero_orden, pregunta, opcion_1, opcion_2, opcion_3, opcion_4, respuesta_correcta)
VALUES
    (1, 1, '¿Cuanto es 4 + 5?', '9', '8', '10', '7', 1),
    (1, 2, '¿Cuanto es 12 - 7?', '4', '5', '6', '3', 2),
    (1, 3, '¿Cual numero es mayor: 18 o 21?', '18', '21', 'Son iguales', '15', 2),
    (2, 1, '¿Como se dice "perro" en ingles?', 'Cat', 'Dog', 'Bird', 'Fish', 2),
    (2, 2, '¿Como se dice "gato" en ingles?', 'Dog', 'Bird', 'Cat', 'Cow', 3),
    (2, 3, 'What color is the sky on a sunny day?', 'Green', 'Blue', 'Red', 'Yellow', 2);

INSERT INTO notificaciones (titulo, mensaje, id_profesor) VALUES
    ('¡Bienvenidos a EduNova!', 'Ya puedes resolver las primeras guias de matematicas e ingles. Gana puntos e insignias.', 2);
