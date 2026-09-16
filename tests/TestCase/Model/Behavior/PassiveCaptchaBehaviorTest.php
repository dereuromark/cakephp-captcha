<?php

namespace Captcha\Test\TestCase\Model\Behavior;

use Cake\Log\Log;
use Cake\TestSuite\LogTestTrait;
use Cake\TestSuite\TestCase;
use TestApp\Form\PassiveCaptchaTestForm;

class PassiveCaptchaBehaviorTest extends TestCase {

	use LogTestTrait;

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
		$this->Form->behaviors()->PassiveCaptcha->addPassiveCaptchaValidation($this->Form->getValidator());

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
		$this->Form->behaviors()->PassiveCaptcha->addPassiveCaptchaValidation($this->Form->getValidator());

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

	/**
	 * The submitted value is attacker controlled: it must not reach the log verbatim.
	 *
	 * @return void
	 */
	public function testExecuteLogsSanitizedValue() {
		$this->setupLog(['info' => ['className' => 'Array']]);

		$this->Form->addBehavior('Captcha.PassiveCaptcha', ['log' => true]);
		$this->Form->behaviors()->PassiveCaptcha->addPassiveCaptchaValidation($this->Form->getValidator());

		$data = [
			'foo' => 'bar',
			'email_homepage' => "spam\nINFO: injected line",
		];
		$this->assertFalse($this->Form->execute($data));

		$this->assertLogMessageContains('info', 'PassiveCaptcha trigger on field `email_homepage`');

		$messages = Log::engine('test-info')->read();
		$this->assertCount(1, $messages);
		$this->assertStringNotContainsString("\n", $messages[0]);
		$this->assertStringContainsString('(24 bytes)', $messages[0]);
	}

}
