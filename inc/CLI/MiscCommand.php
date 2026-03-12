<?php

namespace HimeNihongo\KanjiPlugin\CLI;

use Bojaghi\CustomTables\CustomTables;
use WP_CLI;

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
}
