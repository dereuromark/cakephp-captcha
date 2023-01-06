<?php

namespace Captcha\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class CommentsFixture extends TestFixture {

	/**
	 * Fields
	 *
	 * @var array
	 */
	public array $fields = [
		'id' => ['type' => 'integer'],
		'comment' => ['type' => 'string', 'null' => false, 'default' => '', 'length' => 100, 'comment' => ''],
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
	];

	/**
	 * Records
	 *
	 * @var array
	 */
	public array $records = [
		[
			'comment' => 'Abc',
		],
		[
			'comment' => 'Def',
		],
	];

}
