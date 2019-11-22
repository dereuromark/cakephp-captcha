<?php

// The following configs can be globally configured, copy the array content over to your ROOT/config

return [
	'Captcha' => [
		'maxPerUser' => 1000, // Total stored captchas
		'cleanupProbability' => 10, // 0...100 - Use 0 if you use a cronjob to manually garbage collect
	],
];
