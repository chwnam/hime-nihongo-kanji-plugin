<?php

namespace HimeNihongo\KanjiPlugin\CLI;

use Bojaghi\CustomTables\CustomTables;
use Exception;
use HimeNihongo\KanjiPlugin\Supports\DB\HimeMigrateSupport;
use HimeNihongo\KanjiPlugin\Supports\DB\HimeTables;
use HimeNihongo\KanjiPlugin\Supports\DB\JMDictImportSupport;
use HimeNihongo\KanjiPlugin\Supports\DB\JMDictReadSupport;
use HimeNihongo\KanjiPlugin\Supports\DB\JMDictTables;
use HimeNihongo\KanjiPlugin\Supports\DB\KasumiImportSupport;
use HimeNihongo\KanjiPlugin\Supports\DB\KasumiTables;
use HimeNihongo\KanjiPlugin\Supports\DB\MidImportSupport;
use HimeNihongo\KanjiPlugin\Supports\DB\MidTables;
use WP_CLI;
use WP_CLI\ExitException;

/**
 * 커스텀 데이터베이스 테이블 관련 CLI 명령을 지원합니다.
 */
class CliCommand
{
    /**
     * 프로토타입으로 만들었던 N3, N4, N5 CSV 파일을 카스미 테이블로 이전합니다
     *
     * 주의사항: 급수는 **파일 이름**에서 가져오므로,
     * 파일 이름은 반드시 n5.csv, n4.csv, n3.csv와 같은 형식이어야 합니다.
     *
     * 이전 테이블의 내용을 모두 지우고 시작하므로 여러 파일을 한번에 동시에 입력해야 합니다.
     *
     * ## EXAMPLES
     *
     *     wp hnkp kasumi-import n5.csv n4.csv n3.csv
     *
     * ## OPTIONS
     *
     * <csv_path>...
     * : CSV 파일 경로를 입력합니다. 여러 개의 파일을 입력할 수 있습니다.
     *
     * [--yes]
     * : --force 옵션이 있을 때, 물어보지 않습니다.
     * [--drop]
     * : 테이블을 drop 시킨 후 다시 생성합니다.
     *
     * @param array $args
     * @param array $assoc_args
     *
     * @return void
     * @throws ExitException
     *
     * @subcommand import-kasumi
     * @when       after_wp_load
     */
    public function importCsvToKasumi(array $args, array $assoc_args): void
    {
        if (isset($assoc_args['drop'])) {
            WP_CLI::confirm('카스미 테이블을 삭제하고 시작하시겠습니까?', $assoc_args);
            KasumiTables::dropTables();
        } else {
            WP_CLI::confirm('카스미 테이블의 내용을 비우고 시작하시겠습니까?', $assoc_args);
            KasumiTables::truncateTables();
        }

        /** @var KasumiImportSupport $support */
        $support = hnkp_get(KasumiImportSupport::class);

        try {
            foreach ($args as $path) {
                WP_CLI::line("Importing $path");
                $support->importCsv($path);
            }
        } catch (Exception $e) {
            WP_CLI::error("$path 처리 중 에러: " . $e->getMessage());
        }

        WP_CLI::success('모두 성공적으로 가져왔습니다.');
    }

    /**
     * Kanjidic2.xml 파일을 읽어 테이블에 저장합니다.
     *
     * 이전 테이블의 내용을 모두 지우고 시작합니다.
     *
     * ## EXAMPLES
     *
     *     wp hnkp kanjidic2-import kanjidic2.xml
     *
     * ## OPTIONS
     *
     * <path>
     * : kanjidic2.xml 파일의 경로
     *
     * [--yes]
     * : 묻지 않습니다
     *
     * [--drop]
     * : 테이블을 drop 시킨 후 다시 생성합니다.
     *
     * @param array $args
     * @param array $assoc_args
     *
     * @return void
     *
     * @subcommand import-kanjidic2
     * @when       after_wp_load
     * @throws ExitException
     */
    public function importKanjidic2Xml(array $args, array $assoc_args): void
    {
        if (isset($assoc_args['drop'])) {
            WP_CLI::confirm('테이블을 삭제하고 시작하시겠습니까?', $assoc_args);
            MidTables::dropSingleTable('kanji');
        } else {
            WP_CLI::confirm('테이블의 내용을 비우고 시작하시겠습니까?', $assoc_args);
            MidTables::truncateSingleTable('kanji');
        }

        $path = $args[0];
        if (!file_exists($path) || !is_readable($path)) {
            WP_CLI::error("파일을 찾을 수 없거나, 일을 수 없습니다: $path");
        }

        try {
            /** @var MidImportSupport $support */
            $support = hnkp_get(MidImportSupport::class);
            $support->importKanjidic2($path);
        } catch (Exception $e) {
            WP_CLI::error("$path 처리 중 에러: " . $e->getMessage());
        }

        WP_CLI::success('모두 성공적으로 가져왔습니다.');
    }

    /**
     * hanja.csv 파일을 읽어 테이블에 저장합니다.
     *
     * 이전 테이블의 내용을 모두 지우고 시작합니다.
     *
     * ## EXAMPLES
     *
     *     wp hnkp hanja-import hanja.csv
     *
     * ## OPTIONS
     *
     * <path>
     * : hanja.csv 파일의 경로
     *
     * [--yes]
     * : 묻지 않습니다
     *
     * [--drop]
     * : 테이블을 drop 시킨 후 다시 생성합니다.
     *
     * @param array $args
     * @param array $assoc_args
     *
     * @return void
     *
     * @subcommand import-hanja
     * @when       after_wp_load
     * @throws ExitException
     */
    public function importHanjaCsv(array $args, array $assoc_args): void
    {
        if (isset($assoc_args['drop'])) {
            WP_CLI::confirm('테이블을 삭제하고 시작하시겠습니까?', $assoc_args);
            MidTables::dropSingleTable('hanja');
        } else {
            WP_CLI::confirm('테이블의 내용을 비우고 시작하시겠습니까?', $assoc_args);
            MidTables::truncateSingleTable('hanja');
        }

        $path = $args[0];
        if (!file_exists($path) || !is_readable($path)) {
            WP_CLI::error("파일을 찾을 수 없거나, 일을 수 없습니다: $path");
        }

        try {
            /** @var MidImportSupport $support */
            $support = hnkp_get(MidImportSupport::class);
            $support->importHanja($path);
        } catch (Exception $e) {
            WP_CLI::error("$path 처리 중 에러: " . $e->getMessage());
        }

        WP_CLI::success('모두 성공적으로 가져왔습니다.');
    }

    /**
     * Unihan_Readings.txt, Unihan_Variants.txt 파일을 읽어 테이블에 저장합니다.
     *
     * 이전 테이블의 내용을 모두 지우고 시작합니다.
     *
     * ## EXAMPLES
     *
     *     wp hnkp unihan-import Unihan_Readings.txt Unihan_Variants.txt
     *
     * ## OPTIONS
     *
     * <r_path>
     * : Unihan_Readings.txt 파일의 경로
     *
     * <v_path>
     * : Unihan_Variants.txt 파일의 경로
     *
     * [--yes]
     * : 묻지 않습니다
     *
     * [--drop]
     * : 테이블을 drop 시킨 후 다시 생성합니다.
     *
     * @param array $args
     * @param array $assoc_args
     *
     * @return void
     * @throws ExitException
     *
     * @subcommand import-unihan
     * @when       after_wp_load
     */
    public function importUnihan(array $args, array $assoc_args): void
    {
        if (isset($assoc_args['drop'])) {
            WP_CLI::confirm('테이블을 삭제하고 시작하시겠습니까?', $assoc_args);
            MidTables::dropSingleTable('map');
            MidTables::dropSingleTable('sinji');
        } else {
            WP_CLI::confirm('테이블의 내용을 비우고 시작하시겠습니까?', $assoc_args);
            MidTables::truncateSingleTable('map');
            MidTables::truncateSingleTable('sinji');
        }

        $rPath = $args[0];
        $vPath = $args[1];

        if (!file_exists($rPath) || !is_readable($rPath)) {
            WP_CLI::error("파일을 찾을 수 없거나, 일을 수 없습니다: $rPath");
        }
        if (!file_exists($rPath) || !is_readable($rPath)) {
            WP_CLI::error("파일을 찾을 수 없거나, 일을 수 없습니다: $rPath");
        }

        try {
            /** @var MidImportSupport $support */
            $support = hnkp_get(MidImportSupport::class);
            $support->importUnihan($rPath, $vPath);
        } catch (Exception $e) {
            WP_CLI::error("처리 중 에러: " . $e->getMessage());
        }

        WP_CLI::success('모두 성공적으로 가져왔습니다.');
    }

    /**
     * JLPT 등급별 한자 정보를 가져옵니다.
     *
     * 입력할 파일은 다음처럼 구성되어 있습니다.
     * 첫줄은 'N5'라고 적고,  다음줄에 공백으로 분리된 한자 목록이 나옵니다. 목록이 끝난 후 빈 줄 하나가 나옵니다.
     * 빈 줄 후 'N4'라고 적고 같은 방법으로 한 줄에 모든 한자 목록이 나열합니다. 목록이 끝난 후 빈 줄 하나를 추가합니다.
     * 이후 N1까지 동일합니다.
     *
     * 이전 테이블의 내용을 모두 지우고 시작합니다.
     *
     * ## EXAMPLES
     *
     *     wp hnkp jlpt-import jlpt-kanji.txt
     *
     *     (Sample of file)
     *     N5
     *     一 七 万 三 上 下 ....
     *
     *     N4
     *     不 世 主 事 京 仕 代 ...
     *
     *     N3
     *     ...
     *
     * ## OPTIONS
     *
     * <path>
     * : 텍스트 파일의 경로
     *
     * [--yes]
     * : 묻지 않습니다
     *
     * [--drop]
     * : 테이블을 drop 시킨 후 다시 생성합니다.
     *
     * @param array $args
     * @param array $assoc_args
     *
     * @return void
     *
     * @subcommand import-jlpt
     * @when       after_wp_load
     * @throws ExitException
     */
    public function importJlpt(array $args, array $assoc_args): void
    {
        if (isset($assoc_args['drop'])) {
            WP_CLI::confirm('테이블을 삭제하고 시작하시겠습니까?', $assoc_args);
            MidTables::dropSingleTable('jlpt');
        } else {
            WP_CLI::confirm('테이블의 내용을 비우고 시작하시겠습니까?', $assoc_args);
            MidTables::truncateSingleTable('jlpt');
        }

        $path = $args[0];
        if (!file_exists($path) || !is_readable($path)) {
            WP_CLI::error("파일을 찾을 수 없거나, 일을 수 없습니다: $path");
        }

        try {
            /** @var MidImportSupport $support */
            $support = hnkp_get(MidImportSupport::class);
            $support->importJlpt($path);
        } catch (Exception $e) {
            WP_CLI::error("$path 처리 중 에러: " . $e->getMessage());
        }

        WP_CLI::success('모두 성공적으로 가져왔습니다.');
    }

    /**
     * jmdict_e.xml 파일을 읽어 테이블에 저장합니다.
     *
     * 이전 테이블의 내용을 모두 지우고 시작합니다.
     *
     * ## EXAMPLES
     *
     *     wp hnkp jmdict-import jmdict_e.xml
     *
     * ## OPTIONS
     *
     * <path>
     * : jmdict_e.xml 파일의 경로
     *
     * [--yes]
     * : 묻지 않습니다
     *
     * [--drop]
     * : 테이블을 drop 시킨 후 다시 생성합니다.
     *
     * @param array $args
     * @param array $assoc_args
     *
     * @return void
     * @throws ExitException
     *
     * @subcommand import-jmdict
     * @when       after_wp_load
     */
    public function importJmdict(array $args, array $assoc_args): void
    {
        if (isset($assoc_args['drop'])) {
            WP_CLI::confirm('테이블을 삭제하고 시작하시겠습니까?', $assoc_args);
            JMDictTables::dropTables();
        } else {
            WP_CLI::confirm('테이블의 내용을 비우고 시작하시겠습니까?', $assoc_args);
            JMDictTables::truncateTables();
        }

        $path = $args[0];
        if (!file_exists($path) || !is_readable($path)) {
            WP_CLI::error("파일을 찾을 수 없거나, 일을 수 없습니다: $path");
        }

        try {
            WP_CLI::log('파일을 읽고 있습니다. 잠시 기다려 주세요 ...');

            /** @var JMDictReadSupport $rs */
            $support = hnkp_get(JMDictReadSupport::class);
            $entries = $support->readXML($args[0]);
            $total   = count($entries);

            WP_CLI::log(sprintf('파일을 모두 읽었습니다. %d 항목이 있습니다.', $total));

            /** @var JMDictImportSupport $support */
            $support = hnkp_get(JMDictImportSupport::class);
            $chunks  = array_chunk($entries, 5000);
            $count   = count($chunks);

            for ($i = 0; $i < $count; ++$i) {
                WP_CLI::log(sprintf('테이블에 분할 삽입합니다. %d/%d', ($i + 1), $count));

                $chunk = array_shift($chunks);

                foreach ($chunk as $entry) {
                    $support->collectKeb($entry);
                    $support->collectKEle($entry);
                    $support->collectREle($entry);
                    $support->collectSense($entry);
                    $support->collectEntry($entry);
                }

                $support->insertKeb();
                $support->clearValues('keb');

                $support->insertKEle();
                $support->clearValues('k_ele');

                $support->insertREle();
                $support->clearValues('r_ele');

                $support->insertSense();
                $support->clearValues('sense');

                $support->insertEntry();
                $support->clearValues('entry');

                $support->clearMap();
            }

        } catch (Exception $e) {
            WP_CLI::error("$path 처리 중 에러: " . $e->getMessage());
        }

        WP_CLI::success('모두 성공적으로 가져왔습니다.');
    }

    /**
     * 테이블 작업을 마무리합니다
     *
     * hime_chars 테이블에 레코드를 추가합니다.
     * jmdict_keb 테이블 계산을 진행합니다.
     *
     * hime_chars 테이블의 내용은 모두 지우고 시작합니다.
     *
     * ## EXAMPLES
     *
     *     wp hnkp migrate-hime
     *
     * ## OPTIONS
     *
     * [--yes]
     * : 묻지 않습니다
     *
     * @param array $args
     * @param array $assoc_args
     *
     * @return void
     * @throws ExitException
     *
     * @subcommand migrate-hime
     * @when       after_wp_load
     */
    public function migrateHime(array $args, array $assoc_args): void
    {
        WP_CLI::confirm('테이블의 내용을 업데이트 하시겠습니까?', $assoc_args);
        HimeTables::truncateSingleTable('chars');

        try {
            /** @var HimeMigrateSupport $support */
            $support = hnkp_get(HimeMigrateSupport::class);
            $support->migrateHimeChars();
            WP_CLI::log('chars 내용이 갱신되었습니다.');

            $support->initCalcKeb();
            while ($support->getKebCurrentPage() <= $support->getKebLastPage()) {
                WP_CLI::log(
                    sprintf('계산 중... %d/%d', $support->getKebCurrentPage(), $support->getKebLastPage()),
                );
                $support->calcKebPage();
            }
        } catch (Exception $e) {
            WP_CLI::error("처리 중 에러: " . $e->getMessage());
        }

        WP_CLI::success('모두 성공적으로 가져왔습니다.');
    }

    /**
     * 데이터베이스 변경을 바로 적용합니다.
     *
     * 게발시 자잘한 테이블 변경 사항의 바로 반영합니다.
     *
     * ## EXAMPLES
     *
     *     wp hnkp update-tables
     *
     * ## OPTIONS
     *
     * [--drop]
     * : 테이블을 drop 시킨 후 다시 생성합니다.
     *
     * [--yes]
     * : --drop 옵션이 있을 때, 물어보지 않습니다.
     *
     * @param array $args
     * @param array $assoc_args
     *
     * @subcommand update-tables
     * @when       after_wp_load
     */
    public function updateTables(array $args, array $assoc_args): void
    {
        if (isset($assoc_args['drop'])) {
            $this->dropTables($args, $assoc_args);
        } else {
            update_option(HNKP_DB_VERSION_NAME, '0.0.0');
        }

        /** @var CustomTables $ct */
        $ct = hnkp_get('bojaghi/custom-tables');
        $ct->updateTables();

        WP_CLI::success('테이블이 업데이트 되었습니다.');
    }

    /**
     * 모든 'hnkp_' 테이블을 삭제합니다.
     *
     * ## EXAMPLES
     *
     *     wp hnkp drop-tables
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
        WP_CLI::confirm('히메 일본어 한자 플러그인 커스텀 테이블을 모두 삭제하시겠습니까?', $assoc_args);

        /** @var CustomTables $ct */
        $ct = hnkp_get('bojaghi/custom-tables');
        $ct->deleteTables();

        WP_CLI::success('테이블을 삭제했습니다.');
    }
}
