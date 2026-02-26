<?php
/**
 * Plugin Name:       히메 일본어 한자 플러그인
 * Description:       한자 쓰기 인쇄물 관리를 지원합니다.
 * Plugin URI:        https://github.com/chwnam/hime-nihongo-kanji-plugin
 * Version::          0.0.0
 * Author:            남창우
 * Author URI:        https://blog.changwoo.pe.kr
 * Requires PHP:      8.3
 * Requires at least: 6.9
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

const HNKP_MAIN    = __FILE__;
const HNKP_VERSION = '0.0.0';

require_once __DIR__ . '/vendor/autoload.php';
