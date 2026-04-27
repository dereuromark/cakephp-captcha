<?php
/**
 * @var \App\View\AppView $this
 * @var string $label
 * @var int|string $value
 * @var string $note
 * @var string $flavor
 */
?>
<div class="col-sm-6 col-lg-4 col-xl-2">
	<div class="card stat-tile <?= h($flavor) ?>">
		<div class="card-body">
			<span class="label"><?= h($label) ?></span>
			<h2><?= h((string)$value) ?></h2>
			<span class="delta"><?= h($note) ?></span>
		</div>
	</div>
</div>
