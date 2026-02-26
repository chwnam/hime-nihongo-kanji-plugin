<?php
/**
 * @var Bojaghi\Template\Template $this
 *
 * Context:
 * - level_title
 * - items
 */

use function HemeNihongo\KanjiPlugin\assets_url;

?>

<!-- 페이지 시작 .sheet -->
<?php foreach ($this->get('items', []) as $idx => $paged_items) : ?>
    <div class="sheet pt-[10mm] pb-[6mm] px-[5mm]">
        <div class="relative h-full">
            <header>
                <h1 class="text-[18pt] font-bold text-center mb-[9mm]">
                    <span class="current-level"><?php echo esc_html($this->get('level_title')); ?></span> 한자 연습
                    <span class="current-page"><?php echo absint($idx + 1); ?></span>/<span
                        class="total-page"><?php echo absint(count($this->get('items', []))); ?></span>
                </h1>
            </header>
            <main>
                <?php
                foreach ($paged_items as $item): ?>
                    <!-- <?php echo esc_html($item->kanji); ?> 섹션 시작 -->
                    <section class="border-t border-dotted flex m-6">
                        <!-- <?php echo esc_html($item->kanji); ?> 소개 -->
                        <div class="border-0 border-dotted grow-0 shrink-0 ps-0 pe-3 pb-2">
                            <!-- 한자 -->
                            <p class="font-mincho text-[52pt]"><?php echo esc_html($item->kanji); ?></p>
                            <!-- 한국어 훈-음 -->
                            <p class="text-center text-[11pt]"><?php echo esc_html($item->meaning); ?></p>
                            <!-- TODO: 음이 길어지면 콤마로 분리 후 강제 개행 -->
                        </div>

                        <!-- <?php echo esc_html($item->kanji); ?> 일본어 음-훈 -->
                        <div class="border-0 border-dotted p-2 shrink-0 w-1/6">
                            <div class="flex flex-col gap-y-2">
                                <div class="font-mincho">
                                    <p class="">
                                        <span class="text-[8pt] border border-dotted rounded-[8px] px-1">音</span>
                                    </p>
                                    <p class="break-keep text-[10pt]"><?php echo esc_html($item->on_yomi); ?></p>
                                </div>
                                <div class="font-mincho">
                                    <p class="">
                                        <span class="text-[8pt] border border-dotted rounded-[8px] px-1">訓</span>
                                    </p>
                                    <p class="break-keep text-[10pt]"><?php echo esc_html($item->kun_yomi); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- <?php echo esc_html($item->kanji); ?> 활용 -->
                        <div class="ps-2 grow w-full">
                            <ul class="grid grid-cols-[repeat(2,auto_auto_2fr)] leading-7">
                                <?php
                                foreach ($item->rei_items as $rei) : ?>
                                    <li class="rei-kanji font-noto-jp text-[12pt] me-1"><?php echo esc_html($rei->word); ?></li>
                                    <li class="rei-hiragana font-noto-jp text-[10pt] me-2"><?php echo esc_html($rei->yomikata); ?></li>
                                    <li class="rei-meaning text-[10pt]"><?php echo esc_html($rei->meaning); ?></li>
                                <?php
                                endforeach; ?>
                            </ul>
                        </div>
                    </section>
                    <!-- <?php echo esc_html($item->kanji); ?> 섹션 끝 -->
                <?php
                endforeach; ?>
            </main>
            <footer class="absolute bottom-0 left-0 w-full">
                <h3 class="flex items-center justify-center text-sm font-bold">
                    <span class="me-4">히메 일본어 교실</span>
                    <img class="inline"
                         src="<?php echo esc_url(assets_url('img/instagram.svg')); ?>"
                         width="20"
                         height="20"
                         alt="Instagram logo"/>
                    <span class="text-xs">hime_nihongo</span>
                </h3>
            </footer>
        </div>
    </div>
<?php
endforeach; ?>
<!-- 페이지 끝 .sheet -->