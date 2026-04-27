<?php
declare(strict_types=1);

namespace Captcha\Cache;

/**
 * Builds the cache key used by the verify rate limiter.
 *
 * The same algorithm is used by the captcha behavior at write time and by
 * the admin backend when reading or wiping keys for a given (ip, session)
 * tuple. Keep both callers in sync via this single source of truth.
 */
class RateLimitKey {

	/**
	 * @var string
	 */
	public const SCOPE_IP = 'ip';

	/**
	 * @var string
	 */
	public const SCOPE_IP_SESSION = 'ip_session';

	/**
	 * @var string
	 */
	public const KEY_PREFIX = 'captcha_verify_rate_limit_';

	/**
	 * @param string $ip
	 * @param string $sessionId
	 * @param string $scope `ip` or `ip_session`
	 * @param int $window Seconds (must be >= 1).
	 * @param int|null $now Optional timestamp override for tests.
	 *
	 * @return string
	 */
	public static function build(
		string $ip,
		string $sessionId,
		string $scope,
		int $window,
		?int $now = null,
	): string {
		$keyData = $scope === static::SCOPE_IP ? $ip : $ip . '|' . $sessionId;
		$window = max($window, 1);
		$bucket = (int)floor(($now ?? time()) / $window);

		return static::KEY_PREFIX . sha1($keyData) . '_' . $bucket;
	}

}
