<?php use core\View;
/** @var string $csrf_token */
?>
<div class="uk-section uk-section-primary uk-light">
    <div class="uk-container">
        <h2>Регистрация</h2>
        <form method="post" action="/register">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            Имя пользователя: <input type="text" name="username" required><br>
            Пароль: <input type="password" name="password" required><br>
            <button type="submit">Зарегистрироваться</button>
        </form>
    </div>
</div>
