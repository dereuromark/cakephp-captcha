<?php

namespace Captcha\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Form\Form;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Captcha\Controller\Component\CaptchaComponent;
use Captcha\Model\Table\CaptchasTable;

class CaptchaComponentTest extends TestCase {

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
	}

	/**
	 * @return void
	 */
	public function testAddValidation() {
		$captchaComponent = new CaptchaComponent(new ComponentRegistry(new Controller(new ServerRequest())));

		$contactForm = new Form();

		$captchaComponent->addValidation($contactForm->getValidator());

		$this->assertFalse($contactForm->execute([]));
	}

	public function testDisabledAutoAllowsModelLessController(): void {
		$controller = new class (new ServerRequest()) extends Controller {
			public function initialize(): void {
				$this->defaultTable = null;
			}
		};
		$component = new CaptchaComponent(new ComponentRegistry($controller), ['auto' => false]);
		$this->expectNotToPerformAssertions();

		$component->beforeFilter(new Event('Controller.initialize', $controller));
	}

	public function testAutoSupportsClassNameDefaultTable(): void {
		$controller = new class (new ServerRequest()) extends Controller {
			public function initialize(): void {
				$this->defaultTable = CaptchasTable::class;
			}
		};
		$component = new CaptchaComponent(new ComponentRegistry($controller));
		$component->beforeFilter(new Event('Controller.initialize', $controller));

		$this->assertTrue($controller->fetchTable()->hasBehavior('Captcha'));
	}

	public function testPassiveFormValidationDoesNotLoadCaptchaTable(): void {
		Configure::write('Captcha.log', false);
		$component = new CaptchaComponent(new ComponentRegistry(new Controller(new ServerRequest())), ['auto' => false]);
		$form = new Form();
		$validator = $form->getValidator();
		$component->addValidation($validator, 'Passive');

		$this->assertSame([], $validator->validate(['email_homepage' => '']));
		$this->assertNotEmpty($validator->validate(['email_homepage' => 'bot']));
		$this->assertNotEmpty($validator->validate([]));
		$this->assertFalse($this->getTableLocator()->exists('Captcha.Captchas'));
	}

}
