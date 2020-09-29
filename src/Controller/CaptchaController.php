<?php

namespace Captcha\Controller;

use App\Controller\AppController;
use Cake\Event\EventInterface;

/**
 * @property \Captcha\Model\Table\CaptchasTable $Captchas
 * @property \Captcha\Controller\Component\PreparerComponent $Preparer
 */
class CaptchaController extends AppController {

	/**
	 * @var string
	 */
	protected $modelClass = 'Captcha.Captchas';

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
		if (isset($this->Auth)) {
			$this->Auth->allow();
		} elseif (isset($this->Authentication) && method_exists($this->Authentication, 'addUnauthenticatedActions')) {
			$this->Authentication->addUnauthenticatedActions(['display']);
		}
	}

	/**
	 * Displays a captcha image
	 *
	 * @param int|null $id
	 * @return \Cake\Http\Response|null|void
	 */
	public function display($id = null) {
		$captcha = $this->Captchas->get($id);
		$captcha = $this->Preparer->prepare($captcha);

		$this->set(compact('captcha'));

		$this->viewBuilder()->setClassName('Captcha.Captcha');
		$this->viewBuilder()->setTemplatePath('Captcha');
	}

}
