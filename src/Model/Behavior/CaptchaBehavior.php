<?php

namespace Captcha\Model\Behavior;

use Cake\Cache\Cache;
use Cake\Cache\Engine\FileEngine;
use Cake\Cache\Engine\NullEngine as CacheNullEngine;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\I18n\DateTime;
use Cake\ORM\Behavior;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\Validation\Validator;
use Captcha\Engine\EngineInterface;
use Captcha\Engine\MathEngine;
use Captcha\Engine\NullEngine;
use Captcha\Model\Table\CaptchasTable;
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
		'verifyRateLimit' => [
			'enabled' => true,
			'maxFailures' => 5,
			'window' => 600,
			'scope' => 'ip_session',
			'cache' => 'default',
		],
	];

	/**
	 * @var \Captcha\Engine\EngineInterface
	 */
	protected EngineInterface $_engine;

	/**
	 * @var \Captcha\Model\Table\CaptchasTable
	 */
	protected CaptchasTable $_captchasTable;

	/**
	 * @var array
	 */
	protected array $_captchas = [];

	/**
	 * Tracks UUID-scoped verification failures already counted during the current request.
	 *
	 * @var array<string, bool>
	 */
	protected array $_countedFailures = [];

	/**
	 * @param \Cake\ORM\Table $table
	 * @param array<string, mixed> $config
	 */
	public function __construct(Table $table, array $config = []) {
		$config += (array)Configure::read('Captcha');
		if (isset($config['verifyRateLimit']) && is_array($config['verifyRateLimit'])) {
			$config['verifyRateLimit'] += $this->_defaultConfig['verifyRateLimit'];
		}

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

			if ($this->_isVerifyRateLimitEnabled()) {
				$validator->add('captcha_result', [
					'verifyRateLimit' => [
						'rule' => 'validateCaptchaRateLimit',
						'provider' => 'table',
						'message' => __d('captcha', 'Too many failed attempts. Please retry later'),
						'last' => true,
					],
				]);
			}
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
		return !empty($context['data']['captcha_uuid']);
	}

	/**
	 * @param string $value
	 * @param array $context
	 *
	 * @return bool
	 */
	public function validateCaptchaRateLimit($value, $context) {
		return !$this->_isRateLimited();
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
		if ($captcha->used !== null) {
			return false;
		}

		$isValid = hash_equals((string)$captcha->result, (string)$value);
		if (!$this->_captchasTable->markUsed($captcha)) {
			return false;
		}
		if (!$isValid) {
			$this->_incrementFailedAttemptCounter();

			return false;
		}

		$this->_clearFailedAttemptCounter();

		return true;
	}

	/**
	 * @param array $data
	 *
	 * @return \Captcha\Model\Entity\Captcha|null
	 */
	protected function _getCaptcha(array $data) {
		$uuid = !empty($data['captcha_uuid']) ? (string)$data['captcha_uuid'] : null;

		if ($uuid && array_key_exists($uuid, $this->_captchas)) {
			$captcha = $this->_captchas[$uuid];
			if ($captcha && $captcha->used === null) {
				return $captcha;
			}
			if ($captcha) {
				$this->_registerLookupFailure($uuid);
			}

			return null;
		}

		['sessionId' => $sessionId, 'ip' => $ip] = $this->_getRequestIdentity();

		if (!$uuid) {
			$this->_captchas[$uuid] = null;

			return null;
		}

		$conditions = [
			'uuid' => $uuid,
			'ip' => $ip,
			'session_id' => $sessionId,
		];
		/** @var \Captcha\Model\Entity\Captcha|null $captcha */
		$captcha = $this->_captchasTable->find()->where($conditions)->first();
		if ($captcha && $captcha->used === null) {
			$this->_captchas[$uuid] = $captcha;

			return $this->_captchas[$uuid];
		}

		$this->_registerLookupFailure($uuid);
		$this->_captchas[$uuid] = $captcha;

		return null;
	}

	/**
	 * @return array{sessionId: string, ip: string}
	 */
	protected function _getRequestIdentity(): array {
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

		return [
			'sessionId' => $sessionId,
			'ip' => (string)$request->clientIp(),
		];
	}

	/**
	 * @return bool
	 */
	protected function _isVerifyRateLimitEnabled(): bool {
		$config = $this->_getVerifyRateLimitConfig();

		return !empty($config['enabled']);
	}

	/**
	 * @return bool
	 */
	protected function _isRateLimited(): bool {
		if (!$this->_isVerifyRateLimitEnabled()) {
			return false;
		}

		$config = $this->_getVerifyRateLimitConfig();
		$key = $this->_buildRateLimitKey();
		$count = Cache::read($key, $config['cache']);
		if (!is_int($count)) {
			return false;
		}

		return $count >= (int)$config['maxFailures'];
	}

	/**
	 * @return void
	 */
	protected function _incrementFailedAttemptCounter(): void {
		if (!$this->_isVerifyRateLimitEnabled()) {
			return;
		}

		$config = $this->_getVerifyRateLimitConfig();
		$key = $this->_buildRateLimitKey();

		if (!Cache::add($key, 0, $config['cache'])) {
			$cache = Cache::pool($config['cache']);
			if (!$cache instanceof FileEngine && !$cache instanceof CacheNullEngine) {
				$count = Cache::increment($key, 1, $config['cache']);
				if ($count !== false) {
					return;
				}
			}
		}

		$count = Cache::read($key, $config['cache']);
		$count = is_int($count) ? $count + 1 : 1;

		Cache::write($key, $count, $config['cache']);
	}

	/**
	 * @return void
	 */
	protected function _clearFailedAttemptCounter(): void {
		if (!$this->_isVerifyRateLimitEnabled()) {
			return;
		}

		$config = $this->_getVerifyRateLimitConfig();
		Cache::delete($this->_buildRateLimitKey(), $config['cache']);
	}

	/**
	 * @param string $uuid
	 * @return void
	 */
	protected function _registerLookupFailure(string $uuid): void {
		if (isset($this->_countedFailures[$uuid])) {
			return;
		}

		$this->_incrementFailedAttemptCounter();
		$this->_countedFailures[$uuid] = true;
	}

	/**
	 * @return array{enabled: bool, maxFailures: int, window: int, scope: string, cache: string}
	 */
	protected function _getVerifyRateLimitConfig(): array {
		/** @var array{enabled: bool, maxFailures: int, window: int, scope: string, cache: string} $config */
		$config = (array)$this->getConfig('verifyRateLimit') + $this->_defaultConfig['verifyRateLimit'];

		return $config;
	}

	/**
	 * @return string
	 */
	protected function _buildRateLimitKey(): string {
		$config = $this->_getVerifyRateLimitConfig();
		['sessionId' => $sessionId, 'ip' => $ip] = $this->_getRequestIdentity();

		$scope = $config['scope'];
		if ($scope === 'ip') {
			$keyData = $ip;
		} else {
			$keyData = $ip . '|' . $sessionId;
		}
		$window = max((int)$config['window'], 1);
		$bucket = (int)floor(time() / $window);

		return 'captcha_verify_rate_limit_' . sha1($keyData) . '_' . $bucket;
	}

	/**
	 * @return array
	 */
	public function generate() {
		return $this->_engine->generate();
	}

}
