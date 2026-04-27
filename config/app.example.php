<?php

// The following configs can be globally configured, copy the array content over to your ROOT/config

return [
	'Captcha' => [
		'maxPerUser' => 100, // Total stored captchas per user
		'deadlockMinutes' => 60, // How long at most to block a user who generated too much captchas
		'cleanupProbability' => 10, // 0...100 - Use 0 if you use a cronjob to manually garbage collect
		'verifyRateLimit' => [
			'enabled' => true, // Enabled by default; override to tune or disable
			'maxFailures' => 5,
			'window' => 600,
			'scope' => 'ip_session', // 'ip_session' or 'ip'
			'cache' => 'default',
		],
	],
];
