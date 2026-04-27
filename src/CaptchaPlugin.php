<?php

namespace Captcha;

use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

class CaptchaPlugin extends BasePlugin {

	/**
	 * @var bool
	 */
	protected bool $middlewareEnabled = false;

	/**
	 * @var bool
	 */
	protected bool $consoleEnabled = false;

	/**
	 * @param \Cake\Routing\RouteBuilder $routes The route builder to update.
	 * @return void
	 */
	public function routes(RouteBuilder $routes): void {
		$routes->plugin('Captcha', ['path' => '/captcha'], function (RouteBuilder $routes): void {
			$routes->setExtensions(['png', 'jpg']);
			$routes->fallbacks(DashedRoute::class);
		});

		$adminPrefix = (string)Configure::read('Captcha.adminPrefix', 'Admin');
		$adminPath = (string)Configure::read('Captcha.adminRoutePath', '/captcha');
		$routes->prefix($adminPrefix, function (RouteBuilder $routes) use ($adminPath): void {
			$routes->plugin('Captcha', ['path' => $adminPath], function (RouteBuilder $routes): void {
				$routes->connect('/', ['controller' => 'Captcha', 'action' => 'index']);
				$routes->fallbacks(DashedRoute::class);
			});
		});
	}

}
