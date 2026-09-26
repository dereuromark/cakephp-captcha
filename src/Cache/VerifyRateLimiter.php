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

		$count = Cache::read($this->key($ip, $sessionId), (string)$this->config['cache']);

		return is_int($count) && $count >= (int)$this->config['maxFailures'];
	}

	public function increment(string $ip, string $sessionId): void {
		if (!$this->enabled()) {
			return;
		}

		$cacheName = (string)$this->config['cache'];
		$key = $this->key($ip, $sessionId);
		if (!Cache::add($key, 0, $cacheName)) {
			$cache = Cache::pool($cacheName);
			if (!$cache instanceof FileEngine && !$cache instanceof CacheNullEngine) {
				$count = Cache::increment($key, 1, $cacheName);
				if ($count !== false) {
					return;
				}
			}
		}

		$count = Cache::read($key, $cacheName);
		Cache::write($key, is_int($count) ? $count + 1 : 1, $cacheName);
	}

	public function clear(string $ip, string $sessionId): void {
		if ($this->enabled()) {
			Cache::delete($this->key($ip, $sessionId), (string)$this->config['cache']);
		}
	}

	protected function key(string $ip, string $sessionId): string {
		return RateLimitKey::build(
			$ip,
			$sessionId,
			(string)$this->config['scope'],
			(int)$this->config['window'],
		);
	}

}
