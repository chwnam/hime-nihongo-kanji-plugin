<?php

namespace HimeNihongo\KanjiPlugin\Supports\DB;

use Bojaghi\Contract\Support;
use Exception;
use HimeNihongo\KanjiPlugin\CLI\Utils;
use SimpleXMLElement;
use WP_CLI;
use WP_CLI\ExitException;
use XMLReader;

/**
 * Read JMdict.xml file
 */
class JMDictReadSupport implements Support
{
    /**
     * @throws ExitException
     */
    public function readXML(string $path): array
    {
        $output = [
            /*
             [
               'ent_seq' => 1000000,
               'k_ele'   => [ ... ],
               'r_ele'   => [ ... ],
               'sense'   => [ ... ],
             ]
             */
        ];

        $reader = new XMLReader();
        $reader->open($path);

        try {
            while ($reader->read()) {
                if ($reader->nodeType == XMLReader::ELEMENT && 'entry' == $reader->name) {
                    $chunk    = str_replace('&', '&amp;', $reader->readOuterXML());
                    $output[] = $this->parseOneNode(new SimpleXMLElement($chunk));
                }
            }
        } catch (Exception $e) {
            WP_CLI::error($e->getMessage());
        } finally {
            $reader->close();
        }

        return $output;
    }

    public function parseOneNode(SimpleXMLElement $entry): array
    {
        return [
            'ent_seq' => (int)$entry->ent_seq,
            'k_ele'   => $this->parseOneNode_k_ele($entry),
            'r_ele'   => $this->parseOneNode_r_ele($entry),
            'sense'   => $this->parseOneNode_sense($entry),
        ];
    }

    public function parseOneNode_k_ele(SimpleXMLElement $entry): array
    {
        $output = [
            /*
            [
              'keb'     => '(kanji)',
              'keb_len' => 2,
              'ke_inf'  => 'i1,i2',
              'ke_pri'  => 'p1,p2',
            ],
            .... 0 or more
            */
        ];

        if (isset($entry->k_ele)) {
            foreach ($entry->k_ele as $k_ele) {
                // 성급한 노멀라이즈 하면 안됨.
                $keb     = (string)$k_ele->keb;
                $keb_len = mb_strlen($keb, 'UTF-8');
                $ke_inf  = self::mergeEntities($k_ele, 'ke_inf');
                $ke_pri  = self::mergeEntities($k_ele, 'ke_pri');

                $output[] = compact('keb', 'keb_len', 'ke_inf', 'ke_pri');
            }
        }

        return $output;
    }

    public function parseOneNode_r_ele(SimpleXMLElement $entry): array
    {
        $output = [
            /*
             [
               'reb'        => '(yomikata)',
               're_nokanji' => false,
               're_inf'     => 'i1,i2',
               're_pri'     => 'p1,p2,p3',
               're_restr'   => ['(kanji)', ... 0 or more],
             ],
             .... 1 or more
             */
        ];

        // r_ele
        if ($entry->r_ele) {
            foreach ($entry->r_ele as $r_ele) {
                $output[] = [
                    'reb'        => (string)$r_ele->reb,
                    're_nokanji' => isset($r_ele->re_nokanji),
                    're_inf'     => self::mergeEntities($r_ele, 're_inf'),
                    're_pri'     => self::mergeEntities($r_ele, 're_pri'),
                    're_restr'   => self::pluckEntries($r_ele, 're_restr'),
                ];
            }
        }

        return $output;
    }

    public function parseOneNode_sense(SimpleXMLElement $entry): array
    {
        $output = [
            /*
             [
               'stagk' => ['(kanji)', ... 0 or more],
               'stagr' => ['(yomikata)', ... 0 or more],
               'pos'   => 'p1,p2,p3',
               'field' => 'f1,f2',
               'misc'  => 'm1,m2',
               'gloss' => 'gloss1{\n}gloss2',
             ],
             ... 1 or more
             */
        ];

        if ($entry->sense) {
            foreach ($entry->sense as $sense) {
                $output[] = [
                    'stagk' => self::pluckEntries($sense, 'stagk'),
                    'stagr' => self::pluckEntries($sense, 'stagr'),
                    'pos'   => self::mergeEntities($sense, 'pos'),
                    'field' => self::mergeEntities($sense, 'field'),
                    'misc'  => self::mergeEntities($sense, 'misc'),
                    'gloss' => self::mergeEntities($sense, 'gloss', "\n"),
                ];
            }
        }

        return $output;
    }

    private static function pluckEntries(SimpleXMLElement $node, string $element): array
    {
        $buffer = [];

        if (isset($node->{$element})) {
            foreach ($node->$element as $e) {
                $buffer[] = (string)$e;
            }
        }

        return $buffer;
    }

    private static function mergeEntities(SimpleXMLElement $node, string $element, string $glue = ','): string
    {
        return implode(
            $glue,
            array_map(
                fn($item) => trim($item, '&;'),
                self::pluckEntries($node, $element),
            ),
        );

    }
}
