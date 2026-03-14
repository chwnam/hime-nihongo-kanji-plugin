<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * How to create table
 *
 * This method uses dbDelta() function.
 *
 * Please keep in mind that dbDelta() is rather demanding:
 * - You must put each field on its own line in your SQL statement.
 * - You must have two spaces between the words PRIMARY KEY and the definition of your primary key.
 * - You must use the key word KEY rather than its synonym INDEX and you must include at least one KEY.
 * - KEY must be followed by a SINGLE SPACE then the key name then a space then open parenthesis with the field name
 *   then a closed parenthesis.
 * - You must not use any apostrophes or backticks around field names.
 * - Field types must be all lowercase.
 * - SQL keywords, like CREATE TABLE and UPDATE, must be uppercase.
 * - You must specify the length of all fields that accept a length parameter. int(11), for example.
 * - Use 'UNIQUE KEY', not just 'UNIQUE'. Likewise, use 'FULLTEXT KEY', and 'SPATIAL KEY'.
 *
 * @return array
 * @link   https://developer.wordpress.org/plugins/creating-tables-with-plugins/
 */

global $wpdb;

return [
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // 사전 중간 테이블 ----
    //
    // 한자 테이블
    [
        'table_name'    => "{$wpdb->prefix}hnkp_dic_kanji",
        'table_comment' => '중간 단계 사전의 일본어 한자 목록. Kanjidic2.xml 파싱 결과.',
        'field'         => [
            "id int(10) unsigned NOT NULL AUTO_INCREMENT",
            "kanji char(1) NOT NULL COMMENT '한자.'",
            "kun_yomi varchar(100) NOT NULL COMMENT '訓読み, 훈독.'",
            "on_yomi varchar(50) NOT NULL COMMENT '音読み, 음독.'",
            "radical tinyint(10) unsigned NOT NULL COMMENT '강희자전 부수 코드, 공식: U+2F00 + (부수코드 - 1).'",
            "stroke_count tinyint(10) unsigned NOT NULL COMMENT '획수.'",
            "freq smallint(10) unsigned NOT NULL DEFAULT '0' COMMENT '현대 일본어에서 자주 사용되는 한자. 빈도 숫자가 낮을수록 높은 빈도. 1~2500 사이 정수. 0은 순위 밖.'",
        ],
        'index'         => [
            'PRIMARY KEY  (id)',
            'UNIQUE KEY uni_kanji (kanji)',
        ],
        'engine'        => 'InnoDB',
        'charset'       => '',
        'collate'       => '',
    ],

    // 단어 테이블
    [
        'table_name'    => "{$wpdb->prefix}hnkp_dic_tango",
        'table_comment' => '중간 단계 사전의 단어 목록. JMDict_e.xml 파싱 결과.',
        'field'         => [
            "id int(10) unsigned NOT NULL",
            "tango varchar(50) NOT NULL COMMENT '単語, 단어.'",
            "tango_info text NOT NULL COMMENT 'ke_inf 엘리먼트 정보를 콤마(,)로 나열'",
            "yomikata varchar(255) NOT NULL COMMENT '読み方, 읽기 표기. \"히라가나(중요도), 히라가나 (중요도), ...\" 식으로 나열한다. priority가 없다면 괄호는 생략 가능.'",
            "sense text NOT NULL COMMENT 'sense 엘리먼트의 field, pos, misc, gloss 정보를 JSON으로 기록'",
        ],
        'index'         => [
            'PRIMARY KEY  (id)',
            'KEY idx_tango (tango)',
        ],
        'engine'        => 'InnoDB',
        'charset'       => '',
        'collate'       => '',
    ],

    // 한국어 한자 정보 테이블
    [
        'table_name'    => "{$wpdb->prefix}hnkp_dic_hanja",
        'table_comment' => '중간 단계 사전의 한국어 한자 정보. 한국어문학 등급별 선정한자 CSV hanja.csv 입력 결과.',
        'field'         => [
            "id int(10) unsigned NOT NULL AUTO_INCREMENT",
            "hanja char(1) NOT NULL",
            "main_sound char(1) NOT NULL",
            "meaning varchar(255) NOT NULL COMMENT '의미 JSON과 유사한 형식으로 기록되어 있어, 한자의 여러 뜻이나 발음을 기록한다.'",
            "level enum('8a', '7a', '7b', '6a', '6b', '5a', '5b', '4a', '4b', '3a', '3b', '2a', '1a', 'sa', 'sb') NOT NULL COMMENT 'a는 그냥 \\'급\\' b는 \\'급Ⅱ\\', s는 \\'특\\'을 말함. 8, 2, 1급을 제외하고는 모두 b가 있음.'",
        ],
        'index'         => [
            'PRIMARY KEY  (id)',
            'UNIQUE KEY uni_hanja (hanja)',
        ],
        'engine'        => 'InnoDB',
        'charset'       => '',
        'collate'       => '',
    ],

    // 구자-신자 매핑 테이블
    [
        'table_name'    => "{$wpdb->prefix}hnkp_dic_sinji",
        'table_comment' => '중간 단계 사전의 신체자 목록.',
        'field'         => [
            "kanji char(1) NOT NULL COMMENT '일본어 신자체만을 기록. 구자를 신자로 매핑할 때, 신자가 여기에 없으면 중국 간체자.'",
        ],
        'index'         => [
            "UNIQUE KEY uni_kanji (kanji)",
        ],
        'engine'        => 'InnoDB',
        'charset'       => '',
        'collate'       => '',
    ],
    [
        'table_name'    => "{$wpdb->prefix}hnkp_dic_map",
        'table_comment' => '중간 단계 사전의 신체자-구체자 매핑 테이블.',
        'field'         => [
            "k_in char(1) NOT NULL",
            "k_out char(1) NOT NULL",
            "type enum('t', 's', 'z') NOT NULL COMMENT 't는 kTraditionalVariant (신자를 구자로 매핑), s는 kSimplifiedVariant (구자를 신자로 매핑), z는 자형만 약간 다른 동일한 글자(Typeface/Glyph variant).'",
        ],
        'index'         => [
            'KEY idx_in_type (k_in, type)',
            'KEY idx_out_type (k_out, type)',
            'UNIQUE KEY uni_map (k_in, k_out, type)',
        ],
        'engine'        => 'InnoDB',
        'charset'       => '',
        'collate'       => '',
    ],

    [
        'table_name'    => "{$wpdb->prefix}hnkp_dic_jlpt",
        'table_comment' => '중간 단계 사전의 JLPT 급수별 한자 목록',
        'field'         => [
            "kanji char(1) NOT NULL",
            "level tinyint(1) unsigned NOT NULL",
        ],
        'index'         => [
            'PRIMARY KEY  (kanji)',
        ],
        'engine'        => 'InnoDB',
        'charset'       => '',
        'collate'       => '',
    ],

    // 사전 중간 테이블 끝 ----
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // 히메 버전 테이블 ----
    //
    // 한자 테이블
    [
        'table_name'    => "{$wpdb->prefix}hnkp_hime_chars",
        'table_comment' => '한자 목록 테이블',
        'field'         => [
            "id int(10) unsigned NOT NULL AUTO_INCREMENT",
            "kanji char(1) NOT NULL COMMENT '한자.'",
            "kun_yomi varchar(100) NOT NULL COMMENT '훈독, 도텐(、)으로 구분.'",
            "on_yomi varchar(50) NOT NULL COMMENT '음독, 도텐(,)으로 구분.'",
            "radical tinyint(10) unsigned NOT NULL COMMENT '강희자전 부수 코드. 공식: U+2F00 + (부수코드 - 1).'",
            "stroke_count tinyint(10) unsigned NOT NULL COMMENT '신자체 기준 총횟수.'",
            "freq smallint(10) unsigned NOT NULL DEFAULT 0  COMMENT'1~2500사이의 빈도수 랭킹, 0은 순위 외.'",
            "jlpt tinyint(10) unsigned NOT NULL DEFAULT 0 COMMENT 'JLPT 등급, 등급오는 0.'",
            "ko_hanja char(1) NULL DEFAULT NULL COMMENT '한국에서 사용하는 구체자.'",
            "ko_on char(1) NULL DEFAULT NULL NULL COMMENT '한국어 대표 음.'",
            "ko_meaning varchar(100) NULL DEFAULT NULL COMMENT '한국어 한자 훈과 음.'",
            "ko_level enum('8a', '7a', '7b', '6a', '6b', '5a', '5b', '4a', '4b', '3a', '3b', '2a', '1a', 'sa', 'sb') NULL DEFAULT NULL COMMENT 'a는 그냥 \\'급\\' b는 \\'급Ⅱ\\', s는 \\'특\\'을 말함. 8, 2, 1급을 제외하고는 모두 b가 있음.'",
        ],
        'index'         => [
            'PRIMARY KEY  (id)',            // Two spaces after 'PRIMARY KEY'. 'PRIMARY KEY' 다음 두 개의 공백.
            'UNIQUE KEY uni_kanji (kanji)', // Just as-is, from here.          여기부터는 그대로.
            'KEY idx_freq (freq)',
            'KEY idx_jlpt_ko_on (jlpt, ko_on)',
            'KEY idx_ko_on (ko_on)',
        ],
        'engine'        => 'InnoDB', // Optional, defaults to 'InnoDB'.
        'charset'       => '',       // Optional, leave blank to use the default value of $wpdb.
        'collate'       => '',       // Optional, leave blank to use the default value of $wpdb.
    ],

    // 단어 테이블
    [
        'table_name'    => "{$wpdb->prefix}hnkp_hime_words",
        'table_comment' => '단어 목록 테이블',
        'field'         => [
            "id int(10) unsigned NOT NULL AUTO_INCREMENT",
            "word varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '단어.'",
            "word_len tinyint(10) unsigned NOT NULL COMMENT '단어 길이.'",
            "hi_jlpt tinyint(10) unsigned NOT NULL DEFAULT '0' COMMENT '단어 내 한자 중 가장 높은 JLPT 급수. 0은 등급 외.'",
            "hi_jlpt_pos tinyint(10) NOT NULL DEFAULT '-1' COMMENT '해당 한자가 나온 위치. 음수는 유효한 갑 없음.'",
            "lo_jlpt tinyint(10) unsigned NOT NULL DEFAULT '0' COMMENT '단어 내 한자 중 가장 낮은 JLPT 급수. 0은 등급 외.'",
            "lo_jlpt_pos tinyint(10) NOT NULL DEFAULT '-1' COMMENT '해당 한자가 나온 위치. 음수는 유효한 갑 없음.'",
            "hi_freq smallint(10) unsigned NOT NULL DEFAULT '0' COMMENT '단어 내 한자의 빈도수 중 가장 높은 값.'",
            "hi_freq_pos tinyint(10) NOT NULL DEFAULT '-1' COMMENT '해당 한자가 나온 위치. 음수는 유효한 값 없음.'",
            "lo_freq smallint(10) unsigned NOT NULL DEFAULT '0' COMMENT '단어 내 한자의 빈도수 중 가장 높은 값.'",
            "lo_freq_pos tinyint(10) NOT NULL DEFAULT '-1' COMMENT '해당 한자가 나온 위치. 음수는 유효한 값 없음.'",
        ],
        'index'         => [
            'PRIMARY KEY  (id)',
            'UNIQUE KEY uni_word (word)',
            'KEY idx_word_len (word_len)',
        ],
        'engine'        => 'InnoDB',
        'charset'       => '',
        'collate'       => '',
    ],

    // 단어 상세 테이블
    // 일본어는 같은 한자라도 읽는 방법이 다를 수 있다
    // priority 를 생각하지 않고도 같은 단어의 읽기라면 id 가 낮은 쪽이 우세하다.
    // priority: news1/2, ichi1/2, spec1/2, gai1/2, nfxx
    // 상세한 사항은 JMDict 참조
    [
        'table_name'    => "{$wpdb->prefix}hnkp_hime_word_details",
        'table_comment' => '단어의 상세 정보 테이블.',
        'field'         => [
            "id int(10) unsigned NOT NULL AUTO_INCREMENT",
            "word_id int(10) unsigned NOT NULL COMMENT '단어 테이블의 ID.'",
            "ent_seq int(10) unsigned NOT NULL COMMENT 'JMDict 원래 ent_seq 값.'",
            "yomikata varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '단어 읽는 법.'",
            "priority varchar(30) NOT NULL COMMENT '콤마(,)로 구분된 우선순위 기준.'",
            "info varchar(20) NULL DEFAULT NULL COMMENT '콤마(,)로 구분된 사전에서 추출한 단어 정보 엔티티.'",
            "senses json NULL DEFAULT NULL COMMENT '사전 sense 엘리먼트의 field, pos, misc, gloss 정보를 JSON으로 기록'",
            "parent_id int(10) unsigned NOT NULL DEFAULT 0 COMMENT '상위 단어 정보 ID. 같은 의미인데 읽기가 여럿인 경우에 유효. 해당 ID의 info, senses, meaning 필드를 공유.'",
            "meaning varchar(100) NOT NULL DEFAULT '' COMMENT '단어의 뜻 - 유일하게 사용자가 자유롭게 편집할 수 있는 필드.'",
        ],
        'index'         => [
            'PRIMARY KEY  (id)',
            'UNIQUE KEY uni_word_ent_yomi (word_id, ent_seq, yomikata)',
        ],
        'engine'        => 'InnoDB',
        'charset'       => '',
        'collate'       => '',
    ],

    // 한자 - 단어 매핑
    [
        'table_name'    => "{$wpdb->prefix}hnkp_hime_char_word_rels",
        'table_comment' => '한자-단어 매핑 테이블.',
        'field'         => [
            "char_id int(10) unsigned NOT NULL COMMENT '한자 테이블의 ID.'",
            "word_id int(10) unsigned NOT NULL COMMENT '단어 테이블의 ID.'",
            "char_pos tinyint(1) unsigned NOT NULL COMMENT '단어에서 해당 글자가 나온 위치, 0부터 시작.'",
        ],
        'index'         => [
            'PRIMARY KEY  (word_id, char_pos)',
            'KEY idx_char_word (char_id, word_id)',
        ],
        'engine'        => 'InnoDB',
        'charset'       => '',
        'collate'       => '',
    ],

    // 히베 버전 테이블 끝 ----
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // 카스미 CSV 의미 마이그레이션 임시 테이블 ----
    //
    // 단어 테이블
    [
        'table_name'    => "{$wpdb->prefix}hnkp_kasumi_words",
        'table_comment' => '카스미 한자 교실 N3, N4, N5 한자 예제 단어 임시 테이블. N5부터 N4, N3 순으로 입력. 부정화한 자료 포함 가능성 있음',
        'field'         => [
            "id int(10) unsigned NOT NULL AUTO_INCREMENT",
            "jlpt tinyint(1) unsigned NOT NULL COMMENT '단어 JLPT 레벨'",
            "entry int(10) unsigned NOT NULL COMMENT '해당 레벨에서 등록된 엔트리 번호'",
            "word varchar(50) NOT NULL COMMENT '단어'",
            "yomikata varchar(100) NOT NULL COMMENT '단어 읽기'",
            "meaning varchar(100) NOT NULL COMMENT '단어 의미'",
        ],
        'index'         => [
            'PRIMARY KEY  (id)',
            'KEY idx_jlpt_entry (jlpt, entry)',
            'UNIQUE KEy uni_word (word, yomikata)',
        ],
        'engine'        => 'InnoDB',
        'charset'       => '',
        'collate'       => '',
    ],

    // 카스미 CSV 의미 마이그레이션 임시 테이블 끝 ----
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

];
