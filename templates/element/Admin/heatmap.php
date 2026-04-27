<?php
/**
 * @var \App\View\AppView $this
 * @var array<int, array<int, array{issued: int, solved: int, failed: int}>> $heatmap
 */

$max = 0;
foreach ($heatmap as $row) {
	foreach ($row as $cell) {
		if ($cell['issued'] > $max) {
			$max = $cell['issued'];
		}
	}
}

$bin = function (int $issued) use ($max): int {
	if ($issued <= 0 || $max <= 0) {
		return 0;
	}
	$ratio = $issued / $max;
	if ($ratio > 0.75) {
		return 4;
	}
	if ($ratio > 0.5) {
		return 3;
	}
	if ($ratio > 0.25) {
		return 2;
	}

	return 1;
};
?>
<div class="heatmap">
	<div class="col-label"></div>
	<?php for ($h = 0; $h < 24; $h++) { ?>
		<div class="col-label"><?= $h ?></div>
	<?php } ?>
	<?php for ($d = 0; $d < 7; $d++) { ?>
		<div class="day-label"><?= $d === 0 ? __d('captcha', 'today') : __d('captcha', '-{0}d', $d) ?></div>
		<?php for ($h = 0; $h < 24; $h++) {
			$cell = $heatmap[$d][$h];
			$tooltip = sprintf(
				'%d issued · %d solved · %d failed',
				$cell['issued'],
				$cell['solved'],
				$cell['failed'],
			);
			?>
			<div class="heatmap-cell" data-bin="<?= $bin($cell['issued']) ?>" title="<?= h($tooltip) ?>"></div>
		<?php } ?>
	<?php } ?>
</div>
