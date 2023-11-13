<?php

use Phinx\Migration\AbstractMigration;

class Binary extends AbstractMigration {

	/**
	 * @return void
	 */
	public function change() {
		$table = $this->table('captchas');
		$table->changeColumn('image', 'blob', [
			'default' => null,
			'limit' => 60000,
			'null' => true,
		]);
		$table->update();
	}

}
