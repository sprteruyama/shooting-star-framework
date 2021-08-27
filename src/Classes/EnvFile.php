<?php

namespace ShootingStar;

class EnvFile
{
    public static $envs = [];

    public static function decodeEnv()
    {
        $path = ROOT_DIR . '/.env';
        if (file_exists($path)) {
            $content = file_get_contents($path);
            $content = str_replace("\r\n", "\n", $content);
            $lines = explode("\n", $content);
            foreach ($lines as $line) {
                if (preg_match('/^\s+#/', $content)) {
                    continue;
                }
                $items = explode('=', $line);
                $value = '';
                if (count($items) > 1) {
                    for ($i = 1; $i < count($items); $i++) {
                        $value .= $items[$i];
                    }
                    self::$envs[trim($items[0])] = trim($value);
                }
            }
        }
    }

    public static function get($key, $default = null)
    {
        return isset(self::$envs[$key]) ? self::$envs[$key] : $default;
    }
}