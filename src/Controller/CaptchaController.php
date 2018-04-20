<?php
namespace Captcha\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

/**
 * @property \Captcha\Model\Table\CaptchasTable $Captchas
 * @property \Captcha\Controller\Component\PreparerComponent $Preparer
 */
class CaptchaController extends AppController {

	/**
	 * @var string
	 */
	public $modelClass = 'Captcha.Captchas';

	/**
	 * @return void
	 */
	public function initialize() {
		parent::initialize();

		$this->loadComponent('Captcha.Preparer');
	}

	/**
	 * @param \Cake\Event\Event $event
	 * @return void
	 */
	public function beforeFilter(Event $event) {
		if (isset($this->Auth)) {
			$this->Auth->allow();
		}
	}

	/**
	 * Displays a captcha image
	 *
	 * @param int|null $id
	 * @return \Cake\Http\Response|void
	 */
	public function display($id = null) {
		$captcha = $this->Captchas->get($id);
		$captcha = $this->Preparer->prepare($captcha);

		$this->set(compact('captcha'));

		$this->viewBuilder()->setClassName('Captcha.Captcha');
	}

}
