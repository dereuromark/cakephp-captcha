<?php
namespace Captcha\Database\Type;

use Cake\Database\Driver;
use Cake\Database\Type\BinaryType;

/**
 * Binary type converter.
 *
 * Use to convert binary values including UUIDs between PHP and the database types.
 */
class ImageType extends BinaryType {

	/**
	 * Convert binary into resource handles
	 *
	 * @param null|string|resource $value The value to convert.
	 * @param \Cake\Database\Driver $driver The driver instance to convert with.
	 * @return resource|null
	 * @throws \Cake\Core\Exception\Exception
	 */
	public function toPHP($value, Driver $driver) {
		// Do not convert UUIDs into a resource
		if (is_string($value)) {
			return $value;
		}

		return parent::toPHP($value, $driver);
	}

}
