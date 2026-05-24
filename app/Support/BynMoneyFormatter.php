<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\HtmlString;

class BynMoneyFormatter
{
    public static function format(float|int|string $amount, int $decimals = 2): HtmlString
    {
        $value = number_format((float) $amount, $decimals, '.', ' ');

        return new HtmlString($value . ' ' . self::icon());
    }

    private static function icon(): string
    {
        $src = e(asset('byn-ico.svg'));

        return '<img src="' . $src . '" alt="" aria-hidden="true" width="11" height="14" style="display:inline-block;width:0.81em;height:1em;vertical-align:-0.12em">';
    }
}
