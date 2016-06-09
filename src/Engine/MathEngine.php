<?php

namespace Captcha\Engine;

use Cake\Core\Plugin;
use Cake\Validation\Validator;
use Captcha\Engine\Math\SimpleMath;
use expression_math;

require Plugin::path('Captcha') . 'vendor/' . 'mathpublisher.php';

class MathEngine implements EngineInterface {

	const FORMAT_JPEG = 'jpeg';
	const FORMAT_PNG = 'png';

	/**
	 * @var array
	 */
	protected $_defaultConfig = [
		'size' => 14,
		'imageFormat' => self::FORMAT_PNG,
		'mathType' => SimpleMath::class
	];

	/**
	 * @var array
	 */
	protected $config;

	public function __construct(array $config) {
		$this->config = $config + $this->_defaultConfig;
	}

	/**
	 * @param array $config
	 *
	 * @return array
	 */
	public function generate() {
		$class = $this->_getTypeClass();

		$expression = $class->getExpression();
		$value = $class->getValue();

		$image = $this->render($expression);

		return [
			'result' => $value,
			'image' => $image
		];
	}

	/**
	 * @param \Cake\Validation\Validator $validator
	 *
	 * @return void
	 */
	public function buildValidator(Validator $validator) {
		$validator->add('captcha_result', [
			'valid' => [
				'rule' => 'validateCaptchaResult',
				'provider' => 'table',
				'message' => __('The solution is not correct'),
				'last' => true
			]
		]);
	}

	/**
	 * @param string $expression
	 * @return string Binary image data
	 */
	protected function render($expression) {
		$formula = new expression_math(tableau_expression($expression));
		$formula->dessine($this->config['size']);
		ob_start();
		switch ($this->config['imageFormat']) {
			case self::FORMAT_JPEG:
				imagejpeg($formula->image);
				break;
			case self::FORMAT_PNG:
				imagepng($formula->image);
				break;
		}
		imagedestroy($formula->image);

		return ob_get_clean();
	}

	/**
	 * @return \Captcha\Engine\Math\MathInterface
	 */
	protected function _getTypeClass() {
		$config = $this->config;
		return new $config['mathType']($config);
	}

}
