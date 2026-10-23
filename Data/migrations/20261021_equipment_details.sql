-- Migracion aditiva e idempotente para instalaciones con Equipamiento basica.
-- Repetirla es seguro: solo agrega columnas o la restriccion que falten.
USE sistema_clinico;

DROP PROCEDURE IF EXISTS migrar_equipamiento_detalle;
DELIMITER $$
CREATE PROCEDURE migrar_equipamiento_detalle()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Equipamiento' AND COLUMN_NAME = 'Tipo_Equipo'
    ) THEN
        ALTER TABLE Equipamiento ADD COLUMN Tipo_Equipo VARCHAR(100) NOT NULL DEFAULT 'Equipo general' AFTER Nombre_Equipo;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Equipamiento' AND COLUMN_NAME = 'Marca') THEN
        ALTER TABLE Equipamiento ADD COLUMN Marca VARCHAR(100) NULL AFTER Tipo_Equipo;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Equipamiento' AND COLUMN_NAME = 'Modelo') THEN
        ALTER TABLE Equipamiento ADD COLUMN Modelo VARCHAR(100) NULL AFTER Marca;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Equipamiento' AND COLUMN_NAME = 'Numero_Serie') THEN
        ALTER TABLE Equipamiento ADD COLUMN Numero_Serie VARCHAR(100) NULL AFTER Modelo;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Equipamiento' AND COLUMN_NAME = 'Estado_Operativo') THEN
        ALTER TABLE Equipamiento ADD COLUMN Estado_Operativo VARCHAR(30) NOT NULL DEFAULT 'Operativo' AFTER Id_Sucursal;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Equipamiento' AND COLUMN_NAME = 'Fecha_Incorporacion') THEN
        ALTER TABLE Equipamiento ADD COLUMN Fecha_Incorporacion DATE NULL AFTER Estado_Operativo;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Equipamiento' AND COLUMN_NAME = 'Observaciones') THEN
        ALTER TABLE Equipamiento ADD COLUMN Observaciones TEXT NULL AFTER Fecha_Incorporacion;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Equipamiento' AND COLUMN_NAME = 'Creado_En') THEN
        ALTER TABLE Equipamiento ADD COLUMN Creado_En TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER Observaciones;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Equipamiento' AND COLUMN_NAME = 'Actualizado_En') THEN
        ALTER TABLE Equipamiento ADD COLUMN Actualizado_En TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER Creado_En;
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'Equipamiento' AND CONSTRAINT_NAME = 'CHK_Equipamiento_Estado'
    ) THEN
        ALTER TABLE Equipamiento ADD CONSTRAINT CHK_Equipamiento_Estado
            CHECK (Estado_Operativo IN ('Operativo', 'Mantenimiento', 'Fuera de servicio'));
    END IF;
END$$
DELIMITER ;

CALL migrar_equipamiento_detalle();
DROP PROCEDURE migrar_equipamiento_detalle;
