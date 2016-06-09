<?php

use Phinx\Migration\AbstractMigration;

class CaptchaInit extends AbstractMigration {
	/**
	 * Change Method.
	 *
	 * Write your reversible migrations using this method.
	 *
	 * More information on writing migrations is available here:
	 * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
	 *
	 * The following commands can be used in this method and Phinx will
	 * automatically reverse them when rolling back:
	 *
	 *    createTable
	 *    renameTable
	 *    addColumn
	 *    renameColumn
	 *    addIndex
	 *    addForeignKey
	 *
	 * Remember to call "create()" or "update()" and NOT "save()" when working
	 * with the Table class.
	 *
	 * @return void
	 */
	public function change() {
		$table = $this->table('captchas');
		$table->addColumn('session_id', 'string', [
			'default' => null,
			'limit' => 255,
			'null' => false,
		]);
		$table->addColumn('ip', 'string', [
			'default' => null,
			'limit' => 255,
			'null' => false,
		]);
		$table->addColumn('image', 'binary', [
			'default' => null,
			'null' => true,
		]);
		$table->addColumn('result', 'string', [
			'default' => null,
			'limit' => 255,
			'null' => true,
		]);
		$table->addColumn('created', 'datetime', [
			'default' => null,
			'null' => true,
		]);
		$table->addColumn('used', 'datetime', [
			'default' => null,
			'null' => true,
		]);
		$table->create();
	}

}
