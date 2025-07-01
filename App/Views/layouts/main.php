<?php use core\View;?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= View::get('title', 'Мой сайт') ?></title>
    <link rel="icon" href="/public/assets/img/favicon.ico">

    <!-- Meta -->
    <?php if (!empty($meta['description'])): ?>
    <meta name="description" content="<?= htmlspecialchars($meta['description']) ?>">
    <?php endif; ?>
    <?php if (!empty($meta['keywords'])): ?>
    <meta name="keywords" content="<?= htmlspecialchars($meta['keywords']) ?>">
    <?php endif; ?>

    <!-- Open Graph (пример) -->
    <?php if (!empty($meta['og:title'])): ?>
    <meta property="og:title" content="<?= htmlspecialchars($meta['og:title']) ?>">
    <?php endif; ?>
    <!-- Добавишь остальные OG по необходимости -->

    <!-- CSS -->
    <link rel="stylesheet" href="/public/assets/css/uikit.min.css">
    <?= View::stack('styles') ?>
</head>

<body <?= View::stack('attributes') ?>>
<?= View::get('content') ?>

<script src="/public/assets/js/uikit.min.js"></script>
<script src="/public/assets/js/uikit-icons.min.js"></script>
<?= View::stack('scripts') ?>

</body>
</html>

