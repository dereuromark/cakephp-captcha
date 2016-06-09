<?php

namespace Captcha\Engine\Math;

interface MathInterface {

	/**
	 * @return string
	 */
	public function getExpression();

	/**
	 * @return string
	 */
	public function getValue();

}
