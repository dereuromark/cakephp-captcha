<?php

namespace Captcha\Test\TestCase\Engine;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Captcha\Engine\NullEngine;

class NullEngineTest extends TestCase {

	/**
	 * @var \Captcha\Engine\NullEngine
	 */
	protected $engine;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		Configure::write('Captcha', []);
		$config = [];
		$this->engine = new NullEngine($config);
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();

		unset($this->engine);
	}

	/**
	 * @return void
	 */
	public function testGenerate() {
		$result = $this->engine->generate();
		$this->assertSame(['result', 'image'], array_keys($result));
	}

}
