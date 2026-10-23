<?php
require_once __DIR__ . '/../Repository/EquipmentStore.php';
require_once __DIR__ . '/../Repository/EquipmentStoreFactory.php';
require_once __DIR__ . '/../Repository/BranchRepository.php';
require_once __DIR__ . '/../Repository/TechnicianBranchStore.php';
require_once __DIR__ . '/../Repository/TechnicianBranchStoreFactory.php';

/** Equipment rules and branch authorization, independent of the storage adapter. */
final class EquipmentService
{
    public const STATUSES = ['Operativo', 'Mantenimiento', 'Fuera de servicio'];
    private EquipmentStore $equipment;
    private BranchRepository $branches;
    private TechnicianBranchStore $technicianBranches;

    public function __construct(?EquipmentStore $equipment = null, ?BranchRepository $branches = null, ?TechnicianBranchStore $technicianBranches = null)
    {
        $this->equipment = $equipment ?? EquipmentStoreFactory::create();
        $this->branches = $branches ?? new BranchRepository();
        $this->technicianBranches = $technicianBranches ?? TechnicianBranchStoreFactory::create();
    }

    public function branchesForTechnician(int $technicianId): array
    {
        $activeById = [];
        foreach ($this->branches->all() as $branch) {
            if (is_array($branch) && (!array_key_exists('activo', $branch) || (bool) $branch['activo'])) {
                $activeById[(int) ($branch['id'] ?? 0)] = $branch;
            }
        }
        $assigned = [];
        foreach ($this->technicianBranches->branchIdsForTechnician($technicianId) as $id) {
            if (isset($activeById[$id])) $assigned[] = $activeById[$id];
        }
        return $assigned;
    }

    public function listForTechnicianBranch(int $technicianId, int $branchId): array
    {
        $this->assertBranchAccess($technicianId, $branchId);
        return $this->equipment->allForBranch($branchId);
    }

    public function findForTechnicianBranch(int $technicianId, int $branchId, int $equipmentId): ?array
    {
        $this->assertBranchAccess($technicianId, $branchId);
        return $this->equipment->findForBranch($equipmentId, $branchId);
    }

    public function create(int $technicianId, int $branchId, array $input): int
    {
        $this->assertBranchAccess($technicianId, $branchId);
        return $this->equipment->create($branchId, $this->validate($input));
    }

    public function update(int $technicianId, int $branchId, int $equipmentId, array $input): bool
    {
        $this->assertBranchAccess($technicianId, $branchId);
        if ($equipmentId < 1 || !$this->equipment->findForBranch($equipmentId, $branchId)) return false;
        return $this->equipment->updateForBranch($equipmentId, $branchId, $this->validate($input));
    }

    private function assertBranchAccess(int $technicianId, int $branchId): void
    {
        if ($branchId < 1) throw new DomainException('Seleccioná una sucursal válida.');
        foreach ($this->branchesForTechnician($technicianId) as $branch) {
            if ((int) ($branch['id'] ?? 0) === $branchId) return;
        }
        throw new DomainException('No tenés acceso a esa sucursal.');
    }

    private function validate(array $input): array
    {
        $name = trim((string) ($input['nombre'] ?? ''));
        $type = trim((string) ($input['tipo'] ?? ''));
        $brand = trim((string) ($input['marca'] ?? ''));
        $model = trim((string) ($input['modelo'] ?? ''));
        $serial = trim((string) ($input['numero_serie'] ?? ''));
        $status = trim((string) ($input['estado'] ?? 'Operativo'));
        $acquired = trim((string) ($input['fecha_incorporacion'] ?? ''));
        $notes = trim((string) ($input['observaciones'] ?? ''));
        $length = static fn(string $value): int => function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);

        if ($name === '' || $type === '') throw new InvalidArgumentException('Completá el nombre y el tipo de equipo.');
        if ($length($name) > 150 || $length($type) > 100 || $length($brand) > 100 || $length($model) > 100 || $length($serial) > 100 || $length($notes) > 5000) {
            throw new InvalidArgumentException('Revisá la longitud de los campos del equipo.');
        }
        if (!in_array($status, self::STATUSES, true)) throw new InvalidArgumentException('Seleccioná un estado operativo válido.');
        if ($acquired !== '') {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $acquired);
            $errors = DateTimeImmutable::getLastErrors();
            if (!$date || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $date->format('Y-m-d') !== $acquired) {
                throw new InvalidArgumentException('La fecha de incorporación no es válida.');
            }
        }

        return [
            'nombre' => $name,
            'tipo' => $type,
            'marca' => $brand !== '' ? $brand : null,
            'modelo' => $model !== '' ? $model : null,
            'numero_serie' => $serial !== '' ? $serial : null,
            'estado' => $status,
            'fecha_incorporacion' => $acquired !== '' ? $acquired : null,
            'observaciones' => $notes !== '' ? $notes : null,
        ];
    }
}
