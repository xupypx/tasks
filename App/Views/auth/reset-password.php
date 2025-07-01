<?php View::start('auth'); ?>
<h2>Сброс пароля</h2>

<?php if (!empty($_SESSION['error'])): ?>
    <div class="uk-alert-danger" uk-alert><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
<?php endif; ?>

<form method="POST" action="/reset-password">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

    <div class="uk-margin">
        <label class="uk-form-label">Новый пароль</label>
        <div class="uk-form-controls">
            <input class="uk-input" type="password" name="password" required>
        </div>
    </div>

    <button type="submit" class="uk-button uk-button-primary">Сохранить</button>
</form>
<?php View::end(); ?>
