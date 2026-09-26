<?php
declare(strict_types=1);

namespace Captcha\Validation;

use Cake\Validation\ValidationSet;
use Cake\Validation\Validator;
use Captcha\Cache\VerifyRateLimiter;
use Closure;

/**
 * Coordinates captcha checks for one Validator::validate() call.
 *
 * @internal
 */
class VerificationContext {

	protected array $fields = [];

	protected array $passiveFields = [];

	protected array $callbacks = [];

	protected array $counts = [];

	protected array $counted = [];

	protected bool $passiveValid = true;

	public static function forValidator(Validator $validator): self {
		$context = $validator->getProvider('captchaVerification');
		if (!$context instanceof self) {
			$context = new self();
			$validator->setProvider('captchaVerification', $context);
		}

		return $context;
	}

	public function onStart(Closure $callback): void {
		$this->callbacks[] = $callback;
	}

	public function addPassiveFields(array $fields): void {
		$this->passiveFields = array_unique(array_merge($this->passiveFields, $fields));
	}

	public function attach(Validator $validator, string $field): void {
		$this->fields[$field] = true;
		$begin = function (array $context) use ($validator, $field): void {
			// Metadata queries supply a field name; validate() supplies its rule set.
			if (!$context['field'] instanceof ValidationSet) {
				return;
			}

			foreach ($validator as $name => $rules) {
				if (!isset($this->fields[$name]) || (!empty($context['fields']) && !in_array($name, $context['fields'], true))) {
					continue;
				}
				if ($name !== $field) {
					return;
				}

				break;
			}

			$this->counts = [];
			$this->counted = [];
			$this->passiveValid = true;
			foreach ($this->passiveFields as $name) {
				if (!array_key_exists($name, $context['data']) || $context['data'][$name] !== '') {
					$this->passiveValid = false;
				}
			}
			foreach ($this->callbacks as $callback) {
				$callback($context['data']);
			}
		};
		$validator->requirePresence($field, function (array $context) use ($begin): bool {
			$begin($context);

			return true;
		});
		// Empty strings must reach the rules; null still fails the emptiness check.
		$validator->allowEmptyFor($field, Validator::EMPTY_NULL, function (array $context) use ($begin, $field): bool {
			if (!$context['field'] instanceof ValidationSet) {
				return in_array($field, $this->passiveFields, true);
			}
			$begin($context);

			return false;
		});
	}

	public function passiveValid(): bool {
		return $this->passiveValid;
	}

	public function limited(VerifyRateLimiter $limiter, string $ip, string $sessionId): bool {
		if (!$limiter->enabled()) {
			return false;
		}
		$key = $limiter->identity($ip, $sessionId);
		$count = $this->counts[$key] ??= $limiter->count($ip, $sessionId);

		return $count >= $limiter->threshold();
	}

	public function increment(VerifyRateLimiter $limiter, string $ip, string $sessionId): void {
		$key = $limiter->identity($ip, $sessionId);
		if (isset($this->counted[$key]) || $this->limited($limiter, $ip, $sessionId)) {
			return;
		}
		$limiter->increment($ip, $sessionId);
		$this->counted[$key] = true;
	}

}
