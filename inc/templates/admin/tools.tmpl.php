<?php
?>
<div class="wrap">
    <h1>데이터 도구</h1>
    <hr class="wp-header-end"/>

    <h3>CSV 불러오기</h3>
    <div>
        <p>한자 템플릿 작업으로 생성한 N3, N4, N5 CSV 결과 파일을 데이터베이스로 불러오는 도구 실행</p>
        <form id="csv-import"
              name="csv"
              action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
              method="post"
              enctype="multipart/form-data">
            <input type="FILE" accept="text/csv" name="csv" required/>
            <?php wp_nonce_field('hnkp_csv_import', '_hnkp_nonce'); ?>
            <input type="hidden" name="action" value="hnkp_csv_import"/>
            <input class="button button-primary" type="submit" value="실행하기"/>
        </form>
    </div>
</div>
