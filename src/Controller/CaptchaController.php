<?php

namespace Captcha\Controller;

use App\Controller\AppController;
use Cake\Event\EventInterface;
use Captcha\Model\Entity\Captcha;

/**
 * @property \Captcha\Model\Table\CaptchasTable $Captchas
 * @property \Captcha\Controller\Component\PreparerComponent $Preparer
 * @property object|null $Auth
 * @property object|null $Authentication
 */
class CaptchaController extends AppController {

	/**
	 * @var string|null
	 */
	protected ?string $defaultTable = 'Captcha.Captchas';

	/**
	 * @return void
	 */
	public function initialize(): void {
		parent::initialize();

		$this->loadComponent('Captcha.Preparer');
	}

	/**
	 * @param \Cake\Event\EventInterface $event
	 * @return \Cake\Http\Response|null|void
	 */
	public function beforeFilter(EventInterface $event) {
		if ($this->components()->has('Security')) {
			$this->components()->get('Security')->setConfig('validatePost', false);
		}

		if ($this->components()->has('Auth') && method_exists($this->components()->get('Auth'), 'allow')) {
			$this->components()->get('Auth')->allow();
		} elseif ($this->components()->has('Authentication') && method_exists($this->components()->get('Authentication'), 'addUnauthenticatedActions')) {
			$this->components()->get('Authentication')->addUnauthenticatedActions(['display']);
		}
		if ($this->components()->has('Authorization') && method_exists($this->components()->get('Authorization'), 'skipAuthorization')) {
			$this->components()->get('Authorization')->skipAuthorization();
		}
	}

	/**
	 * Displays a captcha image
	 *
	 * @param int|null $id
	 * @return \Cake\Http\Response|null|void
	 */
	public function display($id = null) {
		if ($id === null) {
			$captcha = new Captcha();
		} else {
			$captcha = $this->Captchas->get($id);
		}
		$captcha = $this->Preparer->prepare($captcha);

		$this->set(compact('captcha'));

		$this->viewBuilder()->setClassName('Captcha.Captcha');
		$this->viewBuilder()->setTemplatePath('Captcha');
	}

}
