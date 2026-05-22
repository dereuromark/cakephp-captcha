<?php

// The following configs can be globally configured, copy the array content over to your ROOT/config

use Captcha\Engine\MathEngine;

return [
	'Captcha' => [
		'engine' => MathEngine::class, // Engine FQCN (must implement Captcha\Engine\EngineInterface); default is MathEngine
		'engineConfig' => [], // Only read by the admin preview (Admin/CaptchaController::preview) when rendering a test captcha; the live captcha flow does NOT read this — set production engine options at the Captcha.* top level
		'maxTime' => DAY, // Seconds a generated captcha stays valid before it is considered stale/expired; default DAY (86400)

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

		// The following are behavior/helper _defaultConfig values (CaptchaBehavior,
		// PassiveCaptchaBehavior, CaptchaHelper). They can be set per-attachment, but
		// can also be overridden globally here via the Captcha namespace, which is
		// merged onto each component's defaults at runtime.
		'minTime' => 2, // CaptchaBehavior: minimum seconds a human is expected to need to fill in the form
		'dummyField' => 'email_homepage', // PassiveCaptchaBehavior/Helper: honeypot field name (string or array of names)
		'ext' => null, // CaptchaHelper: image URL extension for the rendered captcha (e.g. 'png'); null = none
		'log' => null, // PassiveCaptchaBehavior: log honeypot hits; null = auto-detect based on debug mode
	],
];
