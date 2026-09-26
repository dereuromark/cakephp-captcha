<?php

namespace Captcha\Model\Behavior;

use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Log\Log;
use Cake\ORM\Behavior;
use Cake\Routing\Router;
use Cake\Validation\Validator;
use Captcha\Cache\VerifyRateLimiter;
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

	protected bool $_countedFailure = false;

	public function __construct(\Cake\ORM\Table $table, array $config = []) {
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
			if ($this->_verifyRateLimiter()->enabled()) {
				$validator->add($field, [
					'verifyRateLimit' => [
						'rule' => fn (): bool => !$this->_isRateLimited(),
						'message' => __d('captcha', 'Too many failed attempts. Please retry later'),
						'last' => true,
					],
				]);
			}
			$validator->allowEmptyString($field);
			$validator->add($field, [
				$field => [
					'rule' => function ($value) use ($field) {
						$ok = $value === '';
						if (!$ok && !$this->_countedFailure) {
							$this->_incrementFailedAttemptCounter();
							$this->_countedFailure = true;
						}
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

		return $this->_verifyRateLimiter()->limited($ip, $sessionId);
	}

	protected function _incrementFailedAttemptCounter(): void {
		$limiter = $this->_verifyRateLimiter();
		if (!$limiter->enabled()) {
			return;
		}
		['sessionId' => $sessionId, 'ip' => $ip] = $this->_getRequestIdentity();
		$limiter->increment($ip, $sessionId);
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
