<?php

namespace Captcha\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Event\EventDispatcherTrait;

class PreparerComponent extends Component {

	use EventDispatcherTrait;

	/**
	 * @var \Captcha\Model\Table\CaptchasTable
	 */
	protected $Captchas;

	/**
	 * @var array
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
	public function initialize(array $config) {
		$controller = $this->_registry->getController();
		$this->setEventManager($controller->getEventManager());

		$this->Captchas = $controller->Captchas;
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
	protected function _getEngine() {
		$config = (array)Configure::read('Captcha') + $this->_defaultConfig;
		$engine = $config['engine'];
		return new $engine($config);
	}

}
