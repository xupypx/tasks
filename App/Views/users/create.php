<?php
/** @var string $title */
/** @var string $csrf_token */
use core\View;
View::set('title', htmlspecialchars($title));
View::append('styles', '<link rel="stylesheet" href="/public/assets/css/login-dark.css">');
View::append('attributes', 'class="login uk-cover-container uk-background-secondary uk-flex uk-flex-center uk-flex-middle uk-height-viewport uk-overflow-hidden uk-light" data-uk-height-viewport');
?>
<!-- overlay -->
<div class="uk-position-cover uk-overlay-primary"></div>
<!-- /overlay -->
<div class="uk-position-bottom-center uk-position-small uk-visible@m uk-position-z-index">
    <span class="uk-text-small uk-text-muted">© <?=date('Y')?> Company Name - <a href="https://webhat.by">Created by Xupypx</a></span>
</div>

<div class="uk-width-xlarge uk-padding-small uk-position-z-index" uk-scrollspy="cls: uk-animation-fade">

    <div class="uk-text-center uk-margin">
        <a href="/"><img src="/public/assets/img/login-logo.svg" alt="Logo"></a>
    </div>
        <h2 class="uk-heading-divider"><?= htmlspecialchars($title) ?></h2>

        <?php if ($msg = flash('error')): ?>
            <div class="uk-alert-danger" uk-alert><?= htmlspecialchars($msg) ?></div>
        <?php elseif ($msg = flash('success')): ?>
            <div class="uk-alert-success" uk-alert><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <form method="POST" action="/register" class="toggle-class">
        <fieldset class="uk-fieldset">
            <div class="uk-margin">
                <div class="uk-inline uk-width-1-1">
                    <span class="uk-form-icon uk-form-icon-flip" data-uk-icon="icon: user"></span>
                    <input class="uk-input uk-border-pill" required placeholder="Имя пользователя" type="text" name="username">
                </div>
            </div>

            <div class="uk-margin">
                <div class="uk-inline uk-width-1-1">
                    <span class="uk-form-icon uk-form-icon-flip" data-uk-icon="icon: lock"></span>
                    <input class="uk-input uk-border-pill" required placeholder="Пароль" type="password" name="password">
                </div>
            </div>

            <div class="uk-margin">
                <div class="uk-form-controls">
                    <select class="uk-select uk-border-pill" name="role" placeholder="Роль">
                        <option value="user">Пользователь</option>
                        <option value="manager">Менеджер</option>
                        <option value="admin">Администратор</option>
                    </select>
                </div>
            </div>

            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <div class="uk-margin-bottom">
                <button type="submit" class="uk-button uk-button-primary uk-border-pill uk-width-1-1">Создать пользователя</button>
            </div>
            </fieldset>
        </form>
</div>
