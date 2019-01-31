<?php

namespace ShootingStar;

class ClassLoader
{
    private static $loads = [];

    public static function loadClass($className, $baseDir)
    {
        if (isset(self::$loads[$className])) {
            return true;
        }
        if (strpos($className, '\\') !== false) {
            return false;
        }
        if (realpath($baseDir) == realpath(APP_DIR)) {
            return false;
        }
        $baseDir = realpath($baseDir);
        $classPath = $baseDir . '/' . $className . '.php';
        if (file_exists($classPath)) {
            $code = file_get_contents($classPath);
            if (preg_match("/{$className}\s*?extends\s*?([a-zA-Z0-9]+?)\s*?{?\s/is", $code, $matches)) {
                $className = $matches[1];
                self::loadClass($className, $baseDir);
            }
            /** @noinspection PhpIncludeInspection */
            require_once $classPath;
            self::$loads[$className] = true;
            return true;
        } else {
            return self::loadClass($className, dirname($baseDir));
        }
    }
}