<?php

if (! defined('ABSPATH')) {
    exit;
}

return [
    'version_name' => 'hnkp_db_version', // Optional
    'version'      => '1.0.0',           // Optional
    'is_theme'     => false,             // Optional, defaults to false.
    'main_file'    => HNKP_MAIN,         // Optional, defaults to blank.
    'activation'   => true,              // Optional, defaults to false. Create tables on activation.
    'deactivation' => true,              // Optional, defaults to false. Delete tables on deactivation.
    'uninstall'    => false,             // Optional, defaults to false. Delete tables on uninstall.
];
