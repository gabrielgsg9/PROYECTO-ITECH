<?php
require_once __DIR__ . '/StudyWorkflowStore.php';
/** Informes privados asociados a las citas JSON que usa actualmente la aplicación. */
class ReportRepository implements StudyWorkflowStore
{
    private string $index;
    private string $storage;
    public function __construct(?string $storage = null)
    {
        $this->storage = $storage ?: (getenv('CLINICA_REPORTS_DIR') ?: sys_get_temp_dir() . '/clinica-imagen-informes');
        $this->index = $this->storage . '/index.json';
        if (!is_dir($this->storage) && !mkdir($this->storage, 0700, true) && !is_dir($this->storage)) {
            throw new RuntimeException('No fue posible preparar el almacenamiento privado.');
        }
    }
    public function findByAppointment(int $appointmentId): ?array
    {
        foreach ($this->all() as $report) if ((int)$report['appointment_id'] === $appointmentId) return $report;
        return null;
    }
    public function all(): array
    {
        if (!is_file($this->index)) return [];
        $raw = @file_get_contents($this->index);
        if ($raw === false || !is_array(json_decode($raw, false, 512, JSON_THROW_ON_ERROR)))
            throw new RuntimeException('No se pudo leer el Ã­ndice de informes o su raÃ­z JSON no es una lista.');
        $rows = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($rows)) throw new RuntimeException('Índice de informes inválido.');
        foreach ($rows as &$row) {
            if (is_array($row)) $row['_file_available'] = $this->filePath($row) !== null;
        }
        unset($row);
        return $rows;
    }
    public function save(int $appointmentId, int $technicianId, string $originalName, string $temporaryPath): array
    {
        $lock = fopen($this->storage . '/index.lock', 'c');
        if (!$lock || !flock($lock, LOCK_EX)) throw new RuntimeException('No se pudo bloquear el índice.');
        try {
            if (is_file($this->index)) {
                $raw = @file_get_contents($this->index);
                if ($raw === false || !is_array(json_decode($raw, false, 512, JSON_THROW_ON_ERROR)))
                    throw new RuntimeException('No se pudo leer el Ã­ndice de informes o su raÃ­z JSON no es una lista.');
                $rows = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            } else {
                $rows = [];
            }
            foreach ($rows as $row) if ((int)$row['appointment_id'] === $appointmentId) throw new DomainException('duplicate');
            $storedName = bin2hex(random_bytes(24)) . '.pdf';
            $destination = $this->storage . '/' . $storedName;
            if (!move_uploaded_file($temporaryPath, $destination)) throw new RuntimeException('No fue posible guardar el informe.');
            chmod($destination, 0600);
            $report = ['id'=>bin2hex(random_bytes(12)), 'appointment_id'=>$appointmentId, 'technician_id'=>$technicianId,
                'original_name'=>mb_substr(basename($originalName), 0, 255), 'stored_name'=>$storedName, 'uploaded_at'=>date(DATE_ATOM)];
            $rows[] = $report;
            $tmp = $this->index . '.' . bin2hex(random_bytes(6)) . '.tmp';
            if (file_put_contents($tmp, json_encode($rows, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR), LOCK_EX) === false || !rename($tmp, $this->index)) {
                @unlink($tmp); @unlink($destination); throw new RuntimeException('No fue posible registrar el informe.');
            }
            return $report;
        } finally { flock($lock, LOCK_UN); fclose($lock); }
    }
    public function filePath(array $report): ?string
    {
        $name = (string)($report['stored_name'] ?? '');
        if (!preg_match('/^[a-f0-9]{48}\.pdf$/', $name)) return null;
        $path = $this->storage . '/' . $name;
        return is_file($path) ? $path : null;
    }
}
