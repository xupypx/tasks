<?php
/** @var string $title */
/** @var string $csrf_token */
use core\View;
?>
<h1><?= htmlspecialchars($title) ?></h1>

<?php if (!empty($_SESSION['error'])): ?>
    <div style="color: red;">
        <?= htmlspecialchars($_SESSION['error']) ?>
        <?php unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>

<form action="/tasks/store" method="post">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

    <label>Проект ID:
        <input type="number" name="project_id" required>
    </label><br>

    <label>Заголовок задачи:
        <input type="text" name="title" required>
    </label><br>

    <label>Описание:
        <textarea name="description"></textarea>
    </label><br>

    <label>Статус:
        <select name="status">
            <option value="новая">Новая</option>
            <option value="в работе">В работе</option>
            <option value="выполнена">Выполнена</option>
        </select>
    </label><br>

    <button type="submit">Создать</button>
</form>
