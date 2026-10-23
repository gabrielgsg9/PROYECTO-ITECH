<?php
require_once __DIR__ . '/../Repository/AppointmentWorkflowStore.php';
require_once __DIR__ . '/../Repository/StudyWorkflowStore.php';

/** Coordinates report drafts and the explicit appointment completion transition. */
final class StudyWorkflowService
{
    private AppointmentWorkflowStore $appointments;
    private StudyWorkflowStore $studies;
    private DateTimeZone $timezone;

    public function __construct(AppointmentWorkflowStore $appointments, StudyWorkflowStore $studies, ?DateTimeZone $timezone = null)
    {
        $this->appointments = $appointments;
        $this->studies = $studies;
        $this->timezone = $timezone ?? new DateTimeZone(getenv('CLINICA_TIMEZONE') ?: 'America/Montevideo');
    }

    public function uploadReport(int $appointmentId, int $technicianId, string $originalName, string $temporaryPath): array
    {
        if ($appointmentId < 1 || $technicianId < 1) {
            throw new InvalidArgumentException('La cita o el técnico indicado no son válidos.');
        }
        return $this->appointments->withStudyUploadEligibleAppointment($appointmentId, function (array $appointment) use ($appointmentId, $technicianId, $originalName, $temporaryPath): array {
            if (($appointment['status'] ?? '') === 'Confirmada' && !$this->appointmentHasStarted($appointment)) {
                throw new DomainException('No se puede cargar el informe antes del horario de la cita.');
            }
            if ($this->studies->findByAppointment($appointmentId) !== null) {
                throw new DomainException('Ya existe un informe para esta cita.');
            }
            return $this->studies->save($appointmentId, $technicianId, $originalName, $temporaryPath);
        });
    }

    public function confirmCompleted(int $appointmentId): bool
    {
        if ($appointmentId < 1) {
            return false;
        }
        if ($this->studies->findByAppointment($appointmentId) === null) {
            throw new DomainException('Primero debe existir un informe asociado a la cita.');
        }
        $appointment = $this->appointments->findById($appointmentId);
        if (!$appointment) {
            return false;
        }
        if (($appointment['status'] ?? '') === 'Confirmada' && !$this->appointmentHasStarted($appointment)) {
            throw new DomainException('No se puede confirmar un estudio antes del horario de la cita.');
        }
        return $this->appointments->markAttended($appointmentId);
    }

    /** Return a displayable workflow phase without changing the appointment or report. */
    public function phaseFor(array $appointment, ?array $report): string
    {
        $status = (string) ($appointment['status'] ?? '');
        if (in_array($status, ['Cancelada', 'No asistio'], true)) {
            return 'closed';
        }
        if ($report !== null) {
            if ($status === 'Atendida') {
                return 'completed';
            }
            if ($status !== 'Confirmada') {
                return 'waiting_confirmation';
            }
            return $this->appointmentHasStarted($appointment) ? 'confirm_completion' : 'waiting_for_appointment';
        }
        if ($status === 'Atendida') {
            return 'ready_for_report';
        }
        if ($status === 'Confirmada') {
            return $this->appointmentHasStarted($appointment) ? 'ready_for_report' : 'not_started';
        }
        return 'not_ready';
    }

    private function appointmentHasStarted(array $appointment): bool
    {
        $date = $appointment['date'] ?? null;
        $time = $appointment['time'] ?? null;
        if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
            || !is_string($time) || !preg_match('/^\d{2}:\d{2}$/', $time)) {
            return false;
        }
        $scheduledAt = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $date . ' ' . $time, $this->timezone);
        $errors = DateTimeImmutable::getLastErrors();
        if (!$scheduledAt || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $scheduledAt->format('Y-m-d H:i') !== $date . ' ' . $time) {
            return false;
        }
        return $scheduledAt <= new DateTimeImmutable('now', $this->timezone);
    }
}
