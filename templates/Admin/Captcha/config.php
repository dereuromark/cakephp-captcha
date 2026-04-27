<?php
/**
 * @var \App\View\AppView $this
 * @var array $config
 */

$this->assign('title', __d('captcha', 'Captcha · Config'));

$flatten = function (array $config, string $prefix = '') use (&$flatten): array {
	$out = [];
	foreach ($config as $key => $value) {
		$compoundKey = $prefix === '' ? (string)$key : $prefix . '.' . (string)$key;
		if (is_array($value)) {
			$out = array_merge($out, $flatten($value, $compoundKey));

			continue;
		}
		$out[$compoundKey] = $value;
	}

	return $out;
};

$flat = $flatten($config);
ksort($flat);
?>

<div class="card">
	<div class="card-header"><?= __d('captcha', 'Resolved Captcha.* configuration') ?></div>
	<div class="card-body">
		<?php if (!$flat) { ?>
			<p class="text-muted mb-0"><?= __d('captcha', 'No configuration loaded.') ?></p>
		<?php } else { ?>
			<table class="table table-sm config-table mb-0">
				<thead>
					<tr>
						<th><?= __d('captcha', 'Key') ?></th>
						<th><?= __d('captcha', 'Value') ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($flat as $key => $value) { ?>
						<tr>
							<td><?= h('Captcha.' . $key) ?></td>
							<td>
								<?php if ($value instanceof Closure) { ?>
									<em class="text-muted"><?= __d('captcha', 'Closure') ?></em>
								<?php } elseif (is_bool($value)) { ?>
									<?= $value ? 'true' : 'false' ?>
								<?php } else { ?>
									<?= h((string)$value) ?>
								<?php } ?>
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
		<?php } ?>
	</div>
</div>
