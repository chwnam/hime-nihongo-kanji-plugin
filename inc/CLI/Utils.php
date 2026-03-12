<?php

namespace HimeNihongo\KanjiPlugin\CLI;

use Normalizer;
use WP_CLI;
use WP_CLI\ExitException;

class Utils
{
    /**
     * @throws ExitException
     */
    public static function getBulkQueries(string $tableName, array $columns, array $rows, int $chunkSize = 0): array
    {
        global $wpdb;

        $inserts = [];

        foreach ($chunkSize === 0 ? [$rows] : array_chunk($rows, $chunkSize) as $chunk) {
            $buffer = [];

            $cols = implode(',', array_map(fn($c) => "`$c`", $columns));
            $stmt = "INSERT INTO $tableName ($cols) VALUES ";

            foreach ($chunk as $idx => $row) {
                $item = [];
                foreach ($columns as $column) {
                    if (!isset($row[$column])) {
                        WP_CLI::error("Column '$column' not found in row:$idx.");
                    }
                    $item[] = $wpdb->prepare('%s', $row[$column]);
                }
                $buffer[] = '(' . implode(',', $item) . ')';
            }

            $inserts[] = $stmt . implode(", ", $buffer) . ';';
        };

        return $inserts;
    }

    /**
     * @param array<string> $tables
     *
     * @return array<string, int>
     */
    public static function getTablesRowCounts(array $tables): array
    {
        global $wpdb;

        $output = [];

        $placeholder = implode(', ', array_fill(0, count($tables), '%s'));
        $colName     = 'Tables_in_' . DB_NAME;
        $query       = $wpdb->prepare("SHOW TABLES WHERE `$colName` IN ($placeholder)", $tables);
        $queried     = $wpdb->get_col($query);

        foreach ($queried as $table) {
            $query = "SELECT COUNT(*) FROM `$table`";
            $count = (int)$wpdb->get_var($query);

            $output[$table] = $count;
        }

        return $output;
    }

    public static function getDicPrefix(): string
    {
        global $wpdb;

        return "{$wpdb->prefix}hnkp_dic_";
    }

    public static function getHimePrefix(): string
    {
        global $wpdb;

        return "{$wpdb->prefix}hnkp_hime_";
    }


    public static function getDicTables(): array
    {
        global $wpdb;

        $tableName = $wpdb->esc_like(self::getDicPrefix()) . '%';
        $query     = $wpdb->prepare("SHOW TABLES LIKE '$tableName%'");

        return $wpdb->get_col($query);
    }

    public static function getHimeTables(): array
    {
        global $wpdb;

        $tableName = $wpdb->esc_like(self::getHimePrefix()) . '%';
        $query     = $wpdb->prepare("SHOW TABLES LIKE '$tableName%'");

        return $wpdb->get_col($query);
    }

    public static function unicodeToStr(string $unicode): string
    {
        if (preg_match('/^U\+([0-9A-F]{4,6})$/i', $unicode, $matches)) {
            return mb_chr(hexdec($matches[1]), 'UTF-8');
        }

        return '';
    }

    public static function normalize(string $str): string
    {
        return normalizer_normalize($str, Normalizer::FORM_C);
    }
}
