<?php
require_once __DIR__ . '/StudyReadStore.php';

interface StudyWorkflowStore extends StudyReadStore
{
    public function findByAppointment(int $appointmentId): ?array;

    public function save(int $appointmentId, int $technicianId, string $originalName, string $temporaryPath): array;
}
