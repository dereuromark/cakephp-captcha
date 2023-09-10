<?php

use Cake\Database\TypeFactory;

TypeFactory::map('image', 'Captcha\Database\Type\ImageType');

if (!defined('SECOND')) {
	define('SECOND', 1);
	define('MINUTE', 60);
	define('HOUR', 3600);
	define('DAY', 86400);
	define('WEEK', 604800);
	define('MONTH', 2592000);
	define('YEAR', 31536000);
}
