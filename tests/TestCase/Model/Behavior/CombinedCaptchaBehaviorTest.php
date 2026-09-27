<?php
declare(strict_types=1);

namespace Captcha\Test\TestCase\Model\Behavior;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\ORM\Table;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;
use Captcha\Cache\RateLimitKey;
use Captcha\Engine\MathEngine;
use Captcha\Engine\NullEngine;
use Captcha\Model\Entity\Captcha;
use Captcha\Model\Table\CaptchasTable;

class CombinedCaptchaBehaviorTest extends TestCase {

	protected array $fixtures = ['plugin.Captcha.Captchas', 'plugin.Captcha.Comments'];

	protected CaptchasTable $captchas;

	protected array $limit = ['enabled' => true, 'cache' => 'combined', 'maxFailures' => 3, 'window' => 600, 'scope' => 'ip'];

	public function setUp(): void {
		parent::setUp();
		Configure::delete('Captcha');
		Cache::drop('combined');
		Cache::setConfig('combined', ['className' => 'Array']);
		Router::setRequest((new ServerRequest())->withEnv('REMOTE_ADDR', '127.0.0.1'));
		$this->captchas = $this->getTableLocator()->get('Captcha.Captchas');
	}

	public function tearDown(): void {
		Cache::drop('combined');
		parent::tearDown();
	}

	protected function table(string $engine, bool $passiveFirst): Table {
		$table = new Table(['table' => 'comments', 'connection' => $this->captchas->getConnection()]);
		$behaviors = [
			'Captcha.Captcha' => ['engine' => $engine, 'minTime' => 0, 'maxTime' => 0, 'verifyRateLimit' => $this->limit],
			'Captcha.PassiveCaptcha' => ['log' => false, 'verifyRateLimit' => $this->limit],
		];
		foreach ($passiveFirst ? array_reverse($behaviors) : $behaviors as $name => $config) {
			$table->addBehavior($name, $config);
		}

		return $table;
	}

	protected function token(): Captcha {
		$session = Router::getRequest()->getSession();
		$session->start();
		$captcha = $this->captchas->newEntity([
			'uuid' => Text::uuid(),
			'result' => '7',
			'ip' => '127.0.0.1',
			'session_id' => $session->id() ?: 'test',
		]);
		$this->captchas->saveOrFail($captcha);

		return $captcha;
	}

	public function testCombinedFailuresAccumulateInBothOrdersAndEngines(): void {
		$key = RateLimitKey::build('127.0.0.1', '', 'ip', 600);
		foreach ([NullEngine::class, MathEngine::class] as $engine) {
			foreach ([false, true] as $passiveFirst) {
				foreach ([false, true] as $wrongAnswer) {
					Cache::clear('combined');
					$table = $this->table($engine, $passiveFirst);
					for ($i = 1; $i <= 4; $i++) {
						$captcha = $this->token();
						$entity = $table->newEntity([
							'captcha_uuid' => $captcha->uuid,
							'captcha_result' => $engine === NullEngine::class ? '' : ($wrongAnswer ? '8' : '7'),
							'email_homepage' => $i < 4 ? 'bot' : '',
						]);
						$this->assertTrue($entity->hasErrors());
						$this->assertSame(min($i, 3), Cache::read($key, 'combined'));
						if ($i === 4) {
							$this->assertArrayHasKey('verifyRateLimit', $entity->getError('captcha_result'));
							$this->assertNull($this->captchas->get($captcha->id)->used);
						}
					}
				}
			}
		}
	}

	public function testSuccessClearsFailuresOnlyWhenAllCaptchaChecksPass(): void {
		$key = RateLimitKey::build('127.0.0.1', '', 'ip', 600);
		foreach ([NullEngine::class, MathEngine::class] as $engine) {
			foreach ([false, true] as $passiveFirst) {
				$table = $this->table($engine, $passiveFirst);
				Cache::write($key, 1, 'combined');
				$captcha = $this->token();
				$entity = $table->newEntity([
					'captcha_uuid' => $captcha->uuid,
					'captcha_result' => $engine === NullEngine::class ? '' : '7',
					'email_homepage' => '',
				]);
				$this->assertFalse($entity->hasErrors());
				$this->assertNull(Cache::read($key, 'combined'));
			}
		}
	}

	public function testOnlyOnePreloadedEntityCanConsumeToken(): void {
		$captcha = $this->token();
		$first = $this->captchas->get($captcha->id);
		$second = $this->captchas->get($captcha->id);
		$this->assertTrue($this->captchas->markUsed($first));
		$second->solved = false;
		$this->assertFalse($this->captchas->markUsed($second));
		$this->assertNull($this->captchas->get($captcha->id)->solved);
		$this->assertNotNull($this->captchas->get($captcha->id)->used);
	}

	public function testDifferentThresholdsDoNotWeakenActiveGate(): void {
		$table = $this->table(NullEngine::class, true);
		$table->getBehavior('Captcha')->setConfig('verifyRateLimit.maxFailures', 1);
		$captcha = $this->token();
		$key = RateLimitKey::build('127.0.0.1', '', 'ip', 600);
		Cache::write($key, 1, 'combined');
		$entity = $table->newEntity([
			'captcha_uuid' => $captcha->uuid,
			'captcha_result' => '',
			'email_homepage' => '',
		]);
		$this->assertArrayHasKey('verifyRateLimit', $entity->getError('captcha_result'));
		$this->assertNull($this->captchas->get($captcha->id)->used);
		$this->assertSame(1, Cache::read($key, 'combined'));
	}

	public function testPartialOptInPreservesGlobalLimiterSettings(): void {
		Configure::write('Captcha.verifyRateLimit', ['enabled' => false] + $this->limit);
		foreach (['Captcha.Captcha', 'Captcha.PassiveCaptcha'] as $behavior) {
			$table = new Table(['table' => 'comments', 'connection' => $this->captchas->getConnection()]);
			$table->addBehavior($behavior, ['verifyRateLimit' => ['enabled' => true, 'maxFailures' => 2]]);
			$name = substr($behavior, strlen('Captcha.'));
			$this->assertEquals(
				['enabled' => true, 'maxFailures' => 2] + $this->limit,
				$table->getBehavior($name)->getConfig('verifyRateLimit'),
			);
		}
	}

}
