<?php

namespace ShootingStar;

class Config
{

    private static $config = [];

    public static function get($key, $default = null, $config = null)
    {
        if (!$config) {
            $config = self::$config;
        }
        if (isset($config[$key])) {
            return $config[$key];
        } else {
            $keys = explode('.', $key);
            if (count($keys) > 1) {
                $targetKey = $keys[0];
                if (isset($config[$targetKey])) {
                    return self::get(str_replace($targetKey . '.', '', $key), $default, $config[$targetKey]);
                } else {
                    return $default;
                }
            } else {
                return $default;
            }
        }
    }

    public static function set($key, $value)
    {
        self::setValueWithDotKey($key, $value, self::$config);
    }

    public static function sets($values = [])
    {
        foreach ($values as $key => $value) {
            self::setValueWithDotKey($key, $value, self::$config);
        }
    }

    public static function setValueWithDotKey($key, $value, &$config)
    {
        $keys = explode('.', $key);
        $firstKey = array_shift($keys);
        if (count($keys) == 0) {
            if (isset($config[$firstKey])) {
                $config[$firstKey] = array_merge($config[$firstKey], $value);
            } else {
                $config[$firstKey] = $value;
            }
        } else {
            if (!isset($config[$firstKey])) {
                $config[$firstKey] = [];
            }
            self::setValueWithDotKey(implode('.', $keys), $value, $config[$firstKey]);
        }
    }
}

require_once CONFIG_BASE_DIR . '/env.php';
if (!defined('ENV')) {
    define('ENV', 0);
}
$envDir = [
    '0' => 'local',
    '1' => 'development',
    '2' => 'staging',
    '3' => 'production',
];
if (!isset($envDir[ENV])) {
    die('環境指定ENVが正しくありません。');
}
loadConfig(CONFIG_BASE_DIR . '/common');
$configDir = CONFIG_BASE_DIR . '/' . $envDir[ENV];
define('CONFIG_DIR', $configDir);
loadConfig($configDir);

function loadConfig($configDir)
{
    $configs = scandir($configDir);
    foreach ($configs as $config) {
        $config = $configDir . '/' . $config;
        if (preg_match('/\.php$/i', $config)) {
            /** @noinspection PhpIncludeInspection */
            require_once($config);
        }
    };
}

$urlRoot = Config::get('url.root');
if ($urlRoot == '/') {
    $urlRoot = '';
}
if (isset($_SERVER['HTTP_HOST'])) {
    Config::set('url.base', (empty($_SERVER["HTTPS"]) ? "http://" : "https://") . $_SERVER["HTTP_HOST"] . $urlRoot);
} else {
    Config::set('url.base', Config::get('url.url'));
}

