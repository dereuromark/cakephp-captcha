<?php
namespace Captcha\Test\TestCase\View\Helper;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use Captcha\View\Helper\CaptchaHelper;

/**
 *
 */
class CaptchaHelperTest extends TestCase {

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

		$this->request = $this->getMock('Cake\Network\Request', []);
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
	public function testEncodeDecode() {
		$id = 1;

	}

}
