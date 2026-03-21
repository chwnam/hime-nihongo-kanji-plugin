<?php

namespace HimeNihongo\KanjiPlugin\Supports\DB;

use Bojaghi\Contract\Support;
use Exception;

class HimeMigrateSupport implements Support
{
    private const int PAGE_SIZE = 5000;

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

        $tableChars   = HimeTables::getTableChars();
        $tableKanji   = MidTables::getTableKanji();
        $tableJlpt    = MidTables::getTableJlpt();
        $tableMap     = MidTables::getTableMap();
        $tableHanja   = MidTables::getTableHanja();
        $tableJyouyou = MidTables::getTableJyouyou();

        $query = "INSERT INTO `$tableChars` (\n" .
            "kanji, kun_yomi, on_yomi, radical, stroke_count, freq, jlpt, jyouyou, gakunen, ko_hanja, ko_on, ko_meaning, ko_level)\n" .
            "SELECT\n" .
            "    k.kanji,\n" .
            "    k.kun_yomi,\n" .
            "    k.on_yomi,\n" .
            "    k.radical,\n" .
            "    k.stroke_count,\n" .
            "    k.freq,\n" .
            "    COALESCE(j.level, 0) AS jlpt,\n" .
            "    jy.id AS jyouyou,\n" .
            "    jy.gakunen,\n" .
            "    MAX(CASE\n" .
            "        WHEN COALESCE(h1.hanja, h2.hanja, h3.hanja) IS NULL THEN NULL\n" .
            "        WHEN COALESCE(h1.hanja, h2.hanja, h3.hanja) = k.kanji THEN ''\n" .
            "        ELSE COALESCE(h1.hanja, h2.hanja, h3.hanja)\n" .
            "    END) AS ko_hanja,\n" .
            "    MAX(COALESCE(h1.main_sound, h2.main_sound, h3.main_sound)) AS ko_on,\n" .
            "    MAX(COALESCE(h1.meaning, h2.meaning, h3.meaning)) AS ko_meaning,\n" .
            "    MAX(COALESCE(h1.level, h2.level, h3.level)) AS ko_level\n" .
            "FROM `$tableKanji` k\n" .
            "LEFT JOIN `$tableJlpt` j ON j.kanji = k.kanji\n" .
            "LEFT JOIN `$tableJyouyou` jy ON jy.kanji = k.kanji\n" .
            "LEFT JOIN `$tableMap` m1 ON m1.k_in = k.kanji\n" .
            "LEFT JOIN `$tableHanja` h1 ON h1.hanja = m1.k_out\n" .
            "LEFT JOIN `$tableMap` m2 ON m2.k_out = k.kanji\n" .
            "LEFT JOIN `$tableHanja` h2 ON h2.hanja = m2.k_in\n" .
            "LEFT JOIN `$tableHanja` h3 ON h3.hanja = k.kanji\n" .
            "GROUP BY k.id ORDER BY k.id";
        // echo $query;
        $wpdb->query($query);
        if ($wpdb->last_error) {
            throw new Exception($wpdb->last_error);
        }
    }

    /**
     * mid_keb 테이블의 hi, lo 필드 계산을 초기화
     *
     * @return void
     * @used-by CliCommand::migrateHime()
     */
    public function initCalcKeb(): void
    {
        global $wpdb;

        $charsTable = HimeTables::getTableChars();
        $kebTable   = JMDictTables::getTableKeb();

        $totalRows         = (int)$wpdb->get_var("SELECT COUNT(*) FROM `$kebTable`");
        $this->lookup      = $wpdb->get_results("SELECT kanji, id, jlpt, freq FROM `$charsTable` ORDER BY kanji", OBJECT_K);
        $this->lastPage    = (int)ceil((float)$totalRows / (float)self::PAGE_SIZE);
        $this->currentPage = 1;
    }

    /**
     * mid_keb 테이블 hi, lo 필드 계산 작업 중 현재 작업 중인 페이지를 리턴
     *
     * @return int
     * @used-by CliCommand::migrateHime()
     */
    public function getKebCurrentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * mid_keb 테이블 hi, lo 필드 계산 작업 중, 계산한 전체 작업량 페이지를 리턴
     *
     * @return int
     * @used-by CliCommand::migrateHime()
     */
    public function getKebLastPage(): int
    {
        return $this->lastPage;
    }

    /**
     * mid_keb 테이블 hi, lo 필드 계산 본체
     *
     * @throws Exception
     * @used-by CliCommand::migrateHime()
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

    /**
     * migrate-kasumi-to-hime 명령의 실제 실행 코드
     *
     * @throws Exception
     * @used-by CliCommand::migrateKasumiToHime()
     */
    public function migrateKasumiToHime(): void
    {
        global $wpdb;

        // kasumi_chars의 모든 한자는 hime_chars에서 찾을 수가 있습니다.
        // 그러므로 chars에서 중복되지 않게 hime_chars.id 만 추출하는 것을 목표로 합니다.

        // kasumi_words에는 오류가 있을 가능성이 높습니다.
        // 우선 히메 테이블로 모든 단어를 중복 없이 복제합니다.
        $kasumiWords = KasumiTables::getTableWords();
        $himeWords   = HimeTables::getTableWords();

        $query = "INSERT IGNORE INTO `$himeWords` (word, yomikata, meaning) SELECT word, yomikata, meaning FROM `$kasumiWords`";
        $wpdb->query($query);
        if ($wpdb->last_error) {
            throw new Exception($wpdb->last_error);
        }

        // hime_words 단어와 읽는 법을 기준으로
        // jmdict 단어에 등재된 사항이 있는지 검사하고
        // jmdict에서 발견할 수 없는 경우라면 교열 대상이 되므로 로그로 표시합니다.
    }
}