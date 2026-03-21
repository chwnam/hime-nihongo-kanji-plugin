<?php

namespace HimeNihongo\KanjiPlugin\Tests;

use HimeNihongo\KanjiPlugin\Supports\DB\MidImportSupport;
use HimeNihongo\KanjiPlugin\Supports\DB\MidTables;
use HimeNihongo\KanjiPlugin\Supports\DB\Utils;
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
        foreach ($matches[1] as $count) {
            $total += (int)$count;
        }

        $this->assertEquals($total, (int)$wpdb->get_var("SELECT COUNT(*) FROM `$table`"));
    }

    public function testUnihanVariants(): void
    {
        $input = <<< PHP_EOL
U+3400	kSemanticVariant	U+4E18
U+3405	kSemanticVariant	U+4E94<kMatthews
U+5909	kSemanticVariant	U+8B8A<kHanyu:T
U+8B8A	kSemanticVariant	U+53D8<kMatthews,kMeyerWempe U+5909<kHanyu:T
PHP_EOL;

        $output = [
            ['㐀', 'sev', ['丘']],
            ['㐅', 'sev', ['五']],
            ['変', 'sev', ['變']],
            ['變', 'sev', ['变', '変']],
        ];

        $lines = explode("\n", $input);

        foreach ($lines as $idx => $line) {
            $this->assertEquals($output[$idx], $this->support->parseUnihanVariantLine($line));
        }
    }
}