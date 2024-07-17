<?php

namespace Captcha\Engine\Null;

interface NullInterface {

	/**
	 * @return string
	 */
	public function getExpression(): string;

	/**
	 * @return string
	 */
	public function getValue(): string;

}
