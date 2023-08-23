<?php

// The following configs can be globally configured, copy the array content over to your ROOT/config

return [
	'Captcha' => [
		'maxPerUser' => 100, // Total stored captchas per user
		'deadlockMinutes' => 60, // How long at most to block a user who generated too much captchas
		'cleanupProbability' => 10, // 0...100 - Use 0 if you use a cronjob to manually garbage collect
	],
];
