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
    // 카스미 버전 아카이브 테이블    //
    ...[
        [
            'table_name'    => "{$wpdb->prefix}hnkp_kasumi_chars",
            'table_comment' =>
                '카스미 한자 교실 N3, N4, N5 한자 테이블. ' .
                'N5부터 N4, N3 순으로 입력. 아카이빙 목적이며, 부정화한 자료 포함 가능성 있음',
            'field'         => [
                "id int(10) unsigned NOT NULL AUTO_INCREMENT",
                "entry int(10) unsigned NOT NULL COMMENT '해당 레벨에서 등록된 엔트리 번호'",
                "kanji char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '한자'",
                "kun_yomi varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '훈독 - 콤마(,) 여러 개 구분'",
                "on_yomi varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '음독 - 콤마(,) 여러 개 구분'",
                "kun_ko varchar(10) NOT NULL COMMENT '한국어 훈 (대표)'",
                "on_ko varchar(10) NOT NULL COMMENT '한국어 음 (대표)'",
                "ko_extra varchar(25) NOT NULL COMMENT '한국어 음, 훈이 2개 이상인 경우 사용'",
                "jlpt tinyint(1) unsigned NOT NULL COMMENT '단어 JLPT 레벨'",
            ],
            'index'         => [
                'PRIMARY KEY  (id)',
                'UNIQUE KEY uni_row (jlpt, entry, kanji)', // 멱등성을 보정하기 위한 최소 장치
                'KEY idx_kanji (kanji)',
                'KEY idx_jlpt_entry (jlpt, entry)',
            ],
            'engine'        => 'InnoDB',
            'charset'       => '',
            'collate'       => '',
        ],
        [
            'table_name'    => "{$wpdb->prefix}hnkp_kasumi_words",
            'table_comment' =>
                '카스미 한자 교실 N3, N4, N5 한자 예제 단어 임시 테이블. ' .
                'N5부터 N4, N3 순으로 입력. 아카이빙 목적이며, 부정화한 자료 포함 가능성 있음',
            'field'         => [
                "id int(10) unsigned NOT NULL AUTO_INCREMENT",
                "jlpt tinyint(1) unsigned NOT NULL COMMENT '단어 JLPT 레벨'",
                "entry int(10) unsigned NOT NULL COMMENT '해당 레벨에서 등록된 엔트리 번호'",
                "word varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '단어'",
                "yomikata varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '단어 읽기'",
                "meaning varchar(100) NOT NULL COMMENT '단어 의미'",
            ],
            'index'         => [
                'PRIMARY KEY  (id)',
                'UNIQUE KEY uni_row (jlpt, entry, word, yomikata)', // 멱등성을 보정하기 위한 최소 장치
                'KEY idx_word (word, yomikata)',
                'KEY idx_jlpt_entry (jlpt, entry)',
            ],
            'engine'        => 'InnoDB',
            'charset'       => '',
            'collate'       => '',
        ],
    ],
    // 카스미 버전 아카이브 테이블 끝 //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // 옥편 중간 자료 테이블    //
    ...[
        [
            'table_name'    => "{$wpdb->prefix}hnkp_mid_kanji",
            'table_comment' => '중간 단계 사전의 일본어 한자 목록. kanjidic2.xml 파싱 결과.',
            'field'         => [
                "id int(10) unsigned NOT NULL AUTO_INCREMENT",
                "kanji char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '한자.'",
                "kun_yomi varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '訓読み, 훈독.'",
                "on_yomi varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '音読み, 음독.'",
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
        [
            'table_name'    => "{$wpdb->prefix}hnkp_mid_hanja",
            'table_comment' => '중간 단계 사전의 한국어 한자 정보. 한국어문학 등급별 선정한자 CSV hanja.csv 입력 결과.',
            'field'         => [
                "id int(10) unsigned NOT NULL AUTO_INCREMENT",
                "hanja char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL",
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
        [
            'table_name'    => "{$wpdb->prefix}hnkp_mid_sinji",
            'table_comment' => '중간 단계 사전의 신자 목록.',
            'field'         => [
                "kanji char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '일본어 신자체만을 기록. 구자를 신자로 매핑할 때, 신자가 여기에 없으면 중국 간체자.'",
            ],
            'index'         => [
                "UNIQUE KEY uni_kanji (kanji)",
            ],
            'engine'        => 'InnoDB',
            'charset'       => '',
            'collate'       => '',
        ],
        [
            'table_name'    => "{$wpdb->prefix}hnkp_mid_map",
            'table_comment' => '중간 단계 사전의 신체자-구체자 매핑 테이블.',
            'field'         => [
                "k_in char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL",
                "k_out char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL",
                "type enum('tv', 'siv', 'sev', 'zv') NOT NULL COMMENT 'tv=kTraditionalVariant/siv=kSimplifiedVariant/sev=kSemanticVariant/zv=kZVariant'",
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
            'table_name'    => "{$wpdb->prefix}hnkp_mid_jlpt",
            'table_comment' => '중간 단계 사전의 JLPT 급수별 한자 목록',
            'field'         => [
                "kanji char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL",
                "level tinyint(1) unsigned NOT NULL",
            ],
            'index'         => [
                'PRIMARY KEY  (kanji)',
            ],
            'engine'        => 'InnoDB',
            'charset'       => '',
            'collate'       => '',
        ],
    ],
    // 옥푠 중간 자료 테이블 끝 //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // JMDict 간이 구현 테이블    //
    ...[
        [
            'table_name'    => "{$wpdb->prefix}hnkp_jmdict_keb",
            'table_comment' => 'JMDict k_ele > keb 엘리먼트, 중복 없는 단어 자체 목록',
            'field'         => [
                "id int(10) unsigned NOT NULL AUTO_INCREMENT",
                "word varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '단어'",
                "word_len tinyint(10) unsigned NOT NULL COMMENT '단어 길이'",
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
        [
            'table_name'    => "{$wpdb->prefix}hnkp_jmdict_k_ele",
            'table_comment' => 'JMDict k_ele 엘리먼트',
            'field'         => [
                "id int(10) unsigned NOT NULL AUTO_INCREMENT",
                "keb_id int(10) unsigned NOT NULL COMMENT 'keb.id'",
                "ke_inf varchar(30) NULL COMMENT '단어 부가 정보 엔티티를 콤마로 합침. 없을 수도 있음'",
                "ke_pri varchar(30) NULL COMMENT '단어 사용 빈도 정보 엔티티를 콤마로 합침. 없을 수도 있음'",
            ],
            'index'         => [
                'PRIMARY KEY  (id)',
                'KEY idx_keb_id (keb_id)',
            ],
            'engine'        => 'InnoDB',
            'charset'       => '',
            'collate'       => '',
        ],
        [
            'table_name'    => "{$wpdb->prefix}hnkp_jmdict_r_ele",
            'table_comment' => 'JMDict r_ele 엘리먼트 단어 읽는 법 목록',
            'field'         => [
                "id int(10) unsigned NOT NULL AUTO_INCREMENT",
                "reb varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '읽는 법'",
                "re_nokanji bool NOT NULL COMMENT '이것이 1이면 이 읽기는 한자의 진짜 읽는 법은 아니며, 외국 지명, 한자 또는 카타카나로 표기할 수 있는 외레어와 같은 단어에 사용된다.'",
                "re_inf varchar(30) NULL COMMENT '발음의 부가 정보 엔티티를 콤마로 합침. 없을 수도 있음'",
                "re_pri varchar(30) NULL COMMENT '이 발음의 빈도 부가 정보 엔티티를 콤마로 합침. 없을 수도 있음'",
            ],
            'index'         => [
                'PRIMARY KEY  (id)',
                'KEY idx_reb (reb)',
            ],
            'engine'        => 'InnoDB',
            'charset'       => '',
            'collate'       => '',
        ],
        [
            'table_name'    => "{$wpdb->prefix}hnkp_jmdict_sense",
            'table_comment' => 'JMDict sense 엘리먼트 정보를 간략화한 테이블',
            'field'         => [
                "id int(10) unsigned NOT NULL AUTO_INCREMENT",
                "gloss text NOT NULL COMMENT '엔터로 구분한 gloss 엘리먼트를 합친 텍스트'",
                "pos varchar(30) NULL COMMENT '품사 정보 엔티티를 콤마로 합침 없을 수도 있음'",
                "field varchar(30) NULL COMMENT '사용 분야 정보 엔티티를 콤마로 합침. 없을 수도 있음'",
                "misc varchar(30) NULL COMMENT '기타 정보 엔티티를 콤마로 합침. 없을 수도 있음'",
            ],
            'index'         => [
                'PRIMARY KEY  (id)',
            ],
            'engine'        => 'InnoDB',
            'charset'       => '',
            'collate'       => '',
        ],
        [
            'table_name'    => "{$wpdb->prefix}hnkp_jmdict_entry",
            'table_comment' => 'JMDict 쓰기, 읽기, 의미 관계를 기록',
            'field'         => [
                "ent_seq int(10) unsigned NOT NULL COMMENT '엔트리 고유 번호'",
                "k_id int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'k_ele.id'",
                "r_id int(10) unsigned NOT NULL COMMENT 'r_ele.id'",
                "s_id int(10) unsigned NULL COMMENT 'sense.id'",
            ],
            'index'         => [
                'UNIQUE KEY uni_entry (ent_seq, k_id, r_id, s_id)',
            ],
            'engine'        => 'InnoDB',
            'charset'       => '',
            'collate'       => '',
        ],
    ],
    // JMDict 간이 구현 테이블 끝 //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // 히메 한자 학습 테이블    //
    ...[
        [
            'table_name'    => "{$wpdb->prefix}hnkp_hime_chars",
            'table_comment' => '한자 테이블, 옥편 중간 자료를 정리한 결과',
            'field'         => [
                "id int(10) unsigned NOT NULL AUTO_INCREMENT",
                "kanji char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '한자.'",
                "kun_yomi varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '훈독, 도텐(、)으로 구분.'",
                "on_yomi varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '음독, 도텐(,)으로 구분.'",
                "radical tinyint(10) unsigned NOT NULL COMMENT '강희자전 부수 코드. 공식: U+2F00 + (부수코드 - 1).'",
                "stroke_count tinyint(10) unsigned NOT NULL COMMENT '신자체 기준 총횟수.'",
                "freq smallint(10) unsigned NOT NULL DEFAULT 0  COMMENT'1~2500사이의 빈도수 랭킹, 0은 순위 외.'",
                "jlpt tinyint(10) unsigned NOT NULL DEFAULT 0 COMMENT 'JLPT 등급, 등급오는 0.'",
                "ko_hanja char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT NULL COMMENT '한국에서 사용하는 구체자.'",
                "ko_on char(1) NULL DEFAULT NULL NULL COMMENT '한국어 대표 음.'",
                "ko_meaning varchar(100) NULL DEFAULT NULL COMMENT '한국어 한자 훈과 음.'",
                "ko_level enum('8a', '7a', '7b', '6a', '6b', '5a', '5b', '4a', '4b', '3a', '3b', '2a', '1a', 'sa', 'sb') NULL DEFAULT NULL COMMENT 'a는 그냥 \\'급\\' b는 \\'급Ⅱ\\', s는 \\'특\\'을 말함. 8, 2, 1급을 제외하고는 모두 b가 있음.'",
            ],
            'index'         => [
                'PRIMARY KEY  (id)',
                'UNIQUE KEY uni_kanji (kanji)',
                'KEY idx_freq (freq)',
                'KEY idx_jlpt_ko_on (jlpt, ko_on)',
                'KEY idx_ko_on (ko_on)',
            ],
            'engine'        => 'InnoDB', // Optional, defaults to 'InnoDB'.
            'charset'       => '',       // Optional, leave blank to use the default value of $wpdb.
            'collate'       => '',       // Optional, leave blank to use the default value of $wpdb.
        ],
        [
            'table_name'    => "{$wpdb->prefix}hnkp_hime_words",
            'table_comment' => '단어 목록 테이블. 한자 연습 예제 단어 목록으로서 심플하게 구성한다.',
            'field'         => [
                "id int(10) unsigned NOT NULL AUTO_INCREMENT",
                "word varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '단어.'",
                "yomikata varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '단어 읽는 법.'",
                "meaning varchar(100) NOT NULL DEFAULT '' COMMENT '단어의 뜻'",
            ],
            'index'         => [
                'PRIMARY KEY  (id)',
                'UNIQUE KEY uni_word (word, yomikata)',
                'KEY idx_yomikata (yomikata)',
            ],
            'engine'        => 'InnoDB',
            'charset'       => '',
            'collate'       => '',
        ],
        [
            'table_name'    => "{$wpdb->prefix}hnkp_hime_char_word_rels",
            'table_comment' => '한자-단어 매핑 테이블.',
            'field'         => [
                "char_id int(10) unsigned NOT NULL COMMENT '한자 테이블의 ID.'",
                "word_id int(10) unsigned NOT NULL COMMENT '단어 테이블의 ID.'",
            ],
            'index'         => [
                'KEY idx_char_word (char_id, word_id)',
            ],
            'engine'        => 'InnoDB',
            'charset'       => '',
            'collate'       => '',
        ],
    ],
    // 히베 헌자 학습 테이블 끝 //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
];
