<?php

namespace Captcha\Test\TestCase\Model\Behavior;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\Log\Log;
use Cake\Routing\Router;
use Cake\TestSuite\LogTestTrait;
use Cake\TestSuite\TestCase;
use Captcha\Cache\RateLimitKey;
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

		Configure::delete('Captcha');
		Cache::drop('captcha_test');
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

	public function testGlobalDummyFieldConfigIsApplied(): void {
		Configure::write('Captcha.dummyField', 'my_trap');
		$this->Form->addBehavior('Captcha.PassiveCaptcha');

		$this->assertSame('my_trap', $this->Form->behaviors()->PassiveCaptcha->getConfig('dummyField'));
	}

	public function testHoneypotFailuresAreCountedPerValidation(): void {
		Cache::drop('captcha_test');
		Cache::setConfig('captcha_test', ['className' => 'Array']);
		Router::setRequest((new ServerRequest())->withEnv('REMOTE_ADDR', '127.0.0.1'));
		$this->Form->addBehavior('Captcha.PassiveCaptcha', [
			'dummyField' => ['dummy_one', 'dummy_two'],
			'log' => false,
			'verifyRateLimit' => ['enabled' => true, 'maxFailures' => 3, 'scope' => 'ip', 'cache' => 'captcha_test'],
		]);
		$validator = $this->Form->getValidator();
		// Existing fields can have a different order and emptiness policy.
		$validator->allowEmptyString('dummy_two');
		$this->Form->behaviors()->PassiveCaptcha->addPassiveCaptchaValidation($validator);
		$key = RateLimitKey::build('127.0.0.1', '', 'ip', 600);
		$this->assertTrue($validator->isEmptyAllowed('dummy_two', true));
		$this->assertTrue($validator->isPresenceRequired('dummy_two', true));
		$this->assertNull(Cache::read($key, 'captcha_test'));

		$this->assertFalse($this->Form->execute(['dummy_one' => 'bot', 'dummy_two' => 'bot']));
		$this->assertSame(1, Cache::read($key, 'captcha_test'));
		$this->assertFalse($this->Form->execute(['dummy_one' => '', 'dummy_two' => null]));
		$this->assertSame(2, Cache::read($key, 'captcha_test'));
		$this->assertFalse($this->Form->execute([]));
		$this->assertSame(3, Cache::read($key, 'captcha_test'));
		$this->assertFalse($this->Form->execute(['dummy_one' => '', 'dummy_two' => '']));
		foreach (['dummy_one', 'dummy_two'] as $field) {
			$this->assertSame('Too many failed attempts. Please retry later', $this->Form->getErrors()[$field]['verifyRateLimit']);
		}
		$this->assertSame(3, Cache::read($key, 'captcha_test'));
	}

	public function testDefaultPassiveValidationNeedsNoRequest(): void {
		Router::reload();
		$this->Form->addBehavior('Captcha.PassiveCaptcha', ['log' => false]);
		$this->Form->behaviors()->PassiveCaptcha->addPassiveCaptchaValidation($this->Form->getValidator());
		$this->assertFalse($this->Form->execute(['email_homepage' => 'bot']));
		$this->assertTrue($this->Form->execute(['email_homepage' => '']));
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
