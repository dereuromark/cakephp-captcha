<?php

namespace Captcha\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Form\Form;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Captcha\Controller\Component\CaptchaComponent;

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

}
