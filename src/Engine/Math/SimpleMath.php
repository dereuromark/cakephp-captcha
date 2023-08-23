<?php

namespace Captcha\Engine\Math;

class SimpleMath implements MathInterface {

	/**
	 * @var array<string, mixed>
	 */
	protected $_defaultConfig = [
		'complexity' => 10,
	];

	/**
	 * @var array
	 */
	protected $_config;

	/**
	 * @var array
	 */
	protected $data = [];

	/**
	 * @param array $config
	 */
	public function __construct(array $config) {
		$this->_config = $config + $this->_defaultConfig;
		$this->data[0] = $this->_randomNumber();
		$this->data[1] = $this->_randomOperator();
		$this->data[2] = $this->_randomNumber(1);
		while ($this->data[2] === $this->data[0]) {
			$this->data[2] = $this->_randomNumber(10);
		}

		if ($this->data[1] === '-' && $this->data[2] > $this->data[0]) {
			$tmp = $this->data[2];
			$this->data[2] = $this->data[0];
			$this->data[0] = $tmp;
		}
	}

	/**
	 * @return string
	 */
	public function getExpression(): string {
		$numberOne = $this->data[0];
		$operator = $this->data[1];
		$numberTwo = $this->data[2];

		return "{$numberOne} {$operator} {$numberTwo}";
	}

	/**
	 * @return string
	 */
	public function getValue(): string {
		$operator = $this->data[1];

		if ($operator === '-') {
			$value = $this->data[0] - $this->data[2];
		} else {
			$value = $this->data[0] + $this->data[2];
		}

		return (string)$value;
	}

	/**
	 * @param int $complexity
	 * @return int
	 */
	protected function _randomNumber(int $complexity = 0) {
		if (!$complexity) {
			$complexity = $this->_config['complexity'];
		}

		return random_int(1, 10 * $complexity);
	}

	/**
	 * @return string
	 */
	protected function _randomOperator() {
		$operators = [
			'+',
			'-',
		];
		$key = random_int(0, 1);

		return $operators[$key];
	}

}
