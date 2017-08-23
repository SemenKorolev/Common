<?php

$version = config('global.version');
if (!empty($settings['cache'])) {
    echo '<?php $version = config(\'global.version\'); ?>';
    $version = '<?=$version?>';
}

?>
<!DOCTYPE html>
<html lang="<?=config('global.lang')?>">
<head prefix="og: http://ogp.me/ns#">
    <title><?=(!empty($title) ? $title . APPEND_TITLE : '')?></title>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1">
    <?php if (!empty($keywords)): ?><meta name="keywords" content="<?=fesc($keywords)?>"><?php endif; ?>
    <?php if (!empty($description)): ?><meta name="description" content="<?=fesc($description)?>"><?php endif; ?>
    <?php if (IS_DEV): ?>
        <link rel="stylesheet" href="/css/style.css?<?=mt_rand()?>" type="text/css">
    <?php else: ?>
        <link rel="stylesheet" href="/css/all.css?<?=$version?>" type="text/css">
    <?php endif; ?>
    <?php if (IS_DEV): ?>
        <script type="text/javascript" src="/js/jquery-3.2.1.min.js?<?=mt_rand()?>"></script>
        <script type="text/javascript" src="/js/jquery.form.min.js?<?=mt_rand()?>"></script>
        <script type="text/javascript" src="/js/script.js?<?=mt_rand()?>"></script>
    <?php else: ?>
        <script type="text/javascript" src="/js/all.js?<?=$version?>"></script>
    <?php endif; ?>
    <?php if (!empty($linkprev)): ?><link rel="prev" href="<?=fesc($linkprev)?>"><?php endif; ?>
    <?php if (!empty($linknext)): ?><link rel="next" href="<?=fesc($linknext)?>"><?php endif; ?>
</head>
<body class="<?=$_route?>">
</body>
</html>
