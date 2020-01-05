<?php

namespace Captcha\Engine\Math;

interface MathInterface {

	/**
	 * @return string
	 */
	public function getExpression(): string;

	/**
	 * @return string
	 */
	public function getValue(): string;

}
