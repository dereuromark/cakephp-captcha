<?php
declare(strict_types=1);

namespace Captcha\Test\TestCase\Controller\Admin;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Event\EventManager;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\ServerRequest;
use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Captcha\Cache\VerifyRateLimiter;
use RuntimeException;

/**
 * @uses \Captcha\Controller\Admin\CaptchaController
 */
class CaptchaControllerTest extends TestCase {

	use IntegrationTestTrait;

	/**
	 * @var array<string>
	 */
	protected array $fixtures = [
		'plugin.Captcha.Captchas',
		'core.Sessions',
	];

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		Configure::write('Captcha', []);
		$this->loadPlugins(['Captcha']);
	}

	/**
	 * @return void
	 */
	public function testIndexDeniedByDefault() {
		$this->disableErrorHandlerMiddleware();
		$this->expectException(ForbiddenException::class);
		$this->get(['plugin' => 'Captcha', 'prefix' => 'Admin', 'controller' => 'Captcha', 'action' => 'index']);
	}

	/**
	 * @return void
	 */
	public function testIndexAllowedWithClosure() {
		Configure::write('Captcha.adminAccess', fn (ServerRequest $request): bool => true);

		$this->get(['plugin' => 'Captcha', 'prefix' => 'Admin', 'controller' => 'Captcha', 'action' => 'index']);

		$this->assertResponseOk();
		$this->assertResponseContains('Captcha');
	}

	/**
	 * @return void
	 */
	public function testIndexClosureMayDeny() {
		Configure::write('Captcha.adminAccess', fn (ServerRequest $request): bool => false);

		$this->disableErrorHandlerMiddleware();
		$this->expectException(ForbiddenException::class);
		$this->get(['plugin' => 'Captcha', 'prefix' => 'Admin', 'controller' => 'Captcha', 'action' => 'index']);
	}

	/**
	 * @return void
	 */
	public function testConfig() {
		Configure::write('Captcha', ['adminAccess' => fn (): bool => true, 'maxPerUser' => 42]);

		$this->get(['plugin' => 'Captcha', 'prefix' => 'Admin', 'controller' => 'Captcha', 'action' => 'config']);

		$this->assertResponseOk();
		$this->assertResponseContains('Captcha.maxPerUser');
		$this->assertResponseContains('42');
	}

	/**
	 * @return void
	 */
	public function testEngine() {
		Configure::write('Captcha.adminAccess', fn (): bool => true);

		$this->get(['plugin' => 'Captcha', 'prefix' => 'Admin', 'controller' => 'Captcha', 'action' => 'engine']);

		$this->assertResponseOk();
		$this->assertResponseContains('MathEngine');
	}

	/**
	 * @return void
	 */
	public function testCleanupRunsAndFlashes() {
		Configure::write('Captcha.adminAccess', fn (): bool => true);
		$this->enableRetainFlashMessages();

		$this->post(['plugin' => 'Captcha', 'prefix' => 'Admin', 'controller' => 'Captcha', 'action' => 'cleanup']);

		$this->assertRedirect(['plugin' => 'Captcha', 'prefix' => 'Admin', 'controller' => 'Captcha', 'action' => 'index']);
		$this->assertFlashMessage(__d('captcha', '{0} captcha row(s) deleted.', 1));
	}

	/**
	 * @return void
	 */
	public function testHardResetTruncates() {
		Configure::write('Captcha.adminAccess', fn (): bool => true);
		$this->enableRetainFlashMessages();

		$this->post(['plugin' => 'Captcha', 'prefix' => 'Admin', 'controller' => 'Captcha', 'action' => 'hardReset']);

		$this->assertRedirect(['plugin' => 'Captcha', 'prefix' => 'Admin', 'controller' => 'Captcha', 'action' => 'index']);
		$Captchas = $this->getTableLocator()->get('Captcha.Captchas');
		$this->assertSame(0, $Captchas->find()->count());
	}

	/**
	 * @return void
	 */
	public function testCleanupRequiresPost() {
		Configure::write('Captcha.adminAccess', fn (): bool => true);

		$this->disableErrorHandlerMiddleware();
		$this->expectException(MethodNotAllowedException::class);
		$this->get(['plugin' => 'Captcha', 'prefix' => 'Admin', 'controller' => 'Captcha', 'action' => 'cleanup']);
	}

	/**
	 * @return void
	 */
	public function testIndexCountsCorrectly() {
		Configure::write('Captcha.adminAccess', fn (): bool => true);

		$Captchas = $this->getTableLocator()->get('Captcha.Captchas');
		// Solved
		$row = $Captchas->newEntity([
			'uuid' => '22222222-2222-4222-8222-222222222222',
			'session_id' => 's',
			'ip' => '1.1.1.1',
			'result' => '1',
			'used' => new DateTime('-30 minutes'),
			'solved' => true,
		]);
		$Captchas->saveOrFail($row);
		// Failed
		$row = $Captchas->newEntity([
			'uuid' => '33333333-3333-4333-8333-333333333333',
			'session_id' => 's',
			'ip' => '2.2.2.2',
			'result' => '1',
			'used' => new DateTime('-30 minutes'),
			'solved' => false,
		]);
		$Captchas->saveOrFail($row);

		$this->get(['plugin' => 'Captcha', 'prefix' => 'Admin', 'controller' => 'Captcha', 'action' => 'index']);

		$this->assertResponseOk();
		$this->assertResponseContains('Solved');
		$this->assertResponseContains('Failed');
	}

	public function testDashboardCountsPassiveOnlyThrottleAndReflectsClear(): void {
		Cache::setConfig('dashboard_limiter', ['className' => 'Array']);
		try {
			$config = ['enabled' => true, 'maxFailures' => 1, 'window' => 600, 'scope' => 'ip_session', 'cache' => 'dashboard_limiter'];
			Configure::write('Captcha', ['adminAccess' => fn (): bool => true, 'verifyRateLimit' => $config]);
			Configure::write('Captcha.verifyRateLimit.enabled', false);
			$limiter = new VerifyRateLimiter($config);
			$limiter->increment('9.9.9.9', 'passive-only');
			$this->get(['plugin' => 'Captcha', 'prefix' => 'Admin', 'controller' => 'Captcha', 'action' => 'index']);
			$this->assertResponseOk();
			$this->assertSame(1, $this->viewVariable('throttledIps'));
			$limiter->clear('9.9.9.9', 'passive-only');
			$this->get(['plugin' => 'Captcha', 'prefix' => 'Admin', 'controller' => 'Captcha', 'action' => 'index']);
			$this->assertResponseOk();
			$this->assertSame(0, $this->viewVariable('throttledIps'));
		} finally {
			Cache::drop('dashboard_limiter');
		}
	}

	public function testHostRedirectStopsLaterListeners(): void {
		Configure::write('Test.hostRedirectViaEvent', true);
		Configure::write('Captcha.adminAccess', function (): bool {
			$this->fail('The plugin gate must not run after a host redirect.');
		});
		$listener = function (EventInterface $event): void {
			$this->fail('Later listeners must not replace the host redirect.');
		};
		EventManager::instance()->on('Controller.initialize', ['priority' => 100], $listener);

		try {
			$this->get(['prefix' => 'Admin', 'plugin' => 'Captcha', 'controller' => 'Captcha', 'action' => 'index']);
			$this->assertRedirect('/login');
		} finally {
			EventManager::instance()->off('Controller.initialize', $listener);
			Configure::delete('Test.hostRedirectViaEvent');
		}
	}

	public function testReturnedHostRedirectStopsAction(): void {
		Configure::write('Test.hostRedirect', true);
		Configure::write('Captcha.adminAccess', function (): bool {
			$this->fail('The plugin gate must not run after a host redirect.');
		});
		try {
			$this->get(['prefix' => 'Admin', 'plugin' => 'Captcha', 'controller' => 'Captcha', 'action' => 'index']);
			$this->assertRedirect('/login');
		} finally {
			Configure::delete('Test.hostRedirect');
		}
	}

	public function testGateExceptionDeniesAccess(): void {
		$this->disableErrorHandlerMiddleware();
		Configure::write('Captcha.adminAccess', function (): bool {
			throw new RuntimeException('Gate unavailable');
		});
		$this->expectException(ForbiddenException::class);

		$this->get(['prefix' => 'Admin', 'plugin' => 'Captcha', 'controller' => 'Captcha', 'action' => 'index']);
	}

	public function testRequestAuthorizationServiceIsMarkedChecked(): void {
		$service = new class {
			public bool $skipped = false;

			public function skipAuthorization(): void {
				$this->skipped = true;
			}
		};
		Configure::write('Captcha.adminAccess', fn (): bool => true);
		$listener = function (EventInterface $event) use ($service): void {
			$controller = $event->getSubject();
			$controller->setRequest($controller->getRequest()->withAttribute('authorization', $service));
		};
		EventManager::instance()->on('Controller.initialize', $listener);
		try {
			$this->get(['prefix' => 'Admin', 'plugin' => 'Captcha', 'controller' => 'Captcha', 'action' => 'index']);
		} finally {
			EventManager::instance()->off('Controller.initialize', $listener);
		}

		$this->assertResponseOk();
		$this->assertTrue($service->skipped);
	}

}
