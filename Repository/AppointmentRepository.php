<?php
require_once __DIR__ . '/AppointmentWorkflowStore.php';
/** Persistencia de citas JSON; estados alineados al CHECK de Cita en database.sql. */
class AppointmentRepository implements AppointmentWorkflowStore
{
    public const STATES = ['Pendiente', 'Confirmada', 'Atendida', 'Cancelada', 'No asistio'];
    private string $file;
    public function __construct(?string $file = null)
    {
        $this->file = $file ?? __DIR__ . '/../Data/appointments.json';
    }
    public function all(): array
    {
        $raw = @file_get_contents($this->file);
        if ($raw === false)
            throw new RuntimeException('No se pudo leer citas.');
        if (!is_array(json_decode($raw, false, 512, JSON_THROW_ON_ERROR)))
            throw new RuntimeException('La raÃ­z del archivo de citas debe ser una lista JSON.');
        $rows = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($rows))
            throw new RuntimeException('Formato de citas no válido.');
        return $rows;
    }

    public function findById(int $id): ?array
    {
        foreach ($this->all() as $row) {
            if ((int) ($row['id'] ?? 0) === $id) {
                return $row;
            }
        }
        return null;
    }
    public function create(array $appointment): array
    {
        return $this->mutate(function (&$rows) use ($appointment) {
            $appointment['id'] = 1 + max(array_map(static fn($r) => (int) ($r['id'] ?? 0), $rows) ?: [0]);
            $rows[] = $appointment;
            return $appointment; });
    }
    /** Atomic create: the slot check and append share the same exclusive JSON lock. */
    public function createIfSlotAvailable(array $appointment, ?callable $conflictsWith = null): array
    {
        $conflictsWith ??= static fn(array $existing, array $candidate): bool =>
            (int) ($existing['branch_id'] ?? 0) === (int) $candidate['branch_id']
            && (string) ($existing['date'] ?? '') === (string) $candidate['date']
            && (string) ($existing['time'] ?? '') === (string) $candidate['time'];
        return $this->mutate(function (&$rows) use ($appointment, $conflictsWith) {
            foreach ($rows as $row) {
                if ($conflictsWith($row, $appointment)
                    && !in_array((string) ($row['status'] ?? ''), ['Cancelada', 'No asistio'], true)) {
                    throw new DomainException('Ese horario ya no está disponible. Elegí otro horario.');
                }
            }
            $appointment['id'] = 1 + max(array_map(static fn($row) => (int) ($row['id'] ?? 0), $rows) ?: [0]);
            $rows[] = $appointment;
            return $appointment;
        });
    }
    public function setStatus(int $id, string $status): bool
    {
        if (!in_array($status, ['Confirmada', 'Cancelada'], true))
            return false;
        return $this->mutate(function (&$rows) use ($id, $status) {
            foreach ($rows as &$r)
                if ((int) ($r['id'] ?? 0) === $id && in_array($r['status'] ?? '', ['Pendiente', 'Confirmada'], true) && !(($r['status'] ?? '') === 'Confirmada' && $status === 'Confirmada')) {
                    $r['status'] = $status;
                    return true; }return false; });
    }

    public function withStudyUploadEligibleAppointment(int $id, callable $operation)
    {
        if ($id < 1) {
            throw new InvalidArgumentException('La cita indicada no es válida.');
        }
        $lock = fopen($this->file . '.lock', 'c');
        if (!$lock || !flock($lock, LOCK_EX)) {
            throw new RuntimeException('No se pudo bloquear citas.');
        }
        try {
            $rows = $this->readRows();
            foreach ($rows as $row) {
                if ((int) ($row['id'] ?? 0) !== $id) {
                    continue;
                }
                if (!in_array(($row['status'] ?? ''), ['Confirmada', 'Atendida'], true)) {
                    throw new DomainException('La cita debe estar confirmada para cargar el informe.');
                }
                return $operation($row);
            }
            throw new DomainException('La cita indicada no existe.');
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    public function markAttended(int $id): bool
    {
        if ($id < 1) {
            return false;
        }
        return $this->mutate(function (&$rows) use ($id) {
            foreach ($rows as &$row) {
                if ((int) ($row['id'] ?? 0) !== $id) {
                    continue;
                }
                if (($row['status'] ?? '') === 'Atendida') {
                    return true;
                }
                if (($row['status'] ?? '') !== 'Confirmada') {
                    return false;
                }
                $row['status'] = 'Atendida';
                return true;
            }
            return false;
        });
    }

    private function readRows(): array
    {
        $raw = @file_get_contents($this->file);
        if ($raw === false || !is_array(json_decode($raw, false, 512, JSON_THROW_ON_ERROR))) {
            throw new RuntimeException('No se pudieron leer las citas.');
        }
        $rows = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($rows)) {
            throw new RuntimeException('El formato de citas no es válido.');
        }
        return $rows;
    }
    private function mutate(callable $fn)
    {
        $lock = fopen($this->file . '.lock', 'c');
        if (!$lock || !flock($lock, LOCK_EX))
            throw new RuntimeException('No se pudo bloquear citas.');
        try {
            $raw = @file_get_contents($this->file);
            if ($raw === false || !is_array(json_decode($raw, false, 512, JSON_THROW_ON_ERROR)))
                throw new RuntimeException('No se pudo leer citas o su raÃ­z JSON no es una lista.');
            $rows = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($rows))
                throw new RuntimeException('Formato de citas no válido.');
            $result = $fn($rows);
            $tmp = $this->file . '.' . bin2hex(random_bytes(8)) . '.tmp';
            if (file_put_contents($tmp, json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . PHP_EOL, LOCK_EX) === false || !rename($tmp, $this->file)) {
                @unlink($tmp);
                throw new RuntimeException('No se pudo guardar citas.');
            }
            return $result;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
