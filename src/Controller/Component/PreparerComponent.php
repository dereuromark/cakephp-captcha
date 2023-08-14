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
	protected $_defaultConfig = [
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
		 * Silently ignore saving failures, especially because of application rules.
		 * This will result in the captcha to be displayed, but in the form
		 * submission to fail intentionally since the expected result will still be
		 * NULL.
		 */
		if (!$this->Captchas->save($captcha)) {
			$this->Captchas->delete($captcha);  // Now useless if not updated
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
