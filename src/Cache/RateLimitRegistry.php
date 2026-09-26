<?php
declare(strict_types=1);

namespace Captcha\Cache;

use Cake\Cache\Cache;
use Cake\Cache\CacheEngine;

/**
 * Bounded, best-effort discovery for the admin UI. Enforcement uses the counters directly.
 *
 * @internal
 */
class RateLimitRegistry {

	/**
     * @var string
     */
	protected const KEY = 'captcha_verify_rate_limit_registry';

	/**
     * @var int
     */
	protected const LIMIT = 1000;

	public function __construct(protected string $cache) {
	}

	public function record(string $key, string $ip, int $expires, int $threshold): void {
		$lock = static::KEY . '_lock';
		for ($attempt = 0; $attempt < 20; $attempt++) {
			if ($this->acquireLock($lock)) {
				try {
					$entries = $this->entries();
					unset($entries[$key]);
					$entries[$key] = compact('ip', 'expires', 'threshold');
					Cache::write(static::KEY, array_slice($entries, -static::LIMIT, null, true), $this->cache);
				} finally {
					Cache::delete($lock, $this->cache);
				}

				return;
			}
			usleep(1000);
		}
	}

	protected function acquireLock(string $key): bool {
		$cache = Cache::pool($this->cache);
		if (!$cache instanceof CacheEngine) {
			return false;
		}
		$duration = $cache->getConfig('duration');
		$cache->setConfig('duration', 2);
		try {
			return $cache->add($key, true);
		} finally {
			$cache->setConfig('duration', $duration);
		}
	}

	/**
	 * @return array<string, array{ip: string, expires: int, threshold: int}>
	 */
	protected function entries(): array {
		$entries = Cache::read(static::KEY, $this->cache);
		if (!is_array($entries)) {
			return [];
		}

		return array_filter($entries, static fn (array $entry): bool => $entry['expires'] > time());
	}

	/**
	 * @return array<int, array{ip: string, n: int}>
	 */
	public function throttledIps(): array {
		$counts = [];
		foreach ($this->entries() as $key => $entry) {
			$count = Cache::read($key, $this->cache);
			if (is_int($count) && $count >= $entry['threshold']) {
				$counts[$entry['ip']] = ($counts[$entry['ip']] ?? 0) + $count;
			}
		}
		arsort($counts);
		$result = [];
		foreach ($counts as $ip => $n) {
			$result[] = ['ip' => $ip, 'n' => $n];
		}

		return $result;
	}

	public function clearIp(string $ip): int {
		$cleared = 0;
		foreach ($this->entries() as $key => $entry) {
			if ($entry['ip'] === $ip && Cache::delete($key, $this->cache)) {
				$cleared++;
			}
		}

		return $cleared;
	}

}
