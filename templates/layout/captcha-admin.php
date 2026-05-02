<?php
/**
 * Captcha admin backend layout.
 *
 * @var \App\View\AppView $this
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8"/>
	<meta name="viewport" content="width=device-width, initial-scale=1"/>
	<title><?= $this->fetch('title') ?: __d('captcha', 'Captcha Admin') ?></title>
	<link rel="stylesheet"
		href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
		integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
		crossorigin="anonymous"
		referrerpolicy="no-referrer">
	<link rel="stylesheet"
		href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css"
		integrity="sha384-PPIZEGYM1v8zp5Py7UjFb79S58UeqCL9pYVnVPURKEqvioPROaVAJKKLzvH2rDnI"
		crossorigin="anonymous"
		referrerpolicy="no-referrer">
	<style>
		body { background: #f5f7fa; }
		.captcha-admin-nav { background: #1f2937; }
		.captcha-admin-nav .navbar-brand,
		.captcha-admin-nav .nav-link { color: #f9fafb; }
		.captcha-admin-nav .nav-link:hover { color: #93c5fd; }
		.captcha-admin-nav .nav-link.active { color: #fbbf24; }
		.stat-tile { border-left: 4px solid #3b82f6; }
		.stat-tile.success { border-left-color: #16a34a; }
		.stat-tile.warning { border-left-color: #f59e0b; }
		.stat-tile.danger { border-left-color: #dc2626; }
		.stat-tile h2 { font-size: 1.85rem; margin: 0; }
		.stat-tile .label { color: #6b7280; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.04em; }
		.stat-tile .delta { color: #6b7280; font-size: 0.8rem; }
		.heatmap { display: grid; grid-template-columns: 4rem repeat(24, 1fr); gap: 2px; font-size: 0.75rem; }
		.heatmap .day-label { color: #6b7280; padding-right: 0.5rem; text-align: right; align-self: center; }
		.heatmap .col-label { color: #9ca3af; text-align: center; align-self: end; padding-bottom: 0.25rem; }
		.heatmap-cell { aspect-ratio: 1; border-radius: 2px; background: #e5e7eb; }
		.heatmap-cell[data-bin="1"] { background: #bfdbfe; }
		.heatmap-cell[data-bin="2"] { background: #60a5fa; }
		.heatmap-cell[data-bin="3"] { background: #2563eb; }
		.heatmap-cell[data-bin="4"] { background: #1e3a8a; }
		.config-table tbody td { font-family: ui-monospace, monospace; font-size: 0.85rem; }
		.captcha-snapshot { background: #fff; border: 1px solid #e5e7eb; border-radius: 0.375rem; padding: 0.75rem 1rem; margin-bottom: 1rem; font-size: 0.9rem; }
		.captcha-snapshot .badge { margin-right: 0.5rem; }
		.solved-icon-true { color: #16a34a; }
		.solved-icon-false { color: #dc2626; }
		.solved-icon-null { color: #9ca3af; }
	</style>
</head>
<body>
<nav class="navbar navbar-expand-lg captcha-admin-nav">
	<div class="container-fluid">
		<a class="navbar-brand" href="<?= $this->Url->build(['plugin' => 'Captcha', 'prefix' => 'Admin', 'controller' => 'Captcha', 'action' => 'index']) ?>">
			<i class="fas fa-shield-halved me-2"></i><?= __d('captcha', 'Captcha') ?>
		</a>
		<?= $this->element('Captcha.Admin/nav') ?>
	</div>
</nav>

<div class="container-fluid py-4">
	<?= $this->Flash->render() ?>
	<?= $this->fetch('content') ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
	integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
	crossorigin="anonymous"
	referrerpolicy="no-referrer"></script>
</body>
</html>
