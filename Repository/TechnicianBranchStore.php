<?php
/** Storage contract for technician-to-branch assignments. */
interface TechnicianBranchStore
{
    /** @return array<int, int[]> keyed by technician user ID */
    public function allAssignments(): array;
    /** @return int[] */
    public function branchIdsForTechnician(int $technicianId): array;
    public function replaceForTechnician(int $technicianId, array $branchIds): void;
}
