<?php

if (!defined('ABSPATH')) {
    exit;
}

return [
    'version_name'    => HNKP_DB_VERSION_NAME, // Optional
    'version'         => HNKP_DB_VERSION,      // Optional
    'is_theme'        => false,                // Optional, defaults to false.
    'main_file'       => HNKP_MAIN,            // Optional, defaults to blank.
    'activation'      => true,                 // Optional, defaults to false. Create tables on activation.
    'deactivation'    => false,                // Optional, defaults to false. [Delete] tables on deactivation.
    'uninstall'       => false,                // Optional, defaults to false. [Delete] tables on uninstall.
    'suppress_errors' => false,                // Optional, defaults to false.
];
