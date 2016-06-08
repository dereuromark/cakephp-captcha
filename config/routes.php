<?php
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\Router;

Router::prefix('admin', function ($routes) {
	$routes->plugin('Captcha', function ($routes) {
		$routes->connect('/', ['controller' => 'Captchas', 'action' => 'index'], ['routeClass' => DashedRoute::class]);

		$routes->connect('/:controller', ['action' => 'index'], ['routeClass' => DashedRoute::class]);
		$routes->connect('/:controller/:action/*', [], ['routeClass' => DashedRoute::class]);
	});
});

Router::plugin('Captcha', ['path' => '/captcha'], function ($routes) {
	$routes->connect('/:controller', ['action' => 'index'], ['routeClass' => DashedRoute::class]);
	$routes->connect('/:controller/:action/*', [], ['routeClass' => DashedRoute::class]);
});
