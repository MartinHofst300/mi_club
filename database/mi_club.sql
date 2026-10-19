-- Base de datos para el sistema de gestion de club de barrio: MiClub
-- Compatible con MariaDB 10.4+ y MySQL 8.0+
-- Reglas de diseno: UTF-8 mb4, InnoDB, restricciones de integridad referencial y de dominio.

CREATE DATABASE IF NOT EXISTS mi_club CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mi_club;

SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- 1. Tabla de Roles (Especializacion del Usuario segun modelo UML)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS roles;
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. Tabla de Usuarios (Actor abstracto segun modelo UML)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS usuarios;
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rol_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    dni VARCHAR(20) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuarios_roles FOREIGN KEY (rol_id) REFERENCES roles (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_usuarios_email ON usuarios (email);
CREATE INDEX idx_usuarios_dni ON usuarios (dni);
CREATE INDEX idx_usuarios_rol ON usuarios (rol_id);

-- -----------------------------------------------------------------------------
-- 3. Tabla de Grupos Familiares (agregada por diseno de persistencia para Regla R5)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS grupos_familiares;
CREATE TABLE grupos_familiares (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 4. Tabla de Categorias (Define rangos de edad y precio base de cuota segun R3 y R8)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS categorias;
CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255) NULL,
    edad_minima INT NOT NULL DEFAULT 0,
    edad_maxima INT NOT NULL DEFAULT 99,
    precio_cuota DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT chk_categorias_edades CHECK (edad_minima <= edad_maxima),
    CONSTRAINT chk_categorias_precio CHECK (precio_cuota >= 0.00)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 5. Tabla de Socios (Clase del modelo UML con atributos propios)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS socios;
CREATE TABLE socios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL UNIQUE,
    grupo_familiar_id INT NULL,
    categoria_id INT NOT NULL,
    numero_socio VARCHAR(20) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    dni VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    telefono VARCHAR(50) NULL,
    domicilio VARCHAR(200) NULL,
    fecha_nacimiento DATE NOT NULL,
    fecha_alta DATE NOT NULL,
    estado ENUM('ACTIVO', 'MOROSO', 'SUSPENDIDO', 'BAJA') NOT NULL DEFAULT 'ACTIVO',
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_socios_usuarios FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_socios_grupos FOREIGN KEY (grupo_familiar_id) REFERENCES grupos_familiares (id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_socios_categorias FOREIGN KEY (categoria_id) REFERENCES categorias (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_socios_dni ON socios (dni);
CREATE INDEX idx_socios_email ON socios (email);
CREATE INDEX idx_socios_estado ON socios (estado);
CREATE INDEX idx_socios_categoria ON socios (categoria_id);
CREATE INDEX idx_socios_grupo ON socios (grupo_familiar_id);

-- -----------------------------------------------------------------------------
-- 6. Tabla de Divisiones (Clase del modelo UML)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS divisiones;
CREATE TABLE divisiones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT NOT NULL,
    entrenador_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    cupo_maximo INT NOT NULL DEFAULT 30,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT chk_divisiones_cupo CHECK (cupo_maximo > 0),
    CONSTRAINT fk_divisiones_categorias FOREIGN KEY (categoria_id) REFERENCES categorias (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_divisiones_entrenadores FOREIGN KEY (entrenador_id) REFERENCES usuarios (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_divisiones_categoria ON divisiones (categoria_id);
CREATE INDEX idx_divisiones_entrenador ON divisiones (entrenador_id);

-- -----------------------------------------------------------------------------
-- 7. Tabla de Inscripciones (agregada por diseno de persistencia para asociar socio a division)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS inscripciones;
CREATE TABLE inscripciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    division_id INT NOT NULL,
    socio_id INT NOT NULL,
    fecha_inscripcion DATE NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT uq_division_socio UNIQUE (division_id, socio_id),
    CONSTRAINT fk_inscripciones_divisiones FOREIGN KEY (division_id) REFERENCES divisiones (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_inscripciones_socios FOREIGN KEY (socio_id) REFERENCES socios (id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_inscripciones_division ON inscripciones (division_id);
CREATE INDEX idx_inscripciones_socio ON inscripciones (socio_id);

-- -----------------------------------------------------------------------------
-- 8. Tabla de Entrenamientos (Clase del modelo UML)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS entrenamientos;
CREATE TABLE entrenamientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    division_id INT NOT NULL,
    entrenador_id INT NOT NULL,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    lugar VARCHAR(150) NOT NULL,
    observaciones TEXT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_entrenamientos_divisiones FOREIGN KEY (division_id) REFERENCES divisiones (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_entrenamientos_entrenadores FOREIGN KEY (entrenador_id) REFERENCES usuarios (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_entrenamientos_division ON entrenamientos (division_id);
CREATE INDEX idx_entrenamientos_fecha ON entrenamientos (fecha);

-- -----------------------------------------------------------------------------
-- 9. Tabla de Asistencias (Clase del modelo UML)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS asistencias;
CREATE TABLE asistencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entrenamiento_id INT NOT NULL,
    socio_id INT NOT NULL,
    estado ENUM('PRESENTE', 'AUSENTE', 'JUSTIFICADO') NOT NULL DEFAULT 'PRESENTE',
    observaciones VARCHAR(255) NULL,
    CONSTRAINT uq_entrenamiento_socio UNIQUE (entrenamiento_id, socio_id),
    CONSTRAINT fk_asistencias_entrenamientos FOREIGN KEY (entrenamiento_id) REFERENCES entrenamientos (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_asistencias_socios FOREIGN KEY (socio_id) REFERENCES socios (id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_asistencias_entrenamiento ON asistencias (entrenamiento_id);
CREATE INDEX idx_asistencias_socio ON asistencias (socio_id);

-- -----------------------------------------------------------------------------
-- 10. Tabla de Apercibimientos (agregada por diseno de persistencia para Regla R10)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS apercibimientos;
CREATE TABLE apercibimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    socio_id INT NOT NULL,
    entrenador_id INT NOT NULL,
    fecha DATE NOT NULL,
    gravedad ENUM('LEVE', 'GRAVE') NOT NULL DEFAULT 'LEVE',
    motivo TEXT NOT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_apercibimientos_socios FOREIGN KEY (socio_id) REFERENCES socios (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_apercibimientos_entrenadores FOREIGN KEY (entrenador_id) REFERENCES usuarios (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_apercibimientos_socio ON apercibimientos (socio_id);

-- -----------------------------------------------------------------------------
-- 11. Tabla de Cuotas (Clase del modelo UML, Reglas R1, R2, R3, R4, R5, R6)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS cuotas;
CREATE TABLE cuotas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    socio_id INT NOT NULL,
    periodo CHAR(7) NOT NULL, -- Formato AAAA-MM
    monto_base DECIMAL(10,2) NOT NULL,
    monto_descuento DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    monto_recargo DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    monto_final DECIMAL(10,2) NOT NULL,
    monto_pagado DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    fecha_vencimiento DATE NOT NULL,
    estado ENUM('PENDIENTE', 'PAGADA', 'VENCIDA', 'ANULADA') NOT NULL DEFAULT 'PENDIENTE',
    fecha_anulacion DATETIME NULL,
    motivo_anulacion VARCHAR(255) NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_cuota_socio_periodo UNIQUE (socio_id, periodo),
    CONSTRAINT chk_cuotas_montos CHECK (monto_base >= 0.00 AND monto_descuento >= 0.00 AND monto_recargo >= 0.00 AND monto_final >= 0.00 AND monto_pagado >= 0.00),
    CONSTRAINT fk_cuotas_socios FOREIGN KEY (socio_id) REFERENCES socios (id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_cuotas_periodo ON cuotas (periodo);
CREATE INDEX idx_cuotas_estado ON cuotas (estado);
CREATE INDEX idx_cuotas_socio ON cuotas (socio_id);

-- -----------------------------------------------------------------------------
-- 12. Tabla de Pagos (Clase del modelo UML, Regla R7)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS pagos;
CREATE TABLE pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    socio_id INT NOT NULL,
    usuario_id INT NULL, -- Usuario del sistema que registro o proceso el pago
    monto_total DECIMAL(10,2) NOT NULL,
    medio_pago ENUM('EFECTIVO', 'TRANSFERENCIA', 'TARJETA_SIMULADA') NOT NULL,
    autogestionado TINYINT(1) NOT NULL DEFAULT 0,
    fecha_pago DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    observaciones VARCHAR(255) NULL,
    CONSTRAINT chk_pagos_monto CHECK (monto_total > 0.00),
    CONSTRAINT fk_pagos_socios FOREIGN KEY (socio_id) REFERENCES socios (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pagos_usuarios FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_pagos_socio ON pagos (socio_id);
CREATE INDEX idx_pagos_fecha ON pagos (fecha_pago);

-- -----------------------------------------------------------------------------
-- 13. Tabla de Imputaciones (agregada por diseno de persistencia para asociar pago a cuotas segun R7)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS imputaciones;
CREATE TABLE imputaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pago_id INT NOT NULL,
    cuota_id INT NOT NULL,
    monto_imputado DECIMAL(10,2) NOT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_imputaciones_monto CHECK (monto_imputado > 0.00),
    CONSTRAINT fk_imputaciones_pagos FOREIGN KEY (pago_id) REFERENCES pagos (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_imputaciones_cuotas FOREIGN KEY (cuota_id) REFERENCES cuotas (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_imputaciones_pago ON imputaciones (pago_id);
CREATE INDEX idx_imputaciones_cuota ON imputaciones (cuota_id);

-- -----------------------------------------------------------------------------
-- 14. Tabla de Recibos (agregada por diseno de persistencia para numeracion correlativa segun R7)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS recibos;
CREATE TABLE recibos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pago_id INT NOT NULL UNIQUE,
    numero_recibo VARCHAR(30) NOT NULL UNIQUE,
    fecha_emision DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    monto_total DECIMAL(10,2) NOT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_recibos_monto CHECK (monto_total > 0.00),
    CONSTRAINT fk_recibos_pagos FOREIGN KEY (pago_id) REFERENCES pagos (id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_recibos_numero ON recibos (numero_recibo);

-- -----------------------------------------------------------------------------
-- 15. Tabla de Configuracion (agregada por diseno de persistencia, una sola fila)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS configuracion;
CREATE TABLE configuracion (
    id INT PRIMARY KEY DEFAULT 1,
    nombre_club VARCHAR(150) NOT NULL DEFAULT 'Club Social y Deportivo MiClub',
    color_primario VARCHAR(7) NOT NULL DEFAULT '#1F5C3B',
    color_primario_oscuro VARCHAR(7) NOT NULL DEFAULT '#14402A',
    color_secundario VARCHAR(7) NOT NULL DEFAULT '#E4EFE7',
    color_acento VARCHAR(7) NOT NULL DEFAULT '#C98A1B',
    color_fondo VARCHAR(7) NOT NULL DEFAULT '#F6F4EC',
    color_texto VARCHAR(7) NOT NULL DEFAULT '#1E2A24',
    cuotas_para_moroso INT NOT NULL DEFAULT 3,
    porcentaje_recargo DECIMAL(5,2) NOT NULL DEFAULT 10.00,
    porcentaje_descuento_familiar DECIMAL(5,2) NOT NULL DEFAULT 15.00,
    dias_vencimiento INT NOT NULL DEFAULT 10,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_configuracion_id CHECK (id = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- DATOS DE EJEMPLO REALISTAS Y NEUTROS (Octubre de 2026)
-- =============================================================================

-- Configuracion institucional inicial
INSERT INTO configuracion (
    id, nombre_club, color_primario, color_primario_oscuro, color_secundario,
    color_acento, color_fondo, color_texto, cuotas_para_moroso, porcentaje_recargo,
    porcentaje_descuento_familiar, dias_vencimiento
) VALUES (
    1, 'Club Social y Deportivo MiClub', '#1F5C3B', '#14402A', '#E4EFE7',
    '#C98A1B', '#F6F4EC', '#1E2A24', 3, 10.00, 15.00, 10
);

-- Roles del sistema
INSERT INTO roles (id, nombre, descripcion) VALUES
(1, 'ADMINISTRADOR', 'Gestion global de usuarios, socios, divisiones, categorias y precios'),
(2, 'TESORERO', 'Gestion economica, generacion de cuotas, cobro de pagos y emision de recibos'),
(3, 'ENTRENADOR', 'Gestion de planteles, planificacion de entrenamientos, asistencias y apercibimientos'),
(4, 'SOCIO', 'Acceso al portal del socio, consulta de carnet, estado de cuenta y pago en linea');

-- Usuarios institucionales (Hashes bcrypt verificados)
-- admin@miclub.local      -> admin123      ($2y$10$gX5rUV5QC7LgJ3JcseVYa.LRrIJMuZXw6KU3tM1sATKa5K7NyHumm)
-- tesoreria@miclub.local  -> tesorero123   ($2y$10$xeyQVMcBM4tfEAO2ad2vjOk0s6y95FlsE4mLhlmQKPajXJFC5jRYy)
-- roberto.gomez@miclub.local -> entrenador123 ($2y$10$MdjtM6d49J0DF95VwNHNTOLJLUjbO7jcQA5ePR.93v05KNf2qG/wC)
-- laura.fernandez@miclub.local -> entrenador456 ($2y$10$Gjyo/CF0vRfyDP1MIe49FeBgrRKEozs2pv8DpqDMu/virN7BSu8SK)
-- socio@miclub.local (marcos.moroso) -> socio123 ($2y$10$0ST2pMG1s6sTrDIvuE7CU.EybHQ1xDai.FarRAyIW7lTIJ2yPIFuW)
INSERT INTO usuarios (id, rol_id, nombre, apellido, email, dni, password_hash, activo, creado_en) VALUES
(1, 1, 'Carlos', 'Benitez', 'admin@miclub.local', '20111222', '$2y$10$gX5rUV5QC7LgJ3JcseVYa.LRrIJMuZXw6KU3tM1sATKa5K7NyHumm', 1, '2026-01-10 09:00:00'),
(2, 2, 'Mariana', 'Lopez', 'tesoreria@miclub.local', '25333444', '$2y$10$xeyQVMcBM4tfEAO2ad2vjOk0s6y95FlsE4mLhlmQKPajXJFC5jRYy', 1, '2026-01-10 09:15:00'),
(3, 3, 'Roberto', 'Gomez', 'roberto.gomez@miclub.local', '28555666', '$2y$10$MdjtM6d49J0DF95VwNHNTOLJLUjbO7jcQA5ePR.93v05KNf2qG/wC', 1, '2026-01-15 10:00:00'),
(4, 3, 'Laura', 'Fernandez', 'laura.fernandez@miclub.local', '30777888', '$2y$10$Gjyo/CF0vRfyDP1MIe49FeBgrRKEozs2pv8DpqDMu/virN7BSu8SK', 1, '2026-01-15 10:30:00'),
(5, 4, 'Marcos', 'Moroso', 'marcos.moroso@email.local', '35123456', '$2y$10$0ST2pMG1s6sTrDIvuE7CU.EybHQ1xDai.FarRAyIW7lTIJ2yPIFuW', 1, '2026-02-01 11:00:00'),
(6, 4, 'Martin', 'Gonzalez', 'martin.gonzalez@email.local', '38222333', '$2y$10$0ST2pMG1s6sTrDIvuE7CU.EybHQ1xDai.FarRAyIW7lTIJ2yPIFuW', 1, '2026-02-05 11:30:00'),
(7, 4, 'Lucas', 'Gonzalez', 'lucas.gonzalez@email.local', '45444555', '$2y$10$0ST2pMG1s6sTrDIvuE7CU.EybHQ1xDai.FarRAyIW7lTIJ2yPIFuW', 1, '2026-02-05 11:45:00'),
(8, 4, 'Sofia', 'Martinez', 'sofia.martinez@email.local', '42111222', '$2y$10$0ST2pMG1s6sTrDIvuE7CU.EybHQ1xDai.FarRAyIW7lTIJ2yPIFuW', 1, '2026-02-10 12:00:00'),
(9, 4, 'Julian', 'Alvarez', 'julian.alvarez@email.local', '46333444', '$2y$10$0ST2pMG1s6sTrDIvuE7CU.EybHQ1xDai.FarRAyIW7lTIJ2yPIFuW', 1, '2026-02-15 12:30:00'),
(10, 4, 'Camila', 'Diaz', 'camila.diaz@email.local', '41999888', '$2y$10$0ST2pMG1s6sTrDIvuE7CU.EybHQ1xDai.FarRAyIW7lTIJ2yPIFuW', 1, '2026-03-10 11:00:00');

-- Grupos Familiares (Regla R5)
INSERT INTO grupos_familiares (id, nombre, creado_en) VALUES
(1, 'Familia Gonzalez', '2026-02-05 10:00:00');

-- Categorias (Define edad y precio segun R3 y R8)
INSERT INTO categorias (id, nombre, descripcion, edad_minima, edad_maxima, precio_cuota, activo) VALUES
(1, 'Infantiles', 'Socios de 6 a 12 anos', 6, 12, 8000.00, 1),
(2, 'Juveniles', 'Socios de 13 a 17 anos', 13, 17, 10000.00, 1),
(3, 'Mayores', 'Socios de 18 anos en adelante', 18, 99, 12000.00, 1);

-- Socios (Entre 8 y 10 socios de prueba con distintos estados y perfiles)
INSERT INTO socios (
    id, usuario_id, grupo_familiar_id, categoria_id, numero_socio, nombre, apellido,
    dni, email, telefono, domicilio, fecha_nacimiento, fecha_alta, estado, creado_en
) VALUES
-- Socio 1: Escenario del diagrama de objetos - MOROSO con 3+ cuotas impagas vencidas
(1, 5, NULL, 3, 'SOC-0001', 'Marcos', 'Moroso', '35123456', 'marcos.moroso@email.local', '11-4567-8901', 'Av. San Martin 1234', '1990-05-14', '2026-02-01', 'MOROSO', '2026-02-01 11:00:00'),
-- Socios 2 y 3: Grupo Familiar con 2 integrantes activos (aplica 15% de descuento familiar)
(2, 6, 1, 3, 'SOC-0002', 'Martin', 'Gonzalez', '38222333', 'martin.gonzalez@email.local', '11-5678-9012', 'Calle Mitre 456', '1994-08-22', '2026-02-05', 'ACTIVO', '2026-02-05 11:30:00'),
(3, 7, 1, 2, 'SOC-0003', 'Lucas', 'Gonzalez', '45444555', 'lucas.gonzalez@email.local', '11-5678-9012', 'Calle Mitre 456', '2009-03-11', '2026-02-05', 'ACTIVO', '2026-02-05 11:45:00'),
-- Socio 4: Activa, con division
(4, 8, NULL, 3, 'SOC-0004', 'Sofia', 'Martinez', '42111222', 'sofia.martinez@email.local', '11-6789-0123', 'Belgrano 789', '2000-11-30', '2026-02-10', 'ACTIVO', '2026-02-10 12:00:00'),
-- Socio 5: Activo, con division
(5, 9, NULL, 2, 'SOC-0005', 'Julian', 'Alvarez', '46333444', 'julian.alvarez@email.local', '11-7890-1234', 'Rivadavia 1011', '2010-01-18', '2026-02-15', 'ACTIVO', '2026-02-15 12:30:00'),
-- Socio 6: Menor (Infantil), sin usuario web asociado
(6, NULL, NULL, 1, 'SOC-0006', 'Mateo', 'Rossi', '48555666', 'mateo.rossi@email.local', '11-8901-2345', 'Lavalle 1420', '2015-07-09', '2026-03-01', 'ACTIVO', '2026-03-01 10:00:00'),
-- Socio 7: Activa, sin division inscripta (con cuenta de usuario web ID 10)
(7, 10, NULL, 3, 'SOC-0007', 'Camila', 'Diaz', '41999888', 'camila.diaz@email.local', '11-9012-3456', 'Sarmiento 832', '1999-04-12', '2026-03-10', 'ACTIVO', '2026-03-10 11:00:00'),
-- Socio 8: Suspendido manualmente por Administracion
(8, NULL, NULL, 3, 'SOC-0008', 'Diego', 'Torres', '36888777', 'diego.torres@email.local', '11-1234-5678', 'Guemes 512', '1992-09-05', '2026-01-20', 'SUSPENDIDO', '2026-01-20 09:30:00'),
-- Socio 9: Baja definitiva
(9, NULL, NULL, 3, 'SOC-0009', 'Ana', 'Ramirez', '37444333', 'ana.ramirez@email.local', '11-2345-6789', 'Alsina 964', '1993-12-03', '2026-01-25', 'BAJA', '2026-01-25 14:00:00');

-- Divisiones (Clase del modelo UML, cupos y categorias asociadas)
-- Division 1: cupo_maximo 3 con 2 inscriptos (socios 3 y 5) -> Exactamente un solo cupo libre
-- Division 2: cupo_maximo 2 con 2 inscriptos (socios 2 y 4) -> Cupo lleno (rechaza con 409 segun R8)
-- Division 3: cupo_maximo 20 con 1 inscripto (socio 6) -> Amplia disponibilidad
INSERT INTO divisiones (id, categoria_id, entrenador_id, nombre, cupo_maximo, activo) VALUES
(1, 2, 3, 'Basquet Juvenil', 3, 1),
(2, 3, 4, 'Voley Mayores', 2, 1),
(3, 1, 3, 'Futbol Infantil', 20, 1);

-- Inscripciones a Divisiones (Validando R8)
INSERT INTO inscripciones (id, division_id, socio_id, fecha_inscripcion, activo) VALUES
(1, 1, 3, '2026-03-05', 1), -- Lucas Gonzalez en Basquet Juvenil
(2, 1, 5, '2026-03-05', 1), -- Julian Alvarez en Basquet Juvenil
(3, 2, 2, '2026-03-06', 1), -- Martin Gonzalez en Voley Mayores
(4, 2, 4, '2026-03-06', 1), -- Sofia Martinez en Voley Mayores
(5, 3, 6, '2026-03-07', 1); -- Mateo Rossi en Futbol Infantil

-- Entrenamientos de ejemplo
INSERT INTO entrenamientos (id, division_id, entrenador_id, fecha, hora, lugar, observaciones, creado_en) VALUES
(1, 1, 3, '2026-10-15', '18:00:00', 'Gimnasio Principal', 'Practica de lanzamientos y defensa individual', '2026-10-14 10:00:00'),
(2, 1, 3, '2026-10-19', '18:00:00', 'Gimnasio Principal', 'Entrenamiento tactico previo al fin de semana', '2026-10-18 11:00:00'),
(3, 2, 4, '2026-10-17', '19:30:00', 'Cancha 1 Cubierta', 'Ejercicios de recepcion y armado', '2026-10-16 12:00:00');

-- Asistencias tomadas
INSERT INTO asistencias (id, entrenamiento_id, socio_id, estado, observaciones) VALUES
(1, 1, 3, 'AUSENTE', 'Aviso por viaje escolar'),
(2, 1, 5, 'PRESENTE', NULL),
(3, 3, 2, 'JUSTIFICADO', 'Certificado medico por esguince leve'),
(4, 3, 4, 'PRESENTE', NULL);

-- Apercibimientos de ejemplo (Regla R10)
INSERT INTO apercibimientos (id, socio_id, entrenador_id, fecha, gravedad, motivo, creado_en) VALUES
(1, 5, 3, '2026-09-20', 'LEVE', 'Llegada tarde reiterada sin aviso previo', '2026-09-20 19:00:00');

-- -----------------------------------------------------------------------------
-- Cuotas de ejemplo (Reflejan el estado economico al mes actual: Octubre 2026)
-- -----------------------------------------------------------------------------
-- Escenario del diagrama de objetos: Marcos Moroso (Socio 1)
-- Posee exactamente 3 cuotas impagas (Regla R1: cuotas_para_moroso = 3):
-- Cuota 1 (2026-08): VENCIDA con pago parcial imputado de $6000. Saldo restante: $7200.00
-- Cuota 2 (2026-09): VENCIDA con saldo total impago: $13200.00
-- Cuota 3 (2026-10): VENCIDA con saldo total impago: $12000.00 (vencio el 10/10/2026)
-- Total cuotas impagas vencidas con saldo: exactamente 3 (>= 3 -> MOROSO segun R1)
INSERT INTO cuotas (
    id, socio_id, periodo, monto_base, monto_descuento, monto_recargo,
    monto_final, monto_pagado, fecha_vencimiento, estado, creado_en
) VALUES
(1, 1, '2026-08', 12000.00, 0.00, 1200.00, 13200.00, 6000.00, '2026-08-10', 'VENCIDA', '2026-08-01 08:00:00'),
(2, 1, '2026-09', 12000.00, 0.00, 1200.00, 13200.00, 0.00, '2026-09-10', 'VENCIDA', '2026-09-01 08:00:00'),
(3, 1, '2026-10', 12000.00, 0.00, 0.00, 12000.00, 0.00, '2026-10-10', 'VENCIDA', '2026-10-01 08:00:00');

-- Cuotas del Grupo Familiar (Martin Gonzalez - Socio 2 y Lucas Gonzalez - Socio 3)
-- Aplica descuento familiar del 15% sobre monto base
-- Socio 2 (Cat Mayores: $12000 -> 15% desc = $1800 -> final $10200)
INSERT INTO cuotas (
    id, socio_id, periodo, monto_base, monto_descuento, monto_recargo,
    monto_final, monto_pagado, fecha_vencimiento, estado, creado_en
) VALUES
(4, 2, '2026-09', 12000.00, 1800.00, 0.00, 10200.00, 10200.00, '2026-09-10', 'PAGADA', '2026-09-01 08:00:00'),
(5, 2, '2026-10', 12000.00, 1800.00, 0.00, 10200.00, 0.00, '2026-10-10', 'PENDIENTE', '2026-10-01 08:00:00');

-- Socio 3 (Cat Juveniles: $10000 -> 15% desc = $1500 -> final $8500)
INSERT INTO cuotas (
    id, socio_id, periodo, monto_base, monto_descuento, monto_recargo,
    monto_final, monto_pagado, fecha_vencimiento, estado, creado_en
) VALUES
(6, 3, '2026-09', 10000.00, 1500.00, 0.00, 8500.00, 8500.00, '2026-09-10', 'PAGADA', '2026-09-01 08:00:00'),
(7, 3, '2026-10', 10000.00, 1500.00, 0.00, 8500.00, 0.00, '2026-10-10', 'PENDIENTE', '2026-10-01 08:00:00');

-- Socio 4 (Sofia Martinez: Cat Mayores $12000, cuotas al dia)
INSERT INTO cuotas (
    id, socio_id, periodo, monto_base, monto_descuento, monto_recargo,
    monto_final, monto_pagado, fecha_vencimiento, estado, creado_en
) VALUES
(8, 4, '2026-09', 12000.00, 0.00, 0.00, 12000.00, 12000.00, '2026-09-10', 'PAGADA', '2026-09-01 08:00:00'),
(9, 4, '2026-10', 12000.00, 0.00, 0.00, 12000.00, 12000.00, '2026-10-10', 'PAGADA', '2026-10-01 08:00:00');

-- Socio 5 (Julian Alvarez: Cat Juveniles $10000)
INSERT INTO cuotas (
    id, socio_id, periodo, monto_base, monto_descuento, monto_recargo,
    monto_final, monto_pagado, fecha_vencimiento, estado, creado_en
) VALUES
(10, 5, '2026-09', 10000.00, 0.00, 0.00, 10000.00, 10000.00, '2026-09-10', 'PAGADA', '2026-09-01 08:00:00'),
(11, 5, '2026-10', 10000.00, 0.00, 0.00, 10000.00, 0.00, '2026-10-10', 'PENDIENTE', '2026-10-01 08:00:00');

-- Socio 6 (Mateo Rossi: Cat Infantiles $8000)
INSERT INTO cuotas (
    id, socio_id, periodo, monto_base, monto_descuento, monto_recargo,
    monto_final, monto_pagado, fecha_vencimiento, estado, creado_en
) VALUES
(12, 6, '2026-09', 8000.00, 0.00, 0.00, 8000.00, 8000.00, '2026-09-10', 'PAGADA', '2026-09-01 08:00:00'),
(13, 6, '2026-10', 8000.00, 0.00, 0.00, 8000.00, 0.00, '2026-10-10', 'PENDIENTE', '2026-10-01 08:00:00');

-- Socio 7 (Camila Diaz: Cat Mayores $12000)
INSERT INTO cuotas (
    id, socio_id, periodo, monto_base, monto_descuento, monto_recargo,
    monto_final, monto_pagado, fecha_vencimiento, estado, creado_en
) VALUES
(14, 7, '2026-09', 12000.00, 0.00, 0.00, 12000.00, 12000.00, '2026-09-10', 'PAGADA', '2026-09-01 08:00:00'),
(15, 7, '2026-10', 12000.00, 0.00, 0.00, 12000.00, 0.00, '2026-10-10', 'PENDIENTE', '2026-10-01 08:00:00');

-- -----------------------------------------------------------------------------
-- Pagos, Imputaciones y Recibos correspondientes a los datos de ejemplo
-- -----------------------------------------------------------------------------

-- Pago 1: Pago parcial del moroso (Socio 1) imputado a la cuota 1 (2026-08) por $6000.00
INSERT INTO pagos (id, socio_id, usuario_id, monto_total, medio_pago, autogestionado, fecha_pago, observaciones) VALUES
(1, 1, 2, 6000.00, 'EFECTIVO', 0, '2026-08-15 11:30:00', 'Pago parcial en caja');

INSERT INTO imputaciones (id, pago_id, cuota_id, monto_imputado, creado_en) VALUES
(1, 1, 1, 6000.00, '2026-08-15 11:30:00');

INSERT INTO recibos (id, pago_id, numero_recibo, fecha_emision, monto_total, creado_en) VALUES
(1, 1, 'R-00000001', '2026-08-15 11:30:00', 6000.00, '2026-08-15 11:30:00');

-- Pago 2: Martin Gonzalez paga cuota 2026-09 ($10200)
INSERT INTO pagos (id, socio_id, usuario_id, monto_total, medio_pago, autogestionado, fecha_pago, observaciones) VALUES
(2, 2, 2, 10200.00, 'TRANSFERENCIA', 0, '2026-09-08 10:15:00', 'Transferencia bancaria');

INSERT INTO imputaciones (id, pago_id, cuota_id, monto_imputado, creado_en) VALUES
(2, 2, 4, 10200.00, '2026-09-08 10:15:00');

INSERT INTO recibos (id, pago_id, numero_recibo, fecha_emision, monto_total, creado_en) VALUES
(2, 2, 'R-00000002', '2026-09-08 10:15:00', 10200.00, '2026-09-08 10:15:00');

-- Pago 3: Lucas Gonzalez paga cuota 2026-09 ($8500)
INSERT INTO pagos (id, socio_id, usuario_id, monto_total, medio_pago, autogestionado, fecha_pago, observaciones) VALUES
(3, 3, 2, 8500.00, 'TRANSFERENCIA', 0, '2026-09-08 10:20:00', 'Transferencia bancaria familiar');

INSERT INTO imputaciones (id, pago_id, cuota_id, monto_imputado, creado_en) VALUES
(3, 3, 6, 8500.00, '2026-09-08 10:20:00');

INSERT INTO recibos (id, pago_id, numero_recibo, fecha_emision, monto_total, creado_en) VALUES
(3, 3, 'R-00000003', '2026-09-08 10:20:00', 8500.00, '2026-09-08 10:20:00');

-- Pago 4: Sofia Martinez paga cuota 2026-09 ($12000)
INSERT INTO pagos (id, socio_id, usuario_id, monto_total, medio_pago, autogestionado, fecha_pago, observaciones) VALUES
(4, 4, 8, 12000.00, 'TARJETA_SIMULADA', 1, '2026-09-05 16:40:00', 'Pago web con tarjeta simulada');

INSERT INTO imputaciones (id, pago_id, cuota_id, monto_imputado, creado_en) VALUES
(4, 4, 8, 12000.00, '2026-09-05 16:40:00');

INSERT INTO recibos (id, pago_id, numero_recibo, fecha_emision, monto_total, creado_en) VALUES
(4, 4, 'R-00000004', '2026-09-05 16:40:00', 12000.00, '2026-09-05 16:40:00');

-- Pago 5: Sofia Martinez paga cuota 2026-10 ($12000)
INSERT INTO pagos (id, socio_id, usuario_id, monto_total, medio_pago, autogestionado, fecha_pago, observaciones) VALUES
(5, 4, 8, 12000.00, 'TARJETA_SIMULADA', 1, '2026-10-04 18:20:00', 'Pago web con tarjeta simulada');

INSERT INTO imputaciones (id, pago_id, cuota_id, monto_imputado, creado_en) VALUES
(5, 5, 9, 12000.00, '2026-10-04 18:20:00');

INSERT INTO recibos (id, pago_id, numero_recibo, fecha_emision, monto_total, creado_en) VALUES
(5, 5, 'R-00000005', '2026-10-04 18:20:00', 12000.00, '2026-10-04 18:20:00');

-- Pago 6: Julian Alvarez paga cuota 2026-09 ($10000)
INSERT INTO pagos (id, socio_id, usuario_id, monto_total, medio_pago, autogestionado, fecha_pago, observaciones) VALUES
(6, 5, 2, 10000.00, 'EFECTIVO', 0, '2026-09-09 18:00:00', 'Pago presencial');

INSERT INTO imputaciones (id, pago_id, cuota_id, monto_imputado, creado_en) VALUES
(6, 6, 10, 10000.00, '2026-09-09 18:00:00');

INSERT INTO recibos (id, pago_id, numero_recibo, fecha_emision, monto_total, creado_en) VALUES
(6, 6, 'R-00000006', '2026-09-09 18:00:00', 10000.00, '2026-09-09 18:00:00');

-- Pago 7: Mateo Rossi paga cuota 2026-09 ($8000)
INSERT INTO pagos (id, socio_id, usuario_id, monto_total, medio_pago, autogestionado, fecha_pago, observaciones) VALUES
(7, 6, 2, 8000.00, 'EFECTIVO', 0, '2026-09-10 10:00:00', 'Pago en administracion');

INSERT INTO imputaciones (id, pago_id, cuota_id, monto_imputado, creado_en) VALUES
(7, 7, 12, 8000.00, '2026-09-10 10:00:00');

INSERT INTO recibos (id, pago_id, numero_recibo, fecha_emision, monto_total, creado_en) VALUES
(7, 7, 'R-00000007', '2026-09-10 10:00:00', 8000.00, '2026-09-10 10:00:00');

-- Pago 8: Camila Diaz paga cuota 2026-09 ($12000)
INSERT INTO pagos (id, socio_id, usuario_id, monto_total, medio_pago, autogestionado, fecha_pago, observaciones) VALUES
(8, 7, 2, 12000.00, 'TRANSFERENCIA', 0, '2026-09-09 11:30:00', 'Transferencia bancaria');

INSERT INTO imputaciones (id, pago_id, cuota_id, monto_imputado, creado_en) VALUES
(8, 8, 14, 12000.00, '2026-09-09 11:30:00');

INSERT INTO recibos (id, pago_id, numero_recibo, fecha_emision, monto_total, creado_en) VALUES
(8, 8, 'R-00000008', '2026-09-09 11:30:00', 12000.00, '2026-09-09 11:30:00');
