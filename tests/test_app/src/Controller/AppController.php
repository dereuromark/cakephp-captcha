<?php

namespace TestApp\Controller;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Event\EventInterface;

class AppController extends Controller {

	/**
	 * @param \Cake\Event\EventInterface $event
	 * @return void
	 */
	public function beforeFilter(EventInterface $event): void {
		parent::beforeFilter($event);
		if (Configure::read('Test.hostRedirect')) {
			$event->setResult($this->redirect('/login'));

			return;
		}
		if (Configure::read('Test.hostRedirectViaEvent')) {
			$event->setResult($this->redirect('/login'));
		}

	}

}
