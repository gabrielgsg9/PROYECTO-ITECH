<?php
/** Storage contract for equipment. Implementations may use JSON or SQL. */
interface EquipmentStore
{
    public function allForBranch(int $branchId): array;
    public function findForBranch(int $equipmentId, int $branchId): ?array;
    public function create(int $branchId, array $equipment): int;
    public function updateForBranch(int $equipmentId, int $branchId, array $equipment): bool;
}
