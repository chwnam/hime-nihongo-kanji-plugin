<?php

namespace HimeNihongo\KanjiPlugin\Supports;

use Bojaghi\Contract\Support;

class ToolMenuSupport implements Support
{
    /**
     * @return void
     *
     * @used-by inc/settings/admin-menus.php
     */
    public function outputAdminScreen(): void
    {
        echo hnkp_template('admin/tools');
    }

    /**
     * @return never
     */
    public function importCSV(): never
    {
        global $wpdb;

        $tmp  = $_FILES['csv']['tmp_name'];
        $name = $_FILES['csv']['name'];
        $fp   = fopen($tmp, 'r');
        $rows = [];
        while (($row = fgetcsv($fp, 1000, ',', '"', '\\')) !== false) {
            $rows[] = $row;
        }
        fclose($fp);

        if (!preg_match('/^n(\d)\.csv$/i', $name, $matches)) {
            wp_die('invalid file name!');
        }
        $level = (int)$matches[1];

        $lastIndex = -1;
        $charId    = 0;
        $wordOrder = 0;
        $dupChars  = [];
        $dupWords  = [];

        foreach ($rows as $row) {
            $row   = array_filter($row);
            $index = (int)$row[0];

            if ($index > $lastIndex) {
                $lastIndex = $index;
                $wordOrder = 1;

                // 새 한자 - 한자 입력
                $kanji   = $row[1];
                $meaning = $row[2];
                $onYomi  = $row[3];
                $kunYomi = '-' == $row[4] ? '' : $row[4];
                $query   = $wpdb->prepare("SELECT id FROM {$wpdb->prefix}hnkp_chars WHERE kanji=%s", $kanji);
                $result  = (int)$wpdb->get_var($query);
                if (!$result) {
                    $exp = explode(',', $meaning, 2);
                    if (!$exp) {
                        wp_die('Wrong meaning: ' . $meaning);
                    }
                    $arr = explode(' ', $exp[0], 2);
                    if (!$arr) {
                        wp_die('Wrong meaning: ' . $meaning);
                    }
                    $kunKo   = trim($arr[0]);
                    $onKo    = trim($arr[1]);
                    $koExtra = trim($exp[1] ?? '');
                    $query   = $wpdb->prepare(
                        "INSERT INTO {$wpdb->prefix}hnkp_chars" .
                        " (kanji, kun_yomi, on_yomi, kun_ko, on_ko, ko_extra, level) VALUE " .
                        " ('%s', '%s', '%s', '%s', '%s', '%s', '%d')",
                        $kanji,
                        str_replace('、', ', ', $kunYomi),
                        str_replace('、', ', ', $onYomi),
                        $kunKo,
                        $onKo,
                        $koExtra,
                        $level,
                    );
                    $wpdb->query($query);
                    if ($wpdb->last_error) {
                        wp_die('Error: ' . $wpdb->last_error);
                    }
                    $charId = $wpdb->insert_id;
                } else {
                    $charId     = $result;
                    $dupChars[] = $kanji;
                }
            } elseif ($index === $lastIndex) {
                // 한자의 용례
                $word    = $row[1];
                $yomi    = $row[2];
                $meaning = $row[3];
                $query   = $wpdb->prepare("SELECT id FROM {$wpdb->prefix}hnkp_words WHERE word=%s AND yomi=%s", $word, $yomi);
                $result  = (int)$wpdb->get_var($query);
                if (!$result) {
                    $query = $wpdb->prepare(
                        "INSERT INTO {$wpdb->prefix}hnkp_words (word, yomi, meaning) VALUE ('%s', '%s', '%s')",
                        $word,
                        $yomi,
                        $meaning,
                    );
                    $wpdb->query($query);
                    if ($wpdb->last_error) {
                        wp_die('Error: ' . $wpdb->last_error);
                    }
                    $wordId = $wpdb->insert_id;
                } else {
                    $wordId = $result;
                }

                // 매핑
                if ($charId > 0 && $wordId > 0) {
                    $query  = $wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}hnkp_char_word WHERE char_id=%d AND word_id=%d LIMIT 0, 1",
                        $charId,
                        $wordId,
                    );
                    $result = $wpdb->get_var($query);
                    if (is_null($result)) {
                        wp_die('Error: ' . $wpdb->last_error);
                    }
                    if (0 === (int)$result) {
                        $query = $wpdb->prepare(
                            "INSERT INTO {$wpdb->prefix}hnkp_char_word (char_id, word_id, word_order) VALUE (%d, %d, %d)",
                            $charId,
                            $wordId,
                            $wordOrder,
                        );
                        $wpdb->query($query);
                        if ($wpdb->last_error) {
                            wp_die('Error: ' . $wpdb->last_error);
                        }
                        $wordOrder += 1;
                    } else {
                        $dupWords[] = $word;
                    }
                }
            }
        }

        if ($dupChars) {
            error_log("$name has duplicated characters:\n" . implode("\n", $dupChars));
        }
        if ($dupWords) {
            error_log("$name has duplicated words:\n" . implode("\n", $dupWords));
        }

        wp_redirect(wp_get_referer());
        exit;
    }
}
