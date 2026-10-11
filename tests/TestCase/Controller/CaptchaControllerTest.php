<?php

namespace Captcha\Test\TestCase\Controller;

use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Event\EventManager;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Captcha\Controller\CaptchaController;
use LogicException;
use PHPUnit\Framework\Attributes\UsesClass;

#[UsesClass(CaptchaController::class)]
class CaptchaControllerTest extends TestCase {

	use IntegrationTestTrait;

	/**
	 * Fixtures
	 *
	 * @var array<string>
	 */
	protected array $fixtures = [
		'plugin.Captcha.Captchas',
		'core.Sessions',
	];

	/**
	 * setUp method
	 *
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
	public function testDisplay() {
		$this->disableErrorHandlerMiddleware();

		$id = '11111111-1111-4111-8111-111111111111';
		$this->get(['plugin' => 'Captcha', 'controller' => 'Captcha', 'action' => 'display', $id]);

		$this->assertResponseCode(200);

		$this->assertContentType('image/png');
		$this->assertHeaderContains('Content-Transfer-Encoding', 'binary');
		$this->assertHeaderContains('Cache-Control', 'no-store, no-cache, must-revalidate');
		$this->assertHeaderContains('Pragma', 'no-cache');
		$this->assertHeaderContains('Expires', '0');
		$this->assertResponseNotEmpty();
	}

	/**
	 * @return void
	 */
	public function testDisplayExt() {
		$this->disableErrorHandlerMiddleware();

		$id = '11111111-1111-4111-8111-111111111111';
		$this->get(['plugin' => 'Captcha', 'controller' => 'Captcha', 'action' => 'display', $id, '_ext' => 'png']);

		$this->assertResponseCode(200);

		$this->assertContentType('image/png');
		$this->assertHeaderContains('Content-Transfer-Encoding', 'binary');
		$this->assertHeaderContains('Cache-Control', 'no-store, no-cache, must-revalidate');
		$this->assertHeaderContains('Pragma', 'no-cache');
		$this->assertHeaderContains('Expires', '0');
		$this->assertResponseNotEmpty();
	}

	/**
	 * @return void
	 */
	public function testDisplayRejectsPost() {
		$this->disableErrorHandlerMiddleware();
		$this->expectException(MethodNotAllowedException::class);

		$id = '11111111-1111-4111-8111-111111111111';
		$this->post(['plugin' => 'Captcha', 'controller' => 'Captcha', 'action' => 'display', $id]);
	}

	/**
	 * @return void
	 */
	public function testDisplayExtJpg() {
		$this->disableErrorHandlerMiddleware();

		$id = '11111111-1111-4111-8111-111111111111';
		$this->get(['plugin' => 'Captcha', 'controller' => 'Captcha', 'action' => 'display', $id, '_ext' => 'jpg']);

		$this->assertResponseCode(200);

		$this->assertContentType('image/jpeg');
		$this->assertHeaderContains('Content-Transfer-Encoding', 'binary');
		$this->assertHeaderContains('Cache-Control', 'no-store, no-cache, must-revalidate');
		$this->assertHeaderContains('Pragma', 'no-cache');
		$this->assertHeaderContains('Expires', '0');
		$this->assertResponseNotEmpty();
	}

	public function testDisplayWithComponentWithoutAuthorizationMiddleware(): void {
		$listener = function (EventInterface $event): void {
			$controller = $event->getSubject();
			$controller->components()->set('Authorization', new class ($controller->components()) extends Component {
				public function skipAuthorization(): void {
					throw new LogicException('No Authorization middleware service');
				}
			});
		};
		EventManager::instance()->on('Controller.initialize', $listener);
		try {
			$this->get(['plugin' => 'Captcha', 'controller' => 'Captcha', 'action' => 'display', '11111111-1111-4111-8111-111111111111']);
		} finally {
			EventManager::instance()->off('Controller.initialize', $listener);
		}

		$this->assertResponseOk();
	}

}
