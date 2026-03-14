<?php

namespace HimeNihongo\KanjiPlugin\CLI;

use Exception;
use SimpleXMLElement;
use Normalizer;
use WP_CLI;
use WP_CLI\ExitException;
use XMLReader;

/**
 * Dictionary-related commands.
 */
class DicCommand
{
    /**
     * 한자 사전 데이터베이스 정보를 가져옵니다.
     *
     * 한자사전 kanjidic2.xml 파일로부터 한자 정보를 가져옵니다.
     *
     * ## EXAMPLES
     *
     *     wp hnkp/dic import-kanji kanjidic2.xml
     *
     * ## OPTIONS
     *
     * <path>
     * : kanjidic2.xml 파일의 경로
     *
     * @subcommand import-kanji
     * @when       after_wp_load
     *
     * @param array $args
     *
     * @return void
     * @throws ExitException
     */
    public function importKanji(array $args): void
    {
        global $wpdb;

        $prefix    = Utils::getDicPrefix();
        $tableName = "{$prefix}kanji";

        [$path] = $args;

        if (!file_exists($path) || !is_readable($path)) {
            WP_CLI::error("File not found, or unreadable: $path");
        }

        $rows   = [];
        $norm   = [];
        $reader = new XMLReader();
        $reader->open($path);

        while ($reader->read()) {
            if ($reader->nodeType == XMLReader::ELEMENT && $reader->name == 'character') {
                try {
                    $node = new SimpleXMLElement($reader->readOuterXML());
                } catch (\Exception $e) {
                    WP_CLI::error($e->getMessage());
                }

                $kanji        = '';
                $on_yomi      = '';
                $kun_yomi     = '';
                $radical      = 0;
                $stroke_count = 0;
                $freq         = 0;

                // kanji field
                if (isset($node->literal)) {
                    $kanji = (string)$node->literal;

                    // normalization
                    $n = Utils::normalize($kanji);
                    if (isset($norm[$n])) {
                        continue;
                    }
                    $norm[$n] = true;
                }

                // on_yomi and kun_yomi fields
                if (isset($node->reading_meaning->rmgroup->reading)) {
                    $on  = [];
                    $kun = [];

                    foreach ($node->reading_meaning->rmgroup->reading as $reading) {
                        switch ($reading['r_type']) {
                            case 'ja_on':
                                $on[] = (string)$reading;
                                break;
                            case 'ja_kun':
                                $kun[] = (string)$reading;
                                break;
                        }
                    }

                    if ($on) {
                        $on_yomi = implode('、', $on);
                    }
                    if ($kun) {
                        $kun_yomi = implode('、', $kun);
                    }
                }

                // radical
                if (isset($node->radical->rad_value)) {
                    foreach ($node->radical->rad_value as $rad) {
                        if ('classical' == $rad['rad_type']) {
                            $radical = (int)$rad;
                        }
                    }
                }

                // stroke_count
                if (isset($node->misc->stroke_count)) {
                    $stroke_count = (int)$node->misc->stroke_count;
                }

                // freq
                if (isset($node->misc->freq)) {
                    $freq = (int)$node->misc->freq;
                }

                $rows[] = compact('kanji', 'on_yomi', 'kun_yomi', 'radical', 'stroke_count', 'freq');
            }
        }

        $reader->close();

        // Table task
        $columns = ['kanji', 'on_yomi', 'kun_yomi', 'radical', 'stroke_count', 'freq'];

        $wpdb->query("START TRANSACTION");
        $wpdb->query("TRUNCATE TABLE `$tableName`");
        $query = Utils::getBulkQueries($tableName, $columns, $rows);
        $wpdb->query($query[0]);
        $wpdb->query("COMMIT");

        WP_CLI::success("Successfully imported.");
    }

    /**
     * 단어 사전 데이터베이스 정보를 가져옵니다.
     *
     * 단어사전 JMDict_e.xml 파일로부터 단어 정보를 가져옵니다.
     *
     * ## EXAMPLES
     *
     *     wp hnkp/dic import-tango JMDict_e.xml
     *
     * ## OPTIONS
     *
     * <path>
     * : JMDict_e.xml 파일의 경로
     *
     * @subcommand import-tango
     * @when       after_wp_load
     *
     * @param array $args
     *
     * @return void
     * @throws ExitException
     */
    public function importTango(array $args): void
    {
        global $wpdb;

        $prefix    = Utils::getDicPrefix();
        $tableName = "{$prefix}tango";

        [$path] = $args;

        if (!file_exists($path) || !is_readable($path)) {
            WP_CLI::error("File not found, or unreadable: $path");
        }

        $reader = new XMLReader();
        $reader->open($path);

        $rows  = [];
        $limit = 0;
        $count = 0;
        while ($reader->read()) {
            if ($reader->nodeType == XMLReader::ELEMENT && $reader->name == 'entry') {

                $xmlChunk = $reader->readOuterXML();
                $cleanXml = str_replace('&', '&amp;', $xmlChunk);

                try {
                    $node = new SimpleXMLElement($cleanXml);
                } catch (Exception $e) {
                    die($e->getCode() . ': ' . $e->getMessage());
                }

                // 한자 표기(keb)가 있는 경우만 처리
                if (isset($node->k_ele)) {
                    $seq      = (string)$node->ent_seq;
                    $tango    = (string)$node->k_ele[0]->keb; // 대표 표기만 취한다
                    $info     = array_map(fn($i) => trim($i, '&;'), (array)($node->k_ele[0]->ke_inf ?? []));
                    $yomikata = [];
                    $senses   = [];

                    $k_ele = [];
                    foreach ($node->k_ele as $k_ele) {
                        $tango = (string)$k_ele->keb;
                        $info  = array_map(fn($i) => trim($i, '&;'), (array)($k_ele->ke_inf ?? []));

                        $k_ele
                    }


                    foreach ($node->r_ele as $r_ele) {
                        $yomi   = (string)$r_ele->reb;
                        $priBuf = [];

                        if (isset($r_ele->re_pri)) {
                            foreach ($r_ele->re_pri as $pri) {
                                $priBuf[] = (string)$pri;
                            }
                        }

                        if ($priBuf) {
                            $yomi .= '(' . implode(',', $priBuf) . ')';
                        }

                        $yomikata[] = $yomi;
                    }

                    foreach ($node->sense as $s) {
                        $sense = [];

                        if (isset($s->field)) {
                            $sense['field'] = array_map(fn($f) => trim($f, '&;'), (array)$s->field);
                        }

                        if (isset($s->misc)) {
                            $sense['misc'] = array_map(fn($m) => trim($m, '&;'), (array)$s->misc);
                        }

                        if (isset($s->pos)) {
                            $sense['pos'] = array_map(fn($p) => trim($p, '&;'), (array)$s->pos);
                        }

                        if (isset($s->gloss)) {
                            foreach ($s->gloss as $gloss) {
                                $sense['gloss'][] = (string)$gloss;
                            }
                        }

                        $senses[] = $sense;
                    }

                    $rows[] = [
                        'id'         => $seq,
                        'tango'      => $tango,
                        'tango_info' => implode(',', $info),
                        'yomikata'   => implode('、', $yomikata),
                        'sense'      => json_encode($senses, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ];

                    if ($limit && ++$count >= $limit) {
                        break;
                    }
                }
            }
        }

        $reader->close();

        // Table task
        $wpdb->query("START TRANSACTION");
        $wpdb->query("TRUNCATE TABLE `$tableName`");
        $queries = Utils::getBulkQueries($tableName, ['id', 'tango', 'tango_info', 'yomikata', 'sense'], $rows, 1000);
        foreach ($queries as $query) {
            $wpdb->query($query);
        }
        $wpdb->query("COMMIT");

        // WP_CLI::log($query);

        WP_CLI::success("Successfully imported.");
    }

    /**
     * 한국어문학회 급수별 한자 CSV 데이터베이스 정보를 가져옵니다.
     *
     * 모든 급수 hanja.csv 파일로부터 한자 정보를 가져옵니다.
     *
     * ## EXAMPLES
     *
     *     wp hnkp/dic import-hanja hanja.csv
     *
     * ## OPTIONS
     *
     * <path>
     * : hanja.csv 파일 경로
     *
     * @subcommand import-hanja
     * @when       after_wp_load
     *
     * @param array $args
     *
     * @return void
     * @throws ExitException
     */
    public function importHanja(array $args): void
    {
        global $wpdb;

        $prefix    = Utils::getDicPrefix();
        $tableName = "{$prefix}hanja";

        [$path] = $args;

        if (!file_exists($path) || !is_readable($path)) {
            WP_CLI::error("File not found, or unreadable: $path");
        }

        $fp = fopen($path, 'r');
        if (!$fp) {
            WP_CLI::error("Failed to open file: $path");
        }

        // Discard the first row, the header.
        fgetcsv($fp, 1000, ',', '"', '\\');

        $rows = [];
        while (($data = fgetcsv($fp, 1000, ',', '"', '\\')) !== false) {
            // main_sound, level, hanja, meaning, radical, strokes, total_strokes
            $main_sound = trim($data[0]);
            $level      = match (trim($data[1])) {
                '8급'  => '8a',
                '7급'  => '7a',
                '7급Ⅱ' => '7b',
                '6급'  => '6a',
                '6급Ⅱ' => '6b',
                '5급'  => '5a',
                '5급Ⅱ' => '5b',
                '4급'  => '4a',
                '4급Ⅱ' => '4b',
                '3급'  => '3a',
                '3급Ⅱ' => '3b',
                '2급'  => '2a',
                '1급'  => '1a',
                '특급'  => 'sa',
                '특급Ⅱ' => 'sb',
            };
            $hanja      = trim($data[2]);
            $meaning    = trim($data[3]);

            $rows[] = compact('main_sound', 'level', 'hanja', 'meaning');
        }
        fclose($fp);

        $wpdb->query("START TRANSACTION");
        $wpdb->query("TRUNCATE TABLE `$tableName`");
        $query = Utils::getBulkQueries($tableName, ['main_sound', 'level', 'hanja', 'meaning'], $rows);
        $wpdb->query($query[0]);
        $wpdb->query("COMMIT");

        WP_CLI::success("Successfully imported.");
    }

    /**
     * 신자체 한자 목록 테이블을 가져옵니다.
     *
     * labocho/unihan_utils 리포지터리에서 수집한 Unihan_Readings.txt 파일에서 신자체 한자 목록을 가져옵니다.
     *
     * ## EXAMPLES
     *
     *     $ wp hnkp/dic import-sinji Unihan_Readings.txt
     *
     * ## OPTIONS
     *
     * <path>
     * : Unihan_Readings.txt 파일의 경로
     *
     * @subcommand import-sinji
     * @when       after_wp_load
     *
     * @param array $args
     *
     * @return void
     * @throws ExitException
     */
    public function importSinji(array $args): void
    {
        global $wpdb;

        $prefix    = Utils::getDicPrefix();
        $tableName = "{$prefix}sinji";
        $rows      = $this->getSinjiList($args[0]);

        $wpdb->query("START TRANSACTION");
        $wpdb->query("TRUNCATE TABLE `$tableName`");
        $query = Utils::getBulkQueries($tableName, ['kanji'], array_values($rows));
        $wpdb->query($query[0]);
        $wpdb->query("COMMIT");

        WP_CLI::success("Successfully imported.");
    }

    /**
     * @param string $path
     *
     * @return array
     * @throws ExitException
     */
    private function getSinjiList(string $path): array
    {
        if (!file_exists($path) || !is_readable($path)) {
            WP_CLI::error("File not found, or unreadable: $path");
        }

        $fp = fopen($path, 'r');
        if (!$fp) {
            WP_CLI::error("Failed to open file: $path");
        }

        $rows = [];

        while (false !== ($line = fgets($fp, 1000))) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            $cols = array_map('trim', preg_split("/\s+/", $line));
            $char = Utils::normalize(Utils::unicodeToStr($cols[0]));
            $type = $cols[1];

            if ($char && in_array($type, ['kJapaneseKun', 'kJapaneseOn'], true) && !isset($rows[$char])) {
                $rows[$char] = ['kanji' => $char];;
            }
        }

        fclose($fp);

        return $rows;
    }

    /**
     * 한자 매핑 데이터를 가져옵니다,.
     *
     * labocho/unihan_utils 리포지터리에서 수집한 Unihan_Variants.txt 파일에서 변환 테이블을 읽어옵니다.
     *
     * ## EXAMPLES
     *
     *    wp hnkp/dic import-map Unihan_Variants.txt Unihan_Readings.txt
     *
     * ## OPTIONS
     *
     * <variant_path>
     * : Unihan_Variants.txt 파일의 경로
     *
     * <reading_path>
     * : Unihan_Readings.txt 파일의 경로
     *
     * @subcommand import-map
     * @when       after_wp_load
     *
     * @param array $args
     *
     * @return void
     * @throws ExitException
     */
    public function importMap(array $args): void
    {
        global $wpdb;

        $prefix    = Utils::getDicPrefix();
        $tableName = "{$prefix}map";

        [$path, $rPath] = $args;

        if (!file_exists($path) || !is_readable($path)) {
            WP_CLI::error("File not found, or unreadable: $path");
        }

        $fp = fopen($path, 'r');
        if (!$fp) {
            WP_CLI::error("Failed to open file: $path");
        }

        $sinji = $this->getSinjiList($rPath);
        $rows  = [];

        while (false !== ($line = fgets($fp, 1000))) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            $cols = array_map('trim', preg_split("/\s+/", $line));

            $k_in    = Utils::normalize(Utils::unicodeToStr($cols[0]));
            $k_out   = Utils::normalize(Utils::unicodeToStr($cols[2]));
            $type    = match ($cols[1]) {
                'kTraditionalVariant' => 't', // 신자를 구자로 매핑
                'kSimplifiedVariant'  => 's', // 구자를 신자로 매핑
                'kZVariant'           => 'z', // 자형만 약간 다른 동일한 글자
                default               => '',
            };
            $key     = "$k_in-$k_out-$type";
            $isSinji =
                ('t' === $type && isset($sinji[$k_in])) ||
                ('s' === $type && isset($sinji[$k_out])) ||
                ('z' === $type && isset($sinji[$k_in]));

            if ($isSinji && !isset($rows[$key])) {
                $rows[$key] = compact('k_in', 'k_out', 'type');
            }
        }

        fclose($fp);

        $wpdb->query("START TRANSACTION");
        $wpdb->query("TRUNCATE TABLE `$tableName`");
        $query = Utils::getBulkQueries($tableName, ['k_in', 'k_out', 'type'], array_values($rows));
        $wpdb->query($query[0]);
        $wpdb->query("COMMIT");

        WP_CLI::success("Successfully imported.");
    }

    /**
     * JLPT 등급별 한자 정보를 가져옵니다.
     *
     * 입력할 파일은 다음처럼 구성되어 있습니다.
     * 첫줄은 'N5'라고 적고,  다음줄에 공백으로 분리된 한자 목록이 나옵니다. 목록이 끝난 후 빈 줄 하나가 나옵니다.
     * 빈 줄 후 'N4'라고 적고 같은 방법으로 한 줄에 모든 한자 목록이 나열합니다. 목록이 끝난 후 빈 줄 하나를 추가합니다.
     * 이후 N1까지 동일합니다.
     *
     * ## EXAMPLES
     *
     *     wp hnkp/dic import-jlpt jlpt-kanji.txt
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
     * <path>
     * : 텍스트 파일의 경로
     *
     * @subcommand import-jlpt
     * @when       after_wp_load
     *
     * @param array $args
     *
     * @return void
     * @throws ExitException
     */
    public function importJlpt(array $args): void
    {
        global $wpdb;

        $prefix    = Utils::getDicPrefix();
        $tableName = "{$prefix}jlpt";

        [$path] = $args;

        if (!file_exists($path) || !is_readable($path)) {
            WP_CLI::error("File not found, or unreadable: $path");
        }

        $fp = fopen($path, 'r');
        if (!$fp) {
            WP_CLI::error("Failed to open file: $path");
        }

        $rows  = [];
        $level = 0;

        while (false !== ($line = fgets($fp, 5000))) {
            $line = trim($line);

            if (empty($line)) {
                continue;
            }

            if (preg_match('/^N([1-5])$/', $line, $matches)) {
                $level = (int)$matches[1];
                continue;
            }

            $kanji = array_map(fn($k) => Utils::normalize(trim($k)), array_filter(explode(' ', $line)));

            if ($level && $kanji) {
                foreach ($kanji as $k) {
                    $rows[] = ['level' => $level, 'kanji' => $k];
                }
                $level = 0;
            }
        }

        fclose($fp);

        $wpdb->query("START TRANSACTION");
        $wpdb->query("TRUNCATE TABLE `$tableName`");
        $query = Utils::getBulkQueries($tableName, ['level', 'kanji'], $rows);
        $wpdb->query($query[0]);
        $wpdb->query("COMMIT");

        WP_CLI::success("Successfully imported.");
    }
}
