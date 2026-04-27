<?php
/**
 * @var \App\View\AppView $this
 * @var array<int, array{class: string, short: string}> $engines
 * @var string $activeClass
 */

$this->assign('title', __d('captcha', 'Captcha · Engine'));
?>

<div class="card">
	<div class="card-header"><?= __d('captcha', 'Available engines') ?></div>
	<div class="card-body">
		<table class="table mb-0">
			<thead>
				<tr>
					<th><?= __d('captcha', 'Engine') ?></th>
					<th><?= __d('captcha', 'Class') ?></th>
					<th class="text-end"><?= __d('captcha', 'Active') ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($engines as $engine) { ?>
					<tr>
						<td><?= h($engine['short']) ?></td>
						<td class="font-monospace small text-muted"><?= h($engine['class']) ?></td>
						<td class="text-end">
							<?php if ($engine['class'] === $activeClass) { ?>
								<span class="badge text-bg-success"><?= __d('captcha', 'active') ?></span>
							<?php } ?>
						</td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	</div>
</div>
