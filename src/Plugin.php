<?php

namespace Captcha;

use Cake\Core\BasePlugin;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

/**
 * Plugin for DatabaseLog
 */
class Plugin extends BasePlugin {

	/**
	 * @var bool
	 */
	protected $middlewareEnabled = false;

	/**
	 * @var bool
	 */
	protected $consoleEnabled = false;

	/**
	 * @param \Cake\Routing\RouteBuilder $routes The route builder to update.
	 * @return void
	 */
	public function routes(RouteBuilder $routes): void {
		$routes->plugin('Captcha', ['path' => '/captcha'], function (RouteBuilder $routes) {
			$routes->setExtensions(['png', 'jpg']);
			$routes->fallbacks(DashedRoute::class);
		});
	}

}
