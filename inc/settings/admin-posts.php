<?php

use Bojaghi\AdminAjax\SubmitBase;

if (!defined('ABSPATH')) {
    exit;
}

return [
    'checkContentType' => false,
    [
        'hnkp_csv_import',
        /** @see \HimeNihongo\KanjiPlugin\Supports\ToolMenuSupport::importCSV() */
        'hnkp/tool-menu@importCSV',
        SubmitBase::ONLY_PRIV,
        '_hnkp_nonce',
    ],
];
