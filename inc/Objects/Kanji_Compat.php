<?php

namespace HimeNihongo\KanjiPlugin\Objects;

use function HemeNihongo\KanjiPlugin\hira_to_kata;

class Kanji_Compat
{
    public function __construct(
        /** @var string 한자 */
        public string $kanji,

        /** @var string 한국어 의미 */
        public string $meaning,

        /** @var string 음독 */
        public string $on_yomi,

        /** @var string 훈독 */
        public string $kun_yomi,

        /** @var KanjiRei_Compat[] 활용 예 */
        public array  $rei_items = [],

        public bool   $page_break = false,
    )
    {
        // 음독 훈독의 콤마를 일본식으로 변경
        $this->on_yomi  = preg_replace('/,\s?/', '、', $this->on_yomi);
        $this->kun_yomi = preg_replace('/,\s?/', '、', $this->kun_yomi);

        // 음독은 강제로 히라가나에서 카타카나로 변경
        $this->on_yomi = hira_to_kata($this->on_yomi);
    }
}
