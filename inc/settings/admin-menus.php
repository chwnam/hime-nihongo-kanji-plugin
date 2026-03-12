<?php

if (!defined('ABSPATH')) {
    exit;
}

return [
    'add_menu'       => [
        //
        [
            'page_title' => '히메 일본어 한자 편집 화면',
            'menu_title' => 'ヒメ 日本語',
            'capability' => 'hnkp_editor',
            'menu_slug'  => 'hnkp-kanji',
            'callback'   => '__return_empty_string',
            'icon_url'   => plugins_url('inc/assets/img/menu-icon.png', HNKP_MAIN),
        ],
    ],
    'add_submenu'    => [
        //
        [
            'parent_slug' => 'tools.php',
            'page_title'  => '히메 일본어 데이터 도구',
            'menu_title'  => '히메 일본어 데이터 도구',
            'capability'  => 'hnkp_editor',
            'menu_slug'   => 'hnkp-tools',
            'callback'    => '__return_empty_string',
        ],
    ],
    'remove_menu'    => [],
    'remove_submenu' => [],
];