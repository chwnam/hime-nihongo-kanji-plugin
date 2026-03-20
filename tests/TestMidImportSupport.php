<?php

namespace HimeNihongo\KanjiPlugin\Tests;

use HimeNihongo\KanjiPlugin\Supports\DB\MidImportSupport;
use HimeNihongo\KanjiPlugin\Supports\DB\MidTables;
use WP_UnitTestCase;

class TestMidImportSupport extends WP_UnitTestCase
{
    private MidImportSupport $support;

    public function setUp(): void
    {
        $this->support = new MidImportSupport();
    }

    public function testImportJlpt(): void
    {
        global $wpdb;

        $table = MidTables::getTableJlpt();
        $wpdb->query("TRUNCATE TABLE `$table`");

        $this->support->importJlpt(dirname(HNKP_MAIN) . '/scripts/jlpt_kanji.txt');

        $content = file_get_contents(dirname(HNKP_MAIN) . '/scripts/jlpt_kanji.txt');
        preg_match_all('/^N[1-5], (\d+)$/im', $content, $matches);

        $total = 0;
        foreach($matches[1] as $count) {
            $total += (int)$count;
        }

        $this->assertEquals($total, (int)$wpdb->get_var("SELECT COUNT(*) FROM `$table`"));
    }
}