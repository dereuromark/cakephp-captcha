<?php
declare(strict_types=1);

namespace TestApp\Cache\Engine;

use Cake\Cache\CacheEngine;
use Cake\Cache\Engine\FileEngine;
use DateInterval;

/**
 * Wraps a FileEngine the way DebugKit's DebugEngine wraps every configured cache.
 */
class DecoratedFileEngine extends CacheEngine {

	public function __construct(protected FileEngine $engine) {
	}

	public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool {
		return $this->engine->set($key, $value, $ttl);
	}

	public function get(string $key, mixed $default = null): mixed {
		return $this->engine->get($key, $default);
	}

	public function increment(string $key, int $offset = 1): int|false {
		return $this->engine->increment($key, $offset);
	}

	public function decrement(string $key, int $offset = 1): int|false {
		return $this->engine->decrement($key, $offset);
	}

	public function delete(string $key): bool {
		return $this->engine->delete($key);
	}

	public function clear(): bool {
		return $this->engine->clear();
	}

	public function clearGroup(string $group): bool {
		return $this->engine->clearGroup($group);
	}

}
