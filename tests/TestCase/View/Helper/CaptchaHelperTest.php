<?php
namespace Captcha\Test\TestCase\View\Helper;

use Cake\Core\Configure;
use Cake\Network\Request;
use Cake\Network\Session;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use Captcha\View\Helper\CaptchaHelper;

/**
 *
 */
class CaptchaHelperTest extends TestCase {

	public $fixtures = ['plugin.captcha.captchas'];

	/**
	 * @var \Cake\View\View
	 */
	public $View;

	/**
	 * @var \Captcha\View\Helper\CaptchaHelper
	 */
	public $Captcha;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		Configure::write('Captcha', [
				'debug' => false,
			]
		);

		Router::reload();

		$this->request = new Request(); //$this->getMockBuilder(Request::class)->setMethods([''])->getMock([]);
		$this->session = $this->getMockBuilder(Session::class)->setMethods(['id'])->getMock();
		$this->request->session($this->session);
		$this->session->expects($this->once())->method('id')->willReturn(1);
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
<div class="input text"><label for="captcha-result"><img src="/captcha/captcha/display/2" alt=""/></label><input type="text" name="captcha_result" id="captcha-result"/></div><input type="hidden" name="captcha_id" id="captcha-id" value="2"/><div style="display: none"><div class="input text"><label for="email-homepage">Email Homepage</label><input type="text" name="email_homepage" id="email-homepage" value=""/></div></div>
HTML;
		$this->assertSame($expected, $result);
	}

}
