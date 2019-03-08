<?php

use ShootingStar\Base;
use ShootingStar\Config;

require_once CORE_DIR . '/Classes/Command.php';

if (isset($argv[1]) && $argv[1] == 'init') {
    $htaccess = file_get_contents(PUBLIC_DIR . '/.htaccess');
    $urlRoot = Config::get('url.root', '/');
    $htaccess = preg_replace('/RewriteBase\s+.*?\n/s', 'RewriteBase ' . $urlRoot . "\n", $htaccess);
    file_put_contents(PUBLIC_DIR . '/.htaccess', $htaccess);
    echo "RuleBase: {$urlRoot}\n";
    echo "modified .htaccess.\n";
    $writable = [TMP_DIR, LOG_DIR, SHARE_DIR, SHARE_DIR . '/session'];
    foreach (Config::get('url.writable') as $dir) {
        $writable[] = PUBLIC_DIR . $dir;
    };
    foreach ($writable as $dir) {
        echo "{$dir} is now writable.\n";
        chmod($dir, 0777);
    }
    return;
}
if (count($argv) < 2) {
    $files = scandir(CLI_DIR);
    $availableCommands = [];
    foreach ($files as $filename) {
        if (is_dir($filename)) {
            continue;
        }
        if ($filename == 'BaseCommand.php') {
            continue;
        }
        if (preg_match('/^(.+)Command\.php$/', $filename, $matches)) {
            $availableCommands[] = Base::pascalToSnake($matches[1]);
        }
    }
    if (empty($availableCommands)) {
        echo "No Commands\n";
    } else {
        echo "Following commands are available.\ninit\n";
        foreach ($availableCommands as $command) {
            echo "{$command}\n";
        }
    }
    exit();
}
if (!isset($argv[2])) {
    $argv[2] = 'main';
}
$className = Base::snakeToPascal($argv[1]) . 'Command';
/** @noinspection PhpIncludeInspection */
require_once CLI_DIR . "/BaseCommand.php";
/** @noinspection PhpIncludeInspection */
require_once CLI_DIR . "/{$className}.php";
/** @var \ShootingStar\Command $command */
$command = new $className;
$method = Base::snakeToPascal($argv[2]);
$command->args = array_slice($argv, 3, count($argv) - 3);
$primitiveMethods = get_class_methods('ShootingStar\\Command');
if (array_search($method, $primitiveMethods) !== false) {
    $method = 'main';
}
if (!is_callable([$command, $method])) {
    $method = 'main';
}
call_user_func_array([$command, $method], $command->args);
