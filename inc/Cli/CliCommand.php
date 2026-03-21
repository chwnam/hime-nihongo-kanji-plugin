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
use HimeNihongo\KanjiPlugin\Supports\DB\Utils;
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
     * 첫줄은 'N1, 1137'이라고 적혀 있습니다. 그리고 다음 줄에 한자 10개씩 나열됩니다.
     * 첫줄의 N1은 해당 한자의 추정 JLPT 들급이며, 숫자는 뒤이어 나올 한자의 총 글자수입니다.
     *
     * 목록이 끝난 후 빈 줄 하나가 나옵니다.
     * 그후 'N2'부터 'N5'까지 같은 방법으로 한자를 나열합니다.
     *
     * 이전 테이블의 내용을 모두 지우고 시작합니다.
     *
     * ## EXAMPLES
     *
     *     wp hnkp jlpt-import jlpt-kanji.txt
     *
     *     (Sample of file)
     *     N1, 1137
     *     結張保撃証士第郎応護
     *     ...
     *     塡楷頒頰憬諧𠮟
     *
     *     N2, 381
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
     * 상용한자 목록을 정보를 가져옵니다.
     *
     * 이전 테이블의 내용을 모두 지우고 시작합니다.
     *
     * ## EXAMPLES
     *
     *     wp hnkp jyouyou-import jyouyoukanji.csv
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
     * @subcommand import-jyouyou
     * @when       after_wp_load
     * @throws ExitException
     */
    public function importJyouyou(array $args, array $assoc_args): void
    {
        if (isset($assoc_args['drop'])) {
            WP_CLI::confirm('테이블을 삭제하고 시작하시겠습니까?', $assoc_args);
            MidTables::dropSingleTable('jyouyou');
        } else {
            WP_CLI::confirm('테이블의 내용을 비우고 시작하시겠습니까?', $assoc_args);
            MidTables::truncateSingleTable('jyouyou');
        }

        $path = $args[0];
        if (!file_exists($path) || !is_readable($path)) {
            WP_CLI::error("파일을 찾을 수 없거나, 일을 수 없습니다: $path");
        }

        try {
            /** @var MidImportSupport $support */
            $support = hnkp_get(MidImportSupport::class);
            $support->importJyouyou($path);
        } catch (Exception $e) {
            WP_CLI::error("$path 처리 중 에러: " . $e->getMessage());
        }

        WP_CLI::success('모두 성공적으로 가져왔습니다.');
    }

    /**
     * 입력 JLPT 레벨별 한자 목록의 중복을 제거합니다.
     *
     * 인터넷에서 수집한 한자 목록에는 중복이 있을 수 있습니다.
     * 데이터 입력의 신뢰성을 높이기 위해 중복된 한자는 제거하는 작업을 합니다.
     *
     * 중복된 한자는 마지막에 중복 갯수와 같이 출력됩니다.
     * 중복이 없으면 출력 파일이 생성되지 않습니다.
     *
     * ## EXAMPLES
     *
     *     wp hnkp filter-jlpt jlpt-kanji.txt jlpt-kanji-purified.txt
     *
     * ## OPTIONS
     *
     * <input>
     * : 입력 파일 경로
     *
     * <output>
     * : 출력 파일 경로. 중복이 없을 경우 생성되지 않습니다.
     *
     * @subcommand filter-jlpt
     * @when       after_wp_load
     *
     * @param array $args
     *
     * @return void
     * @throws ExitException
     */
    public function filterJlpt(array $args): void
    {
        [$input, $output] = $args;

        $level = 0;
        $cache = [];
        $chars = [
            [],
            [],
            [],
            [],
            [],
        ];

        $in = fopen($input, 'r');
        if (!$in) {
            WP_CLI::error("Failed to open file: $input");
        }

        while (false !== ($line = fgets($in, 5000))) {
            $line = trim($line);

            if (empty($line)) {
                continue;
            }

            if (preg_match('/^N([1-5]), (\d+)$/i', $line, $matches)) {
                $level = (int)$matches[1];
                continue;
            }

            $kanji = array_map(fn($k) => Utils::normalize(trim($k)), array_filter(mb_str_split($line)));

            if ($level && $kanji) {
                foreach ($kanji as $k) {
                    if (!isset($cache[$k])) {
                        $cache[$k]           = 1;
                        $chars[$level - 1][] = $k;
                    } else {
                        $cache[$k] += 1;
                    }
                }
            }
        }

        fclose($in);

        $filterRequired = false;

        foreach ($cache as $k => $c) {
            if ($c > 1) {
                WP_CLI::log("$k: $c");
                $filterRequired = true;
            }
        }

        if ($filterRequired) {
            $out = fopen($output, 'w');
            if (!$out) {
                WP_CLI::error("Failed to open file: $output");
            }

            foreach ($chars as $level => $kanji) {
                $grade = $level + 1;
                fwrite($out, "N{$grade}, " . count($kanji) . "\n");
                foreach (array_chunk($kanji, 10) as $chunk) {
                    fwrite($out, implode('', $chunk) . "\n");
                }
                fwrite($out, "\n");
            }

            fclose($out);
        } else {
            WP_CLI::log('No duplicate found!');
        }

        WP_CLI::success('Filter complete.');
    }

    /**
     * jmdict_e.xml 파일을 읽어 테이블에 저장합니다.
     *
     * 이전 테이블의 내용을 모두 지우고 시작합니다.
     *
     * ## EXAMPLES
     *
     *     wp hnkp import-jmdict jmdict_e.xml
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
     * 카스티 테이블에서 히메 테이블로 데이터를 이전합니다.
     *
     * 히메 테이블에 자료가 이미 있을 경우, 데이터 오염이 발생할 수 있습니다.
     * --truncate 옵션을 사용하면 테이블을 완전히 비우고 작업을 하므로, 사용에 주의하시기 바랍니다.
     *
     * ## EXAMPLES
     *
     *     wp hnkp migrate-kasumi-to-hime
     *
     * ## OPTIONS
     *
     * [--truncate]
     * : hime_words, hime_char_word_rels 테이블을 truncate 합니다,
     *
     * [--yes]
     * :실행 전에 확인하지 않습니다.
     *
     * @subcommand migrate-kasumi-to-hime
     * @when       after_wp_load
     *
     * @param array $args
     * @param array $assoc_args
     *
     * @return void
     * @throws ExitException
     */
    public function migrateKasumiToHime(array $args, array $assoc_args): void
    {
        if (isset($assoc_args['truncate'])) {
            WP_CLI::confirm(
                sprintf(
                    '주의! %s, %s 테이블을 비우고 시작합니다! 진행하시겠습니까?',
                    HimeTables::getTableWords(),
                    HimeTables::getTableCharWordRels(),
                ),
                $assoc_args,
            );
        } else {
            WP_CLI::confirm(
                sprintf(
                    '주의! %s, 테이블에 데이터 오염이 발생할 수 있습니다.! 진행하시겠습니까?',
                    HimeTables::getTableWords(),
                ),
                $assoc_args,
            );
        }

        try {
            /** @var HimeMigrateSupport $support */
            $support = hnkp_get(HimeMigrateSupport::class);
            $support->migrateKasumiToHime();
        } catch (Exception $e) {
            WP_CLI::error($e->getMessage());
        }

        WP_CLI::success('모두 성공적으로 이전했습니다.');
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
