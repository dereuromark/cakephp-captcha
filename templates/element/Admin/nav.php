<?php
/**
 * @var \App\View\AppView $this
 */

$current = $this->request->getParam('controller');
$action = $this->request->getParam('action');

$links = [
	['label' => __d('captcha', 'Dashboard'), 'url' => ['plugin' => 'Captcha', 'prefix' => 'Admin', 'controller' => 'Captcha', 'action' => 'index'], 'active' => $current === 'Captcha' && $action === 'index'],
	['label' => __d('captcha', 'IPs'), 'url' => ['plugin' => 'Captcha', 'prefix' => 'Admin', 'controller' => 'Ips', 'action' => 'index'], 'active' => $current === 'Ips'],
	['label' => __d('captcha', 'Engine'), 'url' => ['plugin' => 'Captcha', 'prefix' => 'Admin', 'controller' => 'Captcha', 'action' => 'engine'], 'active' => $current === 'Captcha' && $action === 'engine'],
	['label' => __d('captcha', 'Preview'), 'url' => ['plugin' => 'Captcha', 'prefix' => 'Admin', 'controller' => 'Captcha', 'action' => 'preview'], 'active' => $current === 'Captcha' && $action === 'preview'],
	['label' => __d('captcha', 'Config'), 'url' => ['plugin' => 'Captcha', 'prefix' => 'Admin', 'controller' => 'Captcha', 'action' => 'config'], 'active' => $current === 'Captcha' && $action === 'config'],
];

$adminBackUrl = \Cake\Core\Configure::read('Captcha.adminBackUrl');
$adminBackLabel = (string)\Cake\Core\Configure::read('Captcha.adminBackLabel', __d('captcha', 'Back to App'));
?>
<ul class="navbar-nav ms-auto">
	<?php foreach ($links as $link) { ?>
		<li class="nav-item">
			<?= $this->Html->link($link['label'], $link['url'], ['class' => 'nav-link' . ($link['active'] ? ' active' : '')]) ?>
		</li>
	<?php } ?>
	<?php if ($adminBackUrl !== null && $adminBackUrl !== '') { ?>
		<li class="nav-item">
			<?= $this->Html->link(
				'<i class="fas fa-arrow-left me-1"></i>' . h($adminBackLabel),
				$adminBackUrl,
				['class' => 'nav-link', 'escapeTitle' => false],
			) ?>
		</li>
	<?php } ?>
</ul>
