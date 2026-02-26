<?php

namespace HemeNihongo\KanjiPlugin;

function assets_url(string $path): string
{
    $path = ltrim($path, '/\\');

    return plugins_url("inc/assets/$path", HNKP_MAIN);
}

function hira_to_kata(string $string): string
{
    $output  = [];
    $strings = explode('、', $string);

    foreach ($strings as $value) {
        if (preg_match('/^\p{Hiragana}+$/u', $value)) {
            $output[] = mb_convert_kana($value, "KC", "UTF-8");
        } else {
            $output[] = $value;
        }
    }

    return implode('、', $output);
}
