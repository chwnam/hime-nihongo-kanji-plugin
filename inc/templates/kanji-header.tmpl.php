<?php
/**
 * @var Bojaghi\Template\Template $this
 *
 * Context:
 *   title
 */

use function HemeNihongo\KanjiPlugin\assets_url;
?>

<title>
    <?php echo esc_html($this->get('title')); ?>
</title>

<script src="<?php echo esc_url(assets_url('js/tailwind4.min.js')); ?>"></script>
<link rel="stylesheet" href="<?php echo esc_url(assets_url('css/paper.min.css')); ?>"/>
<link rel="icon" type="image/png" href="<?php echo esc_url(assets_url('img/favicon-16.png')); ?>" sizes="16x16"/>
<link rel="icon" type="image/png" href="<?php echo esc_url(assets_url('img/favicon-32.png')); ?>" sizes="32x32"/>
<link rel="icon" type="image/png" href="<?php echo esc_url(assets_url('img/favicon-48.png')); ?>" sizes="48x48"/>
<link rel="icon" type="image/png" href="<?php echo esc_url(assets_url('img/favicon-96.png')); ?>" sizes="96x96"/>
<link rel="icon" type="image/png" href="<?php echo esc_url(assets_url('img/favicon-512.png')); ?>" sizes="512x512"/>
<link rel="apple-touch-icon" href="<?php echo esc_url(assets_url('img/apple-touch-icon-180.png')); ?>"/>
<link rel="icon" type="image/png" href="<?php echo esc_url(assets_url('img/android-icon-192.png')); ?>" sizes="192x19x"/>
<link rel="shortcut icon" type="image/x-icon" href="<?php echo esc_url(assets_url('img/favicon.ico')); ?>"/>
<link rel="stylesheet" href="<?php echo esc_url(assets_url('css/fonts.css')); ?>"/>
<style>
    @page {
        size: A4;
    }
</style>