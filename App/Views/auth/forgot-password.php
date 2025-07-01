<?php View::start('auth'); ?>
<h2>Восстановление пароля</h2>

<?php if (!empty($_SESSION['error'])): ?>
    <div class="uk-alert-danger" uk-alert><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
<?php endif; ?>

<form method="POST" action="/forgot-password">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
    <div class="uk-margin">
        <label class="uk-form-label">Email</label>
        <div class="uk-form-controls">
            <input class="uk-input" type="email" name="email" required>
        </div>
    </div>
    <button type="submit" class="uk-button uk-button-primary">Отправить</button>
</form>
<?php View::end(); ?>
