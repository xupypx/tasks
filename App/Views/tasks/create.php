<?php
use core\View;
$assignedManagerIds = $assignedManagerIds ?? [];
/** @var array $task */
/** @var string $csrf_token */
/** @var string $managers */
/** @var $assignedManagerIds */
View::set('title', 'Добавить задачу');
View::append('styles', '<link rel="stylesheet" href="/public/assets/css/login-dark.css">');
View::append('attributes', 'class="login uk-cover-container uk-background-secondary uk-flex uk-flex-center uk-flex-middle uk-height-viewport uk-overflow-hidden uk-light" data-uk-height-viewport');
View::append('scripts', <<<'JS'
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Основные элементы
    const button = document.getElementById('var_task');
    const checkboxes = document.querySelectorAll('input[name="managers[]"]');
    const form = document.getElementById('add_task');

    // Проверка элементов
    if (!button || !checkboxes.length || !form) {
        console.error('Required elements not found');
        return;
    }

    // Функция для склонения слов
    function getManagerWord(count) {
        const lastDigit = count % 10;
        const lastTwoDigits = count % 100;

        if (lastTwoDigits >= 11 && lastTwoDigits <= 19) {
            return 'менеджеров';
        }
        if (lastDigit === 1) {
            return 'менеджер';
        }
        if (lastDigit >= 2 && lastDigit <= 4) {
            return 'менеджера';
        }
        return 'менеджеров';
    }

    // Обновление текста кнопки
    function updateButtonText() {
        const selectedCount = Array.from(checkboxes)
            .filter(checkbox => checkbox.checked)
            .length;

        button.textContent = selectedCount > 0
            ? `Выбрано: ${selectedCount} ${getManagerWord(selectedCount)}`
            : 'Выберите менеджеров';
    }

    // Обработчики событий
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateButtonText);
    });

    // Валидация формы
    form.addEventListener('submit', function(e) {
        const selectedCount = Array.from(checkboxes)
            .filter(checkbox => checkbox.checked)
            .length;

        if (selectedCount === 0) {
            e.preventDefault();
            if (typeof UIkit !== 'undefined') {
                UIkit.notification({
                    message: 'Пожалуйста, выберите хотя бы одного менеджера',
                    status: 'warning',
                    pos: 'top-center',
                    timeout: 5000
                });
            } else {
                alert('Пожалуйста, выберите хотя бы одного менеджера');
            }
        }
    });

    // Инициализация
    updateButtonText();
});

</script>
JS
);
?>
<div class="uk-width-2xlarge uk-padding-small uk-position-z-index" uk-scrollspy="cls: uk-animation-fade">
    <h1 class="uk-heading-medium">Новая задача</h1>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="uk-alert-danger" uk-alert>
            <a class="uk-alert-close" uk-close></a>
            <p><?= htmlspecialchars($_SESSION['error']) ?></p>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <form id="add_task" method="post" action="/tasks/store" class="toggle-class">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

        <div class="uk-margin">
            <label for="title" class="uk-form-label">Название задачи</label>
            <div class="uk-form-controls">
                <input id="title" class="uk-input" type="text" name="title" required>
            </div>
        </div>

        <div class="uk-margin">
            <label for="project_id" class="uk-form-label">Проект (ID)</label>
            <div class="uk-form-controls">
                <input id="project_id" class="uk-input" type="number" name="project_id" value="1">
            </div>
        </div>

        <div class="uk-margin">
            <label for="description" class="uk-form-label">Описание</label>
            <div class="uk-form-controls">
                <textarea id="description" class="uk-textarea" name="description" rows="5"></textarea>
            </div>
        </div>

        <div class="uk-margin">
            <label class="uk-form-label" for="status">Статус</label>
            <div class="uk-form-controls">
                <select class="uk-select" name="status" id="status">
                    <option value="новая">Новая</option>
                    <option value="в работе">В работе</option>
                    <option value="завершена">Завершена</option>
                </select>
            </div>
        </div>
        <div class="uk-margin">
            <label class="uk-form-label" for="managers">Исполнители</label>
            <div class="uk-form-controls">
                <!-- Создаем выпадающий список с чекбоксами -->
                <div class="uk-inline">
                <button id="var_task" class="uk-button uk-button-default" type="button">Выберите менеджеров</button>

                    <!-- Содержание выпадающего списка -->
                    <div uk-dropdown="mode: click" class="uk-dropdown">
                        <ul class="uk-list">
                        <?php foreach ($managers as $manager): ?>
                            <li>
                                <label>
                                <input type="checkbox" name="managers[]" value="<?= $manager['id'] ?>"
                                <?= in_array($manager['id'], $assignedManagerIds) ? 'checked' : '' ?>>
                                <?= htmlspecialchars($manager['username']) ?>
                                </label>
                            </li>
                        <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="uk-margin-bottom uk-flex uk-flex-center">
        <button  type="submit" class="uk-button uk-button-primary uk-border-pill uk-width-1-2">Создать</button>
        </div>
    </form>
</div>
