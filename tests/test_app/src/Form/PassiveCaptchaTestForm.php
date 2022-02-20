<?php

namespace TestApp\Form;

use Cake\Event\EventManager;
use Cake\Form\Form;
use Cake\ORM\BehaviorRegistry;
use Cake\ORM\Table;

class PassiveCaptchaTestForm extends Form {

	/**
	 * @var \Cake\ORM\BehaviorRegistry
	 */
	protected $_behaviors;

	/**
	 * @param \Cake\Event\EventManager|null $eventManager
	 */
	public function __construct(?EventManager $eventManager = null) {
		parent::__construct($eventManager);

		$this->_behaviors = new BehaviorRegistry();
		$this->_behaviors->setTable(new Table());
	}

	/**
	 * @param string $name
	 * @param array<string, mixed> $options
	 *
	 * @return $this
	 */
	public function addBehavior($name, array $options = []) {
		$this->_behaviors->load($name, $options);

		return $this;
	}

	/**
	 * Returns the behavior registry for this table.
	 *
	 * @return \Cake\ORM\BehaviorRegistry The BehaviorRegistry instance.
	 */
	public function behaviors() {
		return $this->_behaviors;
	}

}
