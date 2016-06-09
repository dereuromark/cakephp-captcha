<?php
namespace Captcha\Model\Table;

use BadMethodCallException;
use Cake\Core\Configure;
use Cake\Database\Schema\Table as Schema;
use Cake\I18n\Time;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Captchas Model
 *
 * @property \Cake\ORM\Association\BelongsTo $Sessions
 */
class CaptchasTable extends Table {

	/**
	 * @var array
	 */
	protected $_defaultConfig = [
		'hashType' => null,
		'engine' => 'Captcha\Engine\MathEngine',
	];

	/**
	 * @param \Cake\Database\Schema\Table $table
	 *
	 * @return \Cake\Database\Schema\Table
	 */
	protected function _initializeSchema(Schema $table) {
		$table->columnType('image', 'image');
		return $table;
	}

	/**
	 * Initialize method
	 *
	 * @param array $config The configuration for the Table.
	 * @return void
	 */
	public function initialize(array $config) {
		parent::initialize($config);

		$this->table('captchas');
		$this->displayField('id');
		$this->primaryKey('id');

		$this->addBehavior('Timestamp');
	}

	/**
	 * Default validation rules.
	 *
	 * @param \Cake\Validation\Validator $validator Validator instance.
	 * @return \Cake\Validation\Validator
	 */
	public function validationDefault(Validator $validator) {
		$validator
			->integer('id')
			->allowEmpty('id', 'create');

		$validator
			->requirePresence('ip', 'create')
			->notEmpty('ip');

		$validator
			->requirePresence('session_id', 'create')
			->notEmpty('session_id');

		$validator
			->allowEmpty('result');

		$validator
			->dateTime('used')
			->allowEmpty('used');

		return $validator;
	}

	/**
	 * Returns a rules checker object that will be used for validating
	 * application integrity.
	 *
	 * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
	 * @return \Cake\ORM\RulesChecker
	 */
	public function buildRules(RulesChecker $rules) {
		return $rules;
	}

	/**
	 * @param string $sessionId
	 * @param string $ip
	 *
	 * @return int
	 */
	public function touch($sessionId, $ip) {
		$captcha = $this->newEntity(
			[
				'session_id' => $sessionId,
				'ip' => $ip
			]
		);
		if (!$this->save($captcha)) {
			throw new BadMethodCallException('Sth went wrong');
		}

		return $captcha->id;
	}

	/**
	 * @param string $ip
	 * @param string $sessionId
	 *
	 * @return int
	 */
	public function getCount($ip, $sessionId) {
		return $this->find()
			->where(['or' => ['ip' => $ip, 'session_id' => $sessionId]])
			->count();
	}

	/**
	 * @return int
	 */
	public function cleanup() {
		return $this->deleteAll(['or' => ['created <' => new Time('-1 day'), 'used' => true]]);
	}

	/**
	 * @param string $ip
	 * @param string $sessionId
	 *
	 * @return int
	 */
	public function cleanupByIpOrSessionId($ip, $sessionId) {
		$count = $this->getCount($ip, $sessionId);
		if ($count < 1000) {
			return 0;
		}

		return $this->deleteAll(['or' => ['ip' => $ip, 'session_id' => $sessionId]]);
	}

	public function getLevel($sessionId, $ip) {

	}

	/**
	 * @param \Captcha\Model\Entity\Captcha $captcha
	 *
	 * @return bool|\Captcha\Model\Entity\Captcha
	 */
	public function prepare($captcha) {
		if ($captcha->result === null || $captcha->result === '') {
			$generated = $this->_getEngine()->generate();
			$captcha = $this->patchEntity($captcha, $generated);
		}
		return $this->save($captcha);
	}

	/**
	 * @param \Captcha\Model\Entity\Captcha $captcha
	 *
	 * @return bool
	 */
	public function markUsed($captcha) {
		$captcha->used = new Time();
		return (bool)$this->save($captcha);
	}

	/**
	 * @return \Captcha\Engine\EngineInterface
	 */
	private function _getEngine() {
		$config = (array)Configure::read('Captcha') + $this->_defaultConfig;
		$engine = $config['engine'];
		return new $engine($config);
	}

}
