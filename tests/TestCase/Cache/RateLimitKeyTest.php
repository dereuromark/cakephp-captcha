<?php
declare(strict_types=1);

namespace Captcha\Test\TestCase\Cache;

use Cake\TestSuite\TestCase;
use Captcha\Cache\RateLimitKey;

class RateLimitKeyTest extends TestCase {

	/**
	 * @return void
	 */
	public function testIpScopeIgnoresSession() {
		$now = 1000;
		$keyOne = RateLimitKey::build('1.2.3.4', 'sessA', RateLimitKey::SCOPE_IP, 600, $now);
		$keyTwo = RateLimitKey::build('1.2.3.4', 'sessB', RateLimitKey::SCOPE_IP, 600, $now);

		$this->assertSame($keyOne, $keyTwo);
	}

	/**
	 * @return void
	 */
	public function testIpSessionScopeIncludesSession() {
		$now = 1000;
		$keyOne = RateLimitKey::build('1.2.3.4', 'sessA', RateLimitKey::SCOPE_IP_SESSION, 600, $now);
		$keyTwo = RateLimitKey::build('1.2.3.4', 'sessB', RateLimitKey::SCOPE_IP_SESSION, 600, $now);

		$this->assertNotSame($keyOne, $keyTwo);
	}

	/**
	 * @return void
	 */
	public function testBucketRollsOver() {
		$keyBefore = RateLimitKey::build('1.2.3.4', 'sess', RateLimitKey::SCOPE_IP_SESSION, 600, 599);
		$keyAfter = RateLimitKey::build('1.2.3.4', 'sess', RateLimitKey::SCOPE_IP_SESSION, 600, 600);

		$this->assertNotSame($keyBefore, $keyAfter);
	}

	/**
	 * @return void
	 */
	public function testKeyShape() {
		$key = RateLimitKey::build('1.2.3.4', 'sess', RateLimitKey::SCOPE_IP_SESSION, 600, 1000);

		$this->assertStringStartsWith(RateLimitKey::KEY_PREFIX, $key);
		$this->assertStringEndsWith('_1', $key);
	}

	/**
	 * @return void
	 */
	public function testZeroWindowGetsClampedToOne() {
		$keyOne = RateLimitKey::build('1.2.3.4', 'sess', RateLimitKey::SCOPE_IP_SESSION, 0, 5);
		$keyTwo = RateLimitKey::build('1.2.3.4', 'sess', RateLimitKey::SCOPE_IP_SESSION, 1, 5);

		$this->assertSame($keyTwo, $keyOne);
	}

	/**
	 * @return void
	 */
	public function testDifferentIpsProduceDifferentKeys() {
		$now = 1000;
		$keyOne = RateLimitKey::build('1.2.3.4', 'sess', RateLimitKey::SCOPE_IP_SESSION, 600, $now);
		$keyTwo = RateLimitKey::build('5.6.7.8', 'sess', RateLimitKey::SCOPE_IP_SESSION, 600, $now);

		$this->assertNotSame($keyOne, $keyTwo);
	}

}
