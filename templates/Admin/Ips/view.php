<?php
/**
 * @var \App\View\AppView $this
 * @var string $ip
 * @var iterable<\Captcha\Model\Entity\Captcha> $captchas
 * @var array{issued: int, solved: int, failed: int} $summary
 */

$this->assign('title', __d('captcha', 'Captcha · IP {0}', $ip));
?>

<nav aria-label="breadcrumb">
	<ol class="breadcrumb">
		<li class="breadcrumb-item"><?= $this->Html->link(__d('captcha', 'IPs'), ['action' => 'index']) ?></li>
		<li class="breadcrumb-item active font-monospace"><?= h($ip) ?></li>
	</ol>
</nav>

<div class="row g-3 mb-4">
	<?= $this->element('Captcha.Admin/stat_tile', ['label' => __d('captcha', 'Issued (24h)'), 'value' => $summary['issued'], 'note' => h($ip), 'flavor' => 'default']) ?>
	<?= $this->element('Captcha.Admin/stat_tile', ['label' => __d('captcha', 'Solved (24h)'), 'value' => $summary['solved'], 'note' => h($ip), 'flavor' => 'success']) ?>
	<?= $this->element('Captcha.Admin/stat_tile', ['label' => __d('captcha', 'Failed (24h)'), 'value' => $summary['failed'], 'note' => h($ip), 'flavor' => 'danger']) ?>
</div>

<div class="card mb-4">
	<div class="card-header d-flex justify-content-between align-items-center">
		<span><?= __d('captcha', 'Recent captchas for {0}', h($ip)) ?></span>
		<div class="btn-group btn-group-sm">
			<?= $this->Form->postLink(
				'<i class="fas fa-bolt me-1"></i>' . __d('captcha', 'Unblock'),
				['action' => 'clearRateLimit', $ip],
				[
					'class' => 'btn btn-outline-warning',
					'escapeTitle' => false,
					'confirm' => __d('captcha', 'Clear rate-limit cache for {0}?', $ip),
					'block' => true,
				],
			) ?>
			<?= $this->Form->postLink(
				'<i class="fas fa-trash me-1"></i>' . __d('captcha', 'Reset'),
				['action' => 'reset', $ip],
				[
					'class' => 'btn btn-outline-danger',
					'escapeTitle' => false,
					'confirm' => __d('captcha', 'Delete all captcha rows for {0}?', $ip),
					'block' => true,
				],
			) ?>
		</div>
	</div>
	<div class="card-body p-0">
		<?php
		$rows = is_array($captchas) ? $captchas : iterator_to_array($captchas);
		?>
		<?php if (!$rows) { ?>
			<p class="text-muted p-3 mb-0"><?= __d('captcha', 'No captchas seen for this IP.') ?></p>
		<?php } else { ?>
			<table class="table table-sm mb-0">
				<thead>
					<tr>
						<th><?= __d('captcha', 'Created') ?></th>
						<th><?= __d('captcha', 'Used') ?></th>
						<th class="text-center"><?= __d('captcha', 'Solved') ?></th>
						<th><?= __d('captcha', 'Session') ?></th>
						<th><?= __d('captcha', 'UUID') ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($rows as $captcha) {
						$solved = $captcha->solved;
						$icon = $solved === true ? '<i class="fas fa-check solved-icon-true"></i>' : ($solved === false ? '<i class="fas fa-xmark solved-icon-false"></i>' : '<i class="fas fa-minus solved-icon-null"></i>');
						?>
						<tr>
							<td class="small"><?= h((string)$captcha->created) ?></td>
							<td class="small"><?= $captcha->used ? h((string)$captcha->used) : '<span class="text-muted">—</span>' ?></td>
							<td class="text-center"><?= $icon ?></td>
							<td class="font-monospace small text-muted"><?= h(substr((string)$captcha->session_id, 0, 12)) ?>…</td>
							<td class="font-monospace small text-muted"><?= h(substr((string)$captcha->uuid, 0, 8)) ?>…</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
		<?php } ?>
	</div>
</div>

<?php if ($this->Paginator->hasPage(2)) { ?>
	<nav><?= $this->Paginator->numbers() ?></nav>
<?php } ?>
