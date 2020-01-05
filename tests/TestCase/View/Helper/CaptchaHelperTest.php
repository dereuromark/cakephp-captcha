<?php

namespace Captcha\Test\TestCase\View\Helper;

use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\Routing\Route\DashedRoute;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use Captcha\View\Helper\CaptchaHelper;

class CaptchaHelperTest extends TestCase {

	/**
	 * @var array
	 */
	protected $fixtures = [
		'plugin.Captcha.Captchas',
	];

	/**
	 * @var \Cake\View\View
	 */
	protected $View;

	/**
	 * @var \Captcha\View\Helper\CaptchaHelper
	 */
	protected $Captcha;

	/**
	 * @var \Cake\Http\ServerRequest
	 */
	protected $request;

	/**
	 * @var \Cake\Http\Session
	 */
	protected $session;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		Configure::write('Captcha', []);

		$this->request = new ServerRequest();
		$this->View = new View($this->request);
		$this->Captcha = new CaptchaHelper($this->View);

		Router::plugin('Captcha', function (RouteBuilder $routes) {
			$routes->fallbacks(DashedRoute::class);
		});

	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();

		unset($this->Captcha);
	}

	/**
	 * @return void
	 */
	public function testRender() {
		$this->request = $this->request->withEnv('REMOTE_ADDR', '127.0.0.1');
		$this->View->setRequest($this->request);

		$result = $this->Captcha->render();
		$expected = <<<HTML
<div class="input text"><label for="captcha-result"><img src="/captcha/captcha/display/2" alt=""/></label><input type="text" name="captcha_result" autocomplete="off" id="captcha-result"/></div><input type="hidden" name="captcha_id" id="captcha-id" value="2"/><div style="display: none"><div class="input text"><label for="email-homepage">Email Homepage</label><input type="text" name="email_homepage" id="email-homepage" value=""/></div></div>
HTML;
		$this->assertSame($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testPassive() {
		$result = $this->Captcha->passive();
		$expected = '<div style="display: none"><div class="input text"><label for="email-homepage">Email Homepage</label><input type="text" name="email_homepage" id="email-homepage" value=""/></div></div>';
		$this->assertSame($expected, $result);
	}

}
