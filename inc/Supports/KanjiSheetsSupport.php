<?php

namespace HimeNihongo\KanjiPlugin\Supports;

use Bojaghi\Contract\Support;
use HimeNihongo\KanjiPlugin\Objects\Kanji_Compat;
use HimeNihongo\KanjiPlugin\Objects\KanjiRei_Compat;

class KanjiSheetsSupport implements Support
{
    public function __construct()
    {
        add_action('bojaghi/clean-pages/head/end', [$this, 'header']);
        add_filter('bojaghi/clean-pages/body/class', [$this, 'bodyClass'], 10, 2);
    }

    public function header(string $name): void
    {
        if ('kanji' !== $name) {
            return;
        }

        echo hnkp_template(
            'kanji-header',
            [
                'title' => '한자 연습',
            ],
        );
    }

    public function bodyClass(string $class, string $name): string
    {
        if ('kanji' === $name) {
            $class .= ' A4 font-noto-ko';
        }

        return $class;
    }

    public function body(string $name): void
    {
        global $wpdb;

        if ('kanji' !== $name) {
            return;
        }

        $items  = [];
        $groups = [];

        $level = max(1, min(5, absint($_GET['level'] ?? '5')));
        $query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}hnkp_chars WHERE level=%d ORDER BY on_ko, kanji", $level);
        $chars = $wpdb->get_results($query);

        $charIds = wp_list_pluck($chars, 'id');
        if ($charIds) {
            $plh   = implode(',', array_pad([], count($charIds), '%d'));
            $query = $wpdb->prepare(
                "SELECT w.*, cw.char_id FROM {$wpdb->prefix}hnkp_char_word AS cw" .
                " INNER JOIN {$wpdb->prefix}hnkp_words AS w ON w.id=cw.word_id" .
                " WHERE cw.char_id IN ($plh) ORDER BY cw.char_id, cw.word_order, w.word",
                $charIds,
            );
            $words = $wpdb->get_results($query);
            foreach ($words as $w) {
                $groups[$w->char_id][] = new KanjiRei_Compat(
                    word: $w->word,
                    yomikata: $w->yomi,
                    meaning: $w->meaning,
                );
            }
        }

        foreach ($chars as $char) {
            $items[] = new Kanji_Compat(
                kanji: $char->kanji,
                meaning: sprintf('%s %s%s', $char->kun_ko, $char->on_ko, $char->ko_extra ? ", $char->ko_extra" : ''),
                on_yomi: $char->on_yomi,
                kun_yomi: $char->kun_yomi,
                rei_items: $groups[$char->id] ?? [],
                page_break: false, // TODO: import page break data
            );
        }

        // Pagination
        $pagedItems   = [];
        $onePage      = [];
        $itemsPerPage = 6;
        $count        = 0;

        foreach ($items as $item) {
            if ($item instanceof Kanji_Compat) {
                $onePage[] = $item;

                if ($itemsPerPage === ++$count || $item->page_break) {
                    $pagedItems[] = $onePage;
                    $count        = 0;
                    $onePage      = [];
                }
            }
        }

        if ($onePage) {
            $pagedItems[] = $onePage;
        }

        echo hnkp_template(
            'kanji-body',
            [
                'level_title' => sprintf('JLPT %d', $level),
                'items'       => $pagedItems,
            ],
        );
    }
}
