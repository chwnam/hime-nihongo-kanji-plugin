<?php

namespace HimeNihongo\KanjiPlugin\Supports\DB;

use Bojaghi\Contract\Support;

class HimeTables extends Tables implements Support
{
    public static function getPrefix(): string
    {
        global $wpdb;

        return "{$wpdb->prefix}hnkp_hime_";
    }

    public static function getTableChars(): string
    {
        return self::getPrefix() . 'chars';
    }

    public static function getTableWords(): string
    {
        return self::getPrefix() . 'words';
    }

    public static function getTableCharWordRels(): string
    {
        return self::getPrefix() . 'char_word_rels';
    }

    public static function getAllTables(): array
    {
        return [
            self::getTableChars(),
            self::getTableWords(),
            self::getTableCharWordRels(),
        ];
    }

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
            'chars'          => self::getTableChars(),
            'words'          => self::getTableWords(),
            'char_word_rels' => self::getTableCharWordRels(),
            default          => '',
        };
    }

    public static function truncateSingleTable(string $table): void
    {
        global $wpdb;

        $table = self::getSingleTableName($table);
        if ($table) {
            $wpdb->query("TRUNCATE TABLE `$table`");
        }
    }
}
