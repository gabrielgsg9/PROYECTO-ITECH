<?php
require_once __DIR__ . '/../Data/connection.php';
require_once __DIR__ . '/TechnicianBranchStore.php';

/** MySQL implementation mapped to Personal.Id_Usuario and Tecnico_Sucursal. */
final class SqlTechnicianBranchRepository implements TechnicianBranchStore
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Conectar::conexion();
    }

    public function allAssignments(): array
    {
        $statement = $this->pdo->query(
            "SELECT pe.Id_Usuario AS tecnico_id, ts.Id_Sucursal AS sucursal_id
             FROM Tecnico_Sucursal ts
             INNER JOIN Profesional p ON p.Id_Profesional = ts.Id_Profesional
             INNER JOIN Personal pe ON pe.Id_Usuario = p.Id_Usuario
             INNER JOIN Rol r ON r.Id_Rol = pe.Id_Rol
             WHERE LOWER(r.Nombre_Rol) = 'tecnico'
             ORDER BY pe.Id_Usuario, ts.Id_Sucursal"
        );
        $assignments = [];
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $assignments[(int) $row['tecnico_id']][] = (int) $row['sucursal_id'];
        }
        return $assignments;
    }

    public function branchIdsForTechnician(int $technicianId): array
    {
        $statement = $this->pdo->prepare(
            "SELECT ts.Id_Sucursal
             FROM Tecnico_Sucursal ts
             INNER JOIN Profesional p ON p.Id_Profesional = ts.Id_Profesional
             INNER JOIN Personal pe ON pe.Id_Usuario = p.Id_Usuario
             INNER JOIN Rol r ON r.Id_Rol = pe.Id_Rol
             WHERE pe.Id_Usuario = :technician_id AND LOWER(r.Nombre_Rol) = 'tecnico'
             ORDER BY ts.Id_Sucursal"
        );
        $statement->execute(['technician_id' => $technicianId]);
        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    public function replaceForTechnician(int $technicianId, array $branchIds): void
    {
        $branchIds = array_values(array_unique(array_map('intval', $branchIds)));
        $this->pdo->beginTransaction();
        try {
            $technician = $this->pdo->prepare(
                "SELECT p.Id_Profesional
                 FROM Profesional p
                 INNER JOIN Personal pe ON pe.Id_Usuario = p.Id_Usuario
                 INNER JOIN Rol r ON r.Id_Rol = pe.Id_Rol
                 WHERE pe.Id_Usuario = :technician_id AND LOWER(r.Nombre_Rol) = 'tecnico'
                 FOR UPDATE"
            );
            $technician->execute(['technician_id' => $technicianId]);
            $professionalId = $technician->fetchColumn();
            if ($professionalId === false) throw new DomainException('El usuario seleccionado no existe o no es técnico.');

            if ($branchIds !== []) {
                $placeholders = implode(',', array_fill(0, count($branchIds), '?'));
                $branches = $this->pdo->prepare("SELECT Id_Sucursal FROM Sucursal WHERE Id_Sucursal IN ($placeholders)");
                $branches->execute($branchIds);
                if (count($branches->fetchAll(PDO::FETCH_COLUMN)) !== count($branchIds)) {
                    throw new DomainException('Una o más sucursales seleccionadas no existen.');
                }
            }

            $delete = $this->pdo->prepare('DELETE FROM Tecnico_Sucursal WHERE Id_Profesional = :professional_id');
            $delete->execute(['professional_id' => (int) $professionalId]);
            $insert = $this->pdo->prepare('INSERT INTO Tecnico_Sucursal (Id_Profesional, Id_Sucursal) VALUES (:professional_id, :branch_id)');
            foreach ($branchIds as $branchId) $insert->execute(['professional_id' => (int) $professionalId, 'branch_id' => $branchId]);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $exception;
        }
    }
}
