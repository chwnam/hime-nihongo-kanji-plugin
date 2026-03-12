<?php

namespace HimeNihongo\KanjiPlugin\Supports\DB;

use Bojaghi\Contract\Support;

class JMDictTables extends Tables implements Support
{
    public static function getPrefix(): string
    {
        global $wpdb;

        return "{$wpdb->prefix}hnkp_jmdict_";
    }

    public static function getTableEntry(): string
    {
        return self::getPrefix() . 'entry';
    }

    public static function getTableKEle(): string
    {
        return self::getPrefix() . 'k_ele';
    }

    public static function getTableKeb(): string
    {
        return self::getPrefix() . 'keb';
    }

    public static function getTableREle(): string
    {
        return self::getPrefix() . 'r_ele';
    }

    public static function getTableSense(): string
    {
        return self::getPrefix() . 'sense';
    }

    public static function getAllTables(): array
    {
        return [
            self::getTableEntry(),
            self::getTableKEle(),
            self::getTableKeb(),
            self::getTableREle(),
            self::getTableSense(),
        ];
    }
}
