<?php
declare(strict_types=1);

namespace Captcha\Controller\Admin;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Http\Exception\BadRequestException;
use Cake\I18n\DateTime;
use Captcha\Engine\EngineInterface;
use Captcha\Engine\MathEngine;
use Captcha\Engine\NullEngine;

/**
 * Captcha admin dashboard, config & engine views, maintenance actions.
 *
 * @property \Captcha\Model\Table\CaptchasTable $Captchas
 */
class CaptchaController extends CaptchaAdminAppController {

	/**
	 * @var string|null
	 */
	protected ?string $defaultTable = 'Captcha.Captchas';

	/**
	 * Window sizes (seconds) used by the dashboard tiles.
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
		$tiles24h = $this->aggregateCounts(static::WINDOW_24H);
		$tiles7d = $this->aggregateCounts(static::WINDOW_7D);
		$throttledIps = $this->countThrottledIps();
		$heatmap = $this->buildHeatmap();
		$snapshot = $this->buildSnapshot();

		$this->set(compact('tiles24h', 'tiles7d', 'throttledIps', 'heatmap', 'snapshot'));
	}

	/**
	 * @return void
	 */
	public function config(): void {
		$config = (array)Configure::read('Captcha');

		$this->set(compact('config'));
	}

	/**
	 * @return void
	 */
	public function engine(): void {
		$engines = $this->knownEngines();
		$activeClass = (string)Configure::read('Captcha.engine', MathEngine::class);

		$this->set(compact('engines', 'activeClass'));
	}

	/**
	 * Renders one captcha through the configured engine for verification purposes.
	 *
	 * @return void
	 */
	public function preview(): void {
		$activeClass = (string)Configure::read('Captcha.engine', MathEngine::class);
		if (!class_exists($activeClass) || !is_subclass_of($activeClass, EngineInterface::class)) {
			throw new BadRequestException(__d('captcha', 'Configured engine is not a valid EngineInterface.'));
		}

		/** @var \Captcha\Engine\EngineInterface $engine */
		$engine = new $activeClass((array)Configure::read('Captcha.engineConfig'));
		$payload = $engine->generate();

		$image = $payload['image'] ?? null;
		$imageDataUri = (is_string($image) && $image !== '')
			? 'data:image/png;base64,' . base64_encode($image)
			: null;

		$this->set([
			'engineClass' => $activeClass,
			'engineShortName' => $this->shortName($activeClass),
			'payload' => $payload,
			'imageDataUri' => $imageDataUri,
		]);
	}

	/**
	 * @return \Cake\Http\Response|null
	 */
	public function cleanup() {
		$this->request->allowMethod('post');

		$count = $this->Captchas->cleanup(100);

		$this->Flash->success(__d('captcha', '{0} captcha row(s) deleted.', $count));

		return $this->redirect(['action' => 'index']);
	}

	/**
	 * @return \Cake\Http\Response|null
	 */
	public function hardReset() {
		$this->request->allowMethod('post');

		$count = $this->Captchas->deleteAll([]);

		$this->Flash->success(__d('captcha', '{0} captcha row(s) deleted.', $count));

		return $this->redirect(['action' => 'index']);
	}

	/**
	 * @param int $window Seconds to look back from now.
	 *
	 * @return array{open: int, solved: int, failed: int, expired: int}
	 */
	protected function aggregateCounts(int $window): array {
		$since = DateTime::now()->subSeconds($window);
		$rows = $this->Captchas->find()
			->select([
				'solved' => 'solved',
				'used' => 'used',
				'created' => 'created',
			])
			->where(['created >' => $since])
			->disableHydration()
			->all();

		$open = 0;
		$solved = 0;
		$failed = 0;
		$expired = 0;
		$staleCutoff = DateTime::now()->subSeconds((int)(Configure::read('Captcha.maxTime') ?? DAY));
		foreach ($rows as $row) {
			$solvedValue = $row['solved'] ?? null;
			if ($solvedValue === true || $solvedValue === 1) {
				$solved++;

				continue;
			}
			if ($solvedValue === false || $solvedValue === 0) {
				$failed++;

				continue;
			}
			if ($row['used'] === null) {
				$rowCreated = $row['created'];
				if ($rowCreated instanceof DateTime && $rowCreated < $staleCutoff) {
					$expired++;
				} else {
					$open++;
				}
			}
		}

		return ['open' => $open, 'solved' => $solved, 'failed' => $failed, 'expired' => $expired];
	}

	/**
	 * @return int
	 */
	protected function countThrottledIps(): int {
		$rl = (array)Configure::read('Captcha.verifyRateLimit');
		if (!($rl['enabled'] ?? true)) {
			return 0;
		}
		$window = (int)($rl['window'] ?? 600);
		$max = (int)($rl['maxFailures'] ?? 5);

		$since = DateTime::now()->subSeconds($window);
		$query = $this->Captchas->find();
		$query->select([
			'ip' => 'ip',
			'failed_in_window' => $query->func()->count('*'),
		])
			->where(['solved' => false, 'created >' => $since])
			->groupBy(['ip'])
			->having(['failed_in_window >=' => $max]);

		return $query->all()->count();
	}

	/**
	 * Returns 7×24 grid: hours = 0..23, days = 0..6 (today back).
	 *
	 * @return array<int, array<int, array{issued: int, solved: int, failed: int}>>
	 */
	protected function buildHeatmap(): array {
		$since = DateTime::now()->subSeconds(static::WINDOW_7D);
		$rows = $this->Captchas->find()
			->select(['created' => 'created', 'solved' => 'solved'])
			->where(['created >' => $since])
			->disableHydration()
			->all();

		$grid = [];
		for ($d = 0; $d < 7; $d++) {
			for ($h = 0; $h < 24; $h++) {
				$grid[$d][$h] = ['issued' => 0, 'solved' => 0, 'failed' => 0];
			}
		}
		$now = DateTime::now();
		foreach ($rows as $row) {
			$created = $row['created'];
			if (!$created instanceof DateTime) {
				continue;
			}
			$daysAgo = (int)$now->diffInDays($created, true);
			if ($daysAgo < 0 || $daysAgo > 6) {
				continue;
			}
			$hour = (int)$created->format('G');
			$cell = $grid[$daysAgo][$hour];
			$cell['issued']++;
			$solvedValue = $row['solved'] ?? null;
			if ($solvedValue === true || $solvedValue === 1) {
				$cell['solved']++;
			} elseif ($solvedValue === false || $solvedValue === 0) {
				$cell['failed']++;
			}
			$grid[$daysAgo][$hour] = $cell;
		}

		return $grid;
	}

	/**
	 * @return array{engine: string, maxPerUser: int, deadlockMinutes: int, cleanupProbability: int, rateLimit: string}
	 */
	protected function buildSnapshot(): array {
		$engineClass = (string)Configure::read('Captcha.engine', MathEngine::class);
		$rl = (array)Configure::read('Captcha.verifyRateLimit');
		$rlSummary = ($rl['enabled'] ?? true)
			? sprintf('%d/%ds', (int)($rl['maxFailures'] ?? 5), (int)($rl['window'] ?? 600))
			: __d('captcha', 'disabled');

		return [
			'engine' => $this->shortName($engineClass),
			'maxPerUser' => (int)(Configure::read('Captcha.maxPerUser') ?? 100),
			'deadlockMinutes' => (int)(Configure::read('Captcha.deadlockMinutes') ?? 60),
			'cleanupProbability' => (int)(Configure::read('Captcha.cleanupProbability') ?? 10),
			'rateLimit' => $rlSummary,
		];
	}

	/**
	 * @return array<int, array{class: string, short: string}>
	 */
	protected function knownEngines(): array {
		$candidates = [MathEngine::class, NullEngine::class];
		$registered = (string)Configure::read('Captcha.engine', '');
		if ($registered && !in_array($registered, $candidates, true)) {
			$candidates[] = $registered;
		}
		$out = [];
		foreach ($candidates as $candidate) {
			if (App::className($candidate, 'Engine') || class_exists($candidate)) {
				$out[] = ['class' => $candidate, 'short' => $this->shortName($candidate)];
			}
		}

		return $out;
	}

	/**
	 * @param string $class
	 *
	 * @return string
	 */
	protected function shortName(string $class): string {
		$pos = strrpos($class, '\\');
		if ($pos === false) {
			return $class;
		}

		return substr($class, $pos + 1);
	}

}
