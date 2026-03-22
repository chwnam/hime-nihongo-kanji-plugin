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
    public function migrateKasumiToHime(string $correctionCsvPath, array $kasumiPaths): void
    {
        global $wpdb;

        $table = HimeTables::getTableWords();

        // kasumi_corrected.csv 파일을 읽어 먼저 이 데이터를 삽입한다.
        $fp = fopen($correctionCsvPath, 'r');
        if (!$fp) {
            throw new Exception("Failed to open file: $correctionCsvPath");
        }

        $correctionRows = [];
        // 헤더 버림
        fgetcsv($fp, 1000, ',', '"', '\\');
        // 우선 모든 행 수집하고 시작
        while (false !== ($row = fgetcsv($fp, 1000, ',', '"', '\\'))) {
            $correctionRows[] = array_map(fn($d) => trim($d), $row);
        }
        fclose($fp);

        // 강제 인서트
        $values = [];
        foreach ($correctionRows as $row) {
            // corrected word, yomikata
            $values[] = $wpdb->prepare('(%s,%s,%s)', $row[8], $row[9], $row[4]);
            if ($row[10]) {
                $values[] = $wpdb->prepare('(%s,%s,%s)', $row[8], $row[10], $row[4]);
            }
        }
        if ($values) {
            $wpdb->query(
                "INSERT IGNORE INTO `$table` (word, yomikata, meaning) VALUES " . implode(',', $values),
            );
        }

        // 수정된 내역에 대해 잘못된 항목이 들어가지 않도록 방어하는 장치 구축
        $guard = [];
        foreach ($correctionRows as $idx => $row) {
            $jlpt        = $row[0];
            $entry       = $row[1];
            $word0       = $row[2];
            $yomi0       = $row[3];
            $key         = "$jlpt@$entry@$word0@$yomi0";
            $guard[$key] = $idx;
        }

        // n3, n4, n5 csv 파일 순회 루프하면서
        // 모든 나머지 단어를 읽어 테이블에 넣는다.
        $kasumiRows = [];

        foreach ($kasumiPaths as $path) {
            if (!preg_match('/^n(\d)\.csv$/i', basename($path), $matches)) {
                throw new Exception('급수 정보는 파일 이름에서 추출합니다. 파일 이름 형식을 맞춰 주세요.');
            }

            $jlpt = (int)$matches[1]; // JLPT level

            $kasumiRows[$jlpt] = [];

            $fp = fopen($path, 'r');
            while (($row = fgetcsv($fp, 1000, ',', '"', '\\')) !== false) {
                $kasumiRows[$jlpt][] = array_map('trim', $row);
            }
            fclose($fp);

            $lastEntry = -1;
            foreach ($kasumiRows[$jlpt] as $row) {
                $entry = (int)$row[0];

                if ($entry > $lastEntry) {
                    // 새 한자 - 한자 입력
                    $lastEntry = $entry;
                } elseif ($entry === $lastEntry) {
                    // 한자의 용례
                    $word    = $row[1];
                    $yomi    = $row[2];
                    $meaning = $row[3];

                    $key = "$jlpt@$entry@$word@$yomi";
                    if (isset($guard[$key])) {
                        continue;
                    }

                    $query = $wpdb->prepare(
                        "INSERT IGNORE INTO `$table` " .
                        "(word, yomikata, meaning) VALUE " .
                        "('%s', '%s', '%s')",
                        $word,
                        $yomi,
                        $meaning,
                    );

                    $wpdb->query($query);
                    if ($wpdb->last_error) {
                        throw new Exception('에러: ' . $wpdb->last_error);
                    }
                }
            }
        }

        $kasumiMap = [
            'chars' => [
                // [
                //     'jlpt'     => 0,
                //     'entry'    => 1,
                //     'kanji'    => '',
                //     'on_yomi'  => '',
                //     'kun_yomi' => '',
                //     'mapping'  => 0',
                // ],
            ],
            'words' => [
                // [
                //     'jlpt'     => 0,
                //     'entry'    => 1,
                //     'word'     => '',
                //     'yomikata' => '',
                //     'meaning'  => '',
                //     'mapping'  => [],
                // ],
            ],
        ];

        // 댜시 CSV 파일 루프를 돌면서, 완전한 매핑 테이블을 구한다.
        $charsTable = HimeTables::getTableChars();
        $wordsTable = HimeTables::getTableWords();

        foreach ($kasumiRows as $jlpt => $rows) {
            $lastEntry = -1;

            foreach ($rows as $row) {
                $entry = (int)$row[0];
                if ($entry > $lastEntry) {
                    $lastEntry = $entry;
                    // 한자 파트
                    $char = [
                        'jlpt'     => $jlpt,
                        'entry'    => (int)$row[0],
                        'kanji'    => Utils::normalize($row[1]),
                        'on_yomi'  => $row[2],
                        'kun_yomi' => $row[3],
                        'mapping'  => [],
                    ];

                    // 양이 적으므로 바로 찾아 바로 매핑한다
                    $id = (int)$wpdb->get_var(
                        $wpdb->prepare("SELECT id FROM `$charsTable` WHERE kanji=%s", $char['kanji']),
                    );
                    if (!$id) {
                        throw new Exception("Failed to find kanji: {$char['kanji']}, LINE: " . __LINE__);
                    }

                    $char['mapping']      = $id;
                    $kasumiMap['chars'][] = $char;
                } elseif ($entry === $lastEntry) {
                    // 용례 파트
                    $entry   = (int)$row[0];
                    $w       = Utils::normalize($row[1]);
                    $yomi    = $row[2];
                    $meaning = $row[3];

                    $word = [
                        'jlpt'     => $jlpt,
                        'entry'    => $entry,
                        'word'     => $w,
                        'yomikata' => $yomi,
                        'meaning'  => $meaning,
                        'mapping'  => [],
                    ];

                    // 오타난 곳의 내역인지 확인하자
                    $key = "$jlpt@$entry@$w@$yomi";

                    if (array_key_exists($key, $guard)) {
                        // $guard 매핑을 활용해 교정한 내역을 확인한다.
                        $idx = $guard[$key]; // $correctionRows 인덱스

                        $_word_arg = $correctionRows[$idx][8];
                        $_yomi_arg = $correctionRows[$idx][9];

                        $query = $wpdb->prepare(
                            "SELECT id FROM `$wordsTable` WHERE word=%s AND yomikata=%s",
                            $_word_arg,
                            $_yomi_arg,
                        );
                        $id    = (int)$wpdb->get_var($query);
                        if (!$id) {
                            throw new Exception("Failed to find word: {$_word_arg}, LINE:" . __LINE__);
                        }
                        $word['mapping'][] = $id;

                        // 중복 의미 검사
                        if ($correctionRows[$idx][10]) {
                            $query = $wpdb->prepare(
                                "SELECT id FROM `$wordsTable` WHERE word=%s AND yomikata=%s",
                                $_word_arg,
                                $correctionRows[$idx][10],
                            );
                            $id    = (int)$wpdb->get_var($query);
                            if (!$id) {
                                throw new Exception("Failed to find word: {$_word_arg}, LINE:" . __LINE__);
                            }
                            $word['mapping'][] = $id;
                        }
                    } else {
                        $_word_arg = Utils::normalize($row[1]);
                        $_yomi_arg = $row[2];

                        $query = $wpdb->prepare(
                            "SELECT id FROM `$wordsTable` WHERE word=%s AND yomikata=%s",
                            $_word_arg,
                            $_yomi_arg,
                        );
                        $id    = (int)$wpdb->get_var($query);
                        if (!$id) {
                            throw new Exception("Failed to find word: {$_word_arg}, LINE:" . __LINE__);
                        }
                        $word['mapping'][] = $id;
                    }
                    $kasumiMap['words'][] = $word;
                }
            } // end foreach ($rows as $row)
        } // end foreach ($kasumiRows as $jlpt => $rows)

        // 카스미 맵이 구해졌다. 우선 표준출력.
        echo json_encode($kasumiMap, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        echo PHP_EOL;
    }

    /**
     * @throws Exception
     */
    public function calcCharWordRels(): void
    {
        global $wpdb;

        $charsTable = HimeTables::getTableChars();
        $wordsTable = HimeTables::getTableWords();
        $relsTable  = HimeTables::getTableCharWordRels();

        // 단어에 사용된 모든 한자 구하기
        $chars   = [];
        $results = $wpdb->get_results("SELECT id, word FROM `$wordsTable`");
        foreach ($results as $row) {
            $word      = $row->word;
            $wordChars = mb_str_split($word, 1, 'UTF-8');

            foreach ($wordChars as $c) {
                if (Utils::isKanji($c) && !isset($chars[$c])) {
                    $chars[$c] = true;
                }
            }
        }

        if (!$chars) {
            throw new Exception('No kanji found.');
        }

        // 한자 룩업 테이블
        $kanjiLookup = [];
        $holder      = implode(',', array_fill(0, count($chars), '%s'));
        $mapResult   = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT kanji, id FROM `$charsTable` WHERE kanji IN ($holder)",
                array_keys($chars),
            ),
        );
        foreach ($mapResult as $row) {
            $kanji               = $row->kanji;
            $id                  = $row->id;
            $kanjiLookup[$kanji] = $id;
        }

        // 다시 단어의 글자를 조회하며 관계 조사
        $rels = [];
        foreach ($results as $result) {
            $wordId = $result->id;
            $chars  = mb_str_split($result->word, 1, 'UTF-8');
            foreach ($chars as $pos => $char) {
                if (isset($kanjiLookup[$char])) {
                    $charId = $kanjiLookup[$char];
                    $rels[] = $wpdb->prepare('(%d,%d,%d)', $charId, $wordId, $pos);
                }
            }
        }

        if (!$rels) {
            throw new Exception('No rels found.');
        }

        $wpdb->query("INSERT IGNORE INTO `$relsTable` (char_id, word_id, pos) VALUES " . implode(',', $rels));
        if ($wpdb->last_error) {
            throw new Exception($wpdb->last_error);
        }
    }
}
