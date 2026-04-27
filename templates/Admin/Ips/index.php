<?php
/**
 * @var \App\View\AppView $this
 * @var array<int, array{ip: string, n: int}> $issued
 * @var array<int, array{ip: string, n: int}> $solved
 * @var array<int, array{ip: string, n: int}> $failed
 * @var array<int, array{ip: string, n: int}> $throttled
 * @var int $window
 */

$this->assign('title', __d('captcha', 'Captcha · IPs'));
$dayLabel = $window === 604800 ? __d('captcha', '7 days') : __d('captcha', '24 hours');

$boards = [
	['title' => __d('captcha', 'Issued'), 'rows' => $issued, 'flavor' => 'primary'],
	['title' => __d('captcha', 'Solved'), 'rows' => $solved, 'flavor' => 'success'],
	['title' => __d('captcha', 'Failed'), 'rows' => $failed, 'flavor' => 'danger'],
	['title' => __d('captcha', 'Currently rate-limited'), 'rows' => $throttled, 'flavor' => 'warning', 'tooltip' => __d('captcha', 'Derived from captcha rows; matches behavior config in the common path.')],
];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
	<h2 class="mb-0"><?= __d('captcha', 'Top IPs') ?></h2>
	<div class="btn-group btn-group-sm" role="group">
		<?= $this->Html->link(
			__d('captcha', '24h'),
			['action' => 'index', '?' => ['window' => 86400]],
			['class' => 'btn btn-' . ($window === 86400 ? 'primary' : 'outline-primary')],
		) ?>
		<?= $this->Html->link(
			__d('captcha', '7d'),
			['action' => 'index', '?' => ['window' => 604800]],
			['class' => 'btn btn-' . ($window === 604800 ? 'primary' : 'outline-primary')],
		) ?>
	</div>
</div>

<div class="row g-3">
	<?php foreach ($boards as $board) { ?>
		<div class="col-md-6">
			<div class="card">
				<div class="card-header bg-<?= h($board['flavor']) ?> text-white d-flex justify-content-between align-items-center">
					<span><?= h($board['title']) ?></span>
					<?php if (isset($board['tooltip'])) { ?>
						<i class="fas fa-circle-info" title="<?= h($board['tooltip']) ?>"></i>
					<?php } else { ?>
						<small class="opacity-75"><?= h($dayLabel) ?></small>
					<?php } ?>
				</div>
				<div class="card-body p-0">
					<?php if (!$board['rows']) { ?>
						<p class="text-muted small p-3 mb-0"><?= __d('captcha', 'No IPs in this category.') ?></p>
					<?php } else { ?>
						<table class="table table-sm mb-0">
							<tbody>
								<?php foreach ($board['rows'] as $row) {
									echo $this->element('Captcha.Admin/ip_row', ['ip' => $row['ip'], 'n' => $row['n']]);
								} ?>
							</tbody>
						</table>
					<?php } ?>
				</div>
			</div>
		</div>
	<?php } ?>
</div>
