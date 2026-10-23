<?php
/** Contrato de acceso a sucursales, implementado actualmente sobre JSON. */
class BranchRepository
{
    private string $file;

    public function __construct(?string $file = null)
    {
        $this->file = $file ?? __DIR__ . '/../Data/branches.json';
    }

    public function all(): array
    {
        $raw = @file_get_contents($this->file);
        if ($raw === false) {
            throw new RuntimeException('No se pudo leer sucursales.');
        }
        $rows = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($rows)) {
            throw new RuntimeException('Formato de sucursales no válido.');
        }
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

    public function create(array $branch): array
    {
        return $this->mutate(function (&$rows) use ($branch) {
            $name = mb_strtolower(trim($branch['nombre']), 'UTF-8');
            foreach ($rows as $row) {
                if (mb_strtolower(trim((string) ($row['nombre'] ?? '')), 'UTF-8') === $name) {
                    throw new DomainException('duplicate');
                }
            }
            $branch['id'] = 1 + max(array_map(static fn($row) => (int) ($row['id'] ?? 0), $rows) ?: [0]);
            $rows[] = $branch;
            return $branch;
        });
    }

    public function update(int $id, string $name, string $address): ?array
    {
        return $this->mutate(function (&$rows) use ($id, $name, $address) {
            $normalizedName = mb_strtolower(trim($name), 'UTF-8');
            foreach ($rows as $row) {
                if ((int) ($row['id'] ?? 0) !== $id && mb_strtolower(trim((string) ($row['nombre'] ?? '')), 'UTF-8') === $normalizedName) {
                    throw new DomainException('duplicate');
                }
            }
            foreach ($rows as &$row) {
                if ((int) ($row['id'] ?? 0) === $id) {
                    $row['nombre'] = $name;
                    $row['direccion'] = $address;
                    $updated = $row;
                    unset($row);
                    return $updated;
                }
            }
            unset($row);
            return null;
        });
    }

    public function setActive(int $id, bool $active): bool
    {
        return $this->mutate(function (&$rows) use ($id, $active) {
            foreach ($rows as &$row) {
                if ((int) ($row['id'] ?? 0) === $id) {
                    $row['activo'] = $active;
                    unset($row);
                    return true;
                }
            }
            unset($row);
            return false;
        });
    }

    private function mutate(callable $callback)
    {
        $lock = fopen($this->file . '.lock', 'c');
        if (!$lock || !flock($lock, LOCK_EX)) {
            throw new RuntimeException('No se pudo bloquear sucursales.');
        }
        try {
            $raw = @file_get_contents($this->file);
            if ($raw === false) {
                throw new RuntimeException('No se pudo leer sucursales.');
            }
            $rows = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($rows)) {
                throw new RuntimeException('Formato de sucursales no válido.');
            }
            $result = $callback($rows);
            $tmp = $this->file . '.' . bin2hex(random_bytes(8)) . '.tmp';
            $json = json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . PHP_EOL;
            if (file_put_contents($tmp, $json, LOCK_EX) === false || !rename($tmp, $this->file)) {
                @unlink($tmp);
                throw new RuntimeException('No se pudo guardar sucursales.');
            }
            return $result;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
