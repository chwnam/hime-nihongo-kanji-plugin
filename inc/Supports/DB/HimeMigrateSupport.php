<?php

namespace HimeNihongo\KanjiPlugin\Supports\DB;

use Bojaghi\Contract\Support;
use Exception;

class HimeMigrateSupport implements Support
{
    private const int PAGE_SIZE = 5000;

    private int $totalRow = 0;

    private int $currentPage = 0;

    private int $lastPage = 0;

    /** @var array<string, object{kanji: string, id: int, jlpt: int, freq: int}> */
    private array $lookup = [];

    /**
     * hime_chars 테이블 마이그레이션
     *
     * @return void
     * @throws Exception
     */
    public function migrateHimeChars(): void
    {
        global $wpdb;

        $tableChars = HimeTables::getTableChars();
        $tableKanji = MidTables::getTableKanji();
        $tableJlpt  = MidTables::getTableJlpt();
        $tableMap   = MidTables::getTableMap();
        $tableSinji = MidTables::getTableSinji();
        $tableHanja = MidTables::getTableHanja();

        $query = "INSERT INTO `$tableChars` (\n" .
            "kanji, kun_yomi, on_yomi, radical, stroke_count, freq, jlpt, ko_hanja, ko_on, ko_meaning, ko_level)\n" .
            "SELECT\n" .
            "    k.kanji,\n" .
            "    k.kun_yomi,\n" .
            "    k.on_yomi,\n" .
            "    k.radical,\n" .
            "    k.stroke_count,\n" .
            "    k.freq,\n" .
            "    COALESCE(j.level, 0) AS jlpt,\n" .
            "    MAX(CASE\n" .
            "        WHEN h.hanja IS NULL THEN NULL\n" .
            "        WHEN k.kanji = h.hanja THEN ''\n" .
            "        ELSE h.hanja\n" .
            "    END) AS ko_hanja,\n" .
            "    MAX(h.main_sound) AS ko_on,\n" .
            "    MAX(h.meaning) AS ko_meaning,\n" .
            "    MAX(h.level) AS ko_level\n" .
            "FROM `$tableKanji` k\n" .
            "LEFT JOIN `$tableJlpt` j ON j.kanji = k.kanji\n" .
            "LEFT JOIN `$tableMap` m1 ON m1.k_in = k.kanji AND m1.type IN ('t', 'z')\n" .
            "LEFT JOIN `$tableMap` m2 ON m2.k_out = k.kanji AND m2.type = 's'\n" .
            "LEFT JOIN `$tableSinji` s1 ON s1.kanji = m1.k_out\n" .
            "LEFT JOIN `$tableSinji` s2 ON s2.kanji = m2.k_out\n" .
            "LEFT JOIN `$tableHanja` h ON h.hanja = (IF(k.kanji = h.hanja, k.kanji, COALESCE(m1.k_out, m2.k_in)))\n" .
            "GROUP BY k.kanji ORDER BY k.id";
        $wpdb->query($query);
        if ($wpdb->last_error) {
            throw new Exception($wpdb->last_error);
        }
    }

    public function initCalcKeb(): void
    {
        global $wpdb;

        $charsTable = HimeTables::getTableChars();
        $kebTable   = JMDictTables::getTableKeb();

        $this->lookup      = $wpdb->get_results("SELECT kanji, id, jlpt, freq FROM `$charsTable` ORDER BY kanji", OBJECT_K);
        $this->totalRow    = (int)$wpdb->get_var("SELECT COUNT(*) FROM `$kebTable`");
        $this->lastPage    = (int)ceil((float)$this->totalRow / (float)self::PAGE_SIZE);
        $this->currentPage = 1;
    }

    public function getKebCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function getKebLastPage(): int
    {
        return $this->lastPage;
    }

    /**
     * @throws Exception
     */
    public function calcKebPage(): void
    {
        global $wpdb;

        if ($this->currentPage > $this->lastPage) {
            return;
        }

        $kebTable = JMDictTables::getTableKeb();
        $offset   = ($this->currentPage - 1) * self::PAGE_SIZE;

        /** @var array<int, array> $loHi */
        $loHi = [];

        $chunks = $wpdb->get_results(
            $wpdb->prepare("SELECT id, word FROM `$kebTable` ORDER BY id LIMIT %d, %d", $offset, self::PAGE_SIZE),
        );

        foreach ($chunks as $word) {
            /** @var object{id: int, word: string} $word */

            // 단어를 한 글자씩 분리
            $wordChars = mb_str_split($word->word, 1, 'UTF-8');

            // hi-lo vars
            $hiJlpt = ['i' => -1, 'jlpt' => 6];
            $loJlpt = ['i' => -1, 'jlpt' => 0];
            $hiFreq = ['i' => -1, 'freq' => 99999];
            $loFreq = ['i' => -1, 'freq' => 0];
            $passed = false;

            foreach ($wordChars as $i => $c) {
                if (!isset($this->lookup[$c])) {
                    continue;
                }
                $passed = true;

                /** @var object{
                 *     kanji: string,
                 *     id: int,
                 *     jlpt: int,
                 *     freq: int
                 * } $keb
                 */
                $keb  = $this->lookup[$c];
                $jlpt = (int)$keb->jlpt;
                $freq = (int)$keb->freq;

                // 최고, 최저 등급의 JLPT 계산
                if ($jlpt < $hiJlpt['jlpt']) {
                    $hiJlpt = compact('i', 'jlpt');
                }
                if ($jlpt > $loJlpt['jlpt']) {
                    $loJlpt = compact('i', 'jlpt');
                }
                // 최저, 최고 빈도 계산
                if ($freq > 0) {
                    if ($freq < $hiFreq['freq']) {
                        $hiFreq = compact('i', 'freq');
                    }
                    if ($freq > $loFreq['freq']) {
                        $loFreq = compact('i', 'freq');
                    }
                }
            }

            if ($passed) {
                $lh = [];
                // lo-hi 데이터 성립
                if ($hiJlpt['i'] > -1) {
                    $lh['hi_jlpt']     = $hiJlpt['jlpt'];
                    $lh['hi_jlpt_pos'] = $hiJlpt['i'];
                }
                if ($loJlpt['i'] > -1) {
                    $lh['lo_jlpt']     = $loJlpt['jlpt'];
                    $lh['lo_jlpt_pos'] = $loJlpt['i'];
                }
                if ($hiFreq['i'] > -1) {
                    $lh['hi_freq']     = $hiFreq['freq'];
                    $lh['hi_freq_pos'] = $hiFreq['i'];
                }
                if ($loFreq['i'] > -1) {
                    $lh['lo_freq']     = $loFreq['freq'];
                    $lh['lo_freq_pos'] = $loFreq['i'];
                }
                if ($lh) {
                    $loHi[$word->id] = $lh;
                }
            }
        }

        foreach ($loHi as $wordId => $lh) {
            $setBuf = [];

            if (isset($lh['hi_jlpt'])) {
                $setBuf[] = $wpdb->prepare("hi_jlpt = %d", $lh['hi_jlpt']);
                $setBuf[] = $wpdb->prepare("hi_jlpt_pos = %d", $lh['hi_jlpt_pos']);
            }
            if (isset($lh['lo_jlpt'])) {
                $setBuf[] = $wpdb->prepare("lo_jlpt = %d", $lh['lo_jlpt']);
                $setBuf[] = $wpdb->prepare("lo_jlpt_pos = %d", $lh['lo_jlpt_pos']);
            }
            if (isset($lh['hi_freq'])) {
                $setBuf[] = $wpdb->prepare("hi_freq = %d", $lh['hi_freq']);
                $setBuf[] = $wpdb->prepare("hi_freq_pos = %d", $lh['hi_freq_pos']);
            }
            if (isset($lh['lo_freq'])) {
                $setBuf[] = $wpdb->prepare("lo_freq = %d", $lh['lo_freq']);
                $setBuf[] = $wpdb->prepare("lo_freq_pos = %d", $lh['lo_freq_pos']);
            }

            if ($setBuf) {
                $sets  = implode(', ', $setBuf);
                $query = $wpdb->prepare("UPDATE `$kebTable` SET $sets WHERE `id` = %d", $wordId);
                $wpdb->query($query);
                if ($wpdb->last_error) {
                    throw new Exception($wpdb->last_error);
                }
            }
        }

        ++$this->currentPage;
    }
}