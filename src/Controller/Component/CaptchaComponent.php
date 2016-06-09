<?php

namespace Captcha\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Event\EventDispatcherTrait;

class CaptchaComponent extends Component {

	use EventDispatcherTrait;

	/**
	 * @var array
	 */
	protected $_defaultConfig = [
		'engine' => 'Captcha\Engine\MathEngine',
	];

	/**
	 * Request object
	 *
	 * @var \Cake\Network\Request
	 */
	public $request;

	/**
	 * Response object
	 *
	 * @var \Cake\Network\Response
	 */
	public $response;

	/**
	 * Initialize properties.
	 *
	 * @param array $config The config data.
	 * @return void
	 */
	public function initialize(array $config) {
		$controller = $this->_registry->getController();
		$this->eventManager($controller->eventManager());
		$this->response = &$controller->response;

		$this->Captchas = $controller->Captchas;
	}

	/**
	 * Callback for Controller.startup event.
	 *
	 * @param \Cake\Event\Event $event Event instance.
	 * @return \Cake\Network\Response|null
	 */
	public function startup(Event $event) {
		//return $this->setUpValidation($event);
	}

	/**
	 * @param \Captcha\Model\Entity\Captcha $captcha
	 *
	 * @return bool|\Captcha\Model\Entity\Captcha
	 */
	public function prepare($captcha) {
		if ($captcha->result === null || $captcha->result === '') {
			$generated = $this->_getEngine()->generate();
			$captcha = $this->Captchas->patchEntity($captcha, $generated);
		}
		return $this->Captchas->save($captcha);
	}

	/**
	 * @return \Captcha\Engine\EngineInterface
	 */
	private function _getEngine() {
		$config = (array)Configure::read('Captcha') + $this->_defaultConfig;
		$engine = $config['engine'];
		return new $engine($config);
	}

}
