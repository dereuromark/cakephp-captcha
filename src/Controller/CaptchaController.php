<?php
namespace Captcha\Controller;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Network\Exception\NotFoundException;
use Cake\View\Exception\MissingTemplateException;

/**
 * @property \Captcha\Model\Table\CaptchasTable $Captchas
 */
class CaptchaController extends AppController
{

    public $modelClass = 'Captcha.Captchas';

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
     * @return \Cake\Network\Response|void
     */
    public function display($id = null) {
        $captcha = $this->Captchas->get($id);
        $captcha = $this->Captchas->prepare($captcha);
		
        $this->set(compact('captcha'));

        $this->viewBuilder()->className('Captcha.Captcha');
    }

}
