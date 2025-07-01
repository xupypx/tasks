<?php
use core\View;
$assignedManagerIds = $assignedManagerIds ?? [];
/** @var array $task */
/** @var string $csrf_token */
/** @var string $managers */
/** @var $assignedManagerIds */
View::set('title', 'Задача: '.htmlspecialchars($task['title']));
View::append('styles', '<link rel="stylesheet" href="/public/assets/css/login-dark.css">');
View::append('attributes', 'class="login uk-cover-container uk-background-secondary uk-flex uk-flex-center uk-flex-middle uk-height-viewport uk-overflow-hidden uk-light" data-uk-height-viewport');
View::append('scripts', <<<'JS'
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Основные элементы
    const button = document.getElementById('var_but');
    const checkboxes = document.querySelectorAll('input[name="managers[]"]');
    const form = document.getElementById('edit_form');
    const form_del = document.getElementById('del_tasker');

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
    <h2>Редактировать задачу: <?= htmlspecialchars($task['title']) ?></h2>
<?php if ($user['role'] === 'admin'): ?>
    <!-- Кнопка для удаления задачи -->
    <form method="post" action="/tasks/delete/<?= htmlspecialchars($task['id']) ?>" onsubmit="return confirm('Вы уверены, что хотите удалить эту задачу?');">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <button type="submit" class="uk-button uk-border-rounded uk-button-danger">Удалить задачу</button>
     </form>
<?php endif; ?>
    <!-- Форма редактирования -->
    <form id="edit_form" class="toggle-class" method="POST" action="/tasks/update/<?= htmlspecialchars($task['id']) ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

        <fieldset class="uk-fieldset">
            <div class="uk-margin">
                <label class="uk-form-label" for="title">Название</label>
                <div class="uk-form-controls">
                    <input class="uk-input" type="text" name="title" id="title" value="<?= htmlspecialchars($task['title']) ?>" required>
                </div>
            </div>

            <div class="uk-margin">
                <label class="uk-form-label" for="description">Описание:</label>
                <div class="uk-form-controls">
                    <textarea class="uk-textarea" rows="5" placeholder="Описание" aria-label="Описание" name="description" id="description" required><?= htmlspecialchars($task['description']) ?></textarea>
                </div>
            </div>

            <div class="uk-child-width-1-2@s uk-text-left uk-margin-large-bottom" uk-grid>
                <div>
                    <div class="uk-margin">
                        <label class="uk-form-label" for="status">Статус:</label>
                        <div class="uk-form-controls">
                            <div uk-form-custom="target: > * > span:last-child">
                                <select name="status" id="status">
                                    <option value="новая" <?= $task['status'] === 'новая' ? 'selected' : '' ?>>Новая</option>
                                    <option value="в работе" <?= $task['status'] === 'в работе' ? 'selected' : '' ?>>В работе</option>
                                    <option value="выполнена" <?= $task['status'] === 'выполнена' ? 'selected' : '' ?>>Выполнена</option>
                                </select>
                                <span class="uk-link">
                                    <span uk-icon="icon: pencil"></span>
                                    <span></span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div>

                    <div class="uk-margin">
                        <label class="uk-form-label" for="managers">Исполнитель:</label>
                        <div class="uk-form-controls">
                            <!-- Создаем выпадающий список с чекбоксами -->
                            <div class="uk-inline">
                            <button id="var_but" class="uk-button uk-button-default" type="button">Выберите менеджеров</button>

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

                </div>
            </div>

            <div class="uk-margin-bottom uk-flex uk-flex-center">
                <button type="submit" class="uk-button uk-button-primary uk-border-pill uk-width-1-2">Сохранить изменения</button>
            </div>
        </fieldset>

    </form>

</div>

