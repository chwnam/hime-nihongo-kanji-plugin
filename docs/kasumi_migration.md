# 카스미 단어 테이블 마이그레이션 노트

## 사전에 등재되지 않은 단어를 조사

아래 SQL 쿼리를 사용하여 사전에 등재되지 않은 카스미 테이블의 단어를 조사했습니다.

```sql
SELECT jlpt, entry, word, yomikata, meaning
FROM wp_hnkp_kasumi_words AS words
WHERE NOT EXISTS (SELECT 1
                  FROM wp_hnkp_jmdict_keb AS keb
                           INNER JOIN wp_hnkp_jmdict_k_ele AS k_ele ON k_ele.keb_id = keb.id
                           INNER JOIN wp_hnkp_jmdict_entry AS entry ON entry.k_id = k_ele.id
                           INNER JOIN wp_hnkp_jmdict_r_ele AS r_ele ON r_ele.id = entry.r_id
                  WHERE keb.word = words.word
                    AND r_ele.reb = words.yomikata);
```

이 결과는 `docs/kasumi_not_found.csv`에 기록했습니다.
총 54항목이며, 이 항목만을 대상으로 조사를 합니다.

쿼리:
```sql
SELECT words.jlpt, words.entry, words.word, words.yomikata, words.meaning, IF (jmdict.word IS NULL, 'N', 'Y') AS in_jmdict
FROM wp_hnkp_kasumi_words words
         LEFT JOIN (SELECT keb.word
                    FROM wp_hnkp_jmdict_entry AS entry
                             INNER JOIN wp_hnkp_jmdict_k_ele AS k_ele ON k_ele.id = entry.k_id
                             INNER JOIN wp_hnkp_jmdict_keb AS keb ON keb.id = k_ele.keb_id) AS jmdict
                   ON jmdict.word = words.word
WHERE words.word IN (
                     '何人', '同期生', '世界中', '勤務外', '前に', '左右', '東海岸', '西海岸', '南口', '天気予報',
                     '元気', '小学校', '校長', '身長', '多数决', '少年', '古本屋', '食事', '言語', '何が', '新宿駅',
                     '直射光', '代わりに', '側縁図', '20度', '同い', '文学', '大阪市', '市会議', '十万弱', '洋楽器',
                     '田中', '成田', '羽田', '〜町', '借り', '着る', '連絡便', '食料品', '好きだ', '~回', '再利用',
                     '美容院', '変だ', '輸出量', '紅葉', '自由', '自由化', '昨日', '～際', '次々と', '平らだ', '～向き',
                     '～向け')
GROUP BY words.word
```

이 결과는 `docs/kasumi_typos.csv`로도 저장하였습니다. `in_jmdict`필드가 Y인 경우는 사전에서 항목은 찾을 수 있으므로,
`yomikata` 필드에 오류가 있는 경우라고 예상할 수 있습니다.

- 읽는 법에 오타가 있습니다.
- 여러 읽는 법을 하나로 합쳐 적었을 수 있습니다.

만약 N인 경우는 사전에 글자 자체가 등재되지 않았으므로,

- 사전에 등재된 단어가 아닙니다.
- 이 단어가 지명일 수 있습니다.
- 임의의 표기를 포함시킨 단어일 수 있습니다.
- 단어의 원형이 아닌, 활용형을 적은 것일 수 있습니다.


## 단어 중복 조사

아래 SQL 쿼리를 사용하여 2번이상 활용된 단어를 추출합니다.
사용 기준은 단어-발음 쌍이며, 만약 사전에 등재되지 않은 위 54개의 단어인 경우 표시합니다.

```sql
SELECT word,
       COUNT(word) as cnt,
       IF(word IN ('何人', '同期生', '世界中', '勤務外', '前に', '左右', '東海岸', '西海岸', '南口', '天気予報',
                   '元気', '小学校', '校長', '身長', '多数决', '少年', '古本屋', '食事', '言語', '何が', '新宿駅',
                   '直射光', '代わりに', '側縁図', '20度', '同い', '文学', '大阪市', '市会議', '十万弱', '洋楽器',
                   '田中', '成田', '羽田', '〜町', '借り', '着る', '連絡便', '食料品', '好きだ', '~回', '再利用',
                   '美容院', '変だ', '輸出量', '紅葉', '自由', '自由化', '昨日', '～際', '次々と', '平らだ', '～向き',
                   '～向け'), 'not found', '') AS jmdict_status
FROM wp_hnkp_kasumi_words AS words
GROUP BY word
HAVING cnt > 1
```

결과는 `docs/kasumi_dup.csv`로 저장하였습니다.
