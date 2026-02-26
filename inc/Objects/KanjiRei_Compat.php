<?php

namespace HimeNihongo\KanjiPlugin\Objects;

class KanjiRei_Compat {
	public function __construct(
		/** @var string 단어 */
		public string $word,

		/** @var string 히라가나 읽는 법 */
		public string $yomikata,

		/** @var string 의미 */
		public string $meaning,
	) {
	}
}
