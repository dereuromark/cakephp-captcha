<?php

namespace Captcha\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\EventDispatcherTrait;
use Cake\Event\EventInterface;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Captcha\Model\Behavior\CaptchaBehavior;
use Captcha\Model\Behavior\PassiveCaptchaBehavior;
use ReflectionClass;
use RuntimeException;

class CaptchaComponent extends Component {

	use EventDispatcherTrait;

	/**
	 * @var array<string, mixed>
	 */
	protected array $defaultConfig = [
		'actions' => [],
		'auto' => true, // Auto load behavior
	];

	/**
	 * @param \Cake\Event\EventInterface $event
	 *
	 * @return void
	 */
	public function beforeFilter(EventInterface $event): void {
		$actions = $this->getConfig('actions');
		if ($actions && !in_array($this->getController()->getRequest()->getParam('action'), $actions, true)) {
			return;
		}

		if (!$this->getConfig('auto')) {
			return;
		}
		$model = $this->getControllerModelClass();
		if (!$model) {
			return;
		}

		$table = $this->getController()->fetchTable($model);
		if (!$table->hasBehavior('Captcha')) {
			$table->addBehavior('Captcha.Captcha');
		}
	}

	/**
	 * @return string
	 */
	protected function getControllerModelClass(): string {
		$property = 'defaultTable';
		$reflection = new ReflectionClass($this->getController());
		if (!$reflection->hasProperty($property)) {
			return '';
		}
		$property = $reflection->getProperty($property);

		return $property->getValue($this->getController()) ?? '';
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
	public function addValidation(Validator $validator, ?string $type = null): void {
		if ($type === 'Passive') {
			$behavior = new PassiveCaptchaBehavior(new Table(['alias' => 'PassiveCaptcha']));
			$behavior->addPassiveCaptchaValidation($validator);

			return;
		}

		$Captchas = TableRegistry::getTableLocator()->get('Captcha.Captchas');

		$Captchas->setValidator('captcha', $validator);

		$behavior = 'Captcha';

		$Captchas->addBehavior('Captcha.' . $behavior);
		$instance = $Captchas->getBehavior($behavior);
		if ($instance instanceof CaptchaBehavior) {
			$instance->addCaptchaValidation($validator);
		} else {
			throw new RuntimeException('Unsupported captcha validation behavior');
		}
	}

	/**
	 * @param string $ip
	 *
	 * @return void
	 */
	public function resetFor(string $ip): void {
		/** @var \Captcha\Model\Table\CaptchasTable $Captchas */
		$Captchas = TableRegistry::getTableLocator()->get('Captcha.Captchas');
		$Captchas->reset($ip);
	}

}
