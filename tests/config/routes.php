<?php

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\Router;

Router::scope('/', function($routes) {
	$routes->connect('/:controller', ['action' => 'index'], ['routeClass' => DashedRoute::class]);
	$routes->connect('/:controller/:action/*', [], ['routeClass' => DashedRoute::class]);
});
