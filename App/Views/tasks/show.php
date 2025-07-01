<?php use core\View;
/** @var array $task */
View::set('title', 'Задача: '.htmlspecialchars($task['title']));
View::append('styles', '<link rel="stylesheet" href="/public/assets/css/login-dark.css">');
View::append('attributes', 'class="login uk-cover-container uk-background-secondary uk-flex uk-flex-center uk-flex-middle uk-height-viewport uk-overflow-hidden uk-light" data-uk-height-viewport');
?>

    <div class="uk-width-2xlarge uk-padding-small uk-position-z-index" uk-scrollspy="cls: uk-animation-fade">
        <h2>Задача: <?= htmlspecialchars($task['title']) ?><a href="/tasks/edit/<?= htmlspecialchars($task['id']) ?>" id="uk-nav-3" role="button" aria-controls="uk-nav-4" aria-expanded="false" aria-disabled="false"><span class="uk-margin-small-left uk-icon" data-uk-icon="icon: pencil; ratio: 1.5"></span></a></h2>

        <p><strong>Описание:</strong> <?= htmlspecialchars($task['description']) ?></p>
        <p><strong>Статус:</strong> <?= htmlspecialchars($task['status']) ?></p>
        <p><strong>Дата создания:</strong> <?= htmlspecialchars($task['created_at']) ?></p>
        <?php if (empty($managers)): ?>
        <p><strong>Исполнители:</strong> не назначены</p>
        <?php else: ?>
        <p><strong>Исполнители: </strong><?= htmlspecialchars(implode(', ', array_map(function($manager) {
    return $manager['username'] . ' (' . $manager['email'] . ')';
}, $managers)), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
        <?php endif; ?>


        <!-- Добавьте дополнительные данные или кнопки для редактирования, если нужно -->
    </div>
