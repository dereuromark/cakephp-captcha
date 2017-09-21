<?php

use Phinx\Migration\AbstractMigration;

class CaptchaInit extends AbstractMigration {

	/**
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
