<?php
namespace Captcha\Test\TestCase\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

class CaptchasTableTest extends TestCase {

	/**
	 * @var \Captcha\Model\Table\CaptchasTable
	 */
	protected $Captchas;

	/**
	 * Fixtures
	 *
	 * @var array
	 */
	public $fixtures = [
		'plugin.Captcha.Captchas',
	];

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$config = TableRegistry::exists('Captchas') ? [] : ['className' => 'Captcha\Model\Table\CaptchasTable'];
		$this->Captchas = TableRegistry::get('Captchas', $config);
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	public function tearDown() {
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
	public function testCleanup() {
		$result = $this->Captchas->cleanup();

		$this->assertNotEmpty($result);
	}

}
