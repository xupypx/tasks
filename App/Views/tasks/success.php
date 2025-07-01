<?php use core\View;
/** @var array $task */
View::set('title', 'Задача создана');
View::append('styles', '<link rel="stylesheet" href="/public/assets/css/login-dark.css">');
View::append('attributes', 'class="login uk-cover-container uk-background-secondary uk-flex uk-flex-center uk-flex-middle uk-height-viewport uk-overflow-hidden uk-light" data-uk-height-viewport');
?>
<div class="uk-width-2xlarge uk-padding-small uk-position-z-index" uk-scrollspy="cls: uk-animation-fade">
    <h1 class="uk-heading-medium">Задача создана</h1>

    <ul class="uk-list uk-list-divider">
        <li><strong>Название:</strong> <?= htmlspecialchars($task['title']) ?></li>
        <li><strong>Описание:</strong> <?= empty($task['description'] ?? null) ? 'Пока отсутствует!' : nl2br(htmlspecialchars($task['description'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) ?></li>
         <?php if (empty($managers)): ?>
        <li><strong>Исполнители:</strong> не назначены</li>
        <?php else: ?>
        <li><strong>Исполнители: </strong><?= htmlspecialchars(implode(', ', array_column($managers, 'username')), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></li>
        <?php endif; ?>
    </ul>

    <a href="/tasks/create" class="uk-button uk-button-default">Создать ещё</a>
    <a href="/dashboard" class="uk-button uk-button-default">Панель</a>
</div>
