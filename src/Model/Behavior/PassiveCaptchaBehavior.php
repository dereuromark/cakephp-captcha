<?php

namespace Captcha\Model\Behavior;

use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\Validation\Validator;

/**
 * Validates the added honey pot trap field.
 */
class PassiveCaptchaBehavior extends Behavior {

	/**
	 * @var array
	 */
	protected $_defaultConfig = [
		'dummyField' => 'email_homepage', // Honeypot trap
	];

	/**
	 * Behavior configuration
	 *
	 * @param array $config
	 * @return void
	 */
	public function initialize(array $config = []) {
		$config += (array)Configure::read('Captcha');
		parent::initialize($config);
	}

	/**
	 * @param \Cake\Event\Event $event
	 * @param \Cake\Validation\Validator $validator
	 * @param string $name
	 * @return void
	 */
	public function buildValidator(Event $event, Validator $validator, $name) {
		$this->addValidation($validator);
	}

	/**
	 * @param \Cake\Validation\Validator $validator
	 *
	 * @return void
	 */
	public function addValidation(Validator $validator) {
		$fields = (array)$this->getConfig('dummyField');
		foreach ($fields as $field) {
			$validator->requirePresence($field);
			$validator->allowEmpty($field);
			$validator->add($field, [
				$field => [
					'rule' => function ($value, $context) {
						return $value === '';
					},
					'last' => true
				],
			]);
		}
	}

}
