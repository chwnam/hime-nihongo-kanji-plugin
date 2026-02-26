<?php
/**
 * Context:
 *
 * title
 * list_table
 *
 * @var Bojaghi\Template\Template                         $this
 * @var HimeNihongo\KanjiPlugin\ListTables\CharsListTable $list_table
 */
$list_table = $this->get('list_table', null);
if (!$list_table) {
    wp_die('올바른 리스트 테이블을 지정하지 않았습니다');
}
$list_table->prepare_items();
?>
<div class="wrap">
    <h1><?php echo esc_html($this->get('title')); ?></h1>
    <hr class="wp-header-end"/>

    <?php $list_table->views(); ?>

    <form id="chars-filter" method="get">
        <?php $list_table->search_box('한자 검색', 'hnkp_chars'); ?>
        <?php $list_table->display(); ?>
    </form>

    <?php
    if ($list_table->has_items()) {
        $list_table->inline_edit();
    }
    ?>

    <div id="ajax-response"></div>
    <div class="clear"></div>
</div>
