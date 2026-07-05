<?php

declare(strict_types=1);

namespace App\Support;

class Formatters
{
    public static function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 Б';
        }

        $units = ['Б', 'КБ', 'МБ', 'ГБ'];
        $k = 1024;
        $i = (int) floor(log($bytes) / log($k));

        return round($bytes / pow($k, $i), 1).' '.$units[$i];
    }
}
