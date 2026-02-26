<?php

namespace HimeNihongo\KanjiPlugin\Supports;

use Bojaghi\Contract\Support;
use HimeNihongo\KanjiPlugin\ListTables\CharsListTable;

class AdminMenuSupport implements Support
{
    public function outputAdminScreen(): void
    {
        $charId = absint($_GET['char_id'] ?? '0');
        $wordId = absint($_GET['word_id'] ?? '0');

        if (!$charId && !$wordId) {
            if (array_key_exists('new_char', $_GET)) {
                // TODO: new char form
                return;
            }

            if (array_key_exists('new_word', $_GET)) {
                // TODO: new word form
                return;
            }
        }

        if ($charId) {
            // TODO: edit char form
            return;
        }

        if ($wordId) {
            // TODO: edit word form
            return;
        }

        $this->outputAdminCharacterListTable();
    }

    public function outputAdminCharacterListTable(): void
    {
        // TODO: implement
        echo hnkp_template(
            'admin/list-table',
            [
                'title'      => '한자 목록',
                'list_table' => hnkp_get(CharsListTable::class),
            ],
        );
    }
}
