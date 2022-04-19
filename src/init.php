<?php

use ShootingStar\Config;
use ShootingStar\EnvFile;

$dir = realpath(__DIR__);
while (!preg_match('/vendor$/', $dir)) {
    $dir = dirname($dir);
}
$rootDir = dirname($dir);
define('ROOT_DIR', $rootDir);
define('CORE_DIR', __DIR__);
define('APP_DIR', $rootDir . '/app');
define('CLI_DIR', APP_DIR . '/Cli');
define('CONFIG_BASE_DIR', APP_DIR . '/Config');
define('LIBS_DIR', APP_DIR . '/Libs');
define('CONTROLLER_DIR', APP_DIR . '/Controllers');
define('MODEL_DIR', APP_DIR . '/Models');
define('VIEW_DIR', APP_DIR . '/Views');
define('PUBLIC_DIR', APP_DIR . '/Public');
define('LOG_DIR', ROOT_DIR . '/logs');
define('TMP_DIR', ROOT_DIR . '/tmp');
define('SHARE_DIR', ROOT_DIR . '/share');
require_once CORE_DIR . '/Classes/EnvFile.php';
EnvFile::decodeEnv();
require_once CORE_DIR . '/Classes/Log.php';
require_once CORE_DIR . '/Classes/Config.php';
ini_set('display_errors', Config::get('debug') ? 1 : 0);
ini_set('error_reporting', Config::get('error.reporting', Config::get('debug') ? E_ALL : E_ALL & ~E_DEPRECATED & E_STRICT & E_NOTICE));
ini_set('date.timezone', Config::get('timezone'));
require_once CORE_DIR . '/Classes/ClassLoader.php';
require_once CORE_DIR . '/Classes/Base.php';
require_once CORE_DIR . '/Classes/Model.php';
require_once CORE_DIR . '/Classes/Validator.php';
require_once CORE_DIR . '/Classes/Router.php';
