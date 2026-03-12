<?php

namespace HimeNihongo\KanjiPlugin\Supports\DB;

abstract class Tables
{
    abstract public static function getPrefix(): string;
    abstract public static function getAllTables(): array;

    public static function truncateTables(): void
    {
        global $wpdb;

        foreach (static::getAllTables() as $table) {
            $wpdb->query("TRUNCATE TABLE `$table`");
        }
    }

    public static function dropTables(): void
    {
        global $wpdb;

        foreach (static::getAllTables() as $table) {
            $wpdb->query("DROP TABLE IF EXISTS `$table`");
        }
    }
}
