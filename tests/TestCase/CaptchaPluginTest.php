<?php
declare(strict_types=1);

namespace Captcha\Test\TestCase;

use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Captcha\CaptchaPlugin;

class CaptchaPluginTest extends TestCase {

	public function testCustomAdminPrefixPathPreservesControllerPrefix(): void {
		Configure::write('Captcha.adminPrefixPath', '/backend');
		Configure::write('Captcha.adminRoutePath', '/spam');
		Router::reload();
		Router::createRouteBuilder('/')->scope('/', function (RouteBuilder $routes): void {
			(new CaptchaPlugin())->routes($routes);
		});
		$this->assertSame('/backend/spam', Router::url(['plugin' => 'Captcha', 'prefix' => 'Admin', 'controller' => 'Captcha', 'action' => 'index']));
		$params = Router::parseRequest(new ServerRequest(['url' => '/backend/spam']));
		$this->assertSame('Admin', $params['prefix']);
		$this->assertSame('Captcha', $params['plugin']);
		Configure::delete('Captcha');
		Router::reload();
	}

}
