<?php

namespace Captcha\Test\Model\Behavior;

use Cake\TestSuite\TestCase;
use TestApp\Form\PassiveCaptchaTestForm;

class PassiveCaptchaBehaviorTest extends TestCase {

	/**
	 * @var \TestApp\Form\PassiveCaptchaTestForm
	 */
	protected $Form;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->Form = new PassiveCaptchaTestForm();
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();

		unset($this->Form);
	}

	/**
	 * @return void
	 */
	public function testExecute() {
		$this->Form->addBehavior('Captcha.PassiveCaptcha');
		$this->Form->behaviors()->PassiveCaptcha->addValidation($this->Form->getValidator());

		$data = [
			'foo' => 'bar',
			'email_homepage' => '123',
		];
		$result = $this->Form->execute($data);
		$this->assertFalse($result);

		$data = [
			'foo' => 'bar',
		];
		$result = $this->Form->execute($data);
		$this->assertFalse($result);

		$data = [
			'foo' => 'bar',
			'email_homepage' => '',
		];
		$result = $this->Form->execute($data);
		$this->assertTrue($result);
	}

	/**
	 * @return void
	 */
	public function testExecuteMultiple() {
		$config = [
			'dummyField' => ['dummy_one', 'dummy_two'],
		];
		$this->Form->addBehavior('Captcha.PassiveCaptcha', $config);
		$this->Form->behaviors()->PassiveCaptcha->addValidation($this->Form->getValidator());

		$data = [
			'dummy_one' => '1',
			'dummy_two' => '',
		];
		$result = $this->Form->execute($data);
		$this->assertFalse($result);

		$data = [
			'dummy_one' => '',
			'dummy_two' => '',
		];
		$result = $this->Form->execute($data);
		$this->assertTrue($result);
	}

}
