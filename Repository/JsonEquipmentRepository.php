<?php
require_once __DIR__ . '/EquipmentStore.php';

/** File-backed equipment storage with locked, atomic writes. */
final class JsonEquipmentRepository implements EquipmentStore
{
    private string $file;

    public function __construct(?string $file = null)
    {
        $this->file = $file ?? __DIR__ . '/../Data/equipment.json';
    }

    public function allForBranch(int $branchId): array
    {
        $rows = array_values(array_filter($this->read(), static fn($row) => (int) ($row['sucursal_id'] ?? 0) === $branchId));
        usort($rows, static fn($a, $b) => strnatcasecmp((string) ($a['nombre'] ?? ''), (string) ($b['nombre'] ?? '')) ?: ((int) $a['id'] <=> (int) $b['id']));
        return $rows;
    }

    public function findForBranch(int $equipmentId, int $branchId): ?array
    {
        foreach ($this->read() as $row) {
            if ((int) ($row['id'] ?? 0) === $equipmentId && (int) ($row['sucursal_id'] ?? 0) === $branchId) return $row;
        }
        return null;
    }

    public function create(int $branchId, array $equipment): int
    {
        return $this->mutate(function (&$rows) use ($branchId, $equipment) {
            $this->assertUniqueName($rows, $branchId, (string) $equipment['nombre']);
            $id = 1;
            foreach ($rows as $row) $id = max($id, (int) ($row['id'] ?? 0) + 1);
            $now = date('Y-m-d H:i:s');
            $rows[] = $equipment + [
                'id' => $id,
                'sucursal_id' => $branchId,
                'creado_en' => $now,
                'actualizado_en' => $now,
            ];
            return $id;
        });
    }

    public function updateForBranch(int $equipmentId, int $branchId, array $equipment): bool
    {
        return $this->mutate(function (&$rows) use ($equipmentId, $branchId, $equipment) {
            foreach ($rows as $index => $row) {
                if ((int) ($row['id'] ?? 0) !== $equipmentId || (int) ($row['sucursal_id'] ?? 0) !== $branchId) continue;
                $this->assertUniqueName($rows, $branchId, (string) $equipment['nombre'], $equipmentId);
                $rows[$index] = array_merge($row, $equipment, [
                    'id' => $equipmentId,
                    'sucursal_id' => $branchId,
                    'creado_en' => (string) ($row['creado_en'] ?? date('Y-m-d H:i:s')),
                    'actualizado_en' => date('Y-m-d H:i:s'),
                ]);
                return true;
            }
            return false;
        });
    }

    private function assertUniqueName(array $rows, int $branchId, string $name, int $exceptId = 0): void
    {
        $needle = function_exists('mb_strtolower') ? mb_strtolower(trim($name), 'UTF-8') : strtolower(trim($name));
        foreach ($rows as $row) {
            $existing = function_exists('mb_strtolower') ? mb_strtolower(trim((string) ($row['nombre'] ?? '')), 'UTF-8') : strtolower(trim((string) ($row['nombre'] ?? '')));
            if ((int) ($row['sucursal_id'] ?? 0) === $branchId && (int) ($row['id'] ?? 0) !== $exceptId && $existing === $needle) {
                throw new DomainException('Ya existe un equipo con ese nombre en la sucursal seleccionada.');
            }
        }
    }

    private function read(): array
    {
        $raw = @file_get_contents($this->file);
        if ($raw === false) throw new RuntimeException('No se pudo leer el archivo de equipos.');
        try {
            $rows = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('El archivo de equipos contiene JSON inválido.', 0, $exception);
        }
        if (!is_array($rows) || ($rows !== [] && array_is_list($rows) === false)) {
            throw new RuntimeException('El archivo de equipos debe contener una lista JSON.');
        }
        foreach ($rows as $row) {
            if (!is_array($row) || (int) ($row['id'] ?? 0) < 1 || (int) ($row['sucursal_id'] ?? 0) < 1) {
                throw new RuntimeException('El archivo de equipos contiene registros inválidos.');
            }
        }
        return $rows;
    }

    private function mutate(callable $callback)
    {
        $directory = dirname($this->file);
        if (!is_dir($directory) || !is_writable($directory)) throw new RuntimeException('La carpeta de datos de equipos no permite escritura.');
        $lock = @fopen($this->file . '.lock', 'c');
        if (!$lock || !flock($lock, LOCK_EX)) {
            if (is_resource($lock)) fclose($lock);
            throw new RuntimeException('No se pudo bloquear el archivo de equipos.');
        }
        try {
            $rows = $this->read();
            $result = $callback($rows);
            $tmp = $this->file . '.' . bin2hex(random_bytes(8)) . '.tmp';
            $json = json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . PHP_EOL;
            if (@file_put_contents($tmp, $json, LOCK_EX) === false || !@rename($tmp, $this->file)) {
                @unlink($tmp);
                throw new RuntimeException('No se pudo guardar el archivo de equipos.');
            }
            return $result;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
