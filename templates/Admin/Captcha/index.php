<?php
/**
 * @var \App\View\AppView $this
 * @var array{open: int, solved: int, failed: int, expired: int} $tiles24h
 * @var array{open: int, solved: int, failed: int, expired: int} $tiles7d
 * @var int $throttledIps
 * @var array<int, array<int, array{issued: int, solved: int, failed: int}>> $heatmap
 * @var array{engine: string, maxPerUser: int, deadlockMinutes: int, cleanupProbability: int, rateLimit: string} $snapshot
 */

$this->assign('title', __d('captcha', 'Captcha · Dashboard'));

$attempted = $tiles24h['solved'] + $tiles24h['failed'];
$solveRate = $attempted > 0 ? round(($tiles24h['solved'] / $attempted) * 100) : null;
?>

<div class="captcha-snapshot">
	<span class="badge text-bg-secondary"><?= __d('captcha', 'Engine') ?>: <?= h($snapshot['engine']) ?></span>
	<span class="badge text-bg-secondary"><?= __d('captcha', 'maxPerUser') ?>: <?= $snapshot['maxPerUser'] ?></span>
	<span class="badge text-bg-secondary"><?= __d('captcha', 'deadlockMinutes') ?>: <?= $snapshot['deadlockMinutes'] ?></span>
	<span class="badge text-bg-secondary"><?= __d('captcha', 'cleanupProbability') ?>: <?= $snapshot['cleanupProbability'] ?>%</span>
	<span class="badge text-bg-secondary"><?= __d('captcha', 'rate-limit') ?>: <?= h($snapshot['rateLimit']) ?></span>
</div>

<div class="row g-3 mb-4">
	<?= $this->element('Captcha.Admin/stat_tile', ['label' => __d('captcha', 'Open'), 'value' => $tiles24h['open'], 'note' => __d('captcha', 'last 24h'), 'flavor' => 'default']) ?>
	<?= $this->element('Captcha.Admin/stat_tile', ['label' => __d('captcha', 'Solved (24h)'), 'value' => $tiles24h['solved'], 'note' => __d('captcha', '7d: {0}', $tiles7d['solved']), 'flavor' => 'success']) ?>
	<?= $this->element('Captcha.Admin/stat_tile', ['label' => __d('captcha', 'Failed (24h)'), 'value' => $tiles24h['failed'], 'note' => __d('captcha', '7d: {0}', $tiles7d['failed']), 'flavor' => 'danger']) ?>
	<?= $this->element('Captcha.Admin/stat_tile', ['label' => __d('captcha', 'Solve rate'), 'value' => $solveRate === null ? '—' : ($solveRate . '%'), 'note' => __d('captcha', 'last 24h'), 'flavor' => 'success']) ?>
	<?= $this->element('Captcha.Admin/stat_tile', ['label' => __d('captcha', 'Expired (no attempt)'), 'value' => $tiles24h['expired'], 'note' => __d('captcha', 'last 24h'), 'flavor' => 'warning']) ?>
	<?= $this->element('Captcha.Admin/stat_tile', ['label' => __d('captcha', 'Throttled now'), 'value' => $throttledIps, 'note' => __d('captcha', 'IPs over the threshold'), 'flavor' => 'warning']) ?>
</div>

<div class="card mb-4">
	<div class="card-header"><?= __d('captcha', 'Issued per hour, last 7 days') ?></div>
	<div class="card-body">
		<?= $this->element('Captcha.Admin/heatmap', ['heatmap' => $heatmap]) ?>
	</div>
</div>

<div class="card">
	<div class="card-header"><?= __d('captcha', 'Maintenance') ?></div>
	<div class="card-body d-flex gap-2 flex-wrap">
		<?= $this->Form->postLink(
			'<i class="fas fa-broom me-1"></i>' . __d('captcha', 'Run cleanup now'),
			['action' => 'cleanup'],
			[
				'class' => 'btn btn-outline-primary btn-sm',
				'escapeTitle' => false,
				'confirm' => __d('captcha', 'Delete all captchas older than maxTime?'),
				'block' => true,
			],
		) ?>
		<?= $this->Form->postLink(
			'<i class="fas fa-trash me-1"></i>' . __d('captcha', 'Hard reset (truncate)'),
			['action' => 'hardReset'],
			[
				'class' => 'btn btn-outline-danger btn-sm',
				'escapeTitle' => false,
				'confirm' => __d('captcha', 'Delete ALL captcha rows? This cannot be undone.'),
				'block' => true,
			],
		) ?>
	</div>
</div>
