<?php

namespace HimeNihongo\KanjiPlugin\Supports\DB;

use Bojaghi\Contract\Support;

class KasumiTables extends Tables implements Support
{
    public static function getPrefix(): string
    {
        global $wpdb;

        return "{$wpdb->prefix}hnkp_kasumi_";
    }

    public static function getTableChars(): string
    {
        return self::getPrefix() . 'chars';
    }

    public static function getTableWords(): string
    {
        return self::getPrefix() . 'words';
    }

    public static function getAllTables(): array
    {
        return [
            self::getTableChars(),
            self::getTableWords(),
        ];
    }
}