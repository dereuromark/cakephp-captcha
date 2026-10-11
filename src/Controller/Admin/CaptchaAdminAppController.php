<?php
declare(strict_types=1);

namespace Captcha\Controller\Admin;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Exception\ForbiddenException;
use Cake\Log\Log;
use Closure;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * Base controller for the Captcha admin backend.
 *
 * Default policy is deny — `Captcha.adminAccess` must be a closure that
 * returns true for the current request to grant access. This is intentional:
 * captcha is security-adjacent and accidental exposure is harmful.
 */
class CaptchaAdminAppController extends AppController {

	/**
	 * @return void
	 */
	public function initialize(): void {
		parent::initialize();

		if (!$this->components()->has('Flash')) {
			$this->loadComponent('Flash');
		}

		$layout = Configure::read('Captcha.adminLayout');
		if ($layout !== false) {
			$this->viewBuilder()->setLayout(is_string($layout) ? $layout : 'Captcha.captcha-admin');
		}
	}

	/**
     * @param \Cake\Event\EventInterface $event
     * @throws \Cake\Http\Exception\ForbiddenException When access is denied.
     * @return void
	 */
	public function beforeFilter(EventInterface $event): void {
		$result = parent::beforeFilter($event);
		if ($result instanceof ResponseInterface) {
			$event->setResult($result);
		}
		if ($event->getResult() instanceof ResponseInterface) {
			$event->stopPropagation();

			return;
		}

		if ($this->components()->has('Authorization') && method_exists($this->components()->get('Authorization'), 'skipAuthorization')) {
			$this->components()->get('Authorization')->skipAuthorization();
		} else {
			$authorization = $this->request->getAttribute('authorization');
			if (is_object($authorization) && is_callable([$authorization, 'skipAuthorization'])) {
				$authorization->skipAuthorization();
			}
		}

		$gate = Configure::read('Captcha.adminAccess');
		if (!($gate instanceof Closure)) {
			throw new ForbiddenException(__d('captcha', 'Captcha admin backend is not configured. Set Captcha.adminAccess to a Closure.'));
		}
		try {
			$allowed = $gate($this->request) === true;
		} catch (ForbiddenException $e) {
			throw $e;
		} catch (Throwable $e) {
			Log::warning(sprintf('Captcha.adminAccess threw %s: %s', $e::class, $e->getMessage()));

			throw new ForbiddenException(__d('captcha', 'Captcha admin access denied.'));
		}
		if (!$allowed) {
			throw new ForbiddenException(__d('captcha', 'Captcha admin access denied.'));
		}
	}

}
