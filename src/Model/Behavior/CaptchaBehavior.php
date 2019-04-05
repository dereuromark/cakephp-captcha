<?php

namespace Captcha\Model\Behavior;

use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\I18n\Time;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\Validation\Validator;

/**
 * CaptchaBehavior
 *
 * Uses different captcha types, database driven and session-free.
 * This allows a cleaner, robust and completely tab-safe approach.
 */
class CaptchaBehavior extends Behavior {

	/**
	 * @var array
	 */
	protected $_defaultConfig = [
		'minTime' => 2, // Seconds the form will need to be filled in by a human
		'maxTime' => DAY, // Seconds the form will need to be submitted in
		'log' => false, // Log errors
		'engine' => 'Captcha\Engine\MathEngine',
		'dummyField' => 'email_homepage', /* @deprecated Use PassiveCaptcha Behavior */
	];

	/**
	 * @var \Captcha\Engine\EngineInterface
	 */
	protected $_engine;

	/**
	 * @var \Captcha\Model\Table\CaptchasTable
	 */
	protected $_captchasTable;

	/**
	 * @var array
	 */
	protected $_captchas = [];

	/**
	 * Behavior configuration
	 *
	 * @param array $config
	 * @return void
	 */
	public function initialize(array $config = []) {
		$config += (array)Configure::read('Captcha');
		parent::initialize($config);

		$engine = $this->getConfig('engine');
		$this->_engine = new $engine($this->getConfig());
		$this->_captchasTable = TableRegistry::get('Captcha.Captchas');
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
		$validator->requirePresence('captcha_result');
		$validator->add('captcha_result', [
			'required' => [
				'rule' => 'notBlank',
				'last' => true
			],
		]);

		/** @deprecated Use PassiveCaptcha Behavior */
		if ($this->getConfig('dummyField')) {
			$validator->requirePresence($this->getConfig('dummyField'));
			$validator->allowEmpty($this->getConfig('dummyField'));
			$validator->add($this->getConfig('dummyField'), [
				'dummyField' => [
					'rule' => function ($value, $context) {
						return $value === '';
					},
					'last' => true
				],
			]);
		}

		$this->_engine->buildValidator($validator);
		if ($this->getConfig('minTime')) {
			$validator->add('captcha_result', [
				'minTime' => [
					'rule' => 'validateCaptchaMinTime',
					'provider' => 'table',
					'message' => __('You were too fast'),
					'last' => true
				],
			]);
		}
		if ($this->getConfig('maxTime')) {
			$validator->add('captcha_result', [
				'maxTime' => [
					'rule' => 'validateCaptchaMaxTime',
					'provider' => 'table',
					'message' => __('You were too slow'),
					'last' => true
				],
			]);
		}
	}

	/**
	 * @param string $value
	 * @param array $context
	 *
	 * @return bool
	 */
	public function validateCaptchaMinTime($value, $context) {
		$captcha = $this->_getCaptcha($context['data']);
		if (!$captcha) {
			return false;
		}
		if ($captcha->created >= new Time('- ' . $this->getConfig('minTime') . ' seconds')) {
			return false;
		}

		return true;
	}

	/**
	 * @param string $value
	 * @param array $context
	 *
	 * @return bool
	 */
	public function validateCaptchaMaxTime($value, $context) {
		$captcha = $this->_getCaptcha($context['data']);
		if (!$captcha) {
			return false;
		}
		if ($captcha->created <= new Time('- ' . $this->getConfig('maxTime') . ' seconds')) {
			return false;
		}

		return true;
	}

	/**
	 * @param string $value
	 * @param array $context
	 *
	 * @return bool
	 */
	public function validateCaptchaResult($value, $context) {
		$captcha = $this->_getCaptcha($context['data']);
		if (!$captcha) {
			return false;
		}
		if ((string)$value !== $captcha->result) {
			return false;
		}

		$this->_captchasTable->markUsed($captcha);
		return true;
	}

	/**
	 * @param array $data
	 *
	 * @return \Captcha\Model\Entity\Captcha|null
	 */
	protected function _getCaptcha(array $data) {
		$id = !empty($data['captcha_id']) ? (int)$data['captcha_id'] : null;

		if (array_key_exists($id, $this->_captchas)) {
			return $this->_captchas[$id];
		}

		$request = Router::getRequest();
		if (!$request->getSession()->started()) {
			$request->getSession()->start();
		}
		$sessionId = $request->getSession()->id();
		if (!$sessionId && PHP_SAPI === 'cli') {
			$sessionId = 'test';
		}

		$ip = $request->clientIp();

		if (!$id) {
			$this->_captchas[$id] = null;
			return null;
		}

		$conditions = [
			'id' => $id,
			'ip' => $ip,
			'session_id' => $sessionId
		];
		$this->_captchas[$id] = $this->_captchasTable->find()->where($conditions)->first();
		return $this->_captchas[$id];
	}

	/**
	 * @return array
	 */
	public function generate() {
		return $this->_engine->generate();
	}

}
