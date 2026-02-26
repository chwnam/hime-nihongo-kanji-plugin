<?php

namespace HimeNihongo\KanjiPlugin\ListTables;

use HimeNihongo\KanjiPlugin\Objects\Character;
use WP_List_Table;

class CharsListTable extends WP_List_Table
{
    /** @var Character[] */
    public $items = [];

    public function __construct()
    {
        parent::__construct(
            [
                'plural'   => 'Characters',
                'singular' => 'Character',
                'ajax'     => false,
                'screen'   => null,
            ],
        );

        $this->modes = [];
    }

    public function get_columns(): array
    {
        return [
            'kanji' => '한자',
            'ko'    => '한국어 훈/음',
            'kun'   => '훈독',
            'on'    => '음독',
            'level' => '급수',
        ];
    }

    /**
     * Get a primary column name.
     *
     * @return string
     */
    protected function get_default_primary_column_name(): string
    {
        return 'id';
    }

    /**
     * Get sortable columns.
     *
     * @return array
     */
    protected function get_sortable_columns(): array
    {
        return [];
    }

    /**
     * Prepare the items for the table
     *
     * @return void
     */
    public function prepare_items(): void
    {
        global $wpdb;

        $paged   = max(1, absint($_GET['paged'] ?? '0'));
        $perPage = $this->get_items_per_page('hnkp_chars_per_page');
        $offset  = ($paged - 1) * $perPage;

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT SQL_CALC_FOUND_ROWS * FROM {$wpdb->prefix}hnkp_chars LIMIT %d, %d",
                $offset,
                $perPage,
            ),
        );

        if ($results) {
            $this->items = array_map([Character::class, 'fromArray'], $results);
        }

        $this->set_pagination_args(
            [
                'total_items' => intval($wpdb->get_var('SELECT FOUND_ROWS()')),
                'per_page'    => $perPage,
            ],
        );
    }

    /**
     * Prepare and return row actions.
     *
     * @param Character $item        Option record.
     * @param string    $column_name Current column name.
     * @param string    $primary     Primary column or not.
     *
     * @return string
     */
    protected function handle_row_actions($item, $column_name, $primary): string
    {
        if ($primary !== $column_name) {
            return '';
        }

        $actions = [];

        return $this->row_actions($actions);
    }

    /**
     * 'column_*' magic method
     */
    public function column_kanji(Character $item): void
    {
        echo esc_html($item->kanji);
    }
}
