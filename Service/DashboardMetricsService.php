<?php
require_once __DIR__ . '/../Repository/AppointmentReadStore.php';
require_once __DIR__ . '/../Repository/StudyReadStore.php';

/** Reglas de indicadores, independientes del formato de almacenamiento. */
final class DashboardMetricsService
{
    private AppointmentReadStore $appointments;
    private StudyReadStore $studies;
    private DateTimeZone $timezone;

    public function __construct(AppointmentReadStore $appointments, StudyReadStore $studies, ?DateTimeZone $timezone = null)
    {
        $this->appointments = $appointments;
        $this->studies = $studies;
        $configuredTimezone = getenv('CLINICA_TIMEZONE') ?: 'America/Montevideo';
        $this->timezone = $timezone ?? new DateTimeZone($configuredTimezone);
    }

    /** @return array<string, mixed> */
    public function forToday(?DateTimeImmutable $now = null): array
    {
        $today = ($now ?? new DateTimeImmutable('now', $this->timezone))
            ->setTimezone($this->timezone)->format('Y-m-d');
        $warnings = [];
        $appointmentsById = [];
        $todayAppointments = [];

        foreach ($this->appointments->all() as $row) {
            if (!is_array($row)) {
                $warnings[] = 'Se omitió un registro de cita con formato inválido.';
                continue;
            }
            $id = filter_var($row['id'] ?? null, FILTER_VALIDATE_INT);
            $date = $this->validDate($row['date'] ?? null);
            $status = $this->normalizeStatus($row['status'] ?? null);
            if ($id === false || $id === null || $id < 1 || $date === null || $status === null) {
                $warnings[] = 'Se omitió una cita con identificador, fecha o estado incompleto o inválido.';
                continue;
            }
            if (!in_array($status, ['pendiente', 'confirmada', 'atendida', 'cancelada', 'no asistio'], true)) {
                $warnings[] = 'La cita ' . $id . ' tiene un estado no reconocido.';
                continue;
            }
            if (isset($appointmentsById[$id])) {
                $warnings[] = 'Se ignoró una cita duplicada (ID ' . $id . ').';
                continue;
            }
            $row['_dashboard_status'] = $status;
            $row['_dashboard_date'] = $date;
            $appointmentsById[$id] = $row;
            if ($date === $today) {
                $todayAppointments[] = $row;
            }
        }

        $scheduledToday = 0;
        $pendingToday = 0;
        $attendedToday = 0;
        foreach ($todayAppointments as $appointment) {
            $status = $appointment['_dashboard_status'];
            if ($status !== 'cancelada') {
                $scheduledToday++;
            }
            if (in_array($status, ['pendiente', 'confirmada'], true)) {
                $pendingToday++;
            }
            if ($status === 'atendida') {
                $attendedToday++;
            }
        }

        $completedStudyAppointments = [];
        foreach ($this->studies->all() as $study) {
            if (!is_array($study)) {
                $warnings[] = 'Se omitió un registro de estudio con formato inválido.';
                continue;
            }
            $appointmentId = filter_var($study['appointment_id'] ?? null, FILTER_VALIDATE_INT);
            $studyId = $study['id'] ?? null;
            $uploadedAt = $this->validTimestamp($study['uploaded_at'] ?? null);
            if (!is_string($studyId) || trim($studyId) === '' || $uploadedAt === null) {
                $warnings[] = 'Se omitió un informe con identificador o fecha de carga ausente o inválido.';
                continue;
            }
            if ($appointmentId === false || $appointmentId === null || $appointmentId < 1 || !isset($appointmentsById[$appointmentId])) {
                $warnings[] = 'El informe ' . $studyId . ' no tiene una cita válida asociada (cita indicada: ' . (string) ($study['appointment_id'] ?? 'ausente') . ').';
                continue;
            }
            if (array_key_exists('_file_available', $study) && $study['_file_available'] !== true) {
                $warnings[] = 'El informe ' . $studyId . ' de la cita ' . $appointmentId . ' no tiene un archivo PDF disponible.';
                continue;
            }
            if (isset($completedStudyAppointments[$appointmentId])) {
                $warnings[] = 'Se ignoró el informe duplicado ' . $studyId . ' para la cita ' . $appointmentId . '.';
                continue;
            }
            $appointmentDate = $appointmentsById[$appointmentId]['_dashboard_date'];
            if ($uploadedAt->setTimezone($this->timezone)->format('Y-m-d') < $appointmentDate) {
                $warnings[] = 'El informe ' . $studyId . ' de la cita ' . $appointmentId . ' fue cargado antes de la fecha programada.';
            }
            $appointmentStatus = $appointmentsById[$appointmentId]['_dashboard_status'];
            if (in_array($appointmentStatus, ['cancelada', 'no asistio'], true)) {
                $warnings[] = 'El informe ' . $studyId . ' está asociado a la cita ' . $appointmentId . ', que está cancelada o no asistida.';
                continue;
            }
            if ($appointmentStatus === 'pendiente') {
                $warnings[] = 'El informe ' . $studyId . ' está asociado a la cita ' . $appointmentId . ', que sigue pendiente de confirmación.';
                continue;
            }
            // Un informe puede guardarse antes de la confirmación explícita del
            // técnico. Esas fases válidas no cuentan como estudio realizado aún.
            if ($appointmentStatus === 'confirmada') {
                continue;
            }

            // Un estudio cuenta si existe informe y la cita figura como atendida.
            $completedStudyAppointments[$appointmentId] = true;
        }

        usort($todayAppointments, static function (array $left, array $right): int {
            $byTime = strcmp((string) ($left['time'] ?? ''), (string) ($right['time'] ?? ''));
            return $byTime !== 0 ? $byTime : ((int) $left['id'] <=> (int) $right['id']);
        });

        return [
            'date' => $today,
            'scheduled_today' => $scheduledToday,
            'pending_today' => $pendingToday,
            'attended_today' => $attendedToday,
            'studies_completed' => count($completedStudyAppointments),
            'today_appointments' => $todayAppointments,
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    private function validDate($value): ?string
    {
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, $this->timezone);
        $errors = DateTimeImmutable::getLastErrors();
        return $date && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
            && $date->format('Y-m-d') === $value ? $value : null;
    }

    private function validTimestamp($value): ?DateTimeImmutable
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }
        try {
            return new DateTimeImmutable($value, $this->timezone);
        } catch (Throwable $exception) {
            return null;
        }
    }

    private function normalizeStatus($value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }
        $status = mb_strtolower(trim($value), 'UTF-8');
        $status = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $status);
        return match ($status) {
            'pendiente' => 'pendiente',
            'confirmada', 'confirmado' => 'confirmada',
            'atendida', 'atendido', 'realizada', 'realizado', 'completada', 'completado' => 'atendida',
            'cancelada', 'cancelado' => 'cancelada',
            'no asistio' => 'no asistio',
            default => $status,
        };
    }
}
