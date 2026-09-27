<?php

namespace Captcha\Model\Behavior;

use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Log\Log;
use Cake\ORM\Behavior;
use Cake\ORM\Table;
use Cake\Routing\Router;
use Cake\Validation\Validator;
use Captcha\Cache\VerifyRateLimiter;
use Captcha\Validation\VerificationContext;
use RuntimeException;

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
		'verifyRateLimit' => [
			'enabled' => false,
			'maxFailures' => 5,
			'window' => 600,
			'scope' => 'ip_session',
			'cache' => 'default',
		],
	];

	protected ?VerificationContext $_verificationContext = null;

	public function __construct(Table $table, array $config = []) {
		$globalConfig = (array)Configure::read('Captcha');
		$config += $globalConfig;
		if (isset($config['verifyRateLimit']) && is_array($config['verifyRateLimit'])) {
			$config['verifyRateLimit'] += (array)($globalConfig['verifyRateLimit'] ?? []) + $this->_defaultConfig['verifyRateLimit'];
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
		$state = VerificationContext::forValidator($validator);
		$state->addPassiveFields($fields);
		$state->onStart(function (array $data) use ($state, $fields): void {
			$this->_verificationContext = $state;
			foreach ($fields as $field) {
				if (!array_key_exists($field, $data) || $data[$field] !== '') {
					$this->_incrementFailedAttemptCounter();

					break;
				}
			}
		});
		foreach ($fields as $field) {
			$state->attach($validator, $field);
			if ($this->_verifyRateLimiter()->enabled()) {
				$validator->add($field, [
					'verifyRateLimit' => [
						'rule' => fn (): bool => !$this->_isRateLimited(),
						'message' => __d('captcha', 'Too many failed attempts. Please retry later'),
						'last' => true,
					],
				]);
			}
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

	protected function _isRateLimited(): bool {
		['sessionId' => $sessionId, 'ip' => $ip] = $this->_getRequestIdentity();

		return $this->_verificationContext?->limited($this->_verifyRateLimiter(), $ip, $sessionId)
			?? $this->_verifyRateLimiter()->limited($ip, $sessionId);
	}

	protected function _incrementFailedAttemptCounter(): void {
		$limiter = $this->_verifyRateLimiter();
		if (!$limiter->enabled()) {
			return;
		}
		['sessionId' => $sessionId, 'ip' => $ip] = $this->_getRequestIdentity();
		$this->_verificationContext?->increment($limiter, $ip, $sessionId);
	}

	protected function _verifyRateLimiter(): VerifyRateLimiter {
		/** @var array{enabled: bool, maxFailures: int, window: int, scope: string, cache: string} $config */
		$config = (array)$this->getConfig('verifyRateLimit') + $this->_defaultConfig['verifyRateLimit'];

		return new VerifyRateLimiter($config);
	}

	protected function _getRequestIdentity(): array {
		$request = Router::getRequest();
		if ($request === null) {
			throw new RuntimeException('No request found.');
		}
		if ($this->getConfig('verifyRateLimit.scope') === 'ip') {
			return ['sessionId' => '', 'ip' => (string)$request->clientIp()];
		}
		if (!$request->getSession()->started()) {
			$request->getSession()->start();
		}
		$sessionId = $request->getSession()->id();
		if (!$sessionId && PHP_SAPI === 'cli') {
			$sessionId = 'test';
		}

		return ['sessionId' => $sessionId, 'ip' => (string)$request->clientIp()];
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
