<?php
require_once __DIR__ . '/TechnicianBranchStore.php';
require_once __DIR__ . '/AppConfigRepository.php';

/** JSON implementation; assignments are stored in the dedicated config key. */
final class TechnicianBranchRepository implements TechnicianBranchStore
{
    private AppConfigRepository $config;

    public function __construct(?AppConfigRepository $config = null)
    {
        $this->config = $config ?? new AppConfigRepository();
    }

    public function allAssignments(): array
    {
        $rows = $this->assignmentRows($this->config->all());
        $assignments = [];
        foreach ($rows as $row) {
            $technicianId = (int) $row['tecnico_id'];
            $assignments[$technicianId] = array_values(array_unique(array_merge($assignments[$technicianId] ?? [], $row['sucursal_ids'])));
        }
        ksort($assignments);
        return $assignments;
    }

    public function branchIdsForTechnician(int $technicianId): array
    {
        return $this->allAssignments()[$technicianId] ?? [];
    }

    public function replaceForTechnician(int $technicianId, array $branchIds): void
    {
        if ($technicianId < 1) throw new InvalidArgumentException('El identificador del técnico no es válido.');
        $branchIds = $this->normalizeBranchIds($branchIds);
        $this->config->update(function (&$config) use ($technicianId, $branchIds) {
            $rows = $this->assignmentRows($config);
            $rows = array_values(array_filter($rows, static fn($row) => (int) $row['tecnico_id'] !== $technicianId));
            if ($branchIds !== []) $rows[] = ['tecnico_id' => $technicianId, 'sucursal_ids' => $branchIds];
            usort($rows, static fn($a, $b) => (int) $a['tecnico_id'] <=> (int) $b['tecnico_id']);
            $config['technician_branch_assignments'] = $rows;
        });
    }

    private function assignmentRows(array $config): array
    {
        $rows = $config['technician_branch_assignments'] ?? [];
        if (!is_array($rows) || ($rows !== [] && !array_is_list($rows))) {
            throw new RuntimeException('Las asignaciones de técnicos en Data/config.json tienen un formato inválido.');
        }
        $normalized = [];
        foreach ($rows as $row) {
            if (!is_array($row)) throw new RuntimeException('Hay una asignación de técnico con formato inválido.');
            $technicianId = filter_var($row['tecnico_id'] ?? null, FILTER_VALIDATE_INT);
            if ($technicianId === false || $technicianId === null || $technicianId < 1 || !isset($row['sucursal_ids']) || !is_array($row['sucursal_ids'])) {
                throw new RuntimeException('Hay una asignación de técnico con identificadores inválidos.');
            }
            $normalized[] = ['tecnico_id' => $technicianId, 'sucursal_ids' => $this->normalizeBranchIds($row['sucursal_ids'])];
        }
        return $normalized;
    }

    private function normalizeBranchIds(array $branchIds): array
    {
        $normalized = [];
        foreach ($branchIds as $id) {
            $branchId = filter_var($id, FILTER_VALIDATE_INT);
            if ($branchId === false || $branchId === null || $branchId < 1) throw new InvalidArgumentException('Hay un identificador de sucursal inválido.');
            $normalized[] = $branchId;
        }
        return array_values(array_unique($normalized));
    }
}
