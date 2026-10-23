-- Migración incremental para el esquema MariaDB definido en Data/database.sql.
-- Ejecutar sobre sistema_clinico existente; no elimina ni modifica datos previos.
-- Id_Tecnico apunta a Profesional.Id_Profesional: Profesional vincula al usuario
-- Personal y su rol mediante Personal.Id_Rol -> Rol.Id_Rol.
USE sistema_clinico;

CREATE TABLE IF NOT EXISTS Informe_Cita (
    Id_Informe INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Id_Cita INT UNSIGNED NOT NULL,
    Id_Tecnico INT UNSIGNED NOT NULL,
    Nombre_Original VARCHAR(255) NOT NULL,
    Ruta_Interna VARCHAR(512) NOT NULL,
    Fecha_Subida DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT UQ_Informe_Cita_Cita UNIQUE (Id_Cita),
    CONSTRAINT FK_Informe_Cita_Cita FOREIGN KEY (Id_Cita)
        REFERENCES Cita(Id_Cita) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT FK_Informe_Cita_Tecnico FOREIGN KEY (Id_Tecnico)
        REFERENCES Profesional(Id_Profesional) ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX IDX_Informe_Cita_Tecnico (Id_Tecnico),
    INDEX IDX_Informe_Cita_Fecha (Fecha_Subida)
) ENGINE=InnoDB;

-- Antes de insertar desde una aplicación SQL, autorizar Id_Tecnico verificando
-- Profesional -> Personal -> Rol (Nombre_Rol = 'tecnico'). El servicio JSON
-- existente valida el rol desde UserRepository en cada endpoint.
