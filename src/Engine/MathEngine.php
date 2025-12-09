<?php

namespace Captcha\Engine;

use Cake\Core\Plugin;
use Cake\Validation\Validator;
use Captcha\Engine\Math\SimpleMath;
use expression_math;

require_once Plugin::path('Captcha') . 'resources/' . 'mathpublisher.php';

class MathEngine implements EngineInterface {

	/**
	 * @var string
	 */
	public const FORMAT_JPEG = 'jpeg';

	/**
	 * @var string
	 */
	public const FORMAT_PNG = 'png';

	/**
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'size' => 14,
		'imageFormat' => self::FORMAT_PNG,
		'mathType' => SimpleMath::class,
	];

	/**
	 * @var array<string, mixed>
	 */
	protected array $_config;

	/**
	 * @param array<string, mixed> $config
	 */
	public function __construct(array $config) {
		$this->_config = $config + $this->_defaultConfig;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function generate(): array {
		$class = $this->_getTypeClass();

		$expression = $class->getExpression();
		$value = $class->getValue();

		$image = $this->render($expression);

		return [
			'result' => $value,
			'image' => $image,
		];
	}

	/**
	 * @param \Cake\Validation\Validator $validator
	 *
	 * @return void
	 */
	public function buildValidator(Validator $validator): void {
		$validator->add('captcha_result', [
			'valid' => [
				'rule' => 'validateCaptchaResult',
				'provider' => 'table',
				'message' => __d('captcha', 'The solution is not correct'),
				'last' => true,
			],
		]);
	}

	/**
	 * @param string $expression
	 * @return string Binary image data
	 */
	protected function render($expression) {
		$formula = new expression_math(tableau_expression($expression));
		$formula->dessine($this->_config['size']);
		ob_start();
		switch ($this->_config['imageFormat']) {
			case static::FORMAT_JPEG:
				imagejpeg($formula->image);

				break;
			case static::FORMAT_PNG:
				imagepng($formula->image);

				break;
		}

		return ob_get_clean() ?: '';
	}

	/**
	 * @return \Captcha\Engine\Math\MathInterface
	 */
	protected function _getTypeClass() {
		$config = $this->_config;

		/** @phpstan-var class-string<\Captcha\Engine\Math\MathInterface> $class */
		$class = $config['mathType'];

		return new $class($config);
	}

}
