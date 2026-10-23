<?php
require_once __DIR__ . '/AppointmentReadStore.php';
require_once __DIR__ . '/StudyReadStore.php';
require_once __DIR__ . '/AppointmentRepository.php';
require_once __DIR__ . '/ReportRepository.php';

final class DashboardRepositoryFactory
{
    /** @return array{appointments: AppointmentReadStore, studies: StudyReadStore} */
    public static function create(): array
    {
        $provider = strtolower(trim((string) (getenv('CLINICA_DATA_PROVIDER') ?: 'json')));
        if ($provider !== 'json') {
            throw new RuntimeException('El proveedor de datos solicitado no está disponible.');
        }

        return [
            'appointments' => new AppointmentRepository(),
            'studies' => new ReportRepository(),
        ];
    }
}
