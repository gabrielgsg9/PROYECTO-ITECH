<?php
require_once __DIR__ . '/AppConfigRepository.php';
require_once __DIR__ . '/EquipmentStore.php';
require_once __DIR__ . '/JsonEquipmentRepository.php';
require_once __DIR__ . '/EquipmentRepository.php';

final class EquipmentStoreFactory
{
    public static function create(?AppConfigRepository $config = null): EquipmentStore
    {
        $settings = ($config ?? new AppConfigRepository())->all();
        $provider = strtolower(trim((string) ($settings['storage']['equipment'] ?? 'json')));
        return match ($provider) {
            'json' => new JsonEquipmentRepository(),
            'sql' => new EquipmentRepository(),
            default => throw new RuntimeException('El proveedor de almacenamiento de equipos configurado no es válido.'),
        };
    }
}
