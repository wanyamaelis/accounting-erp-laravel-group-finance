<?php

namespace App\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Installer
{
    public function lockPath(): string
    {
        return config('installer.lock_file', storage_path('app/private/installed'));
    }

    public function installed(): bool
    {
        return file_exists($this->lockPath());
    }

    /**
     * Atomically create the installed lock.
     *
     * @throws \RuntimeException
     */
    public function markInstalled(): void
    {
        $path = $this->lockPath();
        $dir = dirname($path);

        if (! is_dir($dir) && ! @mkdir($dir, 0750, true) && ! is_dir($dir)) {
            throw new \RuntimeException("Unable to create installer directory: {$dir}");
        }

        $tmp = $path . '.' . bin2hex(random_bytes(8)) . '.tmp';
        $payload = json_encode([
            'installed_at' => now()->toIso8601String(),
            'app' => config('app.name'),
        ]);

        if (file_put_contents($tmp, $payload, LOCK_EX) === false) {
            throw new \RuntimeException('Unable to write temporary installer lock');
        }

        if (! @rename($tmp, $path)) {
            @unlink($tmp);
            throw new \RuntimeException('Atomic rename to installer lock failed');
        }

        @chmod($path, 0640);
    }

    public function generateKey(): string
    {
        return 'base64:' . base64_encode(random_bytes(32));
    }

    /**
     * Merge and write .env safely.
     *
     * @param array<string,string> $values
     */
    public function writeEnv(array $values): void
    {
        $envPath = base_path('.env');
        $examplePath = base_path('.env.example');

        $current = [];

        if (file_exists($envPath)) {
            $current = $this->parseDotEnv(file_get_contents($envPath));
        } elseif (file_exists($examplePath)) {
            $current = $this->parseDotEnv(file_get_contents($examplePath));
        }

        foreach ($values as $k => $v) {
            $current[$k] = (string) $v;
        }

        $this->dumpDotEnv($current, $envPath);
    }

    protected function parseDotEnv(string $content): array
    {
        $lines = preg_split("/\r\n|\n|\r/", $content);
        $result = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (! str_contains($line, '=')) {
                continue;
            }
            [$key, $val] = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val);
            $result[$key] = $this->unquote($val);
        }

        return $result;
    }

    protected function unquote(string $value): string
    {
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            return substr($value, 1, -1);
        }

        return $value;
    }

    protected function dumpDotEnv(array $assoc, string $path): void
    {
        $lines = [];

        foreach ($assoc as $k => $v) {
            $lines[] = $k . '=' . $this->escape((string) $v);
        }

        $content = implode(PHP_EOL, $lines) . PHP_EOL;
        if (file_put_contents($path, $content, LOCK_EX) === false) {
            throw new \RuntimeException("Failed to write .env at {$path}");
        }
    }

    protected function escape(string $v): string
    {
        if ($v === '') {
            return '';
        }

        if (preg_match('/\s|["'"\\\\$]/', $v)) {
            $v = str_replace(["\\", '"'], ["\\\\", '\\"'], $v);
            return "\"{$v}\"";
        }

        return $v;
    }

    /**
     * Test a DB connection using a temporary connection name.
     *
     * @param array<string,string> $cfg
     */
    public function testDatabaseConnection(array $cfg): bool
    {
        $name = 'installer_test_' . bin2hex(random_bytes(4));
        $driver = $cfg['DB_CONNECTION'] ?? $cfg['driver'] ?? 'mysql';

        $connections = config('database.connections');
        $connections[$name] = [
            'driver' => $driver,
            'host' => $cfg['DB_HOST'] ?? '127.0.0.1',
            'port' => $cfg['DB_PORT'] ?? ($driver === 'pgsql' ? 5432 : 3306),
            'database' => $cfg['DB_DATABASE'] ?? null,
            'username' => $cfg['DB_USERNAME'] ?? null,
            'password' => $cfg['DB_PASSWORD'] ?? null,
            'charset' => 'utf8mb4',
            'prefix' => '',
            'schema' => 'public',
        ];

        config(['database.connections' => $connections]);

        try {
            DB::connection($name)->getPdo();
        } catch (\Throwable $e) {
            return false;
        }

        return true;
    }

    public function runMigrationsAndSeed(): void
    {
        Artisan::call('migrate', ['--force' => true]);
        Artisan::call('db:seed', ['--class' => \Database\Seeders\DatabaseSeeder::class, '--force' => true]);
    }
}
