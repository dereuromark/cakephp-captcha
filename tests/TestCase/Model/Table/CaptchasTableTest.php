<?php

namespace Captcha\Test\TestCase\Model\Table;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;

class CaptchasTableTest extends TestCase {

	/**
	 * @var \Captcha\Model\Table\CaptchasTable
	 */
	protected $Captchas;

	/**
	 * Fixtures
	 *
	 * @var array<string>
	 */
	protected $fixtures = [
		'plugin.Captcha.Captchas',
	];

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$config = $this->getTableLocator()->exists('Captchas') ? [] : ['className' => 'Captcha\Model\Table\CaptchasTable'];
		$this->Captchas = $this->getTableLocator()->get('Captchas', $config);

		Configure::delete('Captcha.maxPerUser');
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	public function tearDown(): void {
		unset($this->Captchas);

		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function testTouch() {
		$sessionId = 'cli';
		$ip = '123';
		$result = $this->Captchas->touch($sessionId, $ip);

		$this->assertNotEmpty($result);
	}

	/**
	 * @return void
	 */
	public function testTouchTooManyAttemps() {
		$sessionId = 'cli';
		$ip = '123';

		// Simulate too many attempts by setting the limit to zero
		Configure::write('Captcha.maxPerUser', 0);

		// Shouldn't throw an exception
		$result = $this->Captchas->touch($sessionId, $ip);

		$this->assertNotEmpty($result);
	}

	/**
	 * @return void
	 */
	public function testCleanup() {
		$result = $this->Captchas->cleanup();

		$this->assertNotEmpty($result);
	}

}
