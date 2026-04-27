<?php

use Cake\Utility\Text;
use Migrations\BaseMigration;

class Uuid extends BaseMigration {

	/**
	 * @return void
	 */
	public function up() {
		$table = $this->table('captchas');
		$table->addColumn('uuid', 'string', [
			'default' => null,
			'limit' => 36,
			'null' => true,
			'after' => 'id',
		]);
		$table->update();

		$rows = $this->fetchAll('SELECT id FROM captchas');
		foreach ($rows as $row) {
			$this->execute(sprintf(
				"UPDATE captchas SET uuid = '%s' WHERE id = %d",
				Text::uuid(),
				(int)$row['id'],
			));
		}

		$table = $this->table('captchas');
		$table->changeColumn('uuid', 'string', [
			'default' => null,
			'limit' => 36,
			'null' => false,
		]);
		$table->addIndex(['uuid'], ['unique' => true]);
		$table->update();
	}

	/**
	 * @return void
	 */
	public function down() {
		$table = $this->table('captchas');
		$table->removeIndex(['uuid']);
		$table->removeColumn('uuid');
		$table->update();
	}

}
