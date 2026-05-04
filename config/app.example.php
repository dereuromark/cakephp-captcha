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

		// Admin backend (mounted at /<adminPrefix><adminRoutePath> — default /admin/captcha)
		'adminPrefix' => 'Admin', // Route prefix to mount the admin under
		'adminRoutePath' => '/captcha', // Path segment under the prefix
		'adminLayout' => null, // null = plugin layout, false = host layout, string = custom layout name

		// Back-to-App link in the admin header (opt-in). When set, an outline
		// button appears in the top navbar so admins can escape the
		// plugin-isolated layout. Accepts anything Router::url() takes — Cake
		// URL array, path string, or full URL. Use 'plugin' => false to
		// anchor the builder to the host app rather than the Captcha plugin.
		// 'adminBackUrl' => ['plugin' => false, 'prefix' => 'Admin', 'controller' => 'Overview', 'action' => 'index'],
		// 'adminBackLabel' => 'Back to admin', // Optional. Defaults to "Back to App".

		// REQUIRED for the admin backend. Default is deny — the closure must return true to allow access.
		// 'adminAccess' => function (\Cake\Http\ServerRequest $request): bool {
		//     $identity = $request->getAttribute('identity');
		//     return $identity !== null && in_array('admin', (array)($identity->roles ?? []), true);
		// },
	],
];
