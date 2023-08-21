<?php

namespace Captcha\Model\Table;

use Cake\Core\Configure;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\I18n\FrozenTime;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Captcha\Model\Rule\MaxRule;

/**
 * @property \Cake\ORM\Association\BelongsTo $Sessions
 * @method \Captcha\Model\Entity\Captcha get($primaryKey, $options = [])
 * @method \Captcha\Model\Entity\Captcha newEntity($data = null, array $options = [])
 * @method array<\Captcha\Model\Entity\Captcha> newEntities(array $data, array $options = [])
 * @method \Captcha\Model\Entity\Captcha|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Captcha\Model\Entity\Captcha patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\Captcha\Model\Entity\Captcha> patchEntities($entities, array $data, array $options = [])
 * @method \Captcha\Model\Entity\Captcha findOrCreate($search, callable $callback = null, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class CaptchasTable extends Table {

	/**
	 * @param \Cake\Database\Schema\TableSchemaInterface $schema
	 *
	 * @return \Cake\Database\Schema\TableSchemaInterface
	 */
	protected function _initializeSchema(TableSchemaInterface $schema): TableSchemaInterface {
		$schema->setColumnType('image', 'image');

		return $schema;
	}

	/**
	 * Initialize method
	 *
	 * @param array $config The configuration for the Table.
	 * @return void
	 */
	public function initialize(array $config): void {
		parent::initialize($config);

		$this->setTable('captchas');
		$this->setDisplayField('id');
		$this->setPrimaryKey('id');

		$this->addBehavior('Timestamp');
	}

	/**
	 * Default validation rules.
	 *
	 * @param \Cake\Validation\Validator $validator Validator instance.
	 * @return \Cake\Validation\Validator
	 */
	public function validationDefault(Validator $validator): Validator {
		$validator
			->integer('id')
			->allowEmptyString('id', 'create');

		$validator
			->requirePresence('ip', 'create')
			->notEmptyString('ip');

		$validator
			->requirePresence('session_id', 'create')
			->notEmptyString('session_id');

		$validator
			->allowEmptyString('result');

		$validator
			->dateTime('used')
			->allowEmptyDateTime('used');

		return $validator;
	}

	/**
	 * Returns a rules checker object that will be used for validating
	 * application integrity.
	 *
	 * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
	 * @return \Cake\ORM\RulesChecker
	 */
	public function buildRules(RulesChecker $rules): RulesChecker {
		$rules->addCreate(new MaxRule());

		return $rules;
	}

	/**
	 * @param string $sessionId
	 * @param string $ip
	 *
	 * @throws \BadMethodCallException
	 *
	 * @return int|null
	 */
	public function touch($sessionId, $ip) {
		$probability = (int)Configure::read('Captcha.cleanupProbability') ?: 10;
		$this->cleanup($probability);

		$captcha = $this->newEntity(
			[
				'session_id' => $sessionId,
				'ip' => $ip,
			],
			[
				'validate' => false,
			],
		);
		if ($this->save($captcha)) {
			return $captcha->id;
		}

		return null;
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
	 * @param int $probability
	 *
	 * @return int
	 */
	public function cleanup(int $probability = 100): int {
		if (!$probability) {
			return 0;
		}
		$randomNumber = random_int(1, 100);
		if ($probability < $randomNumber) {
			return 0;
		}

		/** @var int $maxTime */
		$maxTime = Configure::read('Captcha.maxTime') ?? DAY;

		return $this->deleteAll(['or' => ['created <' => new FrozenTime((string)(time() - (int)$maxTime)), 'used IS NOT' => null]]);
	}

	/**
	 * @param string $ip
	 *
	 * @return int
	 */
	public function reset(string $ip): int {
		return $this->deleteAll(['ip' => $ip]);
	}

	/**
	 * @param \Captcha\Model\Entity\Captcha $captcha
	 *
	 * @return bool
	 */
	public function markUsed($captcha): bool {
		$captcha->used = new FrozenTime();

		return (bool)$this->save($captcha);
	}

}
