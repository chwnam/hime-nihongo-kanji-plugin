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

이 결과는 `data/kasumi_not_found.csv`에 기록했습니다.
총 54항목이며, 이 항목만을 대상으로 조사를 합니다.

쿼리:

```sql
SELECT words.jlpt,
       words.entry,
       words.word,
       words.yomikata,
       words.meaning,
       IF(jmdict.word IS NULL, 'N', 'Y') AS in_jmdict
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

이 결과는 `data/kasumi_typos.csv`로도 저장하였습니다. `in_jmdict`필드가 Y인 경우는 사전에서 항목은 찾을 수 있으므로,
`yomikata` 필드에 오류가 있는 경우라고 예상할 수 있습니다.

- 읽는 법에 오타가 있습니다.
- 여러 읽는 법을 하나로 합쳐 적었을 수 있습니다.

만약 N인 경우는 사전에 글자 자체가 등재되지 않았으므로,

- 사전에 등재된 단어가 아닙니다.
- 이 단어가 지명일 수 있습니다.
- 임의의 표기를 포함시킨 단어일 수 있습니다.
- 단어의 원형이 아닌, 활용형을 적은 것일 수 있습니다.

## 마이그레이션 계획

`data\kasumi_correct.csv`에 교정한 내용을 담았습니다.
각 필드의 의미는 아래와 같습니다.

- jlpt: jlpt 급수, n3.csv, n4.csv, n5.csv 파일에 속해 있는지를 구분합니다.
- entry: 해당 파일 내에서 나오는 순번입니다.
- word: 해당 PDF에 담긴 한자
- yomikata: PDF에서 추출한 읽기
- meaning: PDF에서 추출한 뜻
- in_jmdict: JMDict에서 word를 찾을 수 있었는지를 나타냅니다.
- is_correct: word, yomikata를 판단하여 결국 맞는 단어인지 아닌지를 판별한 결과를 기록합니다.
- remarks: 판정의 이유를 기록합니다.
- correct_word: 결국 올바른 단어를 기록합니다.
- correct_yomikata_1: 올바른 요미카타를 기록합니다.
- correct_yomikata_2: 읽는 법이 2개인 경우 분리하여 적었습니다. 여기에도 요미카타가 있다면 해당 단어는 2개로 나누어 기록해야 합니다.

교정된 내용을 우선적으로 hime_words에 삽입합니다.

kasumi_words 테이블과 hime_words의 단어 매핑 테이블을 기록합니다.
매핑 테이블을 이용해 쓰기 내용을 마이그레이션합니다.
