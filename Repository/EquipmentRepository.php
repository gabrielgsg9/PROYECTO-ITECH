<?php
require_once __DIR__ . '/../Data/connection.php';
require_once __DIR__ . '/EquipmentStore.php';

/** SQL persistence for equipment. */
final class EquipmentRepository implements EquipmentStore
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Conectar::conexion();
    }
    public function allForBranch(int $branchId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT Id_Equipo AS id, Nombre_Equipo AS nombre, Tipo_Equipo AS tipo,
                    Marca AS marca, Modelo AS modelo, Numero_Serie AS numero_serie,
                    Id_Sucursal AS sucursal_id, Estado_Operativo AS estado,
                    Fecha_Incorporacion AS fecha_incorporacion, Observaciones AS observaciones,
                    Creado_En AS creado_en, Actualizado_En AS actualizado_en
             FROM Equipamiento WHERE Id_Sucursal = :branch_id ORDER BY Nombre_Equipo, Id_Equipo'
        );
        $statement->execute(['branch_id' => $branchId]);
        return $statement->fetchAll();
    }

    public function findForBranch(int $equipmentId, int $branchId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT Id_Equipo AS id, Nombre_Equipo AS nombre, Tipo_Equipo AS tipo,
                    Marca AS marca, Modelo AS modelo, Numero_Serie AS numero_serie,
                    Id_Sucursal AS sucursal_id, Estado_Operativo AS estado,
                    Fecha_Incorporacion AS fecha_incorporacion, Observaciones AS observaciones,
                    Creado_En AS creado_en, Actualizado_En AS actualizado_en
             FROM Equipamiento WHERE Id_Equipo = :equipment_id AND Id_Sucursal = :branch_id'
        );
        $statement->execute(['equipment_id' => $equipmentId, 'branch_id' => $branchId]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function create(int $branchId, array $equipment): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO Equipamiento
                (Nombre_Equipo, Tipo_Equipo, Marca, Modelo, Numero_Serie, Id_Sucursal,
                 Estado_Operativo, Fecha_Incorporacion, Observaciones)
             VALUES (:name, :type, :brand, :model, :serial, :branch_id, :status, :acquired, :notes)'
        );
        try {
            $statement->execute($this->parameters($branchId, $equipment));
        } catch (PDOException $exception) {
            if (($exception->errorInfo[1] ?? null) === 1062) {
                throw new DomainException('Ya existe un equipo con ese nombre en la sucursal seleccionada.', 0, $exception);
            }
            throw $exception;
        }
        return (int) $this->pdo->lastInsertId();
    }

    public function updateForBranch(int $equipmentId, int $branchId, array $equipment): bool
    {
        $parameters = $this->parameters($branchId, $equipment);
        $parameters['equipment_id'] = $equipmentId;
        $statement = $this->pdo->prepare(
            'UPDATE Equipamiento SET Nombre_Equipo = :name, Tipo_Equipo = :type, Marca = :brand,
                Modelo = :model, Numero_Serie = :serial, Estado_Operativo = :status,
                Fecha_Incorporacion = :acquired, Observaciones = :notes
             WHERE Id_Equipo = :equipment_id AND Id_Sucursal = :branch_id'
        );
        try {
            $statement->execute($parameters);
        } catch (PDOException $exception) {
            if (($exception->errorInfo[1] ?? null) === 1062) {
                throw new DomainException('Ya existe otro equipo con ese nombre en la sucursal seleccionada.', 0, $exception);
            }
            throw $exception;
        }
        return $this->findForBranch($equipmentId, $branchId) !== null;
    }

    private function parameters(int $branchId, array $equipment): array
    {
        return [
            'name' => $equipment['nombre'],
            'type' => $equipment['tipo'],
            'brand' => $equipment['marca'],
            'model' => $equipment['modelo'],
            'serial' => $equipment['numero_serie'],
            'branch_id' => $branchId,
            'status' => $equipment['estado'],
            'acquired' => $equipment['fecha_incorporacion'],
            'notes' => $equipment['observaciones'],
        ];
    }
}
