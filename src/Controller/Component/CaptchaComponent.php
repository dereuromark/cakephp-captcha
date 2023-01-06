<?php

namespace Captcha\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\EventDispatcherTrait;
use Cake\Event\EventInterface;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use ReflectionClass;

class CaptchaComponent extends Component {

	use EventDispatcherTrait;

	/**
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'actions' => [],
		'auto' => true, // Auto load behavior
	];

	/**
	 * @param \Cake\Event\EventInterface $event
	 *
	 * @return void
	 */
	public function beforeFilter(EventInterface $event) {
		$actions = $this->getConfig('actions');
		if ($actions && !in_array($this->getController()->getRequest()->getParam('action'), $actions, true)) {
			return;
		}

		$model = $this->getControllerModelClass();
		if (!$model || !$this->_config['auto']) {
			return;
		}
		if (strpos($model, '.') !== false) {
			[$plugin, $model] = pluginSplit($model);
		}

		$controller = $this->getController();
		if ($controller->$model->hasBehavior('Captcha')) {
			return;
		}
		$controller->$model->addBehavior('Captcha.Captcha');
	}

	/**
	 * @return string
	 */
	protected function getControllerModelClass(): string {
		$reflection = new ReflectionClass($this->getController());
		$property = $reflection->getProperty('modelClass');
		$property->setAccessible(true);

		return $property->getValue($this->getController());
	}

	/**
	 * @param \Cake\Event\EventInterface $event
	 *
	 * @return void
	 */
	public function beforeRender(EventInterface $event): void {
		$helpers = $this->getController()->viewBuilder()->getHelpers();
		if (in_array('Captcha.Captcha', $helpers, true) || isset($helpers['Captcha.Captcha'])) {
			return;
		}

		$this->getController()->viewBuilder()->addHelpers(['Captcha.Captcha']);
	}

	/**
	 * @param \Cake\Validation\Validator $validator
	 * @param string|null $type Default or Passive
	 *
	 * @return void
	 */
	public function addValidation(Validator $validator, ?string $type = null) {
		/** @var \Captcha\Model\Table\CaptchasTable $Captchas */
		$Captchas = TableRegistry::getTableLocator()->get('Captcha.Captchas');

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
