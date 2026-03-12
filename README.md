# 히메 일본어 한자 플러그인

한자 쓰기 인쇄물 관리를 지원합니다.

## 데이터 준비하기

플러그인은 방대한 사전 자료를 구축합니다. 이를 위해 여러 기초 자료가 필요합니다.
또한 WP-CLI가 필수적으로 필요합니다.

플러그인 디렉토리의 `scripts` 서브디렉토리에는 데이터 준비를 위한 스크립트 예시 
`install_tables.sh.dist`가 있습니다. 이 스크립트를 복사한 뒤 현재 설치하려는 시스템 환경에 맞게 수정하세요.

### 변수 설정하기 

`WP_CLI`는 WP-CLI의 경로를 설정합니다.

`KANJIDIC2_XML`은 [KANJIDIC Project](https://www.edrdg.org/wiki/KANJIDIC_Project.html)에서
[다운로드](http://www.edrdg.org/kanjidic/kanjidic2.xml.gz) 받은 XML 파일의 경로를 설정합니다.

`JMDICT_E_XML`은 [The JMdict Project](https://www.edrdg.org/jmdict/j_jmdict.html)에서
[다운로드](ftp://ftp.edrdg.org/pub/Nihongo//JMdict_e.gz) 받은 영어 번역만 있는 XML 파일의 경로를 설정합니다.

모든 XML 파일의 .gz 압축은 해제되어 있어야 합니다.

`HANJA_CSV`는 [한국어문회 등급별 선정한자 CSV 데이터셋](https://github.com/rycont/hanja-grade-dataset/tree/main)에서
[CSV 파일](https://raw.githubusercontent.com/rycont/hanja-grade-dataset/refs/heads/main/hanja.csv)을 다운로드 받습니다.

Github [labocho/unihan_utils](https://github.com/labocho/unihan_utils) 리포지터리에서
[data/Unihan_Readings.txt](https://raw.githubusercontent.com/labocho/unihan_utils/refs/heads/master/data/Unihan_Readings.txt),
[data/Unihan_Variants.txt](https://raw.githubusercontent.com/labocho/unihan_utils/refs/heads/master/data/Unihan_Variants.txt)
두 파일을 받습니다. 각 경로를 `UNIHAN_READINGS`, `UNIHAN_VARIANTS`로 설정합니다.

`JLPT_KANJI` 파일은 임의로 생성한 JLPT N1~N5 한자 목록입니다. 이 파일은 `scripts/jlpt_kanji.txt`를 사용해도 됩니다.
이 파일의 경로를 `JLPT_KANJI`로 설정합니다.

### 스크립트 실행하기

복사하여 수정한 파일에 실행 권한을 부여합니다.

```bash
chmod +x ./scripts/install_tables.sh # 환경에 맞춰 편집한 복사혼
./scripts/install_tables.sh
```

이 스크립트를 실행할 때, 플러그인 루트에서 실행하세요. WP_CLI가 올바르게 워드프레스 환경을 읽어들이기 위해 필요합니다.
올바르게 실행된다면 아래와 비슷한 출력이 생성될 것입니다.

```
Warning: Plugin 'hime-nihongo-kanji-plugin' is already active.
Success: Plugin already activated.
Success: Tables dropped.
Success: Tables updated.
Success: Successfully imported.
Success: Successfully imported.
Success: Successfully imported.
Success: Successfully imported.
Success: Successfully imported.
Success: Successfully imported.
wp_hnkp_dic_hanja: 5978
wp_hnkp_dic_jlpt: 2220
wp_hnkp_dic_kanji: 13033
wp_hnkp_dic_map: 3072
wp_hnkp_dic_sinji: 13367
wp_hnkp_dic_tango: 174761
Migrating hime_chars table...
Migrating hime_words table...
Page 1/35 processed.
Page 2/35 processed.
Page 3/35 processed.
Page 4/35 processed.
Page 5/35 processed.
Page 6/35 processed.
Page 7/35 processed.
Page 8/35 processed.
Page 9/35 processed.
Page 10/35 processed.
Page 11/35 processed.
Page 12/35 processed.
Page 13/35 processed.
Page 14/35 processed.
Page 15/35 processed.
Page 16/35 processed.
Page 17/35 processed.
Page 18/35 processed.
Page 19/35 processed.
Page 20/35 processed.
Page 21/35 processed.
Page 22/35 processed.
Page 23/35 processed.
Page 24/35 processed.
Page 25/35 processed.
Page 26/35 processed.
Page 27/35 processed.
Page 28/35 processed.
Page 29/35 processed.
Page 30/35 processed.
Page 31/35 processed.
Page 32/35 processed.
Page 33/35 processed.
Page 34/35 processed.
Page 35/35 processed.
Migrating the rest of the job ...
Page 1/35 processed.
Page 2/35 processed.
Page 3/35 processed.
Page 4/35 processed.
Page 5/35 processed.
Page 6/35 processed.
Page 7/35 processed.
Page 8/35 processed.
Page 9/35 processed.
Page 10/35 processed.
Page 11/35 processed.
Page 12/35 processed.
Page 13/35 processed.
Page 14/35 processed.
Page 15/35 processed.
Page 16/35 processed.
Page 17/35 processed.
Page 18/35 processed.
Page 19/35 processed.
Page 20/35 processed.
Page 21/35 processed.
Page 22/35 processed.
Page 23/35 processed.
Page 24/35 processed.
Page 25/35 processed.
Page 26/35 processed.
Page 27/35 processed.
Page 28/35 processed.
Page 29/35 processed.
Page 30/35 processed.
Page 31/35 processed.
Page 32/35 processed.
Page 33/35 processed.
Page 34/35 processed.
Page 35/35 processed.
wp_hnkp_hime_char_word_rels: 466685
wp_hnkp_hime_chars: 13033
wp_hnkp_hime_word_details: 193918
wp_hnkp_hime_words: 172504
```