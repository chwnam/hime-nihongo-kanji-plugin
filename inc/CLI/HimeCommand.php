<?php

namespace HimeNihongo\KanjiPlugin\CLI;

use WP_CLI;
use WP_CLI\ExitException;

/**
 * Hime-table related commands.
 */
class HimeCommand
{
    /**
     * 히메 테이블 마이그레이션 작업을 시작합니다.
     *
     * 이 작업을 하기 전에 만드시 중간 데이터베이스 테이블인 dic_* 테이블에 모든 정보가 모여 있어야 합니다.
     * 'wp hnkp/dic' 명령어들을 실행하세요.
     *
     * ## EXAMPLES
     *
     *     wp hnkp/hime migrate
     *
     * @subcommand migrate
     * @when       after_wp_load
     *
     * @return void
     * @throws ExitException
     */
    public function migrate(): void
    {
        $this->checkDicTables();

        $this->migrateHimeChars();
        $this->migrateHimeWords();
        $this->doTheRest();

        $this->showHimeTableStatus();
    }

    /**
     * hime_chars 테이블 마이그레이션
     *
     * @return void
     * @throws ExitException
     */
    private function migrateHimeChars(): void
    {
        global $wpdb;

        WP_CLI::log("Migrating hime_chars table...");

        $dicPrefix  = Utils::getDicPrefix();
        $himePrefix = Utils::getHimePrefix();
        $tableChars = "{$himePrefix}chars";

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
            "FROM `{$dicPrefix}kanji` k\n" .
            "LEFT JOIN `{$dicPrefix}jlpt` j ON j.kanji = k.kanji\n" .
            "LEFT JOIN `{$dicPrefix}map` m1 ON m1.k_in = k.kanji AND m1.type IN ('t', 'z')\n" .
            "LEFT JOIN `{$dicPrefix}map` m2 ON m2.k_out = k.kanji AND m2.type = 's'\n" .
            "LEFT JOIN `{$dicPrefix}hanja` h ON h.hanja = COALESCE(m1.k_out, m2.k_in, k.kanji)\n" .
            "GROUP BY k.kanji;";

        $wpdb->query("TRUNCATE TABLE `$tableChars`");
        $wpdb->query($query);

        if ($wpdb->last_error) {
            WP_CLI::error($wpdb->last_error);
        }
    }

    /**
     * hime_words 마이그레이션 1st pass
     *
     * @return void
     * @throws ExitException
     */
    private function migrateHimeWords(): void
    {
        global $wpdb;

        WP_CLI::log("Migrating hime_words table...");

        $tangoTable   = Utils::getDicPrefix() . 'tango';
        $prefix       = Utils::getHimePrefix();
        $wordsTable   = "{$prefix}words";
        $detailsTable = "{$prefix}word_details";

        // Truncate tables
        $wpdb->query("TRUNCATE TABLE `$wordsTable`");
        $wpdb->query("TRUNCATE TABLE `$detailsTable`");

        $totalRow = $wpdb->get_var("SELECT COUNT(*) FROM `$tangoTable`");
        $perPage  = 5000;
        $lastPage = (int)ceil((float)$totalRow / (float)$perPage);

        $cached = [];

        for ($page = 0; $page < $lastPage; ++$page) {
            $wpdb->query("START TRANSACTION");

            // Get rows from dic_tango.
            $query   = $wpdb->prepare("SELECT * FROM `$tangoTable` LIMIT %d, %d", $page * $perPage, $perPage);
            $results = $wpdb->get_results($query);

            foreach ($results as $r) {
                if (!isset($cached[$r->tango])) {
                    // Insert into hime_words with default values
                    $query = $wpdb->prepare(
                        "INSERT INTO `$wordsTable` (word, word_len) VALUE (%s, %d)",
                        $r->tango,
                        mb_strlen($r->tango, 'UTF-8'),
                    );
                    $wpdb->query($query);
                    if ($wpdb->last_error) {
                        WP_CLI::error($wpdb->last_error);
                    }
                    $cached[$r->tango] = $wpdb->insert_id;
                }
                $wordId = $cached[$r->tango];

                // parse yomikata
                $yomikatas = [];
                if ($r->yomikata) {
                    // explode by '、'
                    $yomiArr = array_filter(array_map('trim', explode('、', $r->yomikata)));
                    foreach ($yomiArr as $y) {
                        if (preg_match('/^(.+?)(\(.+\))?$/', $y, $matches)) {
                            $yomikatas[] = [
                                'yomikata' => trim($matches[1] ?? ''),
                                'priority' => trim($matches[2] ?? '', ' ()'),
                            ];
                        }
                    }
                }

                // Insert each yomikata
                if ($yomikatas) {
                    $first = array_shift($yomikatas);
                    $query = $wpdb->prepare(
                        "INSERT INTO `$detailsTable` (word_id, ent_seq, yomikata, priority, info, senses) " .
                        "VALUES (%d, %d, %s, %s, %s, %s)",
                        $wordId,
                        $r->id,
                        $first['yomikata'],
                        $first['priority'],
                        $r->tango_info,
                        $r->sense,
                    );
                    $wpdb->query($query);
                    if ($wpdb->last_error) {
                        WP_CLI::error($wpdb->last_error);
                    }
                    $parentId = $wpdb->insert_id;
                    foreach ($yomikatas as $rest) {
                        $query = $wpdb->prepare(
                            "INSERT INTO `$detailsTable` (word_id, ent_seq, yomikata, priority, parent_id) " .
                            "VALUES (%d, %d, %s, %s, %d)",
                            $wordId,
                            $r->id,
                            $rest['yomikata'],
                            $rest['priority'],
                            $parentId,
                        );
                        $wpdb->query($query);
                        if ($wpdb->last_error) {
                            WP_CLI::error($wpdb->last_error);
                        }
                    }
                }
            }      // foreach

            $wpdb->query("COMMIT");
            WP_CLI::log("Page " . ($page + 1) . "/$lastPage processed.");
        } // for
    }

    /**
     * 히메 마이그레이션에서 나머지 작업
     *
     * - char_word_rels 테이블 작업
     * - chars 테이블의 hi_*, lo_* 필드 채우기
     *
     * @throws ExitException
     */
    private function doTheRest(): void
    {
        global $wpdb;

        WP_CLI::log("Migrating the rest of the job ...");

        $prefix     = Utils::getHimePrefix();
        $charsTable = "{$prefix}chars";
        $wordsTable = "{$prefix}words";
        $relsTable  = "{$prefix}char_word_rels";

        // Truncate the main table
        $wpdb->query("TRUNCATE TABLE `$relsTable`");

        $totalRow = $wpdb->get_var("SELECT COUNT(*) FROM `$wordsTable`");
        $perPage  = 5000;
        $lastPage = (int)ceil((float)$totalRow / (float)$perPage);

        // 모든 한자를 다 불러들여 캐싱 - 물론 메모리 많이 먹겠지만, ...
        // kanji => stdObject(kanji, id, jlpt, freq)
        $allChars = $wpdb->get_results(
            "SELECT kanji, id, jlpt, freq FROM `$charsTable` ORDER BY kanji",
            OBJECT_K,
        );

        for ($page = 0; $page < $lastPage; ++$page) {
            $loHi = [];
            $rels = [];

            $chunks = $wpdb->get_results(
                $wpdb->prepare("SELECT id, word FROM `$wordsTable` ORDER BY id LIMIT %d, %d", $page * $perPage, $perPage),
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

                foreach ($wordChars as $i => $wc) {
                    if (!isset($allChars[$wc])) {
                        continue;
                    }
                    $passed = true;

                    /** @var object{
                     *     kanji: string,
                     *     id: int,
                     *     jlpt: int,
                     *     freq: int
                     * } $char
                     */
                    $char = $allChars[$wc];
                    $jlpt = (int)$char->jlpt;
                    $freq = (int)$char->freq;

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

                    // 한자 - 단어의 매핑 성립
                    $rels[] = [
                        'char_id'  => $char->id,
                        'word_id'  => $word->id,
                        'char_pos' => $i,
                    ];
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

            // 한 페이지 루프를 돌고 나면 hi-lo, rels 기록
            $wpdb->query("START TRANSACTION");

            // hi-lo
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
                    $query = $wpdb->prepare("UPDATE `$wordsTable` SET $sets WHERE `id` = %d", $wordId);
                    // WP_CLI::log($query);
                    $wpdb->query($query);
                    if ($wpdb->last_error) {
                        WP_CLI::error($wpdb->last_error);
                    }
                }
            }

            // rels
            $queries = Utils::getBulkQueries($relsTable, ['char_id', 'word_id', 'char_pos'], $rels);
            // WP_CLI::log($queries[0]);
            $wpdb->query($queries[0]);
            if ($wpdb->last_error) {
                WP_CLI::error($wpdb->last_error);
            }

            $wpdb->query("COMMIT");
            WP_CLI::log("Page " . ($page + 1) . "/$lastPage processed.");
        } // for
    }

    /**
     * @throws ExitException
     */
    private function checkDicTables(): void
    {
        $tables = Utils::getDicTables();
        $counts = Utils::getTablesRowCounts($tables);

        if (count($counts) != count($tables)) {
            WP_CLI::error("Please run 'wp hnkp/dic' command first.");
        }

        foreach ($counts as $table => $count) {
            WP_CLI::log(sprintf("%s: %d", $table, $count));
            if (!$count) {
                WP_CLI::error("Table $table is empty., Please run 'wp hnkp/dic' command first.");
            }
        }
    }

    /**
     * @throws ExitException
     */
    private function showHimeTableStatus(): void
    {
        $tables = Utils::getHimeTables();
        $counts = Utils::getTablesRowCounts($tables);

        if (count($counts) != count($tables)) {
            WP_CLI::error("Count mismatch.");
        }

        foreach ($counts as $table => $count) {
            WP_CLI::log(sprintf("%s: %d", $table, $count));
        }
    }
}
