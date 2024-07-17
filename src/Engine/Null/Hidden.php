<?php

namespace Captcha\Engine\Null;

class Hidden implements NullInterface {

	/**
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
	];

	/**
	 * @var array<string, mixed>
	 */
	protected $_config;

	/**
	 * @var array
	 */
	protected array $data = [];

	/**
	 * @param array $config
	 */
	public function __construct(array $config) {
		$this->_config = $config + $this->_defaultConfig;
	}

	/**
	 * @return string
	 */
	public function getExpression(): string {
		return '';
	}

	/**
	 * @return string
	 */
	public function getValue(): string {
		return '';
	}

}
