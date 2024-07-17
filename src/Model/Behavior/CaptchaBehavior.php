<?php

namespace Captcha\Model\Behavior;

use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\I18n\DateTime;
use Cake\ORM\Behavior;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\Validation\Validator;
use Captcha\Engine\MathEngine;
use Captcha\Engine\NullEngine;
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
	protected array $_defaultConfig = [
		'minTime' => 2, // Seconds the form will need to be filled in by a human
		'maxTime' => DAY, // Seconds the form will need to be submitted in
		'engine' => MathEngine::class,
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
	protected array $_captchas = [];

	/**
	 * @param \Cake\ORM\Table $table
	 * @param array<string, mixed> $config
	 */
	public function __construct(Table $table, array $config = []) {
		$config += (array)Configure::read('Captcha');

		parent::__construct($table, $config);
	}

	/**
	 * Behavior configuration
	 *
	 * @param array $config
	 * @return void
	 */
	public function initialize(array $config): void {
		parent::initialize($config);

		$this->_captchasTable = TableRegistry::getTableLocator()->get('Captcha.Captchas');
		/** @phpstan-var class-string<\Captcha\Engine\EngineInterface> $engine */
		$engine = $this->getConfig('engine');
		if (!$engine) {
			return;
		}
		$this->_engine = new $engine($this->getConfig());
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
		if ($this->getConfig('engine') !== NullEngine::class) {
			$validator->add('captcha_result', [
				'required' => [
					'rule' => 'notBlank',
					'message' => __d('captcha', 'Please solve the riddle'),
					'last' => true,
				],
			]);

			$validator->add('captcha_result', [
				'maxPerUser' => [
					'rule' => 'validateCaptchaMaxPerUser',
					'provider' => 'table',
					'message' => __d('captcha', 'Limit reached. Please retry later'),
					'last' => true,
				],
			]);
		}

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

		$this->_engine->buildValidator($validator);
	}

	/**
	 * @param string $value
	 * @param array $context
	 *
	 * @return bool
	 */
	public function validateCaptchaMaxPerUser($value, $context) {
		// If no id was provided, the captcha was dummy due to MaxRule failure
		return !empty($context['data']['captcha_id']);
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
		if ($captcha->created >= new DateTime('- ' . $this->getConfig('minTime') . ' seconds')) {
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
		if ($captcha->created <= new DateTime('- ' . $this->getConfig('maxTime') . ' seconds')) {
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
