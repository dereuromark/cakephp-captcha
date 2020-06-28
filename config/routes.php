<?php

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;

Router::plugin('Captcha', ['path' => '/captcha'], function (RouteBuilder $routes) {
	$routes->setExtensions(['png', 'jpg']);
	$routes->fallbacks(DashedRoute::class);
});
