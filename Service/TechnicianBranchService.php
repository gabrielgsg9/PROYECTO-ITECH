<?php
require_once __DIR__ . '/../Repository/UserRepository.php';
require_once __DIR__ . '/../Repository/BranchRepository.php';
require_once __DIR__ . '/../Repository/TechnicianBranchStore.php';
require_once __DIR__ . '/../Repository/TechnicianBranchStoreFactory.php';

/** Assignment rules; the selected store controls JSON vs SQL persistence. */
final class TechnicianBranchService
{
    private UserRepository $users;
    private BranchRepository $branches;
    private TechnicianBranchStore $assignments;

    public function __construct(?UserRepository $users = null, ?BranchRepository $branches = null, ?TechnicianBranchStore $assignments = null)
    {
        $this->users = $users ?? new UserRepository();
        $this->branches = $branches ?? new BranchRepository();
        $this->assignments = $assignments ?? TechnicianBranchStoreFactory::create();
    }

    public function technicians(): array
    {
        $technicians = array_values(array_filter($this->users->all(), static fn($user) => is_array($user) && ($user['role'] ?? '') === 'tecnico' && (int) ($user['id'] ?? 0) > 0));
        usort($technicians, static fn($a, $b) => strnatcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? '')) ?: ((int) $a['id'] <=> (int) $b['id']));
        return $technicians;
    }

    public function branches(): array
    {
        $branches = array_values(array_filter($this->branches->all(), static fn($branch) => is_array($branch) && (int) ($branch['id'] ?? 0) > 0));
        usort($branches, static fn($a, $b) => strnatcasecmp((string) ($a['nombre'] ?? ''), (string) ($b['nombre'] ?? '')));
        return $branches;
    }

    public function assignments(): array
    {
        return $this->assignments->allAssignments();
    }

    public function save(int $actorId, int $technicianId, array $branchIds): void
    {
        $actor = $actorId > 0 ? $this->users->findById($actorId) : null;
        if (!$actor || ($actor['role'] ?? '') !== 'administrador') throw new DomainException('Solo un administrador puede modificar asignaciones.');
        if ($technicianId < 1 || $technicianId === $actorId) throw new InvalidArgumentException('El técnico seleccionado no es válido.');
        $technician = $this->users->findById($technicianId);
        if (!$technician || ($technician['role'] ?? '') !== 'tecnico') throw new DomainException('El usuario seleccionado no existe o no tiene rol técnico.');

        $available = [];
        foreach ($this->branches() as $branch) $available[(int) $branch['id']] = true;
        $normalized = [];
        foreach ($branchIds as $branchId) {
            $id = filter_var($branchId, FILTER_VALIDATE_INT);
            if ($id === false || $id === null || $id < 1 || !isset($available[$id])) throw new InvalidArgumentException('Una de las sucursales seleccionadas no existe.');
            $normalized[] = $id;
        }
        $this->assignments->replaceForTechnician($technicianId, array_values(array_unique($normalized)));
    }
}
