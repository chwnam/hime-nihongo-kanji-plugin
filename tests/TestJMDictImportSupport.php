<?php

namespace HimeNihongo\KanjiPlugin\Tests;

use HimeNihongo\KanjiPlugin\Supports\DB\JMDictImportSupport;
use HimeNihongo\KanjiPlugin\Supports\DB\JMDictTables;
use WP_UnitTestCase;

class TestJMDictImportSupport extends WP_UnitTestCase
{
    private JMDictImportSupport $support;

    public function setUp(): void
    {
        $this->support = new JMDictImportSupport();
        JMDictTables::truncateTables();
    }

    /**
     * @param array $entries
     *
     * @return void
     * @dataProvider providerCollectValues
     */
    public function testCollectValues(array $entries): void
    {
        $this->support->collectAll($entries);

        $values = $this->support->getValues();

        $this->assertCount(1, $values['keb']);
        $this->assertEquals("(1,'日本語',3)", $values['keb'][0]);

        $this->assertCount(1, $values['k_ele']);
        $this->assertEquals("(1,1,NULL,'news1,nf02')", $values['k_ele'][0]);

        $this->assertCount(3, $values['r_ele']);
        $this->assertEquals("(1,'にほんご',0,NULL,'news1,nf02')", $values['r_ele'][0]);
        $this->assertEquals("(2,'にぽんご',0,NULL,NULL)", $values['r_ele'][1]);
        $this->assertEquals("(3,'にっぽんご',0,NULL,NULL)", $values['r_ele'][2]);

        $this->assertCount(1, $values['sense']);
        $this->assertEquals("(1,'Japanese (language)','n',NULL,NULL)", $values['sense'][0]);

        $this->assertCount(3, $values['entry']);
        $this->assertEquals('(1464530,1,1,1)', $values['entry'][0]);
        $this->assertEquals('(1464530,1,2,1)', $values['entry'][1]);
        $this->assertEquals('(1464530,1,3,1)', $values['entry'][2]);
    }

    protected function providerCollectValues(): array
    {
        return [
            '日本語' => [
                // arg 0th
                [
                    [
                        'ent_seq' => 1464530,
                        'k_ele'   => [
                            [
                                'keb'     => '日本語',
                                'keb_len' => '3',
                                'ke_inf'  => '',
                                'ke_pri'  => 'news1,nf02',
                            ],
                        ],
                        'r_ele'   => [
                            [
                                'reb'        => 'にほんご',
                                're_nokanji' => false,
                                're_inf'     => '',
                                're_pri'     => 'news1,nf02',
                                're_restr'   => '',
                            ],
                            [
                                'reb'        => 'にぽんご',
                                're_nokanji' => false,
                                're_inf'     => '',
                                're_pri'     => '',
                                're_restr'   => '',
                            ],
                            [
                                'reb'        => 'にっぽんご',
                                're_nokanji' => false,
                                're_inf'     => '',
                                're_pri'     => '',
                                're_restr'   => '',
                            ],
                        ],
                        'sense'   => [
                            [
                                'stagk' => '',
                                'stagr' => '',
                                'gloss' => 'Japanese (language)',
                                'pos'   => 'n',
                                'field' => '',
                                'misc'  => '',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array $entries
     * @param array $expected
     *
     * @return void
     * @dataProvider providerEntry
     */
    public function testEntry(array $entries, array $expected): void
    {
        $this->support->collectAll($entries);

        $values = $this->support->getValues();
        $entry  = $values['entry'];

        $this->assertCount(count($expected), $entry);
        for ($i = 0; $i < count($expected); $i++) {
            $this->assertEquals($expected[$i], $entry[$i]);
        }
    }

    protected function providerEntry(): array
    {
        return [
            'CASE #1'         => [
                // arg 0th
                [
                    [
                        'ent_seq' => 7777777,
                        'k_ele'   => [
                            [
                                'keb'     => 'w1',
                                'keb_len' => 2,
                                'ke_inf'  => '',
                                'ke_pri'  => '',
                            ],
                            [
                                'keb'     => 'w2',
                                'keb_len' => 2,
                                'ke_inf'  => '',
                                'ke_pri'  => '',
                            ],
                        ],
                        'r_ele'   => [
                            [
                                'reb'        => 'r1',
                                're_nokanji' => false,
                                're_inf'     => '',
                                're_pri'     => '',
                                're_restr'   => '',
                            ],
                            [
                                'reb'        => 'r2',
                                're_nokanji' => false,
                                're_inf'     => '',
                                're_pri'     => '',
                                're_restr'   => ['w1'],
                            ],
                        ],
                        'sense'   => [
                            [
                                'stagk' => ['w1'],
                                'stagr' => [],
                                'gloss' => 's1',
                                'pos'   => 'n',
                                'field' => '',
                                'misc'  => '',
                            ],
                            [
                                'stagk' => [],
                                'stagr' => [],
                                'gloss' => 's2',
                                'pos'   => 'n',
                                'field' => '',
                                'misc'  => '',
                            ],
                        ],
                    ],
                ],
                // arg 1st
                [
                    '(7777777,1,1,1)',
                    '(7777777,1,1,2)',
                    '(7777777,1,2,1)',
                    '(7777777,1,2,2)',
                    '(7777777,2,1,2)',
                ],
            ],
            'CASE #2,1443970' => [
                // arg 0th
                [
                    // ent_seq => 1443970
                    [
                        'ent_seq' => 1443970,
                        'k_ele'   => [
                            [
                                'keb'     => '兎',
                                'keb_len' => 1,
                                'ke_inf'  => '',
                                'ke_pri'  => 'ichi1,news2,nf32',
                            ],
                            [
                                'keb'     => '兔',
                                'keb_len' => 1,
                                'ke_inf'  => 'rK',
                                'ke_pri'  => '',
                            ],
                            [
                                'keb'     => '兔',
                                'keb_len' => 1,
                                'ke_inf'  => 'rK',
                                'ke_pri'  => '',
                            ],
                            [
                                'keb'     => '菟',
                                'keb_len' => 1,
                                'ke_inf'  => 'iK',
                                'ke_pri'  => '',
                            ],
                        ],
                        'r_ele'   => [
                            [
                                'reb'        => 'うさぎ',
                                're_nokanji' => 0,
                                're_inf'     => '',
                                're_pri'     => 'ichi1,news2,nf32',
                                're_restr'   => [],
                            ],
                            [
                                'reb'        => 'う',
                                're_nokanji' => '',
                                're_inf'     => 'ok',
                                're_pri'     => '',
                                're_restr'   => ['兎'],
                            ],
                            [
                                'reb'        => 'ウサギ',
                                're_nokanji' => 1,
                                're_inf'     => '',
                                're_pri'     => '',
                                're_restr'   => [],
                            ],
                        ],
                        'sense'   => [
                            [
                                'stagk' => [],
                                'stagr' => [],
                                'pos'   => 'n',
                                'field' => '',
                                'misc'  => 'uk',
                                'gloss' => "rabbit\nhare\nconey\ncony\nlagomorph (esp. leporids)",
                            ],
                        ],
                    ],
                    // end: ent_seq => 1443970
                ],
                // arg 1st
                [
                    '(1443970,1,1,1)',
                    '(1443970,1,2,1)',
                    '(1443970,1,3,1)',
                    '(1443970,2,1,1)',
                    '(1443970,2,3,1)',
                    '(1443970,3,1,1)',
                    '(1443970,3,3,1)',
                    '(1443970,4,1,1)',
                    '(1443970,4,3,1)',
                ],
            ],
        ];
    }
}
