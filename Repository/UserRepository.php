<?php
/** Contrato de persistencia de usuarios, implementado actualmente sobre JSON. */
class UserRepository
{
    private string $file;
    public function __construct(?string $file = null)
    {
        $this->file = $file ?? __DIR__ . '/../Data/users.json';
    }
    public function all(): array
    {
        return $this->read();
    }
    /** Busca usuarios por ID, nombre, correo o nombre de usuario. */
    public function search(string $query): array
    {
        $query = trim(preg_replace('/\s+/u', ' ', $query) ?? $query);
        if ($query === '')
            return $this->all();
        $needle = self::normalizeSearchText($query);
        $terms = preg_split('/\s+/u', $needle, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        return array_values(array_filter($this->read(), static function ($user) use ($query, $needle, $terms) {
            if (!is_array($user)) return false;
            if (ctype_digit($query) && (string) ($user['id'] ?? '') === ltrim($query, '0')) return true;
            foreach (['name', 'email', 'username'] as $field) {
                $value = self::normalizeSearchText((string) ($user[$field] ?? ''));
                if ($value !== '' && strpos($value, $needle) !== false) return true;
            }
            // Cada palabra puede coincidir en cualquier parte del nombre, así admite
            // consultas "apellido nombre" con los datos de nombre completo actuales.
            $name = self::normalizeSearchText((string) ($user['name'] ?? ''));
            return count($terms) > 1 && count(array_filter($terms, static fn($term) => strpos($name, $term) !== false)) === count($terms);
        }));
    }
    /** Búsqueda de pacientes para paneles autorizados; consulta vacía no lista pacientes. */
    public function searchPatients(string $query): array
    {
        $query = trim(preg_replace('/\s+/u', ' ', $query) ?? $query);
        if ($query === '') return [];
        return array_values(array_filter($this->search($query), static fn($user) => ($user['role'] ?? '') === 'paciente'));
    }
    private static function normalizeSearchText(string $value): string
    {
        return function_exists('mb_strtolower') ? mb_strtolower(trim($value), 'UTF-8') : strtolower(trim($value));
    }
    public function findById(int $id): ?array
    {
        foreach ($this->read() as $u)
            if ((int) ($u['id'] ?? 0) === $id)
                return $u;
        return null;
    }
    public function findByEmail(string $email): ?array
    {
        $email = strtolower(trim($email));
        foreach ($this->read() as $u)
            if (strtolower((string) ($u['email'] ?? '')) === $email)
                return $u;
        return null;
    }
    public function findByName(string $name): ?array
    {
        $name = strtolower(trim($name));
        foreach ($this->read() as $u)
            if (strtolower((string) ($u['username'] ?? '')) === $name || strtolower((string) ($u['email'] ?? '')) === $name)
                return $u;
        return null;
    }
    public function create(array $user): array
    {
        return $this->mutate(function (&$rows) use ($user) {
            foreach ($rows as $r)
                if (strtolower((string) ($r['email'] ?? '')) === strtolower((string) $user['email']) || strtolower((string) ($r['username'] ?? '')) === strtolower((string) $user['username']))
                    throw new DomainException('duplicate');
            $user['id'] = 1 + max(array_map(static fn($r) => (int) ($r['id'] ?? 0), $rows) ?: [0]);
            $rows[] = $user;
            return $user; });
    }
    public function updateRole(int $id, string $role): bool
    {
        return $this->mutate(function (&$rows) use ($id, $role) {
            foreach ($rows as &$r)
                if ((int) ($r['id'] ?? 0) === $id) {
                    if (($r['role'] ?? '') === 'administrador' && $role !== 'administrador' && $this->adminCount($rows) <= 1)
                        return false;
                    $r['role'] = $role;
                    return true; }return false; });
    }
    public function delete(int $id): bool
    {
        return $this->mutate(function (&$rows) use ($id) {
            foreach ($rows as $r)
                if ((int) ($r['id'] ?? 0) === $id && ($r['role'] ?? '') === 'administrador' && $this->adminCount($rows) <= 1)
                    return false;
            $n = count($rows);
            $rows = array_values(array_filter($rows, static fn($r) => (int) ($r['id'] ?? 0) !== $id));
            return count($rows) < $n; });
    }
    private function adminCount(array $rows): int
    {
        return count(array_filter($rows, static fn($r) => ($r['role'] ?? '') === 'administrador'));
    }
    private function read(): array
    {
        $raw = @file_get_contents($this->file);
        if ($raw === false)
            throw new RuntimeException('No se pudo leer usuarios.');
        $rows = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($rows))
            throw new RuntimeException('Formato de usuarios no válido.');
        return $rows;
    }
    private function mutate(callable $fn)
    {
        $lock = fopen($this->file . '.lock', 'c');
        if (!$lock || !flock($lock, LOCK_EX))
            throw new RuntimeException('No se pudo bloquear usuarios.');
        try {
            $rows = $this->read();
            $result = $fn($rows);
            $tmp = $this->file . '.' . bin2hex(random_bytes(8)) . '.tmp';
            $json = json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . PHP_EOL;
            if (file_put_contents($tmp, $json, LOCK_EX) === false || !rename($tmp, $this->file)) {
                @unlink($tmp);
                throw new RuntimeException('No se pudo guardar usuarios.');
            }
            return $result;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
