<?php
/**
 * @var \App\View\AppView $this
 * @var string $engineClass
 * @var string $engineShortName
 * @var array $payload
 * @var string|null $imageDataUri
 */

$this->assign('title', __d('captcha', 'Captcha · Preview'));
?>

<div class="card">
	<div class="card-header d-flex justify-content-between align-items-center">
		<span><?= __d('captcha', 'Engine: {0}', h($engineShortName)) ?></span>
		<?= $this->Html->link(
			'<i class="fas fa-arrows-rotate me-1"></i>' . __d('captcha', 'Regenerate'),
			['action' => 'preview'],
			['class' => 'btn btn-sm btn-outline-primary', 'escapeTitle' => false],
		) ?>
	</div>
	<div class="card-body">
		<?php if ($imageDataUri) { ?>
			<div class="mb-3">
				<img src="<?= h($imageDataUri) ?>" alt="<?= __d('captcha', 'Sample captcha') ?>" class="border rounded p-2 bg-white"/>
			</div>
		<?php } ?>
		<dl class="row mb-0">
			<dt class="col-sm-3"><?= __d('captcha', 'Class') ?></dt>
			<dd class="col-sm-9 font-monospace small"><?= h($engineClass) ?></dd>

			<?php foreach ($payload as $key => $value) {
				if ($key === 'image') {
					continue;
				}
				?>
				<dt class="col-sm-3"><?= h((string)$key) ?></dt>
				<dd class="col-sm-9">
					<?php if (is_array($value) || $value instanceof Closure) { ?>
						<em class="text-muted"><?= __d('captcha', '(non-scalar)') ?></em>
					<?php } else { ?>
						<?= h((string)$value) ?>
					<?php } ?>
				</dd>
			<?php } ?>
		</dl>
	</div>
</div>
