<?php

namespace HimeNihongo\KanjiPlugin\Supports\DB;

use Bojaghi\Contract\Support;
use Normalizer;

class Utils implements Support
{
    public static function normalize(string $str): string
    {
        return normalizer_normalize($str, Normalizer::FORM_C);
    }

    public static function unicodeToStr(string $unicode): string
    {
        if (preg_match('/^U\+([0-9A-F]{4,6})/i', $unicode, $matches)) {
            return mb_chr(hexdec($matches[1]), 'UTF-8');
        }

        return '';
    }

    public static function strToHex(string $str): string
    {
        return strtoupper(bin2hex($str));
    }
}
