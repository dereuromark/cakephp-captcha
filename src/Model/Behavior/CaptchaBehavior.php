<?php

namespace Captcha\Model\Behavior;

use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\I18n\FrozenTime;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\Validation\Validator;
use RuntimeException;

/**
 * CaptchaBehavior
 *
 * Uses different captcha types, database driven and session-free.
 * This allows a cleaner, robust and completely tab-safe approach.
 */
class CaptchaBehavior extends Behavior {

	/**
	 * @var array<string, mixed>
	 */
	protected $_defaultConfig = [
		'minTime' => 2, // Seconds the form will need to be filled in by a human
		'maxTime' => DAY, // Seconds the form will need to be submitted in
		'engine' => 'Captcha\Engine\MathEngine',
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
	public function initialize(array $config): void {
		$config += (array)Configure::read('Captcha');
		parent::initialize($config);

		/** @phpstan-var class-string<\Captcha\Engine\EngineInterface> $engine */
		$engine = $this->getConfig('engine');
		$this->_engine = new $engine($this->getConfig());
		$this->_captchasTable = TableRegistry::getTableLocator()->get('Captcha.Captchas');
	}

	/**
	 * @param \Cake\Event\EventInterface $event
	 * @param \Cake\Validation\Validator $validator
	 * @param string $name
	 * @return void
	 */
	public function buildValidator(EventInterface $event, Validator $validator, $name) {
		$this->addCaptchaValidation($validator);
	}

	/**
	 * @param \Cake\Validation\Validator $validator
	 *
	 * @return void
	 */
	public function addCaptchaValidation(Validator $validator): void {
		$validator->requirePresence('captcha_result');
		$validator->add('captcha_result', [
			'required' => [
				'rule' => 'notBlank',
				'last' => true,
			],
		]);

		$this->_engine->buildValidator($validator);
		if ($this->getConfig('minTime')) {
			$validator->add('captcha_result', [
				'minTime' => [
					'rule' => 'validateCaptchaMinTime',
					'provider' => 'table',
					'message' => __d('captcha', 'You were too fast'),
					'last' => true,
				],
			]);
		}
		if ($this->getConfig('maxTime')) {
			$validator->add('captcha_result', [
				'maxTime' => [
					'rule' => 'validateCaptchaMaxTime',
					'provider' => 'table',
					'message' => __d('captcha', 'You were too slow'),
					'last' => true,
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
		if ($captcha->created >= new FrozenTime('- ' . $this->getConfig('minTime') . ' seconds')) {
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
		if ($captcha->created <= new FrozenTime('- ' . $this->getConfig('maxTime') . ' seconds')) {
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

		$this->_captchasTable->markUsed($captcha);

		if ((string)$value !== $captcha->result) {
			return false;
		}

		return true;
	}

	/**
	 * @param array $data
	 *
	 * @return \Captcha\Model\Entity\Captcha|null
	 */
	protected function _getCaptcha(array $data) {
		$id = !empty($data['captcha_id']) ? (int)$data['captcha_id'] : null;

		if ($id && array_key_exists($id, $this->_captchas)) {
			return $this->_captchas[$id];
		}

		$request = Router::getRequest();
		if ($request === null) {
			throw new RuntimeException('No request found.');
		}
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
			'session_id' => $sessionId,
		];
		/** @var \Captcha\Model\Entity\Captcha|null $captcha */
		$captcha = $this->_captchasTable->find()->where($conditions)->first();
		$this->_captchas[$id] = $captcha;

		return $this->_captchas[$id];
	}

	/**
	 * @return array
	 */
	public function generate() {
		return $this->_engine->generate();
	}

}
