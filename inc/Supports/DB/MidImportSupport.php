<?php

namespace HimeNihongo\KanjiPlugin\Supports\DB;

use Bojaghi\Contract\Support;
use Exception;
use SimpleXMLElement;
use XMLReader;

class MidImportSupport implements Support
{
    /**
     * @param string $path
     *
     * @return void
     * @throws Exception
     */
    public function importKanjidic2(string $path): void
    {
        global $wpdb;

        $table = MidTables::getTableKanji();

        $rows   = [];
        $norm   = [];
        $reader = new XMLReader();
        $reader->open($path);

        while ($reader->read()) {
            if ($reader->nodeType == XMLReader::ELEMENT && $reader->name == 'character') {
                $node = new SimpleXMLElement($reader->readOuterXML());

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

                $rows[] = $wpdb->prepare(
                    '(%s,%s,%s,%d,%d,%d)',
                    $kanji,
                    $on_yomi,
                    $kun_yomi,
                    $radical,
                    $stroke_count,
                    $freq,
                );
            }
        }

        $reader->close();

        $wpdb->query("START TRANSACTION");

        foreach (array_chunk($rows, 500) as $chunk) {
            $query = "INSERT INTO `$table` (`kanji`,`on_yomi`,`kun_yomi`,`radical`,`stroke_count`,`freq`) VALUES ";
            $query .= implode(',', $chunk);
            $wpdb->query($query);
            if ($wpdb->last_error) {
                throw new Exception($wpdb->last_error);
            }
        }

        $wpdb->query("COMMIT");
    }

    /**
     * @param string $path
     *
     * @return void
     * @throws Exception
     */
    public function importHanja(string $path): void
    {
        global $wpdb;

        $table = MidTables::getTableHanja();

        $fp = fopen($path, 'r');
        if (!$fp) {
            throw new Exception("Failed to open file: $path");
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

            $rows[] = $wpdb->prepare(
                '(%s,%s,%s,%s)',
                $main_sound,
                $level,
                $hanja,
                $meaning,
            );
        }
        fclose($fp);

        $wpdb->query("START TRANSACTION");

        foreach (array_chunk($rows, 1000) as $chunk) {
            $query = "INSERT INTO `$table` (`main_sound`,`level`,`hanja`,`meaning`) VALUES ";
            $query .= implode(',', $chunk);
            $wpdb->query($query);
            if ($wpdb->last_error) {
                throw new Exception($wpdb->last_error);
            }
        }

        $wpdb->query("COMMIT");
    }

    /**
     * @param string $readingsPath
     * @param string $variantsPath
     *
     * @return void
     * @throws Exception
     */
    public function importUnihan(string $readingsPath, string $variantsPath): void
    {
        global $wpdb;

        $sinjiTable = MidTables::getTableSinji();
        $mapTable   = MidTables::getTableMap();

        $readingsRows = []; // Unihan_Readings.txt
        $variantsRows = []; // Unihan_Variants.txt
        $rCached      = [];
        $vCached      = [];

        // $readingsPath 텍스트 파일 읽음
        $fp = fopen($readingsPath, 'r');
        if (!$fp) {
            throw new Exception("Failed to open file: $readingsPath");
        }
        while (false !== ($line = fgets($fp, 1000))) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            $cols = array_map('trim', preg_split("/\s+/", $line));
            $char = Utils::normalize(Utils::unicodeToStr($cols[0]));
            $type = $cols[1];

            if ($char && in_array($type, ['kJapaneseKun', 'kJapaneseOn'], true) && !isset($rCached[$char])) {
                $readingsRows[] = $wpdb->prepare('(%s)', $char);;
                $rCached[$char] = true;
            }
        }
        fclose($fp);

        // $variantsPath 텍스트 파일 읽음
        $fp = fopen($variantsPath, 'r');
        if (!$fp) {
            throw new Exception("Failed to open file: $variantsPath");
        }
        while (false !== ($line = fgets($fp, 1000))) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            $cols    = array_map('trim', preg_split("/\s+/", $line));
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
                ('t' === $type && isset($rCached[$k_in])) ||
                ('s' === $type && isset($rCached[$k_out])) ||
                ('z' === $type && isset($rCached[$k_in]));

            if ($isSinji && !isset($vCached[$key])) {
                $variantsRows[] = $wpdb->prepare('(%s,%s,%s)', $k_in, $k_out, $type);
                $vCached[$key]  = true;
            }
        }
        fclose($fp);

        // 테이블에 삽입
        $wpdb->query("INSERT INTO `$sinjiTable` (`kanji`) VALUES " . implode(',', $readingsRows));
        if ($wpdb->last_error) {
            throw new Exception($wpdb->last_error);
        }
        $wpdb->query("INSERT INTO `$mapTable` (`k_in`,`k_out`,`type`) VALUES " . implode(',', $variantsRows));
        if ($wpdb->last_error) {
            throw new Exception($wpdb->last_error);
        }
    }

    /**
     * @param string $path
     *
     * @return void
     * @throws Exception
     */
    public function importJlpt(string $path): void
    {
        global $wpdb;

        $table = MidTables::getTableJlpt();

        if (!file_exists($path) || !is_readable($path)) {
            throw new Exception("File not found, or unreadable: $path");
        }

        $fp = fopen($path, 'r');
        if (!$fp) {
            throw new Exception("Failed to open file: $path");
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
                    $rows[] = $wpdb->prepare('(%d,%s)', $level, $k);
                }
                $level = 0;
            }
        }

        fclose($fp);

        $query = "INSERT INTO `$table` (`level`, `kanji`) VALUES " . implode(',', $rows);
        $wpdb->query($query);
        if ($wpdb->last_error) {
            throw new Exception($wpdb->last_error);
        }
    }
}
