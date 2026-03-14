<?php

namespace HimeNihongo\KanjiPlugin\CLI;

use Bojaghi\CustomTables\CustomTables;
use WP_CLI;
use WP_CLI\ExitException;

/**
 * Table support commands.
 */
class MiscCommand
{
    /**
     * 데이터베이스 변경을 바로 적용합니다.
     *
     * 게발시 자잘한 테이블 변경 사항의 바로 반영합니다.
     *
     * ## EXAMPLES
     *
     *     wp hnkp/misc update-tables
     *
     * @subcommand update-tables
     * @when       after_wp_load
     */
    public function updateTables(): void
    {
        /** @var CustomTables $ct */
        $ct = hnkp_get('bojaghi/custom-tables');
        $ct->updateTables();

        WP_CLI::success('Tables updated.');
    }

    /**
     * 모든 'hnkp_' 테이블을 삭제합니다.
     *
     * ## EXAMPLES
     *
     *     wp hnkp/misc drop-tables
     *
     * ## OPTIONS
     *
     * [--yes]
     * : 물어보지 않습니다.
     *
     * @subcommand drop-tables
     * @when       after_wp_load
     *
     * @param array $_
     * @param array $assoc_args
     *
     * @return void
     */
    public function dropTables(array $_, array $assoc_args): void
    {
        WP_CLI::confirm('Are you sure you want to drop all tables?', $assoc_args);

        $ct = hnkp_get('bojaghi/custom-tables');
        $ct->deleteTables();

        WP_CLI::success('Tables dropped.');
    }

    /**
     * 카스미 버전 CSV 데이터를 테이블로 삽입합니다.
     *
     * ## EXAMPLES
     *
     *     wp hnkp/misc insert-csv n5.csv n4.csv n3.csv
     *
     * ## OPTIONS
     *
     * <csv_path>...
     * : CSV 파일 경로를 입력합니다. 여러 개의 파일을 입력할 수 있습니다.
     *
     * @subcommand insert-csv
     * @when       after_wp_load
     *
     * @param array $args
     *
     * @return void
     * @throws ExitException
     */
    public function insertCsv(array $args): void
    {
        global $wpdb;

        $tableName = "{$wpdb->prefix}hnkp_kasumi_words";

        foreach ($args as $path) {
            if (!file_exists($path) || !is_readable($path)) {
                WP_CLI::error("File not found, or unreadable: $path");
            }
        }

        $wpdb->query("TRUNCATE TABLE `$tableName`");
        $wpdb->query("START TRANSACTION");

        foreach ($args as $path) {
            // Read JLPT level from file name.
            $name = pathinfo($path, PATHINFO_FILENAME);
            if (!preg_match('/^n(\d+)$/', $name, $matches)) {
                WP_CLI::error("Invalid file name: $name");
            }
            $jlpt = (int)$matches[1];

            // Read CSV first.
            $rows = [];
            if (!($fp = fopen($path, 'r'))) {
                WP_CLI::error("Failed to open file: $path");
            }
            while (false !== ($row = fgetcsv($fp, 1000, ',', '"', '\\'))) {
                $rows[] = $row;
            }
            fclose($fp);

            // Insert entries from rows
            $entry = -1;

            foreach ($rows as $row) {
                $row  = array_filter(array_map('trim', $row));
                $col1 = (int)$row[0];

                if ($col1 > $entry) {
                    // 한자 정의 - 스킵
                    $entry = $col1;
                    continue;
                }

                if ($entry === $col1) {
                    // 단어 의미
                    $query = $wpdb->prepare(
                        "INSERT IGNORE INTO `$tableName` (jlpt, entry, word, yomikata, meaning) " .
                        "VALUES (%d, %d, %s, %s, %s)",
                        $jlpt,
                        $entry,
                        $row[1],
                        $row[2],
                        $row[3],
                    );
                    WP_CLI::log($query);
                    $wpdb->query($query);
                    if ($wpdb->last_error) {
                        WP_CLI::error($wpdb->last_error);
                    }
                }
            }
        }

        $wpdb->query("COMMIT");
        WP_CLI::success("Successfully imported.");
    }
}
