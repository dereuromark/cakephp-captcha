<?php

use Migrations\BaseMigration;

class Binary extends BaseMigration {

	/**
	 * @return void
	 */
	public function change() {
		$table = $this->table('captchas');

		$type = $this->getAdapter()->getAdapterType();
		$table->changeColumn('image', 'blob', [
			'default' => null,
			'limit' => ($type !== 'pgsql') ? 6000 : null,
			'null' => true,
		]);
		$table->update();
	}

}
