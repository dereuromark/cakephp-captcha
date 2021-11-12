<?php

namespace Captcha\Test\TestCase\Controller;

use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestCase;

class CaptchaControllerTest extends IntegrationTestCase {

	/**
	 * Fixtures
	 *
	 * @var array<string>
	 */
	protected $fixtures = [
		'plugin.Captcha.Captchas',
		'core.Sessions',
	];

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		Configure::write('Captcha', []);
	}

	/**
	 * @return void
	 */
	public function testDisplay() {
		$this->disableErrorHandlerMiddleware();

		$id = 1;
		$this->get(['plugin' => 'Captcha', 'controller' => 'Captcha', 'action' => 'display', $id]);

		$this->assertResponseCode(200);

		$this->assertContentType('image/png');
		$this->assertHeaderContains('Content-Transfer-Encoding', 'binary');
		$this->assertResponseNotEmpty();
	}

	/**
	 * @return void
	 */
	public function testDisplayExt() {
		$this->disableErrorHandlerMiddleware();

		$id = 1;
		$this->get(['plugin' => 'Captcha', 'controller' => 'Captcha', 'action' => 'display', $id, '_ext' => 'png']);

		$this->assertResponseCode(200);

		$this->assertContentType('image/png');
		$this->assertHeaderContains('Content-Transfer-Encoding', 'binary');
		$this->assertResponseNotEmpty();
	}

	/**
	 * @return void
	 */
	public function testDisplayExtJpg() {
		$this->disableErrorHandlerMiddleware();

		$id = 1;
		$this->get(['plugin' => 'Captcha', 'controller' => 'Captcha', 'action' => 'display', $id, '_ext' => 'jpg']);

		$this->assertResponseCode(200);

		$this->assertContentType('image/jpeg');
		$this->assertHeaderContains('Content-Transfer-Encoding', 'binary');
		$this->assertResponseNotEmpty();
	}

}
