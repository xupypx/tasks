<?php use core\View;
View::set('title', 'Цопало');
?>
   <div class="uk-light wrap uk-background-norepeat uk-background-cover uk-background-center-center uk-cover-container uk-background-secondary">
      <img data-srcset="/public/assets/img/bg/main/482-640x700.jpg 640w,
                   /public/assets/img/bg/main/482-960x700.jpg 960w,
                   /public/assets/img/bg/main/482-1200x900.jpg 1200w,
                   /public/assets/img/bg/main/482-2000x1000.jpg 2000w"
           sizes="100vw"
           data-src="https://picsum.photos/1200/900/?image=482" alt="" data-uk-cover data-uk-img
      >
      <div class="uk-flex uk-flex-center uk-flex-middle uk-height-viewport uk-position-z-index uk-position-relative" data-uk-height-viewport="min-height: 400" style="background-color: rgba(0, 0, 0, 0.5);">

        <!-- NAV -->
        <div class="uk-position-top">
          <div class="uk-container uk-container-small">
            <nav class="uk-navbar-container uk-navbar-transparent" data-uk-navbar>
              <div class="uk-navbar-left">
                <div class="uk-navbar-item">
                  <a class="uk-logo" href=""><img src="/public/assets/img/cover-logo.svg" alt="Logo"></a>
                </div>
              </div>
              <div class="uk-navbar-right">
                <ul class="uk-navbar-nav">
                  <li class="uk-active uk-visible@m"><a href="/" data-uk-icon="home"></a></li>
                  <li class="uk-visible@s"><a href="/tasks/create">Features</a></li>
                  <li class="uk-visible@s"><a href="/register">Новый пользователь</a></li>
                  <li class="uk-visible@s"><a href="/logout">Выход</a></li>
                  <li><a class="uk-navbar-toggle" data-uk-toggle data-uk-navbar-toggle-icon href="#offcanvas-nav"></a></li>
                </ul>
              </div>
            </nav>
          </div>
        </div>
        <!-- /NAV -->


    <div class="uk-container uk-container-small uk-flex-auto uk-text-center uk-margin-large-top uk-margin-large-bottom" data-uk-scrollspy="target: > .animate; cls: uk-animation-slide-bottom-small uk-invisible; delay: 300">
        <h1 class="uk-heading-primary animate uk-invisible" style="font-weight: 700;">Ваши задачи</h1>

        <?php if (empty($tasks)): ?>
          <div class="uk-width-4-5@m uk-margin-auto animate uk-invisible">
            <p class="lead">Задач пока нет.</p>
          </div>
        <?php else: ?>
          <table border="1" cellpadding="10" cellspacing="0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Описание</th>
                    <th>Статус</th>
                    <th>Дата создания</th>
                    <th>Исполнители</th>
                </tr>
            </thead>
            <tbody>
<?php foreach ($tasks as $task): ?>
    <tr>
        <td><?= htmlspecialchars($task['id']) ?></td>
        <td><a href="/tasks/<?= htmlspecialchars($task['id']) ?>"><?= htmlspecialchars($task['title']) ?></a></td>
        <td><?= htmlspecialchars($task['description']) ?></td>
        <td><?= htmlspecialchars($task['status']) ?></td>
        <td><?= htmlspecialchars($task['created_at']) ?></td>
        <td>
            <?php if (empty($task['managers'])): ?>
                Нет менеджеров
            <?php else: ?>
                <?php foreach ($task['managers'] as $manager): ?>
                    <?= htmlspecialchars($manager['username']) ?><br>
                <?php endforeach; ?>
            <?php endif; ?>
        </td>
    </tr>
<?php endforeach; ?>
            </tbody>
        </table>



        <?php endif; ?>
    </div>

        <!-- FOOT -->
        <div class="uk-position-bottom-center uk-position-small">
          <span class="uk-text-small uk-text-center">© <?=date('Y')?> Company Name | <a href="https://webhat.by">Created by Xupypx</a> | Built with <a href="http://getuikit.com" title="Visit UIkit 3 site" target="_blank" data-uk-tooltip><span data-uk-icon="uikit"></span></a></span>
        </div>
        <!-- /FOOT -->
      </div>
    </div>
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
