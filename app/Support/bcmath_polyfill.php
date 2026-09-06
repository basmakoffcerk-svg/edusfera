<?php

declare(strict_types=1);

/**
 * Polyfill for PHP bcmath extension when ext-bcmath is disabled on web hosting servers.
 */

if (! function_exists('bcadd')) {
    function bcadd(string $left, string $right, int $scale = 0): string
    {
        $res = (float) $left + (float) $right;

        return number_format($res, $scale, '.', '');
    }
}

if (! function_exists('bcsub')) {
    function bcsub(string $left, string $right, int $scale = 0): string
    {
        $res = (float) $left - (float) $right;

        return number_format($res, $scale, '.', '');
    }
}

if (! function_exists('bcmul')) {
    function bcmul(string $left, string $right, int $scale = 0): string
    {
        $res = (float) $left * (float) $right;

        return number_format($res, $scale, '.', '');
    }
}

if (! function_exists('bcdiv')) {
    function bcdiv(string $left, string $right, int $scale = 0): string
    {
        $r = (float) $right;
        if ($r === 0.0) {
            throw new \DivisionByZeroError('Division by zero in bcdiv polyfill');
        }
        $res = (float) $left / $r;

        return number_format($res, $scale, '.', '');
    }
}

if (! function_exists('bccomp')) {
    function bccomp(string $left, string $right, int $scale = 0): int
    {
        $l = round((float) $left, $scale);
        $r = round((float) $right, $scale);

        if ($l < $r) {
            return -1;
        }

        if ($l > $r) {
            return 1;
        }

        return 0;
    }
}
