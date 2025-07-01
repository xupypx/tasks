<?php use core\View;
/** @var string $csrf_token */
// Устанавливаем CSRF-токен, если он не передан через render()
// View::set('csrf_token', $_SESSION['csrf_token'] ?? '');
View::set('title', 'Мордоворот');
View::append('styles', '<link rel="stylesheet" href="/public/assets/css/login-dark.css">');
View::append('attributes', 'class="login uk-cover-container uk-background-secondary uk-flex uk-flex-center uk-flex-middle uk-height-viewport uk-overflow-hidden uk-light" data-uk-height-viewport');
?>
		<!-- overlay -->
		<div class="uk-position-cover uk-overlay-primary"></div>
		<!-- /overlay -->
		<div class="uk-position-bottom-center uk-position-small uk-visible@m uk-position-z-index">
			<span class="uk-text-small uk-text-muted">© <?=date('Y')?> Company Name - <a href="https://webhat.by">Created by Xupypx</a></span>
		</div>
		<div class="uk-width-medium uk-padding-small uk-position-z-index" uk-scrollspy="cls: uk-animation-fade">

			<div class="uk-text-center uk-margin">
				<a href="/"><img src="/public/assets/img/login-logo.svg" alt="Logo"></a>
			</div>
			<!-- login -->
			<form class="toggle-class" method="post" action="/login">
			<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
				<fieldset class="uk-fieldset">
					<div class="uk-margin-small">
						<div class="uk-inline uk-width-1-1">
							<span class="uk-form-icon uk-form-icon-flip" data-uk-icon="icon: user"></span>
							<input class="uk-input uk-border-pill" required placeholder="Имя пользователя" type="text" name="username">
						</div>
					</div>
					<div class="uk-margin-small">
						<div class="uk-inline uk-width-1-1">
							<span class="uk-form-icon uk-form-icon-flip" data-uk-icon="icon: lock"></span>
							<input class="uk-input uk-border-pill" required placeholder="Пароль" type="password" name="password">
						</div>
					</div>
					<div class="uk-margin-small">
						<label><input class="uk-checkbox" type="checkbox"> Не выходить из системы</label>
					</div>
					<div class="uk-margin-bottom">
						<button type="submit" class="uk-button uk-button-primary uk-border-pill uk-width-1-1">ВОЙТИ</button>
					</div>
				</fieldset>
			</form>
			<!-- /login -->

			<!-- recover password -->
			<form class="toggle-class" action="login-dark.html" hidden>
				<div class="uk-margin-small">
					<div class="uk-inline uk-width-1-1">
						<span class="uk-form-icon uk-form-icon-flip" data-uk-icon="icon: mail"></span>
						<input class="uk-input uk-border-pill" placeholder="E-mail" required type="text">
					</div>
				</div>
				<div class="uk-margin-bottom">
					<button type="submit" class="uk-button uk-button-primary uk-border-pill uk-width-1-1">ВЫСЛАТЬ ПАРОЛЬ</button>
				</div>
			</form>
			<!-- /recover password -->

			<!-- action buttons -->
			<div>
				<div class="uk-text-center">
					<a class="uk-link-reset uk-text-small toggle-class" data-uk-toggle="target: .toggle-class ;animation: uk-animation-fade">Забыли пароль?</a>
					<a class="uk-link-reset uk-text-small toggle-class" data-uk-toggle="target: .toggle-class ;animation: uk-animation-fade" hidden><span data-uk-icon="arrow-left"></span> Вернуться к входу в систему</a>
				</div>
			</div>
			<!-- action buttons -->
		</div>
