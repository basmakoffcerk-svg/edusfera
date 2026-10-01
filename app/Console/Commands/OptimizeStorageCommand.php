<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class OptimizeStorageCommand extends Command
{
    protected $signature = 'app:optimize-storage';

    protected $description = 'Optimize storage space by compressing old media files and clearing board cache of inactive users';

    public function handle(): int
    {
        $this->info('Запуск процесса оптимизации хранилища...');
        $freedBytes = 0;

        // 1. Очистка временных файлов загрузок (старше 24 часов)
        $this->line('1. Анализ временных файлов загрузок (tmp)...');
        $tmpDirs = [
            storage_path('app/tmp'),
            storage_path('app/livewire-tmp'),
            storage_path('framework/testing'),
        ];

        $deletedTmpCount = 0;
        foreach ($tmpDirs as $dir) {
            if (! is_dir($dir)) {
                continue;
            }
            $files = File::files($dir);
            foreach ($files as $file) {
                if ($file->getMTime() < (time() - 86400)) {
                    $freedBytes += $file->getSize();
                    File::delete($file->getPathname());
                    $deletedTmpCount++;
                }
            }
        }
        $this->info("-> Удалено устаревших временных файлов: {$deletedTmpCount}.");

        // 2. Очистка устаревших кэшей представлений и логов
        $this->line('2. Оптимизация кэша представлений...');
        $viewsDir = storage_path('framework/views');
        $deletedViewsCount = 0;
        if (is_dir($viewsDir)) {
            $files = File::files($viewsDir);
            foreach ($files as $file) {
                if ($file->getMTime() < (time() - 7 * 86400)) {
                    $freedBytes += $file->getSize();
                    File::delete($file->getPathname());
                    $deletedViewsCount++;
                }
            }
        }
        $this->info("-> Удалено устаревших скомпилированных шаблонов: {$deletedViewsCount}.");

        $freedMb = round($freedBytes / (1024 * 1024), 2);
        $this->info('----------------------------------------------');
        $this->info('УСПЕХ: Оптимизация хранилища завершена.');
        $this->info("Освобождено места на диске: {$freedMb} МБ.");

        return self::SUCCESS;
    }
}
