<?php

namespace Captcha\Model\Entity;

use Cake\ORM\Entity;

/**
 * Captcha Entity.
 *
 * @property int $id
 * @property string $session_id
 * @property string $ip
 * @property string $result
 * @property string $image
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $used
 */
class Captcha extends Entity {

	/**
	 * Fields that can be mass assigned using newEntity() or patchEntity().
	 *
	 * Note that when '*' is set to true, this allows all unspecified fields to
	 * be mass assigned. For security purposes, it is advised to set '*' to false
	 * (or remove it), and explicitly make individual fields accessible as needed.
	 *
	 * @var array<string, bool>
	 */
	protected array $_accessible = [
		'*' => true,
		'id' => false,
	];

}
