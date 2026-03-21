<?php

namespace HimeNihongo\KanjiPlugin\Supports\DB;

use Bojaghi\Contract\Support;

class MidTables extends Tables implements Support
{
    public static function dropSingleTable(string $table): void
    {
        global $wpdb;

        $table = self::getSingleTableName($table);
        if ($table) {
            $wpdb->query("DROP TABLE IF EXISTS `$table`");
        }
    }

    private static function getSingleTableName(string $table): string
    {
        return match ($table) {
            'hanja'   => self::getTableHanja(),
            'jlpt'    => self::getTableJlpt(),
            'kanji'   => self::getTableKanji(),
            'map'     => self::getTableMap(),
            'sinji'   => self::getTableSinji(),
            'jyouyou' => self::getTableJyouyou(),
            default   => '',
        };
    }

    public static function getTableHanja(): string
    {
        return self::getPrefix() . 'hanja';
    }

    public static function getPrefix(): string
    {
        global $wpdb;

        return "{$wpdb->prefix}hnkp_mid_";
    }

    public static function getTableJlpt(): string
    {
        return self::getPrefix() . 'jlpt';
    }

    public static function getTableKanji(): string
    {
        return self::getPrefix() . 'kanji';
    }

    public static function getTableMap(): string
    {
        return self::getPrefix() . 'map';
    }

    public static function getTableSinji(): string
    {
        return self::getPrefix() . 'sinji';
    }

    public static function getTableJyouyou(): string
    {
        return self::getPrefix() . 'jyouyou';
    }

    public static function truncateSingleTable(string $table): void
    {
        global $wpdb;

        $table = self::getSingleTableName($table);
        if ($table) {
            $wpdb->query("TRUNCATE TABLE `$table`");
        }
    }

    public static function getAllTables(): array
    {
        return [
            self::getTableHanja(),
            self::getTableJlpt(),
            self::getTableKanji(),
            self::getTableMap(),
            self::getTableSinji(),
            self::getTableJyouyou(),
        ];
    }
}
