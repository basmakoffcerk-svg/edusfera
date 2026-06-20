<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

final class HealthCheckCommand extends Command
{
    protected $signature = 'health:check {--json : Output as JSON}';

    protected $description = 'Comprehensive health check: DB, Redis, queue, disk space';

    public function handle(): int
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'queue' => $this->checkQueue(),
            'disk' => $this->checkDisk(),
        ];

        $healthy = ! in_array(false, array_column($checks, 'healthy'), true);

        if ($this->option('json')) {
            $this->line(json_encode([
                'status' => $healthy ? 'ok' : 'degraded',
                'checks' => $checks,
                'timestamp' => now()->toIso8601String(),
            ]));

            return $healthy ? self::SUCCESS : self::FAILURE;
        }

        $this->newLine();
        $this->line('<info>Health Check</info> — '.($healthy ? '<info>OK</info>' : '<error>DEGRADED</error>'));
        $this->newLine();

        foreach ($checks as $name => $result) {
            $status = $result['healthy'] ? '<info>✓</info>' : '<error>✗</error>';
            $this->line("  {$status} {$name}: {$result['message']}");
        }

        $this->newLine();

        return $healthy ? self::SUCCESS : self::FAILURE;
    }

    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $latency = round((microtime(true) - $start) * 1000, 1);

            return [
                'healthy' => true,
                'message' => "Connected ({$latency}ms)",
            ];
        } catch (Throwable $e) {
            return [
                'healthy' => false,
                'message' => 'Connection failed: '.$e->getMessage(),
            ];
        }
    }

    private function checkRedis(): array
    {
        try {
            $start = microtime(true);
            Redis::ping();
            $latency = round((microtime(true) - $start) * 1000, 1);

            return [
                'healthy' => true,
                'message' => "Pong ({$latency}ms)",
            ];
        } catch (Throwable $e) {
            return [
                'healthy' => false,
                'message' => 'Connection failed: '.$e->getMessage(),
            ];
        }
    }

    private function checkQueue(): array
    {
        try {
            $pending = DB::table('jobs')->count();
            $failed = DB::table('failed_jobs')->count();

            $healthy = $failed < 100;

            return [
                'healthy' => $healthy,
                'message' => "{$pending} pending, {$failed} failed",
            ];
        } catch (Throwable $e) {
            return [
                'healthy' => false,
                'message' => 'Queue check failed: '.$e->getMessage(),
            ];
        }
    }

    private function checkDisk(): array
    {
        try {
            $free = disk_free_space(storage_path());
            $total = disk_total_space(storage_path());

            if ($free === false || $total === false) {
                return [
                    'healthy' => true,
                    'message' => 'Unable to determine disk space',
                ];
            }

            $freePercent = round(($free / $total) * 100, 1);
            $freeMb = round($free / 1024 / 1024);
            $healthy = $freePercent > 10;

            return [
                'healthy' => $healthy,
                'message' => "{$freeMb}MB free ({$freePercent}%)",
            ];
        } catch (Throwable $e) {
            return [
                'healthy' => true,
                'message' => 'Disk check skipped: '.$e->getMessage(),
            ];
        }
    }
}
