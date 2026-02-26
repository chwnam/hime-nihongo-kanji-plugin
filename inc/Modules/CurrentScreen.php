<?php

namespace HimeNihongo\KanjiPlugin\Modules;

use Bojaghi\Contract\Module;
use HimeNihongo\KanjiPlugin\ListTables\CharsListTable;
use WP_List_Table;
use WP_Screen;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class CurrentScreen implements Module
{
    readonly private WP_Screen $screen;
    private ?WP_List_Table     $table = null;

    public function __construct(WP_Screen $screen)
    {
        $this->screen = $screen;

        $this->activateListTable();
    }

    public function activateListTable(): void
    {
        if ('toplevel_page_hnkp-kanji' === $this->screen->id) {
            $this->table = new CharsListTable();
            $this->screen->add_option(
                'per_page',
                [
                    'default' => 20,
                    'option'  => 'hnkp_chars_per_page',
                ],
            );
        }

        if ($this->table) {
            $this->table->prepare_items();
        }
    }

    public function getTheTable(): ?WP_List_Table
    {
        return $this->table;
    }
}
