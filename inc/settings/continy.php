<?php

use Bojaghi\Continy\Continy;
use HimeNihongo\KanjiPlugin\Modules;
use HimeNihongo\KanjiPlugin\Supports;
use function HemeNihongo\KanjiPlugin\addCliCommands;

if (!defined('ABSPATH')) {
    exit;
}

return [
    'main_file' => HNKP_MAIN,    // 플러그인 메인 파일
    'version'   => HNKP_VERSION, // 플러그인의 버전

    /**
     * 훅 선언
     *
     * 키: 훅 이름
     * 값: 콜백 함수에서 허용하는 인자 수, 0 이상의 정수
     */
    'hooks'     => [
        'admin_init'         => 0,
        'admin_menu'         => 0,
        'admin_print_styles' => 0,
        'current_screen'     => 1,
        'init'               => 0,
    ],

    /**
     * 바인딩 선언
     *
     * 키: 별명 (alias)
     * 값: 실제 클래스 (FQCN)
     */
    'bindings'  => [
        'bojaghi/admin-menus'   => Bojaghi\AdminMenus\AdminMenus::class,
        'bojaghi/admin-posts'   => Bojaghi\AdminAjax\AdminPost::class,
        'bojaghi/clean-pages'   => Bojaghi\CleanPages\CleanPages::class,
        'bojaghi/custom-tables' => Bojaghi\CustomTables\CustomTables::class,
        'bojaghi/template'      => Bojaghi\Template\Template::class,
        'bojaghi/vite-scripts'  => Bojaghi\ViteScripts\ViteScript::class,
        //
        // Modules
        'hnkp/activation'       => Modules\ActivationDeactivation::class,
        'hnkp/current-screen'   => Modules\CurrentScreen::class,
        //
        // Supports
        'hnkp/admin-menu'       => Supports\AdminMenuSupport::class,
        'hnkp/kanji-sheets'     => Supports\KanjiSheetsSupport::class,
        'hnkp/tool-menu'        => Supports\ToolMenuSupport::class,
    ],

    /**
     * 클래스 의존성 주입 선언
     *
     * 키: 별명, 또는 FQCN
     * 값: 배열, 또는 함수 - 함수는 배열을 리턴해야 함
     */
    'arguments' => [
        'bojaghi/admin-menus'   => HNKP_SETTINGS . '/admin-menus.php',
        'bojaghi/admin-posts'   => fn($continy) => [HNKP_SETTINGS . '/admin-posts.php', $continy],
        'bojaghi/clean-pages'   => HNKP_SETTINGS . '/clean-pages.php',
        'bojaghi/custom-tables' => [HNKP_SETTINGS . '/custom-tables.php', HNKP_SETTINGS . '/custom-tables-schemas.php'],
        'bojaghi/template'      => HNKP_SETTINGS . '/template.php',
        'bojaghi/vite-scripts'  => HNKP_SETTINGS . '/vite-scripts.php',
    ],

    /**
     * 모듈 선언
     */
    'modules'   => [
        // 1.0.2 부터 지원하는 언더스코어 모듈: Continy 가 인스턴스화 될 때 바로 실행되는 모듈.
        // 우선순위 키는 사용하지 않습니다.
        '_'                  => [
            'bojaghi/clean-pages',
            'bojaghi/custom-tables',
            'hnkp/activation',
            function () { addCliCommands(); },
        ],
        'admin_print_styles' => [
            // Directly print style here
            Continy::PR_LAZY => [
                function (): void { echo "<style>#toplevel_page_hnkp-kanji .wp-menu-image>img{width:22px;height:22px;}</style>\n"; },
            ],
        ],
        'admin_menu'         => [
            Continy::PR_LAZY => [
                'bojaghi/admin-menus',
            ],
        ],
        'current_screen'     => [
            Continy::PR_LOW => [
                'hnkp/current-screen',
            ],
        ],
        //
        // init 훅에 실행되는 모듈
        'init'               => [
            // 모듈 우선순위
            Continy::PR_DEFAULT => [
                // 모듈 목록
                'bojaghi/admin-posts',
            ],
        ],
    ],
];