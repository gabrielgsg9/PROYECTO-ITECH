<?php
require_once __DIR__ . '/../Repository/UserRepository.php';
require_once __DIR__ . '/../Repository/AppointmentRepository.php';

/** Reglas de negocio compartidas para altas de citas desde paciente y recepción. */
final class AppointmentBookingService
{
    private UserRepository $users;
    private AppointmentRepository $appointments;

    public function __construct(?UserRepository $users = null, ?AppointmentRepository $appointments = null)
    {
        $this->users = $users ?? new UserRepository();
        $this->appointments = $appointments ?? new AppointmentRepository();
    }

    public function book(int $patientId, array $selection, array $branches, array $services, array $professionals, array $availableDates): array
    {
        $patient = $this->users->findById($patientId);
        if (!$patient || ($patient['role'] ?? '') !== 'paciente') throw new DomainException('El paciente seleccionado no existe.');
        $branchId = filter_var($selection['branch_id'] ?? null, FILTER_VALIDATE_INT);
        $serviceId = filter_var($selection['service_id'] ?? null, FILTER_VALIDATE_INT);
        $professionalId = ($selection['professional_id'] ?? '') === '' ? null : filter_var($selection['professional_id'], FILTER_VALIDATE_INT);
        $date = (string) ($selection['date'] ?? '');
        $time = (string) ($selection['time'] ?? '');
        $notes = trim((string) ($selection['notes'] ?? ''));
        if (!$branchId || !$serviceId || ($professionalId !== null && (!$professionalId || $professionalId === false))) throw new InvalidArgumentException('Seleccioná una sucursal, un servicio y un profesional válidos.');
        if ($date === '' || !in_array($date, $availableDates, true)) throw new InvalidArgumentException('La fecha seleccionada no está disponible.');
        if (!in_array($time, ['08:00','08:30','09:00','09:30','10:00','10:30','11:00','11:30','14:00','14:30','15:00','15:30','16:00','16:30','17:00','17:30'], true)) throw new InvalidArgumentException('El horario seleccionado no está disponible.');
        if (strlen($notes) > 1000) throw new InvalidArgumentException('Las notas no pueden superar los 1000 caracteres.');
        $branch = $this->findById($branches, (int) $branchId);
        $service = $this->findById($services, (int) $serviceId);
        $professional = $professionalId ? $this->findById($professionals, (int) $professionalId) : null;
        if (!$branch || !$service || ($professionalId && !$professional)) throw new InvalidArgumentException('La sucursal, el servicio o el profesional seleccionado no es válido.');
        $appointment = [
            'patient_id' => (int) $patient['id'], 'patient_name' => (string) $patient['name'],
            'branch_id' => (int) $branch['id'], 'branch_name' => (string) $branch['nombre'],
            'service_id' => (int) $service['id'], 'service_name' => (string) $service['nombre'],
            'professional_id' => $professional ? (int) $professional['id'] : null,
            'professional_name' => (string) ($professional['nombre'] ?? 'Sin preferencia'),
            'date' => $date, 'time' => $time, 'notes' => $notes, 'status' => 'Pendiente',
        ];
        $durationByService = [];
        foreach ($services as $row) $durationByService[(int) ($row['id'] ?? 0)] = max(1, (int) ($row['duracion'] ?? 30));
        $conflictsWith = static function (array $existing, array $candidate) use ($durationByService): bool {
            if ((string) ($existing['date'] ?? '') !== (string) $candidate['date']) return false;
            $sameBranch = (int) ($existing['branch_id'] ?? 0) === (int) $candidate['branch_id'];
            $sameProfessional = !empty($candidate['professional_id']) && (int) ($existing['professional_id'] ?? 0) === (int) $candidate['professional_id'];
            if (!$sameBranch && !$sameProfessional) return false;
            $minutes = static function (string $time): int { [$hour, $minute] = array_map('intval', explode(':', $time)); return $hour * 60 + $minute; };
            if (!preg_match('/^\d{2}:\d{2}$/', (string) ($existing['time'] ?? ''))) return false;
            $existingStart = $minutes((string) ($existing['time'] ?? ''));
            $candidateStart = $minutes((string) $candidate['time']);
            $existingDuration = (int) ($existing['duration_minutes'] ?? $durationByService[(int) ($existing['service_id'] ?? 0)] ?? 30);
            $candidateDuration = (int) ($durationByService[(int) $candidate['service_id']] ?? 30);
            return $existingStart < $candidateStart + $candidateDuration && $candidateStart < $existingStart + $existingDuration;
        };
        return $this->appointments->createIfSlotAvailable($appointment, $conflictsWith);
    }

    private function findById(array $rows, int $id): ?array
    {
        foreach ($rows as $row) if ((int) ($row['id'] ?? 0) === $id) return $row;
        return null;
    }
}
