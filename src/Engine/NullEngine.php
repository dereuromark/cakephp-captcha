<?php

namespace Captcha\Engine;

use Cake\Validation\Validator;
use Captcha\Engine\Null\Hidden;

class NullEngine implements EngineInterface {

	/**
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'imageFormat' => null,
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
	}

	/**
	 * @param string $expression
	 * @return string Binary image data
	 */
	protected function render(string $expression) {
		return '';
	}

	/**
	 * @return \Captcha\Engine\Null\NullInterface
	 */
	protected function _getTypeClass() {
		$config = $this->_config;

		/** @phpstan-var class-string<\Captcha\Engine\Null\NullInterface> $class */
		$class = Hidden::class;

		return new $class($config);
	}

}
