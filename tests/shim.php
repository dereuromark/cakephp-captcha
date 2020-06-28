<?php

use Cake\Core\Plugin;

if (!function_exists('imagejpeg')) {
	/**
	 * @param mixed $image
	 * @return void
	 */
	function imagejpeg($image) {
	}
}
if (!function_exists('imagepng')) {
	/**
	 * @param mixed $image
	 * @return void
	 */
	function imagepng($image) {
	}
}
if (!function_exists('imagedestroy')) {
	/**
	 * @param mixed $image
	 * @return void
	 */
	function imagedestroy($image) {
	}
}

require_once Plugin::path('Captcha') . 'vendor/' . 'mathpublisher.php';
