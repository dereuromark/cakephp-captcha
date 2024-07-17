<?php

namespace Captcha\Engine;

use Cake\Validation\Validator;

interface EngineInterface {

	/**
	 * @return array<string, mixed>
	 */
	public function generate(): array;

	/**
	 * @param \Cake\Validation\Validator $validator
	 * @return void
	 */
	public function buildValidator(Validator $validator): void;

}
