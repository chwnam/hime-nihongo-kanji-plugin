<?php

namespace HimeNihongo\KanjiPlugin\Supports\DB;

use Bojaghi\Contract\Support;
use Exception;

class JMDictImportSupport implements Support
{
    private array $kebMap;

    private array $kEleMap;

    private array $rEleMap;

    private array $senseMap;

    private array $ids;

    private array $values;

    public function __construct()
    {
        $this->reset();
    }

    public function reset(): void
    {
        $this->kebMap   = [];
        $this->kEleMap  = [];
        $this->rEleMap  = [];
        $this->senseMap = [];

        $this->ids = [
            'keb'   => 0,
            'k_ele' => 0,
            'r_ele' => 0,
            'sense' => 0,
        ];

        $this->values = [
            'keb'   => [],
            'k_ele' => [],
            'r_ele' => [],
            'sense' => [],
            'entry' => [],
        ];
    }

    /**
     * 테이블로 레코드를 임포트한다.
     *
     * 테이블은 모두 TRUNCATE 되어 있어야 한다.
     */
    public function collectAll(array $entries): void
    {
        $this->reset();

        /**
         * 스텝 1: 각 기본 테이블의 PK를 확보한다.
         *   엔트리 배열을 순회하며:
         *     k_keb, k_ele 테이블에 넣을 값을 저장한다.
         *     r_ele 테이블에 넣을 값을 저장한다.
         *     sense 테이블에 넣을 값을 저장한다.
         *
         * 스텝 2: entry 관계를 매핑한다
         *   entry 에 넣을 (ent_seq, k_id, r_id, s_id) 튜플을 매핑
         *
         *   엔트리 배열에서 sense > r_ele > e_ele 순서로 중첩하여 순회한다.
         *
         *   sense 배열을 순회하며:
         *     sense k에 stagr가 있다면:
         *       (ent_seq, stagr)을 기준으로 해당 r_ele를 알아낸다
         *       해당 r_ele를 순회 루트에 넣는다
         *     sense k에 stagk가 있다다면:
         *       (ent_seq, stagk)을 기준으로 해당 k_ele를 알아낸다
         *       해당 k_ele를 순회 루트에 넣는다
         *     sense k에 stagk, stagr 둘 다 없다면:
         *       해당 엔트리의 모든 r_ele를 순회 루트에 넣는다
         *
         *     계획된 r_ele 배열을 순회하며:
         *       r_ele j에 re_restr가 있다면:
         *         (ent_seq, re_restr)을 기준으로 k_ele를 알아낸다
         *         계획된 순회 루트에 k_ele만 넣는다
         *       r_ele j에 re_restr가 없다면:
         *         해당 엔트리의 모든 k_ele를 순회 루트에 넣는다
         *
         *       계획된 k_ele 배열을 순회한다.
         *         k_ele i의 id를 알아내여
         *         (ent_seq, i.id, j.id, k.id) 튜플을 저장한다
         */

        foreach ($entries as $entry) {
            $this->collectKeb($entry);
            $this->collectKEle($entry);
            $this->collectREle($entry);
            $this->collectSense($entry);
            $this->collectEntry($entry);
        }
    }

    public function collectKeb(array $entry): void
    {
        global $wpdb;

        $buf = [];

        foreach ($entry['k_ele'] as $k_ele) {
            if (!$this->getKebMap($k_ele['keb'])) {
                $id    = $this->getNextId('keb');
                $buf[] = $wpdb->prepare('(%d,%s,%d)', $id, $k_ele['keb'], $k_ele['keb_len']);
                $this->setKebMap($k_ele['keb'], $id);
            }
        }

        $this->values['keb'] = [...$this->values['keb'], ...$buf];
    }

    private function getKebMap(string $keb): int
    {
        return $this->kebMap[$keb] ?? 0;
    }

    private function getNextId(string $type): int
    {
        return isset($this->ids[$type]) ? ++$this->ids[$type] : 0;
    }

    private function setKebMap(string $keb, int $kebId): void
    {
        $this->kebMap[$keb] = $kebId;
    }

    public function collectKEle(array $entry): void
    {
        global $wpdb;

        $entSeq = $entry['ent_seq'];
        $buf    = [];

        foreach ($entry['k_ele'] as $idx => $k_ele) {
            $kebId = $this->getKebMap($k_ele['keb']);
            if (!$this->getKEleMap($entSeq, $kebId)) {
                $nextId = $this->getNextId('k_ele');
                $buf[]  = '(' .
                    $wpdb->prepare('%d,%d', $nextId, $kebId) .
                    ',' .
                    ($k_ele['ke_inf'] ? $wpdb->prepare('%s', $k_ele['ke_inf']) : 'NULL') .
                    ',' .
                    ($k_ele['ke_pri'] ? $wpdb->prepare('%s', $k_ele['ke_pri']) : 'NULL') .
                    ')';
                $this->setKeleMap($entSeq, $kebId, $idx, $nextId);
            }
        }

        $this->values['k_ele'] = [...$this->values['k_ele'], ...$buf];
    }

    private function getKEleMap(int $entSeq, int $kebId): array|null
    {
        return $this->kEleMap["$entSeq-$kebId"] ?? null;
    }

    private function setKEleMap(int $entSeq, int $kebId, int $idx, int $kEleId): void
    {
        $this->kEleMap["$entSeq-$kebId"] = [$idx, $kEleId];
    }

    public function collectREle(array $entry): void
    {
        global $wpdb;

        $entSeq = $entry['ent_seq'];
        $buf    = [];

        foreach ($entry['r_ele'] as $idx => $r_ele) {
            if (!$this->getREleMap($entSeq, $r_ele['reb'])) {
                $nextId = $this->getNextId('r_ele');
                $buf[]  = '(' .
                    $wpdb->prepare('%d,%s', $nextId, $r_ele['reb']) .
                    ',' .
                    ($r_ele['re_nokanji'] ? '1' : '0') .
                    ',' .
                    ($r_ele['re_inf'] ? $wpdb->prepare('%s', $r_ele['re_inf']) : 'NULL') .
                    ',' .
                    ($r_ele['re_pri'] ? $wpdb->prepare('%s', $r_ele['re_pri']) : 'NULL') .
                    ')';

                $this->setREleMap($entSeq, $r_ele['reb'], $idx, $nextId);
            }
        }

        $this->values['r_ele'] = [...$this->values['r_ele'], ...$buf];
    }

    private function getREleMap(int $entSeq, string $reb): array|null
    {
        return $this->rEleMap["$entSeq-$reb"] ?? null;
    }

    private function setREleMap(int $entSeq, string $reb, int $idx, int $rEleId): void
    {
        $this->rEleMap["$entSeq-$reb"] = [$idx, $rEleId];
    }

    public function collectSense(array $entry): void
    {
        global $wpdb;

        $entSeq = $entry['ent_seq'];
        $buf    = [];

        foreach ($entry['sense'] as $idx => $sense) {
            if (!$this->getSenseMap($entSeq, $idx)) {
                $nextId = $this->getNextId('sense');
                $buf[]  = '(' .
                    $wpdb->prepare('%d,%s', $nextId, $sense['gloss']) .
                    ',' .
                    ($sense['pos'] ? $wpdb->prepare('%s', $sense['pos']) : 'NULL') .
                    ',' .
                    ($sense['field'] ? $wpdb->prepare('%s', $sense['field']) : 'NULL') .
                    ',' .
                    ($sense['misc'] ? $wpdb->prepare('%s', $sense['misc']) : 'NULL') .
                    ')';

                $this->setSenseMap($entSeq, $idx, $nextId);
            }
        }

        $this->values['sense'] = [...$this->values['sense'], ...$buf];
    }

    private function getSenseMap(int $entSeq, int $idx): array|null
    {
        return $this->senseMap["$entSeq-$idx"] ?? null;
    }

    public function setSenseMap(int $entSeq, int $idx, int $senseId): void
    {
        $this->senseMap["$entSeq-$idx"] = [$idx, $senseId];
    }

    public function collectEntry(array $entry): void
    {
        global $wpdb;

        $buffer = [];
        $entSeq = $entry['ent_seq'];

        foreach ($entry['sense'] as $k => $sense) {
            $kEleSched = null;
            $rEleSched = null;

            [$_, $senseId] = $this->getSenseMap($entSeq, $k);

            if ($sense['stagk']) {
                // sense['stagk'] 선언된 경우
                $kEleSched = [];
                foreach ($sense['stagk'] as $stagk) {
                    $kebId       = $this->getKebMap($stagk);
                    $mapped      = $this->getKeleMap($entSeq, $kebId);
                    $kEleSched[] = $mapped[0];
                }
            }

            if ($sense['stagr']) {
                // sense['stagr'] 선언된 경우
                $rEleSched = [];
                foreach ($sense['stagr'] as $stagr) {
                    $mapped      = $this->getREleMap($entSeq, $stagr);
                    $rEleSched[] = $mapped[0];
                }
            }

            if (!$rEleSched) {
                $rEleSched = range(0, count($entry['r_ele']) - 1);
            }

            // $rEleSched 인덱스에 따라 r_ele 순회
            foreach ($rEleSched as $j) {
                $rEle = $entry['r_ele'][$j];
                [$_, $rEleId] = $this->getREleMap($entSeq, $rEle['reb']);

                if ($rEle['re_restr']) {
                    // re_restr 이 붙었다면, $kEleSched를 계산한다
                    $kEleNext = [];
                    foreach ($rEle['re_restr'] as $r_restr) {
                        $kebId      = $this->getKebMap($r_restr);
                        $mapped     = $this->getKeleMap($entSeq, $kebId);
                        $kEleNext[] = $mapped[0];
                    }
                    if ($kEleSched) {
                        // 만약 중복된 항목이 있다면 합집합을 취한다.
                        $kEleSched = array_intersect($kEleSched, $kEleNext);
                    } else {
                        // 계산된 결과를 취한다.
                        $kEleSched = $kEleNext;
                    }
                } elseif (!$kEleSched && count($entry['k_ele'])) {
                    $kEleSched = range(0, count($entry['k_ele']) - 1);
                }

                if ($kEleSched) {
                    foreach ($kEleSched as $i) {
                        $kEle  = $entry['k_ele'][$i];
                        $kebId = $this->getKebMap($kEle['keb']);

                        [$_, $kEleId] = $this->getKEleMap($entSeq, $kebId);

                        $buffer[] = [$entSeq, $kEleId, $rEleId, $senseId];
                    }
                    $kEleSched = null;
                }
            }
        }

        usort($buffer, function ($a, $b) {
            for ($i = 0; $i < 4; ++$i) {
                if ($a[$i] > $b[$i]) {
                    return 1;
                } elseif ($a[$i] < $b[$i]) {
                    return -1;
                }
            }
            return 0;
        });

        $buffer                = array_map(fn($b) => $wpdb->prepare('(%d,%d,%d,%d)', $b), $buffer);
        $this->values['entry'] = [...$this->values['entry'], ... $buffer];
    }

    /**
     * @throws Exception
     */
    public function insertKeb(): void
    {
        global $wpdb;

        $tableKeb = JMDictTables::getTableKeb();

        foreach (array_chunk($this->values['keb'], 5000) as $chunk) {
            $query = "INSERT INTO `$tableKeb` (id, word, word_len) VALUES ";
            $query .= implode(',', $chunk);
            $wpdb->query($query);
            if ($wpdb->last_error) {
                throw new Exception($wpdb->last_error);
            }
        }
    }

    /**
     * @throws Exception
     */
    public function insertKEle(): void
    {
        global $wpdb;

        $tableKEle = JMDictTables::getTableKEle();

        foreach (array_chunk($this->values['k_ele'], 5000) as $chunk) {
            $query = "INSERT INTO `$tableKEle` (id, keb_id, ke_inf, ke_pri) VALUES ";
            $query .= implode(',', $chunk);
            $wpdb->query($query);
            if ($wpdb->last_error) {
                throw new Exception($wpdb->last_error);
            }
        }
    }

    /**
     * @throws Exception
     */
    public function insertREle(): void
    {
        global $wpdb;

        $tableREle = JMDictTables::getTableREle();

        foreach (array_chunk($this->values['r_ele'], 5000) as $chunk) {
            $query = "INSERT INTO `$tableREle` (id, reb, re_nokanji, re_inf, re_pri) VALUES ";
            $query .= implode(',', $chunk);
            $wpdb->query($query);
            if ($wpdb->last_error) {
                throw new Exception($wpdb->last_error);
            }
        }
    }

    /**
     * @throws Exception
     */
    public function insertSense(): void
    {
        global $wpdb;

        $tableSense = JMDictTables::getTableSense();

        foreach (array_chunk($this->values['sense'], 5000) as $chunk) {
            $query = "INSERT INTO `$tableSense` (id, gloss, pos, field, misc) VALUES ";
            $query .= implode(',', $chunk);
            $wpdb->query($query);
            if ($wpdb->last_error) {
                throw new Exception($wpdb->last_error);
            }
        }
    }

    /**
     * @throws Exception
     */
    public function insertEntry(): void
    {
        global $wpdb;

        $tableEntry = JMDictTables::getTableEntry();

        foreach (array_chunk($this->values['entry'], 5000) as $chunk) {
            $query = "INSERT INTO `$tableEntry` (ent_seq, k_id, r_id, s_id) VALUES ";
            $query .= implode(",\n", $chunk);
            $wpdb->query($query);
            if ($wpdb->last_error) {
                throw new Exception($wpdb->last_error);
            }
        }
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function clearMap(): void
    {
        $this->kEleMap  = [];
        $this->rEleMap  = [];
        $this->senseMap = [];
    }

    public function clearValues(string $type): void
    {
        if (isset($this->values[$type])) {
            $this->values[$type] = [];
        }
    }
}
