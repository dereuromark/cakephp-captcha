<?php

use Cake\Routing\Router;
use Cake\Routing\Route\DashedRoute;

Router::scope('/', function($routes) {
	$routes->connect('/:controller', ['action' => 'index'], ['routeClass' => DashedRoute::class]);
	$routes->connect('/:controller/:action/*', [], ['routeClass' => DashedRoute::class]);
});
