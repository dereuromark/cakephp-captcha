<?php
declare(strict_types=1);

namespace Captcha\Cache;

use Cake\Cache\Cache;
use Cake\Cache\Engine\FileEngine;
use Cake\Cache\Engine\NullEngine as CacheNullEngine;

class VerifyRateLimiter {

	/**
	 * @param array{enabled: bool, maxFailures: int, window: int, scope: string, cache: string} $config
	 */
	public function __construct(protected array $config) {
	}

	public function enabled(): bool {
		return !empty($this->config['enabled']);
	}

	public function limited(string $ip, string $sessionId): bool {
		if (!$this->enabled()) {
			return false;
		}

		return $this->count($ip, $sessionId) >= $this->threshold();
	}

	public function count(string $ip, string $sessionId): int {
		$count = Cache::read($this->key($ip, $sessionId), (string)$this->config['cache']);

		return is_int($count) ? $count : 0;
	}

	public function threshold(): int {
		return (int)$this->config['maxFailures'];
	}

	public function increment(string $ip, string $sessionId): void {
		if (!$this->enabled()) {
			return;
		}

		$cacheName = (string)$this->config['cache'];
		$now = time();
		$key = $this->key($ip, $sessionId, $now);
		if (!Cache::add($key, 1, $cacheName)) {
			$cache = Cache::pool($cacheName);
			$count = false;
			if (!$cache instanceof FileEngine && !$cache instanceof CacheNullEngine) {
				$count = Cache::increment($key, 1, $cacheName);
			}
			if ($count === false) {
				$count = Cache::read($key, $cacheName);
				Cache::write($key, is_int($count) ? $count + 1 : 1, $cacheName);
			}
		}

		$window = max(1, (int)$this->config['window']);
		$expires = (int)(floor($now / $window) + 1) * $window;
		(new RateLimitRegistry($cacheName))->record($key, $ip, $expires, (int)$this->config['maxFailures']);
	}

	public function clear(string $ip, string $sessionId): void {
		if ($this->enabled()) {
			Cache::delete($this->key($ip, $sessionId), (string)$this->config['cache']);
		}
	}

	public function identity(string $ip, string $sessionId): string {
		return $this->config['cache'] . ':' . $this->key($ip, $sessionId);
	}

	protected function key(string $ip, string $sessionId, ?int $now = null): string {
		return RateLimitKey::build(
			$ip,
			$sessionId,
			(string)$this->config['scope'],
			(int)$this->config['window'],
			$now,
		);
	}

}
