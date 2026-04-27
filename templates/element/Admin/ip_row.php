<?php
/**
 * @var \App\View\AppView $this
 * @var string $ip
 * @var int $n
 */
?>
<tr>
	<td class="font-monospace">
		<?= $this->Html->link(h($ip), ['action' => 'view', $ip], ['escapeTitle' => false]) ?>
	</td>
	<td class="text-end"><span class="badge text-bg-secondary"><?= h((string)$n) ?></span></td>
	<td class="text-end" style="width: 14rem;">
		<?= $this->Form->postLink(
			'<i class="fas fa-bolt"></i> ' . __d('captcha', 'Unblock'),
			['action' => 'clearRateLimit', $ip],
			[
				'class' => 'btn btn-sm btn-outline-warning',
				'escapeTitle' => false,
				'confirm' => __d('captcha', 'Clear rate-limit cache for {0}?', $ip),
				'block' => true,
			],
		) ?>
		<?= $this->Form->postLink(
			'<i class="fas fa-trash"></i>',
			['action' => 'reset', $ip],
			[
				'class' => 'btn btn-sm btn-outline-danger',
				'escapeTitle' => false,
				'confirm' => __d('captcha', 'Delete all captcha rows for {0}?', $ip),
				'block' => true,
				'title' => __d('captcha', 'Delete all rows for this IP'),
			],
		) ?>
	</td>
</tr>
