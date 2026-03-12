<?php

if (!defined('ABSPATH')) {
    exit;
}

return [
    'distBaseUrl'  => plugin_dir_url(HNKP_MAIN) . 'dist',
    'i18n'         => false,
    'isProd'       => false, // 'production' === wp_get_environment_type(),
    'manifestPath' => plugin_dir_path(HNKP_MAIN) . 'dist/.vite/manifest.json',
];
