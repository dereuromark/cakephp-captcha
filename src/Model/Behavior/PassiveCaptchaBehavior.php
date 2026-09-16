<?php

namespace Captcha\Model\Behavior;

use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Log\Log;
use Cake\ORM\Behavior;
use Cake\Validation\Validator;

/**
 * Validates the added honey pot trap field.
 */
class PassiveCaptchaBehavior extends Behavior {

	/**
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'dummyField' => 'email_homepage', // Honeypot trap
		'log' => null, // Auto detect based on debug mode
	];

	/**
	 * Behavior configuration
	 *
	 * @param array $config
	 * @return void
	 */
	public function initialize(array $config): void {
		$config += (array)Configure::read('Captcha');

		parent::initialize($config);

		if ($this->_config['log'] === null) {
			$this->_config['log'] = (bool)Configure::read('debug');
		}
	}

	/**
	 * @param \Cake\Event\EventInterface $event
	 * @param \Cake\Validation\Validator $validator
	 * @param string $name
	 * @return void
	 */
	public function buildValidator(EventInterface $event, Validator $validator, $name) {
		$this->addPassiveCaptchaValidation($validator);
	}

	/**
	 * @param \Cake\Validation\Validator $validator
	 *
	 * @return void
	 */
	public function addPassiveCaptchaValidation(Validator $validator): void {
		$fields = (array)$this->getConfig('dummyField');
		foreach ($fields as $field) {
			$validator->requirePresence($field);
			$validator->allowEmptyString($field);
			$validator->add($field, [
				$field => [
					'rule' => function ($value) use ($field) {
						$ok = $value === '';
						if (!$ok && $this->_config['log']) {
							Log::write('info', 'PassiveCaptcha trigger on field `' . $field . '`, value ' . $this->sanitizeForLog($value));
						}

						return $ok;
					},
					'last' => true,
				],
			]);
		}
	}

	/**
	 * The honeypot value is attacker controlled and can contain newlines or personal data
	 * (bots do submit real email addresses). Never write it to the log verbatim.
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	protected function sanitizeForLog(mixed $value): string {
		if (!is_scalar($value)) {
			return '(' . get_debug_type($value) . ')';
		}

		$value = (string)$value;
		$length = strlen($value);
		$value = preg_replace('/[^\P{C}]/u', ' ', $value) ?? '';
		$value = mb_substr(trim($value), 0, 40);

		return '`' . $value . '` (' . $length . ' bytes)';
	}

}
