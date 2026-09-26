<?php

namespace Captcha\Test\TestCase\View\Helper;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use Captcha\Cache\RateLimitKey;
use Captcha\Engine\NullEngine;
use Captcha\View\Helper\CaptchaHelper;

class CaptchaHelperTest extends TestCase {

	/**
	 * @var array<string>
	 */
	protected array $fixtures = [
		'plugin.Captcha.Captchas',
	];

	/**
	 * @var \Cake\View\View
	 */
	protected $View;

	/**
	 * @var \Captcha\View\Helper\CaptchaHelper
	 */
	protected $Captcha;

	/**
	 * @var \Cake\Http\ServerRequest
	 */
	protected $request;

	/**
	 * @var \Cake\Http\Session
	 */
	protected $session;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		Configure::write('Captcha', []);

		$this->request = new ServerRequest();
		$this->View = new View($this->request);
		$this->Captcha = new CaptchaHelper($this->View);

		Router::defaultRouteClass(DashedRoute::class);
		$builder = Router::createRouteBuilder('/');
		$builder->plugin('Captcha', function (RouteBuilder $routes): void {
			$routes->fallbacks(DashedRoute::class);
		});
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();

		unset($this->Captcha);
	}

	/**
	 * @return void
	 */
	public function testRender() {
		$this->request = $this->request->withEnv('REMOTE_ADDR', '127.0.0.1');
		$this->View->setRequest($this->request);

		$result = $this->Captcha->render();
		$this->assertMatchesRegularExpression(
			'#^<div class="input text required"><label for="captcha-result"><img src="/captcha/captcha/display/[0-9a-f-]{36}" alt=""></label><input type="text" name="captcha_result" autocomplete="off" required="required" id="captcha-result" value=""></div><input type="hidden" name="captcha_uuid" id="captcha-uuid" value="[0-9a-f-]{36}"><div style="display: none"><input type="text" name="email_homepage" value=""></div>$#',
			$result,
		);
	}

	/**
	 * @return void
	 */
	public function testRenderPng() {
		$this->Captcha->setConfig(['ext' => 'png']);

		$this->request = $this->request->withEnv('REMOTE_ADDR', '127.0.0.1');
		$this->View->setRequest($this->request);

		$result = $this->Captcha->render();
		$this->assertMatchesRegularExpression(
			'#^<div class="input text required"><label for="captcha-result"><img src="/captcha/captcha/display/[0-9a-f-]{36}\.png" alt=""></label><input type="text" name="captcha_result" autocomplete="off" required="required" id="captcha-result" value=""></div><input type="hidden" name="captcha_uuid" id="captcha-uuid" value="[0-9a-f-]{36}"><div style="display: none"><input type="text" name="email_homepage" value=""></div>$#',
			$result,
		);
	}

	/**
	 * @return void
	 */
	public function testRenderJpg() {
		$this->Captcha->setConfig(['ext' => 'jpg']);

		$this->request = $this->request->withEnv('REMOTE_ADDR', '127.0.0.1');
		$this->View->setRequest($this->request);

		$result = $this->Captcha->render();
		$this->assertMatchesRegularExpression(
			'#^<div class="input text required"><label for="captcha-result"><img src="/captcha/captcha/display/[0-9a-f-]{36}\.jpg" alt=""></label><input type="text" name="captcha_result" autocomplete="off" required="required" id="captcha-result" value=""></div><input type="hidden" name="captcha_uuid" id="captcha-uuid" value="[0-9a-f-]{36}"><div style="display: none"><input type="text" name="email_homepage" value=""></div>$#',
			$result,
		);
	}

	/**
	 * @return void
	 */
	public function testPassive() {
		$result = $this->Captcha->passive();
		$expected = '<div style="display: none"><input type="text" name="email_homepage" value=""></div>';
		$this->assertSame($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testPassiveWithClass() {
		$this->Captcha->setConfig(['passiveClass' => 'd-none']);

		$result = $this->Captcha->passive();
		$expected = '<div class="d-none"><input type="text" name="email_homepage" value=""></div>';
		$this->assertSame($expected, $result);
	}

	public function testRenderingDoesNotCountFailuresAndPreservesRequiredInput(): void {
		Cache::setConfig('helper_limiter', ['className' => 'Array']);
		try {
			$limit = ['enabled' => true, 'maxFailures' => 3, 'scope' => 'ip', 'cache' => 'helper_limiter'];
			$this->request = $this->request->withEnv('REMOTE_ADDR', '127.0.0.1');
			$this->View->setRequest($this->request);
			Router::setRequest($this->request);
			$table = $this->getTableLocator()->get('HelperComments', ['table' => 'captchas']);
			$table->addBehavior('Captcha.Captcha', ['verifyRateLimit' => $limit]);
			$table->addBehavior('Captcha.PassiveCaptcha', ['verifyRateLimit' => $limit, 'log' => false]);
			$key = RateLimitKey::build('127.0.0.1', '', 'ip', 600);
			Cache::write($key, 1, 'helper_limiter');
			$this->Captcha->Form->create($table->newEmptyEntity());
			$this->assertStringContainsString('required="required"', $this->Captcha->render());
			// Older EntityContext versions query emptiness when rendering controls.
			$this->assertFalse($table->getValidator()->isEmptyAllowed('captcha_result', true));
			$this->assertTrue($table->getValidator()->isPresenceRequired('captcha_result', true));
			$this->assertTrue($table->getValidator()->isEmptyAllowed('email_homepage', true));
			$this->assertStringNotContainsString('required="required"', $this->Captcha->Form->control('email_homepage'));
			$this->assertSame(1, Cache::read($key, 'helper_limiter'));
			$this->assertStringNotContainsString('required="required"', $this->Captcha->control(['required' => false]));
			$this->Captcha->setConfig('engine', NullEngine::class);
			$this->assertStringNotContainsString('required="required"', $this->Captcha->render());
			$this->Captcha->Form->end();
		} finally {
			Cache::drop('helper_limiter');
		}
	}

}
