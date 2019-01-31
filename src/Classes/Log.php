<?php

use ShootingStar\Config;

ini_set('log_errors', 1);
ini_set('error_log', LOG_DIR . '/error.log');

define('DEBUG_LOG_FILENAME', 'debug.log');

class Log
{
    public static function out($message, $file = DEBUG_LOG_FILENAME, $isDisplay = true, $isSetPublicWritable = true)
    {
        if ($file == DEBUG_LOG_FILENAME && Config::get('debug') && $isDisplay) {
            echo $message . "\n";
        }
        $message = '[' . date('Y-m-d H:i:s') . ']' . $message . "\n";
        $filename = LOG_DIR . "/{$file}";
        if ($isSetPublicWritable && !file_exists($filename)) {
            file_put_contents($filename, '');
            chmod($filename, 0777);
        }
        file_put_contents($filename, $message, FILE_APPEND);
    }

    public static function get($file = DEBUG_LOG_FILENAME)
    {
        return file_get_contents(LOG_DIR . "/{$file}");
    }

    public static function clear($file = DEBUG_LOG_FILENAME)
    {
        file_put_contents(LOG_DIR . "/{$file}", null);
    }
}