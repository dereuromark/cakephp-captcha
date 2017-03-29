<?php
namespace Captcha\Test\TestCase\Controller;

use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestCase;

class CaptchaControllerTest extends IntegrationTestCase {

	/**
	 * Fixtures
	 *
	 * @var array
	 */
	public $fixtures = [
		'plugin.captcha.captchas',
		'core.sessions',
	];

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
	}

	/**
	 * @return void
	 */
	public function testDisplay() {
		$id = 1;
		$this->get(['plugin' => 'Captcha', 'controller' => 'Captcha', 'action' => 'display', $id]);

		$this->assertResponseCode(200);

		$this->assertContentType('image/png');
		$this->assertHeaderContains('Content-Transfer-Encoding', 'binary');
		$this->assertResponseNotEmpty();
	}

}
