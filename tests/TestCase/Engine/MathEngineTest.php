<?php
namespace Captcha\Test\TestCase\Engine;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Captcha\Engine\MathEngine;

/**
 *
 */
class MathEngineTest extends TestCase {

	/**
	 * @var \Captcha\Engine\MathEngine
	 */
	public $Math;

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
		$config = [];
		$this->Math = new MathEngine($config);
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();

		unset($this->Math);
	}

	/**
	 * @return void
	 */
	public function testGenerate() {
		$result = $this->Math->generate();
		$this->assertSame(['result', 'image'], array_keys($result));
	}

	/**
	 * @return void
	 */
	public function testGenerateOptions() {
		$options = [
			'imageFormat' => MathEngine::FORMAT_JPEG,
			'complexity' => 100,
		];
		$this->Math = new MathEngine($options);
		$result = $this->Math->generate();
		$this->assertSame(['result', 'image'], array_keys($result));
	}

}
