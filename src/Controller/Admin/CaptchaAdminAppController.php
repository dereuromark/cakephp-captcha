<?php
declare(strict_types=1);

namespace Captcha\Controller\Admin;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Exception\ForbiddenException;
use Closure;

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
		parent::beforeFilter($event);

		if ($this->components()->has('Authorization') && method_exists($this->components()->get('Authorization'), 'skipAuthorization')) {
			$this->components()->get('Authorization')->skipAuthorization();
		}

		$gate = Configure::read('Captcha.adminAccess');
		if (!($gate instanceof Closure)) {
			throw new ForbiddenException(__d('captcha', 'Captcha admin backend is not configured. Set Captcha.adminAccess to a Closure.'));
		}
		if ($gate($this->request) !== true) {
			throw new ForbiddenException(__d('captcha', 'Captcha admin access denied.'));
		}
	}

}
