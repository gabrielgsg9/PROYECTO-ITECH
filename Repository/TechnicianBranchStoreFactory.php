<?php
require_once __DIR__ . '/AppConfigRepository.php';
require_once __DIR__ . '/TechnicianBranchStore.php';
require_once __DIR__ . '/TechnicianBranchRepository.php';
require_once __DIR__ . '/SqlTechnicianBranchRepository.php';

final class TechnicianBranchStoreFactory
{
    public static function create(?AppConfigRepository $config = null): TechnicianBranchStore
    {
        $settings = ($config ?? new AppConfigRepository())->all();
        $provider = strtolower(trim((string) ($settings['storage']['technician_branches'] ?? 'json')));
        return match ($provider) {
            'json' => new TechnicianBranchRepository($config),
            'sql' => new SqlTechnicianBranchRepository(),
            default => throw new RuntimeException('El proveedor de asignaciones de sucursal configurado no es válido.'),
        };
    }
}
