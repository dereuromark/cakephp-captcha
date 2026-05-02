<?php
declare(strict_types=1);

namespace Captcha\Test\TestCase\Controller\Admin;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Http\Exception\BadRequestException;
use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Captcha\Cache\RateLimitKey;

/**
 * @uses \Captcha\Controller\Admin\IpsController
 */
class IpsControllerTest extends TestCase {

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

		Configure::write('Captcha', ['adminAccess' => fn (): bool => true]);
		Cache::drop('captcha_admin_test');
		Cache::setConfig('captcha_admin_test', ['className' => 'Array']);
		$this->loadPlugins(['Captcha']);
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();

		Cache::clear('captcha_admin_test');
	}

	/**
	 * @return void
	 */
	public function testIndexShowsLeaderboards() {
		$Captchas = $this->getTableLocator()->get('Captcha.Captchas');
		foreach (['1.1.1.1', '2.2.2.2', '3.3.3.3'] as $i => $ip) {
			$row = $Captchas->newEntity([
				'uuid' => sprintf('11111111-1111-4111-8111-%012d', $i + 100),
				'session_id' => 's',
				'ip' => $ip,
				'result' => '1',
				'solved' => $i === 0 ? true : ($i === 1 ? false : null),
				'used' => $i < 2 ? new DateTime('-10 minutes') : null,
			]);
			$Captchas->saveOrFail($row);
		}

		$this->get(['plugin' => 'Captcha', 'prefix' => 'Admin', 'controller' => 'Ips', 'action' => 'index']);

		$this->assertResponseOk();
		$this->assertResponseContains('1.1.1.1');
		$this->assertResponseContains('2.2.2.2');
		$this->assertResponseContains('3.3.3.3');
	}

	/**
	 * @return void
	 */
	public function testIndexShowsThrottledIp() {
		Configure::write('Captcha.verifyRateLimit', [
			'enabled' => true,
			'maxFailures' => 2,
			'window' => 600,
			'scope' => 'ip_session',
			'cache' => 'captcha_admin_test',
		]);

		$Captchas = $this->getTableLocator()->get('Captcha.Captchas');
		for ($i = 0; $i < 3; $i++) {
			$row = $Captchas->newEntity([
				'uuid' => sprintf('11111111-1111-4111-8111-%012d', $i + 200),
				'session_id' => 's',
				'ip' => '9.9.9.9',
				'result' => '1',
				'solved' => false,
				'used' => new DateTime('-1 minute'),
			]);
			$Captchas->saveOrFail($row);
		}

		$this->get(['plugin' => 'Captcha', 'prefix' => 'Admin', 'controller' => 'Ips', 'action' => 'index']);

		$this->assertResponseOk();
		$this->assertResponseContains('9.9.9.9');
		$this->assertResponseContains('rate-limited');
	}

	/**
	 * @return void
	 */
	public function testViewShowsRowsForIp() {
		$Captchas = $this->getTableLocator()->get('Captcha.Captchas');
		$row = $Captchas->newEntity([
			'uuid' => '44444444-4444-4444-8444-444444444444',
			'session_id' => 'sessabc',
			'ip' => '4.4.4.4',
			'result' => '1',
			'solved' => true,
			'used' => new DateTime('-20 minutes'),
		]);
		$Captchas->saveOrFail($row);

		$this->get(['plugin' => 'Captcha', 'prefix' => 'Admin', 'controller' => 'Ips', 'action' => 'view', '4.4.4.4']);

		$this->assertResponseOk();
		$this->assertResponseContains('4.4.4.4');
		$this->assertResponseContains('44444444');
	}

	/**
	 * @return void
	 */
	public function testViewUnknownIpShowsEmptyState() {
		$this->get(['plugin' => 'Captcha', 'prefix' => 'Admin', 'controller' => 'Ips', 'action' => 'view', '0.0.0.0']);

		$this->assertResponseOk();
		$this->assertResponseContains(__d('captcha', 'No captchas seen for this IP.'));
	}

	/**
	 * @return void
	 */
	public function testViewRejectsNonIpString() {
		$this->disableErrorHandlerMiddleware();
		$this->expectException(BadRequestException::class);

		$this->get(['plugin' => 'Captcha', 'prefix' => 'Admin', 'controller' => 'Ips', 'action' => 'view', 'not-an-ip']);
	}

	/**
	 * @return void
	 */
	public function testResetRejectsNonIpString() {
		$this->disableErrorHandlerMiddleware();
		$this->expectException(BadRequestException::class);

		$this->post(['plugin' => 'Captcha', 'prefix' => 'Admin', 'controller' => 'Ips', 'action' => 'reset', 'bogus-value']);
	}

	/**
	 * @return void
	 */
	public function testClearRateLimitRejectsNonIpString() {
		$this->disableErrorHandlerMiddleware();
		$this->expectException(BadRequestException::class);

		$this->post(['plugin' => 'Captcha', 'prefix' => 'Admin', 'controller' => 'Ips', 'action' => 'clearRateLimit', 'totally-not-ip']);
	}

	/**
	 * @return void
	 */
	public function testResetDeletesIpRows() {
		$Captchas = $this->getTableLocator()->get('Captcha.Captchas');
		foreach (['5.5.5.5', '5.5.5.5', '6.6.6.6'] as $i => $ip) {
			$row = $Captchas->newEntity([
				'uuid' => sprintf('55555555-5555-4555-8555-%012d', $i + 300),
				'session_id' => 's',
				'ip' => $ip,
				'result' => '1',
			]);
			$Captchas->saveOrFail($row);
		}
		$this->enableRetainFlashMessages();

		$this->post(['plugin' => 'Captcha', 'prefix' => 'Admin', 'controller' => 'Ips', 'action' => 'reset', '5.5.5.5']);

		$this->assertRedirect(['plugin' => 'Captcha', 'prefix' => 'Admin', 'controller' => 'Ips', 'action' => 'index']);
		$this->assertSame(0, $Captchas->find()->where(['ip' => '5.5.5.5'])->count());
		$this->assertSame(1, $Captchas->find()->where(['ip' => '6.6.6.6'])->count());
	}

	/**
	 * @return void
	 */
	public function testClearRateLimitWipesCacheKeys() {
		Configure::write('Captcha.verifyRateLimit', [
			'enabled' => true,
			'maxFailures' => 5,
			'window' => 600,
			'scope' => 'ip_session',
			'cache' => 'captcha_admin_test',
		]);

		$Captchas = $this->getTableLocator()->get('Captcha.Captchas');
		$row = $Captchas->newEntity([
			'uuid' => '66666666-6666-4666-8666-666666666666',
			'session_id' => 'sess-known',
			'ip' => '7.7.7.7',
			'result' => '1',
			'solved' => false,
		]);
		$Captchas->saveOrFail($row);

		$key = RateLimitKey::build('7.7.7.7', 'sess-known', 'ip_session', 600);
		Cache::write($key, 99, 'captcha_admin_test');
		$this->assertSame(99, Cache::read($key, 'captcha_admin_test'));

		$this->enableRetainFlashMessages();
		$this->post(['plugin' => 'Captcha', 'prefix' => 'Admin', 'controller' => 'Ips', 'action' => 'clearRateLimit', '7.7.7.7']);

		$this->assertRedirect(['plugin' => 'Captcha', 'prefix' => 'Admin', 'controller' => 'Ips', 'action' => 'index']);
		$this->assertNull(Cache::read($key, 'captcha_admin_test'));
	}

}
