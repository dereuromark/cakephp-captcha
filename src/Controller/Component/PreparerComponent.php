<?php

namespace Captcha\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Event\EventDispatcherTrait;

/**
 * @internal Only for use inside this plugin's controller
 */
class PreparerComponent extends Component {

	use EventDispatcherTrait;

	/**
	 * @var \Captcha\Model\Table\CaptchasTable
	 */
	protected $Captchas;

	/**
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'engine' => 'Captcha\Engine\MathEngine',
	];

	/**
	 * Initialize properties.
	 *
	 * @param array $config The config data.
	 * @return void
	 */
	public function initialize(array $config): void {
		$controller = $this->_registry->getController();
		$this->setEventManager($controller->getEventManager());

		$this->Captchas = $controller->Captchas;
	}

	/**
	 * @param \Captcha\Model\Entity\Captcha $captcha
	 *
	 * @return \Captcha\Model\Entity\Captcha|bool
	 */
	public function prepare($captcha) {
		if ($captcha->result === null || $captcha->result === '') {
			$generated = $this->_getEngine()->generate();
			$captcha = $this->Captchas->patchEntity($captcha, $generated);
		}

		/*
		 * If the captcha doesn't exist in DB, don't create it.
		 * It will just be displayed as dummy challenge.
		 */
		if (!$captcha->isNew()) {
			$this->Captchas->save($captcha);
		}

		return $captcha;
	}

	/**
	 * @return \Captcha\Engine\EngineInterface
	 */
	protected function _getEngine() {
		$config = (array)Configure::read('Captcha') + $this->_defaultConfig;
		/** @phpstan-var class-string<\Captcha\Engine\EngineInterface> $engine */
		$engine = $config['engine'];

		return new $engine($config);
	}

}
