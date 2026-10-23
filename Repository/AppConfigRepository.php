<?php
/** Read and atomically update application config while preserving unrelated keys. */
final class AppConfigRepository
{
    private string $file;

    public function __construct(?string $file = null)
    {
        $this->file = $file ?? __DIR__ . '/../Data/config.json';
    }

    public function all(): array
    {
        return $this->read();
    }

    public function update(callable $callback)
    {
        $directory = dirname($this->file);
        if (!is_dir($directory) || !is_writable($directory)) throw new RuntimeException('La carpeta de configuración no permite escritura.');
        $lock = @fopen($this->file . '.lock', 'c');
        if (!$lock || !flock($lock, LOCK_EX)) {
            if (is_resource($lock)) fclose($lock);
            throw new RuntimeException('No se pudo bloquear la configuración.');
        }
        try {
            $config = $this->read();
            $result = $callback($config);
            $tmp = $this->file . '.' . bin2hex(random_bytes(8)) . '.tmp';
            $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . PHP_EOL;
            if (@file_put_contents($tmp, $json, LOCK_EX) === false || !@rename($tmp, $this->file)) {
                @unlink($tmp);
                throw new RuntimeException('No se pudo actualizar la configuración.');
            }
            return $result;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function read(): array
    {
        $raw = @file_get_contents($this->file);
        if ($raw === false) throw new RuntimeException('No se pudo leer Data/config.json.');
        try {
            $config = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Data/config.json contiene JSON inválido.', 0, $exception);
        }
        if (!is_array($config) || ($config !== [] && array_is_list($config))) {
            throw new RuntimeException('Data/config.json debe contener un objeto de configuración.');
        }
        return $config;
    }
}
