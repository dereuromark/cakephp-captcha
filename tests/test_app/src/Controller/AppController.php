<?php

namespace TestApp\Controller;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Event\EventInterface;

class AppController extends Controller {

	/**
	 * @param \Cake\Event\EventInterface $event
	 * @return mixed
	 */
	public function beforeFilter(EventInterface $event) {
		parent::beforeFilter($event);
		if (Configure::read('Test.hostRedirect')) {
			return $this->redirect('/login');
		}
		if (Configure::read('Test.hostRedirectViaEvent')) {
			$event->setResult($this->redirect('/login'));
		}

		return null;
	}

}
