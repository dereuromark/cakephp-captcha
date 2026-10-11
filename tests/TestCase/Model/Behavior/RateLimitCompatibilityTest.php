<?php

namespace Captcha\Test\TestCase\Model\Behavior;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\ORM\Table;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\Validation\Validator;
use Captcha\Cache\RateLimitKey;
use TestApp\Model\Behavior\LegacyRateLimitBehavior;

class RateLimitCompatibilityTest extends TestCase {

	protected array $fixtures = ['plugin.Captcha.Captchas'];

	private ?ServerRequest $previousRequest;

	private mixed $previousConfig;

	public function setUp(): void {
		parent::setUp();
		$this->previousRequest = Router::getRequest();
		$this->previousConfig = Configure::read('Captcha');
		Configure::delete('Captcha');
		Cache::setConfig('captcha_compatibility', ['className' => 'Array']);
		$request = (new ServerRequest())->withEnv('REMOTE_ADDR', '127.0.0.1');
		Router::setRequest($request);
	}

	public function tearDown(): void {
		Cache::drop('captcha_compatibility');
		Configure::write('Captcha', $this->previousConfig);
		if ($this->previousRequest !== null) {
			Router::setRequest($this->previousRequest);
		} else {
			Router::reload();
		}
		parent::tearDown();
	}

	public function testLegacyKeyOverrideControlsLookupIncrementAndClear(): void {
		$behavior = $this->behavior();
		$key = 'tenant_' . RateLimitKey::build('127.0.0.1', '', 'ip', 600);
		$defaultKey = RateLimitKey::build('127.0.0.1', '', 'ip', 600);

		$behavior->recordFailure();
		$this->assertSame(1, Cache::read($key, 'captcha_compatibility'));
		$this->assertNull(Cache::read($defaultKey, 'captcha_compatibility'));
		$this->assertFalse($behavior->limited());
		$behavior->recordFailure();
		$this->assertTrue($behavior->limited());
		$behavior->clearFailures();
		$this->assertNull(Cache::read($key, 'captcha_compatibility'));
		$this->assertFalse($behavior->limited());
	}

	public function testLegacyEnableOverridePreventsLookupIncrementAndClear(): void {
		$behavior = $this->behavior();
		$behavior->enabled = false;
		$key = 'tenant_' . RateLimitKey::build('127.0.0.1', '', 'ip', 600);
		Cache::write($key, 2, 'captcha_compatibility');

		$this->assertFalse($behavior->limited());
		$behavior->recordFailure();
		$this->assertNull(Cache::read(RateLimitKey::build('127.0.0.1', '', 'ip', 600), 'captcha_compatibility'));
		$this->assertSame(2, Cache::read($key, 'captcha_compatibility'));
		$behavior->clearFailures();
		$this->assertSame(2, Cache::read($key, 'captcha_compatibility'));
	}

	public function testLegacyEnableOverrideCanForceRateLimitingOn(): void {
		$behavior = $this->behavior();
		$behavior->setConfig('verifyRateLimit.enabled', false);
		$behavior->forceEnabled = true;
		$key = 'tenant_' . RateLimitKey::build('127.0.0.1', '', 'ip', 600);

		$behavior->recordFailure();
		$behavior->recordFailure();
		$this->assertSame(2, Cache::read($key, 'captcha_compatibility'));
		$this->assertTrue($behavior->limited());
		$behavior->clearFailures();
		$this->assertFalse($behavior->limited());
	}

	public function testValidationContextUsesLegacyKeyOncePerSubmission(): void {
		$behavior = $this->behavior();
		$validator = new Validator();
		$validator->setProvider('table', $behavior);
		$behavior->addCaptchaValidation($validator);
		$data = ['captcha_uuid' => 'missing-uuid', 'captcha_result' => 'wrong'];
		$key = 'tenant_' . RateLimitKey::build('127.0.0.1', '', 'ip', 600);
		$defaultKey = RateLimitKey::build('127.0.0.1', '', 'ip', 600);

		$this->assertNotEmpty($validator->validate($data));
		$this->assertSame(1, Cache::read($key, 'captcha_compatibility'));
		$this->assertNotEmpty($validator->validate($data));
		$this->assertSame(2, Cache::read($key, 'captcha_compatibility'));
		$errors = $validator->validate($data);
		$this->assertArrayHasKey('verifyRateLimit', $errors['captcha_result']);
		$this->assertSame(2, Cache::read($key, 'captcha_compatibility'));
		$this->assertNull(Cache::read($defaultKey, 'captcha_compatibility'));
	}

	private function behavior(): LegacyRateLimitBehavior {
		return new LegacyRateLimitBehavior(new Table(['alias' => 'Comments']), [
			'verifyRateLimit' => [
				'enabled' => true,
				'maxFailures' => 2,
				'window' => 600,
				'scope' => 'ip',
				'cache' => 'captcha_compatibility',
			],
		]);
	}

}
