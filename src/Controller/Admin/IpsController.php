<?php
declare(strict_types=1);

namespace Captcha\Controller\Admin;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Http\Exception\BadRequestException;
use Cake\I18n\DateTime;
use Captcha\Cache\RateLimitKey;

/**
 * Per-IP signals and maintenance for the captcha admin backend.
 *
 * @property \Captcha\Model\Table\CaptchasTable $Captchas
 */
class IpsController extends CaptchaAdminAppController {

	/**
	 * @var string|null
	 */
	protected ?string $defaultTable = 'Captcha.Captchas';

	/**
     * @var int
     */
	protected const WINDOW_24H = 86400;

	/**
     * @var int
     */
	protected const WINDOW_7D = 604800;

	/**
	 * @return void
	 */
	public function index(): void {
		$window = (int)$this->request->getQuery('window', static::WINDOW_24H);
		if (!in_array($window, [static::WINDOW_24H, static::WINDOW_7D], true)) {
			$window = static::WINDOW_24H;
		}
		$since = DateTime::now()->subSeconds($window);

		$issued = $this->topIps($since, null);
		$solved = $this->topIps($since, true);
		$failed = $this->topIps($since, false);
		$throttled = $this->throttledIps();

		$this->set(compact('issued', 'solved', 'failed', 'throttled', 'window'));
	}

	/**
	 * @param string|null $ip
	 *
	 * @return void
	 */
	public function view(?string $ip = null): void {
		$ip = $this->assertValidIp($ip);
		$query = $this->Captchas->find()
			->where(['ip' => $ip])
			->orderBy(['created' => 'DESC']);
		$captchas = $this->paginate($query, ['limit' => 50]);

		$since24h = DateTime::now()->subSeconds(static::WINDOW_24H);
		$summary = ['issued' => 0, 'solved' => 0, 'failed' => 0];
		$rows = $this->Captchas->find()
			->select(['solved' => 'solved'])
			->where(['ip' => $ip, 'created >' => $since24h])
			->disableHydration()
			->all();
		foreach ($rows as $row) {
			$summary['issued']++;
			$solvedValue = $row['solved'] ?? null;
			if ($solvedValue === true || $solvedValue === 1) {
				$summary['solved']++;
			} elseif ($solvedValue === false || $solvedValue === 0) {
				$summary['failed']++;
			}
		}

		$this->set(compact('ip', 'captchas', 'summary'));
	}

	/**
	 * @param string|null $ip
	 *
	 * @return \Cake\Http\Response|null
	 */
	public function reset(?string $ip = null) {
		$this->request->allowMethod('post');
		$ip = $this->assertValidIp($ip);

		$count = $this->Captchas->reset($ip);

		$this->Flash->success(__d('captcha', '{0} captcha row(s) deleted for IP {1}.', $count, $ip));

		return $this->redirect(['action' => 'index']);
	}

	/**
	 * @param string|null $ip
	 *
	 * @return \Cake\Http\Response|null
	 */
	public function clearRateLimit(?string $ip = null) {
		$this->request->allowMethod('post');
		$ip = $this->assertValidIp($ip);

		$rl = (array)Configure::read('Captcha.verifyRateLimit');
		$cache = (string)($rl['cache'] ?? 'default');
		$scope = (string)($rl['scope'] ?? RateLimitKey::SCOPE_IP_SESSION);
		$window = (int)($rl['window'] ?? 600);

		$sessionIds = ['']; // covers ip-only scope
		if ($scope === RateLimitKey::SCOPE_IP_SESSION) {
			$since = DateTime::now()->subSeconds(max($window, static::WINDOW_24H));
			$sessionIds = $this->Captchas->find()
				->select(['session_id' => 'session_id'])
				->where(['ip' => $ip, 'created >' => $since])
				->distinct(['session_id'])
				->disableHydration()
				->all()
				->extract('session_id')
				->toList();
			if (!$sessionIds) {
				$sessionIds = [''];
			}
		}

		$cleared = 0;
		$now = time();
		foreach ($sessionIds as $sessionId) {
			$keyCurrent = RateLimitKey::build($ip, (string)$sessionId, $scope, $window, $now);
			$keyPrev = RateLimitKey::build($ip, (string)$sessionId, $scope, $window, $now - $window);
			foreach ([$keyCurrent, $keyPrev] as $key) {
				if (Cache::delete($key, $cache)) {
					$cleared++;
				}
			}
		}

		$this->Flash->success(__d('captcha', 'Cleared {0} rate-limit cache key(s) for IP {1}.', $cleared, $ip));

		return $this->redirect(['action' => 'index']);
	}

	/**
	 * @param \Cake\I18n\DateTime $since
	 * @param bool|null $solved Filter on the solved column. `null` = no filter (all rows).
	 *
	 * @return array<int, array{ip: string, n: int}>
	 */
	protected function topIps(DateTime $since, ?bool $solved): array {
		$query = $this->Captchas->find();
		$conditions = ['created >' => $since];
		if ($solved !== null) {
			$conditions['solved'] = $solved;
		}
		$query->select([
			'ip' => 'ip',
			'n' => $query->func()->count('*'),
		])
			->where($conditions)
			->groupBy(['ip'])
			->orderBy(['n' => 'DESC'])
			->limit(10);

		$out = [];
		foreach ($query->disableHydration()->all() as $row) {
			$out[] = ['ip' => (string)$row['ip'], 'n' => (int)$row['n']];
		}

		return $out;
	}

	/**
	 * Validate the IP path argument as a real IPv4/IPv6 address.
	 *
	 * Defense-in-depth against arbitrary path strings being reflected into
	 * queries, cache keys and flash messages.
	 *
	 * @param string|null $ip
	 *
	 * @throws \Cake\Http\Exception\BadRequestException
	 *
	 * @return string Normalized IP string.
	 */
	protected function assertValidIp(?string $ip): string {
		$ip = (string)$ip;
		if ($ip === '' || filter_var($ip, FILTER_VALIDATE_IP) === false) {
			throw new BadRequestException(__d('captcha', 'Invalid IP address.'));
		}

		return $ip;
	}

	/**
	 * @return array<int, array{ip: string, n: int}>
	 */
	protected function throttledIps(): array {
		$rl = (array)Configure::read('Captcha.verifyRateLimit');
		if (!($rl['enabled'] ?? true)) {
			return [];
		}
		$window = (int)($rl['window'] ?? 600);
		$max = (int)($rl['maxFailures'] ?? 5);

		$since = DateTime::now()->subSeconds($window);
		$query = $this->Captchas->find();
		$query->select([
			'ip' => 'ip',
			'n' => $query->func()->count('*'),
		])
			->where(['solved' => false, 'created >' => $since])
			->groupBy(['ip'])
			->having(['n >=' => $max])
			->orderBy(['n' => 'DESC'])
			->limit(10);

		$out = [];
		foreach ($query->disableHydration()->all() as $row) {
			$out[] = ['ip' => (string)$row['ip'], 'n' => (int)$row['n']];
		}

		return $out;
	}

}
