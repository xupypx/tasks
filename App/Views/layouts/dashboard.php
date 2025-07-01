<?php use core\View;?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars(View::get('title') ?? 'Dashboard') ?></title>
    <link rel="stylesheet" href="/public/assets/css/uikit.min.css">
    <?= View::stack('styles') ?>
</head>
<body  <?= View::stack('attributes') ?>>
<!-- overlay -->
<div class="uk-position-cover uk-overlay-primary"></div>
<!-- /overlay -->
<div class="uk-position-bottom-center uk-position-small uk-visible@m uk-position-z-index">
    <span class="uk-text-small uk-text-muted">© <?= date('Y') ?> Company Name - <a href="https://webhat.by">Created by Xupypx</a></span>
</div>

        <!-- NAV -->
        <div class="uk-position-top">
          <div class="uk-container uk-container-small">
            <nav class="uk-navbar-container uk-navbar-transparent" data-uk-navbar>
              <div class="uk-navbar-left">
                <div class="uk-navbar-item">
                  <a class="uk-logo" href="/"><img src="/public/assets/img/cover-logo.svg" alt="Logo"></a>
                </div>
              </div>
              <div class="uk-navbar-right">
                <ul class="uk-navbar-nav">
                  <li class="uk-active uk-visible@m"><a href="/dashboard" data-uk-icon="home"></a></li>
                  <li class="uk-visible@s"><a href="/tasks/list">Задачи</a></li>
                  <li class="uk-visible@s"><a href="/tasks/create">Добавить задачу</a></li>
                  <li class="uk-visible@s"><a href="">Testimonials</a></li>
                  <li><a class="uk-navbar-toggle" data-uk-toggle data-uk-navbar-toggle-icon href="#offcanvas-nav"></a></li>
                </ul>
              </div>
            </nav>
          </div>
        </div>
        <!-- /NAV -->


        <?= View::get('content') ?>


		<!-- OFFCANVAS -->
		<div id="offcanvas-nav" data-uk-offcanvas="flip: true; overlay: false">
			<div class="uk-offcanvas-bar uk-offcanvas-bar-animation uk-offcanvas-slide">
				<button class="uk-offcanvas-close uk-close uk-icon" type="button" data-uk-close></button>
				<ul class="uk-nav uk-nav-default">
					<li class="uk-active"><a href="#">Active</a></li>
					<li class="uk-parent">
						<a href="#">Parent</a>
						<ul class="uk-nav-sub">
							<li><a href="#">Sub item</a></li>
							<li><a href="#">Sub item</a></li>
						</ul>
					</li>
					<li class="uk-nav-header">Header</li>
					<li><a href="#js-options"><span class="uk-margin-small-right uk-icon" data-uk-icon="icon: table"></span> Item</a></li>
					<li><a href="#"><span class="uk-margin-small-right uk-icon" data-uk-icon="icon: thumbnails"></span> Item</a></li>
					<li class="uk-nav-divider"></li>
					<li><a href="#"><span class="uk-margin-small-right uk-icon" data-uk-icon="icon: trash"></span> Item</a></li>
				</ul>
				<h3>Title</h3>

			</div>
		</div>
		<!-- /OFFCANVAS -->

<script src="/public/assets/js/uikit.min.js"></script>
<script src="/public/assets/js/uikit-icons.min.js"></script>
<?= View::stack('scripts') ?>

</body>
</html>
