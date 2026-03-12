<?php

namespace HimeNihongo\KanjiPlugin\Supports\DB;

use Bojaghi\Contract\Support;
use Exception;

class KasumiImportSupport implements Support
{
    /**
     * @throws Exception
     */
    public function importCsv(string $path): void
    {
        global $wpdb;

        $tableChars = KasumiTables::getTableChars();
        $tableWords = KasumiTables::getTableWords();

        if (!file_exists($path) || !is_readable($path)) {
            throw new Exception("$path 경로를 찾을 수 없음.");
        }

        if (!preg_match('/^n(\d)\.csv$/i', basename($path), $matches)) {
            throw new Exception('급수 정보는 파일 이름에서 추출합니다. 파일 이름 형식을 맞춰 주세요.');
        }

        // CSV 전체 로드
        $fp   = fopen($path, 'r');
        $rows = [];
        while (($row = fgetcsv($fp, 1000, ',', '"', '\\')) !== false) {
            $rows[] = $row;
        }
        fclose($fp);


        $jlpt      = (int)$matches[1]; // JLPT level
        $lastEntry = -1;

        // 카스미 테이블은 중복이나 잘못된 데이터도 그냥 허용합니다.
        foreach ($rows as $row) {
            $row   = array_filter($row);
            $entry = (int)$row[0];

            if ($entry > $lastEntry) {
                // 새 한자 - 한자 입력
                $lastEntry = $entry;

                $kanji   = $row[1];
                $meaning = $row[2];
                $onYomi  = $row[3];
                $kunYomi = '-' == $row[4] ? '' : $row[4];

                $exp = explode(',', $meaning, 2);
                if (!$exp) {
                    throw new Exception("한자 {$kanji}의 처리하기 어려웃 뜻: $meaning");
                }

                $arr = explode(' ', $exp[0], 2);
                if (!$arr) {
                    throw new Exception("한자 {$kanji}의 처리하기 어려웃 뜻: $meaning");
                }

                $kunKo   = trim($arr[0]);
                $onKo    = trim($arr[1]);
                $koExtra = trim($exp[1] ?? '');

                $query = $wpdb->prepare(
                    "INSERT IGNORE INTO $tableChars" .
                    " (entry, kanji, kun_yomi, on_yomi, kun_ko, on_ko, ko_extra, jlpt) VALUE " .
                    " (%d, '%s', '%s', '%s', '%s', '%s', '%s', %d)",
                    $entry,
                    $kanji,
                    str_replace('、', ',', $kunYomi),
                    str_replace('、', ',', $onYomi),
                    $kunKo,
                    $onKo,
                    $koExtra,
                    $jlpt,
                );
                $wpdb->query($query);

                if ($wpdb->last_error) {
                    throw new Exception('에러: ' . $wpdb->last_error);
                }
            } elseif ($entry === $lastEntry) {
                // 한자의 용례
                $word    = $row[1];
                $yomi    = $row[2];
                $meaning = $row[3];

                $query = $wpdb->prepare(
                    "INSERT IGNORE INTO $tableWords " .
                    "(jlpt, entry, word, yomikata, meaning) VALUE " .
                    "(%d, %d, '%s', '%s', '%s')",
                    $jlpt,
                    $entry,
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
}
