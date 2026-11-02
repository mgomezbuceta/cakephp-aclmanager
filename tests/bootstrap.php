<?php
declare(strict_types=1);

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Database\Connection;
use Cake\Database\Driver\Sqlite;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\Fixture\SchemaLoader;

define('ROOT', dirname(__DIR__));
define('TESTS', ROOT . DS . 'tests' . DS);
define('APP', TESTS . 'TestApp' . DS);
define('CONFIG', TESTS . 'config' . DS);
define('WWW_ROOT', TESTS . 'webroot' . DS);
define('TMP', ROOT . DS . 'tmp' . DS);
define('CACHE', TMP . 'cache' . DS);
define('LOGS', TMP . 'logs' . DS);
define('CAKE_CORE_INCLUDE_PATH', ROOT . DS . 'vendor' . DS . 'cakephp' . DS . 'cakephp');
define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
define('CAKE', CORE_PATH . 'src' . DS);

foreach ([CACHE, LOGS] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
}

require ROOT . DS . 'vendor' . DS . 'autoload.php';
require CORE_PATH . 'config' . DS . 'bootstrap.php';
require_once CAKE . 'Core' . DS . 'functions_global.php';
require_once CAKE . 'I18n' . DS . 'functions_global.php';

Configure::write('debug', true);
Configure::write('App', [
    'namespace' => 'AclManager\Test\TestApp',
    'encoding' => 'UTF-8',
    'base' => false,
    'baseUrl' => false,
    'dir' => 'src',
    'webroot' => 'webroot',
    'wwwRoot' => WWW_ROOT,
    'fullBaseUrl' => 'http://localhost',
    'imageBaseUrl' => 'img/',
    'jsBaseUrl' => 'js/',
    'cssBaseUrl' => 'css/',
    'paths' => [
        'plugins' => [],
        'templates' => [TESTS . 'templates' . DS],
        'locales' => [TESTS . 'locales' . DS],
    ],
]);

foreach (['default', '_cake_core_', '_cake_model_', '_cake_routes_', '_cake_translations_'] as $name) {
    Cache::setConfig($name, ['engine' => 'Array']);
}

ConnectionManager::setConfig('test', [
    'className' => Connection::class,
    'driver' => Sqlite::class,
    'database' => ':memory:',
    'quoteIdentifiers' => false,
]);
ConnectionManager::alias('test', 'default');

(new SchemaLoader())->loadInternalFile(TESTS . 'schema.php', 'test');
