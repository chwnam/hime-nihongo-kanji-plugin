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
    //
    // 한자 테이블
    [
        'table_name' => "{$wpdb->prefix}hnkp_chars",
        'field'      => [
            'id int(10) unsigned NOT NULL AUTO_INCREMENT',
            'kanji char(1) NOT NULL COMMENT \'한자\'',
            'kun_yomi varchar(20) NOT NULL COMMENT \'훈독 - 콤마(,) 여러 개 구분\'',
            'on_yomi varchar(20) NOT NULL COMMENT \'음독 - 콤마(,) 여러 개 구분\'',
            'kun_ko varchar(10) NOT NULL COMMENT \'한국어 훈 (대표)\'',
            'on_ko varchar(10) NOT NULL COMMENT \'한국어 음 (대표)\'',
            'ko_extra varchar(25) NOT NULL COMMENT \'한국어 음, 훈이 2개 이상인 경우 사용\'',
            'level tinyint(1) unsigned NOT NULL COMMENT \'N1~5 급수\'',
        ],
        'index'      => [
            'PRIMARY KEY  (id)',                      // Two spaces after 'PRIMARY KEY'. 'PRIMARY KEY' 다음 두 개의 공백.
            'UNIQUE KEY uni_kanji (kanji)',   // Just as-is, from here.          여기부터는 그대로.
            'KEY key_on_ko (on_ko)',
            'KEY key_level (level)',
        ],
        'engine'     => 'InnoDB', // Optional, defaults to 'InnoDB'.
        'charset'    => '',       // Optional, leave blank to use the default value of $wpdb.
        'collate'    => '',       // Optional, leave blank to use the default value of $wpdb.
    ],
    //
    // 단어 테이블
    [
        'table_name' => "{$wpdb->prefix}hnkp_words",
        'field'      => [
            'id int(10) unsigned NOT NULL AUTO_INCREMENT',
            'word varchar(20) NOT NULL COMMENT \'단어\'',
            'yomi varchar(20) NOT NULL COMMENT \'요미카타\'',
            'meaning varchar(50) NOT NULL COMMENT \'한국어 뜻\'',
        ],
        'index'      => [
            'PRIMARY KEY  (id)',
            'UNIQUE KEY uni_word_yomi (word, yomi)',
        ],
        'engine'     => 'InnoDB',
        'charset'    => '',
        'collate'    => '',
    ],
    //
    // 한자 - 단어 매핑
    [
        'table_name' => "{$wpdb->prefix}hnkp_char_word",
        'field'      => [
            'char_id int(10) unsigned NOT NULL',
            'word_id int(10) unsigned NOT NULL',
            'word_order tinyint(1) NOT NULL DEFAULT 0 COMMENT \'같은 char_id 일 때 word의 수록 순서\'',
        ],
        'index'      => [
            'UNIQUE KEY uni_char_word (char_id, word_id)',
        ],
        'engine'     => 'InnoDB',
        'charset'    => '',
        'collate'    => '',
    ],
];
