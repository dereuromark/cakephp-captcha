<?php

namespace Captcha\Engine\Math;

use Cake\Utility\Security;

class SimpleMath implements MathInterface {

	protected $_defaultConfig = [
		'complexity' => 1
	];

	protected $_config;

	protected $data = [];

	public function __construct(array $config) {
		$this->_config = $config + $this->_defaultConfig;
		$this->data[0] = $this->_randomNumber() + 1;
		$this->data[1] = $this->_randomOperator();
		$this->data[2] = $this->_randomNumber();

		if ($this->data[1] === '-' && $this->data[2] > $this->data[0]) {
			$tmp = $this->data[2];
			$this->data[2] = $this->data[0];
			$this->data[0] = $this->data[2];
		}
	}

	public function getExpression()
	{
		$numberOne = $this->data[0];
		$operator = $this->data[1];
		$numberTwo = $this->data[2];

		return "{$numberOne} {$operator} {$numberTwo}";
	}

	public function getValue()
	{
		$operator = $this->data[1];
		if ($operator === '-') {
			return $this->data[0] - $this->data[2];
		}
		return $this->data[0] + $this->data[2];
	}

	/**
	 * @return int
     */
	protected function _randomNumber() {
		return random_int(1, 10 * $this->_config['complexity']);
	}

	/**
	 * @return string
     */
	protected function _randomOperator() {
		$operators = [
			'+',
			'-'
		];
		$key = random_int(0, 1);
		return $operators[$key];
	}

}
