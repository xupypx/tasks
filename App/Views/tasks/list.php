<?php
use core\View;

/** @var array $task */
/** @var array $tasks */
/** @var string $title */
/** @var string|null $error */
/** @var string|null $success */

// Устанавливаем заголовок страницы с защитой от неопределенных значений
View::set('title', 'Задача: ' . htmlspecialchars($task['title'] ?? 'Без названия'));

// Добавляем стили и атрибуты
View::append('styles', '<link rel="stylesheet" href="/public/assets/css/login-dark.css">');
View::append('attributes', 'class="login uk-cover-container uk-background-secondary uk-flex uk-flex-center uk-flex-middle uk-height-viewport uk-overflow-hidden uk-light" data-uk-height-viewport');
?>





<div class="uk-width-2xlarge uk-padding-small uk-position-z-index" uk-scrollspy="cls: uk-animation-fade">
    <h2><?= htmlspecialchars($title ?? 'Список задач') ?></h2>

    <!-- Обработка ошибок и успеха -->
    <?php if (!empty($error)): ?>
        <div class="uk-alert-danger" uk-alert>
            <a class="uk-alert-close" uk-close></a>
            <p><?= htmlspecialchars($error) ?></p>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="uk-alert-success" uk-alert>
            <a class="uk-alert-close" uk-close></a>
            <p><?= htmlspecialchars($success) ?></p>
        </div>
    <?php endif; ?>

    <!-- Отображение задач -->
    <?php if (empty($tasks)): ?>
        <div class="uk-alert-warning" uk-alert>
            <p>Задачи отсутствуют.</p>
        </div>
    <?php else: ?>
<!--         <div class="uk-card uk-card-default uk-card-body"> -->
            <div>
            <ul class="uk-list uk-list-divider">
                <?php foreach ($tasks as $taskItem): ?>
                    <li>
                        <a href="/tasks/<?= htmlspecialchars($taskItem['id'] ?? '') ?>" class="uk-link-reset">
                            <div class="uk-grid-small" uk-grid>
                                <div class="uk-width-expand">
                                    <h3 class="uk-card-title uk-margin-remove-bottom">
                                        <?= htmlspecialchars($taskItem['title'] ?? 'Без названия') ?>
                                    </h3>
                                    <span class="uk-text-meta">
                                        Статус: <?= htmlspecialchars($taskItem['status'] ?? 'Не указан') ?>
                                        Исполнители: <?= htmlspecialchars(implode(', ', array_map(function($manager) {
            return $manager['username'] . ' (' . $manager['email'] . ')';
        }, $taskItem['managers'])), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>

                                    </span>
                                </div>
                                <div class="uk-width-auto">
                                    <span class="uk-badge">
                                        ID: <?= htmlspecialchars($taskItem['id'] ?? '') ?>
                                    </span>
                                </div>
                            </div>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>
