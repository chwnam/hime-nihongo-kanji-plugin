<?php

if (!defined('ABSPATH')) {
    exit;
}

return [
    [
        'name'           => 'kanji',
        'condition'      => fn(string $name): bool => 'kanji' === $name,
        // 'template' => function (string $name, mixed $body) {},
        'before'         => function (string $name) { 'kanji' === $name && hnkp_get('hnkp/kanji-sheets'); },
        'body'           => function (string $name) { hnkp_get('hnkp/kanji-sheets')?->body($name); },
        'after'          => function (string $name) { },
        'login_required' => false,
        // 'login_url' => '',
    ],
    'exit'           => true,
    'priority'       => 99999,
    'show_admin_bar' => false,
];
