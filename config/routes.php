<?php
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\Routing\Route\DashedRoute;

Router::plugin('Captcha', ['path' => '/captcha'], function (RouteBuilder $routes) {
	$routes->setExtensions(['png', 'jpg']);
	$routes->fallbacks(DashedRoute::class);
});
