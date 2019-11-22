<?php

if (!defined('DS')) {
	define('DS', DIRECTORY_SEPARATOR);
}
if (!defined('WINDOWS')) {
	if (DS === '\\' || substr(PHP_OS, 0, 3) === 'WIN') {
		define('WINDOWS', true);
	} else {
		define('WINDOWS', false);
	}
}
// Path constants to a few helpful things.
define('ROOT', dirname(__DIR__) . DS);
define('CAKE_CORE_INCLUDE_PATH', ROOT . 'vendor' . DS . 'cakephp' . DS . 'cakephp');
define('CORE_PATH', ROOT . 'vendor' . DS . 'cakephp' . DS . 'cakephp' . DS);
define('CAKE', CORE_PATH . 'src' . DS);
define('TESTS', ROOT . 'tests' . DS);
define('TEST_ROOT', ROOT . 'tests' . DS . 'test_app' . DS);
define('APP_DIR', 'src');
define('APP', TEST_ROOT . APP_DIR . DS);
define('WEBROOT_DIR', 'webroot');
define('TMP', sys_get_temp_dir() . DS);
define('CONFIG', TESTS . 'config' . DS);
define('WWW_ROOT', TEST_ROOT . WEBROOT_DIR . DS);
define('CACHE', TMP);
define('LOGS', TMP);

require ROOT . '/vendor/autoload.php';
require CORE_PATH . 'config/bootstrap.php';

Cake\Core\Configure::write('App', [
	'namespace' => 'App',
]);

Cake\Core\Configure::write('debug', true);

$cache = [
	'default' => [
		'engine' => 'File',
		'path' => CACHE,
	],
	'_cake_core_' => [
		'className' => 'File',
		'prefix' => 'crud_myapp_cake_core_',
		'path' => CACHE . 'persistent/',
		'serialize' => true,
		'duration' => '+10 seconds',
	],
	'_cake_model_' => [
		'className' => 'File',
		'prefix' => 'crud_my_app_cake_model_',
		'path' => CACHE . 'models/',
		'serialize' => 'File',
		'duration' => '+10 seconds',
	],
];

Cake\Cache\Cache::setConfig($cache);

if (file_exists(CONFIG . 'app_local.php')) {
	\Cake\Core\Configure::load('app_local', 'default');
}

\Cake\Core\Plugin::load('Captcha', ['path' => ROOT . DS, 'autoload' => true, 'bootstrap' => true, 'routes' => true]);

\Cake\Routing\DispatcherFactory::add('Routing');
\Cake\Routing\DispatcherFactory::add('ControllerFactory');

// Ensure default test connection is defined
if (!getenv('db_class')) {
	putenv('db_class=Cake\Database\Driver\Sqlite');
	putenv('db_dsn=sqlite::memory:');
}

Cake\Datasource\ConnectionManager::setConfig('test', [
	'className' => 'Cake\Database\Connection',
	'driver' => getenv('db_class'),
	'dsn' => getenv('db_dsn'),
	'database' => getenv('db_database'),
	'username' => getenv('db_username'),
	'password' => getenv('db_password'),
	'timezone' => 'UTC',
	'quoteIdentifiers' => true,
	'cacheMetadata' => true,
]);
