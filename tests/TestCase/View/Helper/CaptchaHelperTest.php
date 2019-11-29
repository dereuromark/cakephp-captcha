<?php

namespace Captcha\Test\TestCase\View\Helper;

use Cake\Core\Configure;
use Cake\Network\Request;
use Cake\Network\Session;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use Captcha\View\Helper\CaptchaHelper;

class CaptchaHelperTest extends TestCase {

	/**
	 * @var array
	 */
	public $fixtures = ['plugin.captcha.captchas'];

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
	 * @var \Cake\Network\Session
	 */
	protected $session;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		Configure::write('Captcha', [
			]
		);

		Router::reload();

		$this->request = new Request();
		$this->session = new Session();
		$this->request->session($this->session);
		$this->View = new View($this->request);
		$this->Captcha = new CaptchaHelper($this->View);
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();

		unset($this->Captcha);
	}

	/**
	 * @return void
	 */
	public function testRender() {
		$this->request->env('REMOTE_ADDR', '127.0.0.1');

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
