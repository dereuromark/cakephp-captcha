<?php

namespace Captcha\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\Event\EventDispatcherTrait;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

class CaptchaComponent extends Component {

	use EventDispatcherTrait;

	/**
	 * @var array
	 */
	protected $_defaultConfig = [
		'actions' => [],
	];

	/**
	 * @var \Cake\Controller\Controller
	 */
	public $controller;

	/**
	 * Initialize properties.
	 *
	 * @param array $config The config data.
	 * @return void
	 */
	public function initialize(array $config) {
		$this->controller = $this->_registry->getController();
	}

	/**
	 * @param \Cake\Event\Event $event
	 *
	 * @return void
	 */
	public function beforeFilter(Event $event) {
		$actions = $this->getConfig('actions');
		if ($actions && !in_array($this->controller->request->param('action'), $actions)) {
			return;
		}

		$model = $this->controller->modelClass;
		if (!isset($this->controller->$model) || $this->controller->$model->hasBehavior('Captcha')) {
			return;
		}
		$this->controller->$model->addBehavior('Captcha.Captcha');
	}

	/**
	 * @param \Cake\Event\Event $event
	 *
	 * @return void
	 */
	public function beforeRender(Event $event) {
		if (in_array('Captcha.Captcha', $this->controller->helpers) || isset($this->controller->helpers['Captcha.Captcha'])) {
			return;
		}

		$this->controller->helpers[] = 'Captcha.Captcha';
	}

	/**
	 * @param \Cake\Validation\Validator $validator
	 * @param string|null $type Default or Passive
	 *
	 * @return void
	 */
	public function addValidation(Validator $validator, ?string $type = null) {
		/** @var \Captcha\Model\Table\CaptchasTable $Captchas */
		$Captchas = TableRegistry::getTableLocator()->get('CaptchasValidator', ['class' => 'Captcha.Captchas']);

		$Captchas->setValidator('captcha', $validator);

		$behavior = 'Captcha';
		if ($type === 'Passive') {
			$behavior = 'PassiveCaptcha';
		}

		$Captchas->addBehavior('Captcha.' . $behavior);
		/** @var \Captcha\Model\Behavior\CaptchaBehavior|\Captcha\Model\Behavior\PassiveCaptchaBehavior $Captchas */
		$method = 'add' . $behavior . 'Validation';
		$Captchas->$method($validator);
	}

}
