-- ============================================================
-- BASE DE DATOS: sistema_clinico
-- Compatible con MySQL 8.0+
-- ============================================================

DROP DATABASE IF EXISTS sistema_clinico;

CREATE DATABASE sistema_clinico
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE sistema_clinico;


-- ============================================================
-- TABLA: Rol
-- ============================================================

CREATE TABLE Rol (
    Id_Rol INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Nombre_Rol VARCHAR(100) NOT NULL,
    Permisos TEXT,

    CONSTRAINT UQ_Rol_Nombre
        UNIQUE (Nombre_Rol)
) ENGINE=InnoDB;


-- ============================================================
-- TABLA: Personal
-- ============================================================

CREATE TABLE Personal (
    Id_Usuario INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Credenciales VARCHAR(255) NOT NULL,
    Id_Rol INT UNSIGNED NOT NULL,

    CONSTRAINT UQ_Personal_Credenciales
        UNIQUE (Credenciales),

    CONSTRAINT FK_Personal_Rol
        FOREIGN KEY (Id_Rol)
        REFERENCES Rol(Id_Rol)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;


-- ============================================================
-- TABLA: Sucursal
-- ============================================================

CREATE TABLE Sucursal (
    Id_Sucursal INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Nombre_Sucursal VARCHAR(150) NOT NULL,
    Ubicacion_Direccion VARCHAR(255),

    CONSTRAINT UQ_Sucursal_Nombre
        UNIQUE (Nombre_Sucursal)
) ENGINE=InnoDB;


-- ============================================================
-- TABLA: Profesional
-- ============================================================

CREATE TABLE Profesional (
    Id_Profesional INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Id_Usuario INT UNSIGNED NOT NULL,
    Id_Sucursal INT UNSIGNED NULL,
    Direccion VARCHAR(255),
    Jornada VARCHAR(100),
    Especialidad VARCHAR(150),

    CONSTRAINT UQ_Profesional_Usuario
        UNIQUE (Id_Usuario),

    CONSTRAINT FK_Profesional_Personal
        FOREIGN KEY (Id_Usuario)
        REFERENCES Personal(Id_Usuario)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT FK_Profesional_Sucursal
        FOREIGN KEY (Id_Sucursal)
        REFERENCES Sucursal(Id_Sucursal)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB;


-- ============================================================
-- TABLA: Tecnico_Sucursal
-- ============================================================

CREATE TABLE Tecnico_Sucursal (
    Id_Profesional INT UNSIGNED NOT NULL,
    Id_Sucursal INT UNSIGNED NOT NULL,
    Horarios_Atencion VARCHAR(255),

    PRIMARY KEY (Id_Profesional, Id_Sucursal),

    CONSTRAINT FK_Tecnico_Profesional
        FOREIGN KEY (Id_Profesional)
        REFERENCES Profesional(Id_Profesional)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT FK_Tecnico_Sucursal
        FOREIGN KEY (Id_Sucursal)
        REFERENCES Sucursal(Id_Sucursal)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;


-- ============================================================
-- TABLA: Equipamiento
-- ============================================================

CREATE TABLE Equipamiento (
    Id_Equipo INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Nombre_Equipo VARCHAR(150) NOT NULL,
    Id_Sucursal INT UNSIGNED NOT NULL,

    CONSTRAINT UQ_Equipamiento_Sucursal
        UNIQUE (Nombre_Equipo, Id_Sucursal),

    CONSTRAINT FK_Equipamiento_Sucursal
        FOREIGN KEY (Id_Sucursal)
        REFERENCES Sucursal(Id_Sucursal)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE INDEX IDX_Equipamiento_Sucursal
ON Equipamiento(Id_Sucursal);


-- ============================================================
-- TABLA: Paciente
-- ============================================================

CREATE TABLE Paciente (
    Id_Paciente INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Nombre_Apellido VARCHAR(200) NOT NULL,
    Password_Hash VARCHAR(255),
    Email VARCHAR(150),
    Credenciales VARCHAR(255),
    Datos_Actualizables TEXT,

    CONSTRAINT UQ_Paciente_Email
        UNIQUE (Email),

    CONSTRAINT UQ_Paciente_Credenciales
        UNIQUE (Credenciales)
) ENGINE=InnoDB;


-- ============================================================
-- TABLA: Historial_Clinico
-- ============================================================

CREATE TABLE Historial_Clinico (
    Id_Historial INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Id_Paciente INT UNSIGNED NOT NULL,
    Id_Profesional INT UNSIGNED NOT NULL,
    Fecha DATE NOT NULL,
    Notas_Observaciones TEXT,

    CONSTRAINT FK_Historial_Paciente
        FOREIGN KEY (Id_Paciente)
        REFERENCES Paciente(Id_Paciente)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT FK_Historial_Profesional
        FOREIGN KEY (Id_Profesional)
        REFERENCES Profesional(Id_Profesional)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE INDEX IDX_Historial_Paciente
ON Historial_Clinico(Id_Paciente);

CREATE INDEX IDX_Historial_Profesional
ON Historial_Clinico(Id_Profesional);

CREATE INDEX IDX_Historial_Fecha
ON Historial_Clinico(Fecha);


-- ============================================================
-- TABLA: Orden_Referencia
-- ============================================================

CREATE TABLE Orden_Referencia (
    Id_Orden INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Id_Paciente INT UNSIGNED NOT NULL,
    Inf_Profesional_Ref TEXT,
    Detalles_Solicitud TEXT,
    Fecha_Orden DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Estado VARCHAR(50) NOT NULL DEFAULT 'Pendiente',

    CONSTRAINT FK_Orden_Paciente
        FOREIGN KEY (Id_Paciente)
        REFERENCES Paciente(Id_Paciente)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT CHK_Orden_Estado
        CHECK (Estado IN ('Pendiente', 'Utilizada', 'Cancelada'))
) ENGINE=InnoDB;

CREATE INDEX IDX_Orden_Paciente
ON Orden_Referencia(Id_Paciente);

CREATE INDEX IDX_Orden_Estado
ON Orden_Referencia(Estado);


-- ============================================================
-- TABLA: Servicio
-- ============================================================

CREATE TABLE Servicio (
    Id_Servicio INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Nombre_Servicio VARCHAR(150) NOT NULL,
    Requiere_Referencia BOOLEAN NOT NULL DEFAULT FALSE,

    CONSTRAINT UQ_Servicio_Nombre
        UNIQUE (Nombre_Servicio)
) ENGINE=InnoDB;


-- ============================================================
-- TABLA: Cita
-- ============================================================

CREATE TABLE Cita (
    Id_Cita INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Id_Paciente INT UNSIGNED NOT NULL,
    Id_Profesional INT UNSIGNED NOT NULL,
    Id_Sucursal INT UNSIGNED NOT NULL,
    Id_Servicio INT UNSIGNED NOT NULL,
    Fecha_Hora DATETIME NOT NULL,
    Estado VARCHAR(50) NOT NULL DEFAULT 'Pendiente',

    CONSTRAINT FK_Cita_Paciente
        FOREIGN KEY (Id_Paciente)
        REFERENCES Paciente(Id_Paciente)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT FK_Cita_Profesional
        FOREIGN KEY (Id_Profesional)
        REFERENCES Profesional(Id_Profesional)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT FK_Cita_Sucursal
        FOREIGN KEY (Id_Sucursal)
        REFERENCES Sucursal(Id_Sucursal)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT FK_Cita_Servicio
        FOREIGN KEY (Id_Servicio)
        REFERENCES Servicio(Id_Servicio)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,

    CONSTRAINT CHK_Cita_Estado
        CHECK (
            Estado IN (
                'Pendiente',
                'Confirmada',
                'Atendida',
                'Cancelada',
                'No asistio'
            )
        )
) ENGINE=InnoDB;


-- ============================================================
-- ÍNDICES DE CITA
-- ============================================================

CREATE INDEX IDX_Cita_Paciente
ON Cita(Id_Paciente);

CREATE INDEX IDX_Cita_Profesional
ON Cita(Id_Profesional);

CREATE INDEX IDX_Cita_Sucursal
ON Cita(Id_Sucursal);

CREATE INDEX IDX_Cita_Servicio
ON Cita(Id_Servicio);

CREATE INDEX IDX_Cita_Fecha_Hora
ON Cita(Fecha_Hora);

CREATE INDEX IDX_Cita_Estado
ON Cita(Estado);


-- ============================================================
-- COMPROBACIÓN
-- ============================================================

SELECT TABLE_NAME
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = 'sistema_clinico'
  AND TABLE_TYPE = 'BASE TABLE'
ORDER BY TABLE_NAME;

SELECT 'Base de datos creada correctamente.' AS Mensaje;
