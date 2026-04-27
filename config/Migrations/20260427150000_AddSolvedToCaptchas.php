<?php

use Migrations\BaseMigration;

class AddSolvedToCaptchas extends BaseMigration {

	/**
	 * @return void
	 */
	public function up() {
		$table = $this->table('captchas');
		$table->addColumn('solved', 'boolean', [
			'default' => null,
			'null' => true,
			'after' => 'used',
		]);
		$table->addIndex(['solved', 'created'], ['name' => 'idx_solved_created']);
		$table->update();
	}

	/**
	 * @return void
	 */
	public function down() {
		$table = $this->table('captchas');
		$table->removeIndex(['solved', 'created']);
		$table->removeColumn('solved');
		$table->update();
	}

}
