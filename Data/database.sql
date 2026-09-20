
IF EXISTS (SELECT name FROM sys.databases WHERE name = 'sistema_clinico')
BEGIN
    ALTER DATABASE sistema_clinico SET SINGLE_USER WITH ROLLBACK IMMEDIATE;
    DROP DATABASE sistema_clinico;
END
GO

CREATE DATABASE sistema_clinico
    COLLATE Latin1_General_CI_AI;
GO

USE sistema_clinico;
GO

CREATE TABLE Rol (
    Id_Rol INT IDENTITY(1,1) PRIMARY KEY,
    Nombre_Rol VARCHAR(100) NOT NULL,
    Permisos VARCHAR(MAX),
    CONSTRAINT UQ_Rol_Nombre UNIQUE (Nombre_Rol)
);

CREATE TABLE Personal (
    Id_Usuario INT IDENTITY(1,1) PRIMARY KEY,
    Credenciales VARCHAR(255) NOT NULL,
    Id_Rol INT NOT NULL,
    CONSTRAINT UQ_Personal_Credenciales UNIQUE (Credenciales),
    CONSTRAINT FK_Personal_Rol FOREIGN KEY (Id_Rol) REFERENCES Rol(Id_Rol) ON UPDATE CASCADE ON DELETE NO ACTION
);

CREATE TABLE Sucursal (
    Id_Sucursal INT IDENTITY(1,1) PRIMARY KEY,
    Nombre_Sucursal VARCHAR(150) NOT NULL,
    Ubicacion_Direccion VARCHAR(255),
    CONSTRAINT UQ_Sucursal_Nombre UNIQUE (Nombre_Sucursal)
);

CREATE TABLE Profesional (
    Id_Profesional INT IDENTITY(1,1) PRIMARY KEY,
    Id_Usuario INT NOT NULL,
    Id_Sucursal INT NULL,
    Direccion VARCHAR(255),
    Jornada VARCHAR(100),
    Especialidad VARCHAR(150),
    CONSTRAINT UQ_Profesional_Usuario UNIQUE (Id_Usuario),
    CONSTRAINT FK_Profesional_Personal FOREIGN KEY (Id_Usuario) REFERENCES Personal(Id_Usuario) ON UPDATE CASCADE ON DELETE NO ACTION,
    CONSTRAINT FK_Profesional_Sucursal FOREIGN KEY (Id_Sucursal) REFERENCES Sucursal(Id_Sucursal) ON UPDATE CASCADE ON DELETE SET NULL
);

CREATE TABLE Tecnico_Sucursal (
    Id_Profesional INT NOT NULL,
    Id_Sucursal INT NOT NULL,
    Horarios_Atencion VARCHAR(255),
    PRIMARY KEY (Id_Profesional, Id_Sucursal),
    CONSTRAINT FK_Tecnico_Profesional FOREIGN KEY (Id_Profesional) REFERENCES Profesional(Id_Profesional) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT FK_Tecnico_Sucursal FOREIGN KEY (Id_Sucursal) REFERENCES Sucursal(Id_Sucursal) ON UPDATE NO ACTION ON DELETE NO ACTION
);

CREATE TABLE Equipamiento (
    Id_Equipo INT IDENTITY(1,1) PRIMARY KEY,
    Nombre_Equipo VARCHAR(150) NOT NULL,
    Id_Sucursal INT NOT NULL,
    CONSTRAINT UQ_Equipamiento_Sucursal UNIQUE (Nombre_Equipo, Id_Sucursal),
    CONSTRAINT FK_Equipamiento_Sucursal FOREIGN KEY (Id_Sucursal) REFERENCES Sucursal(Id_Sucursal) ON UPDATE CASCADE ON DELETE NO ACTION
);

CREATE INDEX IDX_Equipamiento_Sucursal ON Equipamiento(Id_Sucursal);

CREATE TABLE Paciente (
    Id_Paciente INT IDENTITY(1,1) PRIMARY KEY,
    Nombre_Apellido VARCHAR(200) NOT NULL,
    Password_Hash VARCHAR(255),
    Email VARCHAR(150),
    Credenciales VARCHAR(255),
    Datos_Actualizables VARCHAR(MAX),
    CONSTRAINT UQ_Paciente_Email UNIQUE (Email),
    CONSTRAINT UQ_Paciente_Credenciales UNIQUE (Credenciales)
);

CREATE TABLE Historial_Clinico (
    Id_Historial INT IDENTITY(1,1) PRIMARY KEY,
    Id_Paciente INT NOT NULL,
    Id_Profesional INT NOT NULL,
    Fecha DATE NOT NULL,
    Notas_Observaciones VARCHAR(MAX),
    CONSTRAINT FK_Historial_Paciente FOREIGN KEY (Id_Paciente) REFERENCES Paciente(Id_Paciente) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT FK_Historial_Profesional FOREIGN KEY (Id_Profesional) REFERENCES Profesional(Id_Profesional) ON UPDATE NO ACTION ON DELETE NO ACTION
);
CREATE INDEX IDX_Historial_Paciente ON Historial_Clinico(Id_Paciente);
CREATE INDEX IDX_Historial_Profesional ON Historial_Clinico(Id_Profesional);
CREATE INDEX IDX_Historial_Fecha ON Historial_Clinico(Fecha);

CREATE TABLE Orden_Referencia (
    Id_Orden INT IDENTITY(1,1) PRIMARY KEY,
    Id_Paciente INT NOT NULL,
    Inf_Profesional_Ref VARCHAR(MAX),
    Detalles_Solicitud VARCHAR(MAX),
    Fecha_Orden DATETIME NOT NULL DEFAULT GETDATE(),
    Estado VARCHAR(50) NOT NULL DEFAULT 'Pendiente',
    CONSTRAINT FK_Orden_Paciente FOREIGN KEY (Id_Paciente) REFERENCES Paciente(Id_Paciente) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT CHK_Orden_Estado CHECK (Estado IN ('Pendiente', 'Utilizada', 'Cancelada'))
);
CREATE INDEX IDX_Orden_Paciente ON Orden_Referencia(Id_Paciente);
CREATE INDEX IDX_Orden_Estado ON Orden_Referencia(Estado);

CREATE TABLE Servicio (
    Id_Servicio INT IDENTITY(1,1) PRIMARY KEY,
    Nombre_Servicio VARCHAR(150) NOT NULL,
    Requiere_Referencia BIT NOT NULL DEFAULT 0, -- BIT reemplaza a BOOLEAN en SQL Server
    CONSTRAINT UQ_Servicio_Nombre UNIQUE (Nombre_Servicio)
);

CREATE TABLE Cita (
    Id_Cita INT IDENTITY(1,1) PRIMARY KEY,
    Id_Paciente INT NOT NULL,
    Id_Profesional INT NOT NULL,
    Id_Sucursal INT NOT NULL,
    Id_Servicio INT NOT NULL,
    Fecha_Hora DATETIME NOT NULL,
    Estado VARCHAR(50) NOT NULL DEFAULT 'Pendiente',
    CONSTRAINT FK_Cita_Paciente FOREIGN KEY (Id_Paciente) REFERENCES Paciente(Id_Paciente) ON UPDATE CASCADE ON DELETE NO ACTION,
    CONSTRAINT FK_Cita_Profesional FOREIGN KEY (Id_Profesional) REFERENCES Profesional(Id_Profesional) ON UPDATE NO ACTION ON DELETE NO ACTION,
    CONSTRAINT FK_Cita_Sucursal FOREIGN KEY (Id_Sucursal) REFERENCES Sucursal(Id_Sucursal) ON UPDATE NO ACTION ON DELETE NO ACTION,
    CONSTRAINT FK_Cita_Servicio FOREIGN KEY (Id_Servicio) REFERENCES Servicio(Id_Servicio) ON UPDATE NO ACTION ON DELETE NO ACTION,
    CONSTRAINT CHK_Cita_Estado CHECK (Estado IN ('Pendiente', 'Confirmada', 'Atendida', 'Cancelada', 'No asistio'))
);
CREATE INDEX IDX_Cita_Paciente ON Cita(Id_Paciente);
CREATE INDEX IDX_Cita_Profesional ON Cita(Id_Profesional);
CREATE INDEX IDX_Cita_Sucursal ON Cita(Id_Sucursal);
CREATE INDEX IDX_Cita_Servicio ON Cita(Id_Servicio);
CREATE INDEX IDX_Cita_Fecha_Hora ON Cita(Fecha_Hora);   
CREATE INDEX IDX_Cita_Estado ON Cita(Estado);
GO

SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE';
PRINT 'Base de datos creada correctamente.';
GO
