<?php

use function HemeNihongo\KanjiPlugin\assets_url;

/**
 * 한자 템플릿 V1
 *
 * 컨텍스트 변수
 * ----------
 *
 * @var string         $level_title 급수 이름. e.g. JLPT N3
 * @var array<Kanji[]> $items       페이지 처리된 학습 한자 목록
 */
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <title>한자 템플릿 목업</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="../../assets/js/tailwind4.min.js"></script>
    <link rel="stylesheet" href="../../assets/css/paper.min.css"/>
    <link rel="icon" type="image/png" href="../../assets/img/favicon-16.png" sizes="16x16"/>
    <link rel="icon" type="image/png" href="../../assets/img/favicon-32.png" sizes="32x32"/>
    <link rel="icon" type="image/png" href="../../assets/img/favicon-48.png" sizes="48x48"/>
    <link rel="icon" type="image/png" href="../../assets/img/favicon-96.png" sizes="96x96"/>
    <link rel="icon" type="image/png" href="../../assets/img/favicon-512.png" sizes="512x512"/>
    <link rel="apple-touch-icon" href="../../assets/img/apple-touch-icon-180.png"/>
    <link rel="icon" type="image/png" href="../../assets/img/android-icon-192.png" sizes="192x19x"/>
    <link rel="shortcut icon" type="image/x-icon" href="../../assets/img/favicon.ico"/>
    <link rel="stylesheet" href="../../assets/css/fonts.css"/>
    <style>
        @page {
            size: A4;
        }
    </style>
</head>
<body class="A4 font-noto-ko">
<div class="sheet pt-[10mm] pb-[6mm] px-[5mm]">
    <div class="relative h-full">
        <header>
            <h1 class="text-[18pt] font-bold text-center mb-[9mm]">
                <span class="current-level">JLPT N3</span>
                한자 연습
                <span class="current-page">1</span>/<span class="total-page">20</span>
            </h1>
        </header>
        <main>
            <!-- 한자 하나의 섹션 配 -->
            <section class="border-t border-dotted flex m-6">
                <div class="border-0 border-dotted grow-0 shrink-0 ps-0 pe-3 pb-2">
                    <!-- 학습할 한자 -->
                    <p class="font-mincho text-[52pt]">配</p>
                    <!-- 한국어 훈과 음 -->
                    <p class="text-center text-[11pt]">나눌 배</p>
                </div>

                <!-- 配 일본어 음독/훈독 -->
                <div class="border-0 border-dotted p-2 shrink-0 w-1/6">
                    <div class="flex flex-col gap-y-2">
                        <div class="font-mincho">
                            <p class=""><span class="text-[8pt] border border-dotted rounded-[8px] px-1">音</span></p>
                            <p class="break-keep text-[10pt]">はい</p>
                        </div>
                        <div class="font-mincho">
                            <p class=""><span class="text-[8pt] border border-dotted rounded-[8px] px-1">訓</span></p>
                            <p class="break-keep text-[10pt]">くばる</p>
                        </div>
                    </div>
                </div>

                <!-- 配 활용 -->
                <div class="ps-2 grow w-full">
                    <ul class="grid grid-cols-[repeat(2,auto_auto_2fr)] leading-7">
                        <li class="rei-kanji font-noto-jp text-[12pt] me-1">気配</li>
                        <li class="rei-hiragana font-noto-jp text-[10pt] me-2">けはい</li>
                        <li class="rei-meaning text-[10pt]">낌새</li>
                        <li class="rei-kanji font-noto-jp text-[12pt] me-1">配達</li>
                        <li class="rei-hiragana font-noto-jp text-[10pt] me-2">はいたつ</li>
                        <li class="rei-meaning text-[10pt]">배달</li>
                        <li class="rei-kanji font-noto-jp text-[12pt] me-1">支配</li>
                        <li class="rei-hiragana font-noto-jp text-[10pt] me-2">しはい</li>
                        <li class="rei-meaning text-[10pt]">지배</li>
                        <li class="rei-kanji font-noto-jp text-[12pt] me-1">配る</li>
                        <li class="rei-hiragana font-noto-jp text-[10pt] me-2">くばる</li>
                        <li class="rei-meaning text-[10pt]">나눠주다</li>
                        <li class="rei-kanji font-noto-jp text-[12pt] me-1">心配</li>
                        <li class="rei-hiragana font-noto-jp text-[10pt] me-2">しんぱい</li>
                        <li class="rei-meaning text-[10pt]">걱정</li>
                        <li class="rei-kanji font-noto-jp text-[12pt] me-1">配布</li>
                        <li class="rei-hiragana font-noto-jp text-[10pt] me-2">はいふ</li>
                        <li class="rei-meaning text-[10pt]">배포</li>
                    </ul>
                </div>
            </section>

            <!-- 한자 하나의 섹션 番 -->
            <section class="border-t border-dotted flex m-6">
                <div class="border-0 border-dotted grow-0 shrink-0 ps-0 pe-3 pb-2">
                    <!-- 학습할 한자 -->
                    <p class="font-mincho text-[52pt]">番</p>
                    <!-- 한국어 훈과 음 -->
                    <p class="text-center text-[11pt]">차례 번</p>
                </div>

                <!-- 일본어 음독/훈독 -->
                <div class="border-0 border-dotted p-2 shrink-0 w-1/6">
                    <div class="flex flex-col gap-y-2">
                        <div class="font-noto-jp">
                            <p class=""><span class="text-[8pt] border border-dotted rounded-[8px] px-1">音</span></p>
                            <p class="break-keep text-[10pt]">ばん</p>
                        </div>
                        <div class="font-noto-jp">
                            <p class=""><span class="text-[8pt] border border-dotted rounded-[8px] px-1">訓</span></p>
                            <p class="break-keep text-[10pt]">-</p>
                        </div>
                    </div>
                </div>

                <!-- 番 활용 -->
                <div class="ps-2 grow w-full">
                    <ul class="grid grid-cols-[repeat(2,auto_auto_2fr)] leading-7">
                        <li class="rei-kanji font-noto-jp text-[12pt] me-1">交番</li>
                        <li class="rei-hiragana font-noto-jp text-[10pt] me-2">こうばん</li>
                        <li class="rei-meaning text-[10pt]">파출소</li>
                        <li class="rei-kanji font-noto-jp text-[12pt] me-1">順番</li>
                        <li class="rei-hiragana font-noto-jp text-[10pt] me-2">じゅんばん</li>
                        <li class="rei-meaning text-[10pt]">순번</li>
                        <li class="rei-kanji font-noto-jp text-[12pt] me-1">番号</li>
                        <li class="rei-hiragana font-noto-jp text-[10pt] me-2">ばんごう</li>
                        <li class="rei-meaning text-[10pt]">번호</li>
                        <li class="rei-kanji font-noto-jp text-[12pt] me-1">番組</li>
                        <li class="rei-hiragana font-noto-jp text-[10pt] me-2">ばんぐみ</li>
                        <li class="rei-meaning text-[10pt]">TV 프로그램</li>
                        <li class="rei-kanji font-noto-jp text-[12pt] me-1">番地</li>
                        <li class="rei-hiragana font-noto-jp text-[10pt] me-2">ばんち</li>
                        <li class="rei-meaning text-[10pt]">번지</li>
                        <li class="rei-kanji font-noto-jp text-[12pt] me-1">当番</li>
                        <li class="rei-hiragana font-noto-jp text-[10pt] me-2">とうばん</li>
                        <li class="rei-meaning text-[10pt]">당번</li>
                    </ul>
                </div>
            </section>

            <!-- 한자 하나의 섹션 法 -->
            <section class="border-t border-dotted flex m-6">
                <div class="border-0 border-dotted grow-0 shrink-0 ps-0 pe-3 pb-2">
                    <!-- 학습할 한자 -->
                    <p class="font-mincho text-[52pt]">法</p>
                    <!-- 한국어 훈과 음 -->
                    <p class="text-center text-[11pt]">법 법</p>
                </div>

                <!-- 일본어 음독/훈독 -->
                <div class="border-0 border-dotted p-2 shrink-0 w-1/6">
                    <div class="flex flex-col gap-y-2">
                        <div class="font-noto-jp">
                            <p class=""><span class="text-[8pt] border border-dotted rounded-[8px] px-1">音</span></p>
                            <p class="break-keep text-[10pt]">ほう</p>
                        </div>
                        <div class="font-noto-jp">
                            <p class=""><span class="text-[8pt] border border-dotted rounded-[8px] px-1">訓</span></p>
                            <p class="break-keep text-[10pt]">-</p>
                        </div>
                    </div>
                </div>

                <!-- 法 활용 -->
                <div class="ps-2 grow w-full">
                    <ul class="grid grid-cols-[repeat(2,auto_auto_2fr)] leading-7">
                        <li class="rei-kanji font-noto-jp text-[12pt] me-1">法律</li>
                        <li class="rei-hiragana font-noto-jp text-[10pt] me-2">ほうりつ</li>
                        <li class="rei-meaning text-[10pt]">법률</li>
                        <li class="rei-kanji font-noto-jp text-[12pt] me-1">方法</li>
                        <li class="rei-hiragana font-noto-jp text-[10pt] me-2">ほうほう</li>
                        <li class="rei-meaning text-[10pt]">방법</li>
                        <li class="rei-kanji font-noto-jp text-[12pt] me-1">文法</li>
                        <li class="rei-hiragana font-noto-jp text-[10pt] me-2">ぶんぽう</li>
                        <li class="rei-meaning text-[10pt]">문법</li>
                        <li class="rei-kanji font-noto-jp text-[12pt] me-1">法則</li>
                        <li class="rei-hiragana font-noto-jp text-[10pt] me-2">ほうそく</li>
                        <li class="rei-meaning text-[10pt]">법칙</li>
                        <li class="rei-kanji font-noto-jp text-[12pt] me-1">作法</li>
                        <li class="rei-hiragana font-noto-jp text-[10pt] me-2">さほう</li>
                        <li class="rei-meaning text-[10pt]">작법, 범절</li>
                        <li class="rei-kanji font-noto-jp text-[12pt] me-1">法</li>
                        <li class="rei-hiragana font-noto-jp text-[10pt] me-2">ほう</li>
                        <li class="rei-meaning text-[10pt]">법</li>
                    </ul>
                </div>
            </section>

            <!-- 한자 하나의 섹션 変 -->
            <section class="border-t border-dotted flex m-6">
                <div class="border-0 border-dotted grow-0 shrink-0 ps-0 pe-3 pb-2">
                    <!-- 학습할 한자 -->
                    <p class="font-mincho text-[52pt]">変</p>
                    <!-- 한국어 훈과 음 -->
                    <p class="text-center text-[11pt]">변할 변</p>
                </div>

                <!-- 일본어 음독/훈독 -->
                <div class="border-0 border-dotted p-2 shrink-0 w-1/6">
                    <div class="flex flex-col gap-y-2">
                        <div class="font-noto-jp">
                            <p class=""><span class="text-[8pt] border border-dotted rounded-[8px] px-1">音</span></p>
                            <p class="break-keep text-[10pt]">へん</p>
                        </div>
                        <div class="font-noto-jp">
                            <p class=""><span class="text-[8pt] border border-dotted rounded-[8px] px-1">訓</span></p>
                            <p class="break-keep text-[10pt]">かわる, かえる</p>
                        </div>
                    </div>
                </div>

                <!-- 変 활용 -->
                <div class="ps-2 grow w-full">
                    <ul class="grid grid-cols-[repeat(2,auto_auto_2fr)] leading-7">
                        <li class="rei-kanji font-noto-jp text-[12pt] me-1">変化</li>
                        <li class="rei-hiragana font-noto-jp text-[10pt] me-2">へんか</li>
                        <li class="rei-meaning text-[10pt]">변화</li>
                        <li class="rei-kanji font-noto-jp text-[12pt] me-1">変身</li>
                        <li class="rei-hiragana font-noto-jp text-[10pt] me-2">へんしん</li>
                        <li class="rei-meaning text-[10pt]">변신</li>
                        <li class="rei-kanji font-noto-jp text-[12pt] me-1">変心</li>
                        <li class="rei-hiragana font-noto-jp text-[10pt] me-2">へんしん</li>
                        <li class="rei-meaning text-[10pt]">변심</li>
                        <li class="rei-kanji font-noto-jp text-[12pt] me-1">変更</li>
                        <li class="rei-hiragana font-noto-jp text-[10pt] me-2">へんこう</li>
                        <li class="rei-meaning text-[10pt]">변경</li>
                        <li class="rei-kanji font-noto-jp text-[12pt] me-1">大変</li>
                        <li class="rei-hiragana font-noto-jp text-[10pt] me-2">たいへん</li>
                        <li class="rei-meaning text-[10pt]">무척 힘듦</li>
                        <li class="rei-kanji font-noto-jp text-[12pt] me-1">変わる</li>
                        <li class="rei-hiragana font-noto-jp text-[10pt] me-2">かわる</li>
                        <li class="rei-meaning text-[10pt]">바뀌다</li>
                        <li class="rei-kanji font-noto-jp text-[12pt] me-1">変える</li>
                        <li class="rei-hiragana font-noto-jp text-[10pt] me-2">かえる</li>
                        <li class="rei-meaning text-[10pt]">바꾸다</li>
                        <li class="rei-kanji font-noto-jp text-[12pt] me-1">変だ</li>
                        <li class="rei-hiragana font-noto-jp text-[10pt] me-2">へんだ</li>
                        <li class="rei-meaning text-[10pt]">이상하다</li>
                    </ul>
                </div>
            </section>

            <!-- 한자 하나의 섹션 普 -->
            <section class="border-t border-dotted flex m-6">
                <div class="border-0 border-dotted grow-0 shrink-0 ps-0 pe-3 pb-2">
                    <!-- 학습할 한자 -->
                    <p class="font-mincho text-[52pt]">普</p>
                    <!-- 한국어 훈과 음 -->
                    <p class="text-center text-[11pt]">널리 보</p>
                </div>

                <!-- 일본어 음독/훈독 -->
                <div class="border-0 border-dotted p-2 shrink-0 w-1/6">
                    <div class="flex flex-col gap-y-2">
                        <div class="font-noto-jp">
                            <p class=""><span class="text-[8pt] border border-dotted rounded-[8px] px-1">音</span></p>
                            <p class="break-keep text-[10pt]">ふ</p>
                        </div>
                        <div class="font-noto-jp">
                            <p class=""><span class="text-[8pt] border border-dotted rounded-[8px] px-1">訓</span></p>
                            <p class="break-keep text-[10pt]">-</p>
                        </div>
                    </div>
                </div>

                <!-- 普 활용 -->
                <div class="ps-2 grow w-full">
                    <ul class="grid grid-cols-[repeat(2,auto_auto_2fr)] leading-7">
                        <li class="rei-kanji font-noto-jp text-[12pt] me-1">普通</li>
                        <li class="rei-hiragana font-noto-jp text-[10pt] me-2">ふつう</li>
                        <li class="rei-meaning text-[10pt]">보통</li>
                        <li class="rei-kanji font-noto-jp text-[12pt] me-1">普及</li>
                        <li class="rei-hiragana font-noto-jp text-[10pt] me-2">ふきゅう</li>
                        <li class="rei-meaning text-[10pt]">보급</li>
                        <li class="rei-kanji font-noto-jp text-[12pt] me-1">普段</li>
                        <li class="rei-hiragana font-noto-jp text-[10pt] me-2">ふだん</li>
                        <li class="rei-meaning text-[10pt]">평소</li>
                    </ul>
                </div>
            </section>
        </main>
        <footer class="absolute bottom-0 left-0 w-full">
            <h3 class="flex items-center justify-center text-sm font-bold">
                <span class="me-4">히메 일본어 교실</span>
                <img class="inline"
                     src="../../assets/img/instagram.svg"
                     width="20"
                     height="20"
                     alt="Instagram logo"/>
                <span class="text-xs">hime_nihongo</span>
            </h3>
        </footer>
    </div>
</div>
</body>
</html>
