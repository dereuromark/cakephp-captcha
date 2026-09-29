<?php
declare(strict_types=1);

namespace Captcha\Test\TestCase\Cache;

use Cake\Cache\Cache;
use Cake\Cache\Engine\FileEngine;
use Cake\TestSuite\TestCase;
use Captcha\Cache\RateLimitKey;
use Captcha\Cache\RateLimitRegistry;
use Captcha\Cache\VerifyRateLimiter;
use TestApp\Cache\Engine\DecoratedFileEngine;

class VerifyRateLimiterTest extends TestCase {

	public function testRegistryUsesLiveCountersAndSessionThresholds(): void {
		Cache::setConfig('limiter_test', ['className' => 'Array', 'duration' => 600]);
		try {
			$limiter = new VerifyRateLimiter(['enabled' => true, 'maxFailures' => 2, 'window' => 600, 'scope' => 'ip_session', 'cache' => 'limiter_test']);
			$registry = new RateLimitRegistry('limiter_test');
			$limiter->increment('127.0.0.1', 'one');
			$limiter->increment('127.0.0.1', 'two');
			$this->assertSame([], $registry->throttledIps());
			$limiter->increment('127.0.0.1', 'two');
			$this->assertSame([['ip' => '127.0.0.1', 'n' => 2]], $registry->throttledIps());
			$this->assertSame(600, Cache::pool('limiter_test')->getConfig('duration'));
			$limiter->clear('127.0.0.1', 'two');
			$this->assertSame([], $registry->throttledIps());
			$expiredKey = RateLimitKey::build('127.0.0.2', '', 'ip', 600, time() - 600);
			Cache::write($expiredKey, 99, 'limiter_test');
			$registry->record($expiredKey, '127.0.0.2', time() - 1, 2);
			$this->assertSame([], $registry->throttledIps());
		} finally {
			Cache::drop('limiter_test');
		}
	}

	public function testFileCacheFallback(): void {
		$path = TMP . 'captcha_limiter_' . bin2hex(random_bytes(8)) . DIRECTORY_SEPARATOR;
		mkdir($path);
		Cache::setConfig('limiter_file', ['className' => 'File', 'path' => $path]);
		try {
			Cache::clear('limiter_file');
			$limiter = new VerifyRateLimiter(['enabled' => true, 'maxFailures' => 2, 'window' => 600, 'scope' => 'ip', 'cache' => 'limiter_file']);
			$limiter->increment('127.0.0.1', 'one');
			$this->assertFalse($limiter->limited('127.0.0.1', 'two'));
			$limiter->increment('127.0.0.1', 'two');
			$this->assertTrue($limiter->limited('127.0.0.1', 'one'));
			$this->assertSame([['ip' => '127.0.0.1', 'n' => 2]], (new RateLimitRegistry('limiter_file'))->throttledIps());
		} finally {
			Cache::clear('limiter_file');
			Cache::drop('limiter_file');
			rmdir($path);
		}
	}

	/**
	 * A decorated FileEngine (e.g. DebugKit's DebugEngine) hides the engine class,
	 * so the limiter must fall back when the atomic increment is not supported.
	 *
	 * @return void
	 */
	public function testDecoratedFileCacheFallback(): void {
		$path = TMP . 'captcha_limiter_' . bin2hex(random_bytes(8)) . DIRECTORY_SEPARATOR;
		mkdir($path);
		$fileEngine = new FileEngine();
		$fileEngine->init(['path' => $path]);
		Cache::setConfig('limiter_decorated', ['className' => new DecoratedFileEngine($fileEngine)]);
		try {
			Cache::clear('limiter_decorated');
			$limiter = new VerifyRateLimiter(['enabled' => true, 'maxFailures' => 3, 'window' => 600, 'scope' => 'ip', 'cache' => 'limiter_decorated']);
			$limiter->increment('127.0.0.1', 'one');
			$limiter->increment('127.0.0.1', 'one');
			$this->assertFalse($limiter->limited('127.0.0.1', 'one'));
			$limiter->increment('127.0.0.1', 'one');
			$this->assertTrue($limiter->limited('127.0.0.1', 'one'));
		} finally {
			Cache::clear('limiter_decorated');
			Cache::drop('limiter_decorated');
			array_map('unlink', glob($path . '*') ?: []);
			rmdir($path);
		}
	}

}
