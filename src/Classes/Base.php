<?php

namespace ShootingStar;

class Base
{
    public $models = [];
    public $libs = [];
    public static $commonDb = null;
    public static $commonSlaveDB = null;

    public function __construct()
    {
        foreach ($this->models as $model) {
            $this->loadModel($model);
        }
        foreach ($this->libs as $lib) {
            $this->loadLibrary($lib);
        }
    }

    public function isCli()
    {
        return !isset($_SERVER['HTTP_HOST']);
    }

    public function loadModel($name)
    {
        $className = "{$name}Model";
        $tableName = self::pascalToSnake(str_replace('Model', '', $className));
        if (ClassLoader::loadClass($className, MODEL_DIR)) {
            $this->$className = new $className();
        } else {
            if (ClassLoader::loadClass('BaseModel', MODEL_DIR)) {
                $baseClassName = 'BaseModel';
                $this->$className = new $baseClassName();
            } else {
                $baseClassName = 'Model';
                $this->$className = new $baseClassName();
            }
        }
        if ($this->$className) {
            /** @var Model $model */
            $model = $this->$className;
            if ($model->table === null) {
                $model->table = $tableName;
            }
            if (!self::$commonDb) {
                self::$commonDb = $model->getConnection('select');
                self::$commonSlaveDB = $model->slaveDB;
            }
            $model->db = self::$commonDb;
            $model->slaveDB = self::$commonSlaveDB;
        }
    }

    public function loadLibrary($name)
    {
        if (!ClassLoader::loadClass($name, LIBS_DIR)) {
            ClassLoader::loadClass($name, CORE_DIR . '/Classes/Libs');
        }
        $this->$name = new $name();
    }

    public static function snakeToPascal($text)
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $text)));
    }

    public static function pascalToSnake($text)
    {
        return ltrim(strtolower(preg_replace('/([A-Z])/', '_\1', $text)), '_');
    }

    public static function decodeArrayVariable($vars)
    {
        foreach ($vars as $name => $value) {
            $originalName = $name;
            $items = explode(',', $name);
            if (count($items) > 1) {
                $name = $items[0];
                self::decodeArrayVariableSub($vars[$name], $value, $items);
                unset($vars[$originalName]);
            }
        }
        return $vars;
    }

    public static function decodeArrayVariableSub(&$vars, $value, $items)
    {
        $items = array_slice($items, 1, count($items) - 1);
        if (count($items) > 0) {
            $index = $items[0];
            if (!isset($vars[$index])) {
                $vars[$index] = [];
            }
            if (count($items) > 1) {
                self::decodeArrayVariableSub($vars[$index], $value, $items);
            } else {
                $vars[$index] = $value;
            }
        }
    }

    public static function encodeArrayVariable(&$outVars, $vars, $name)
    {
        if (is_array($vars)) {
            foreach ($vars as $key => $value) {
                $outVars[$name . ",$key"] = self::encodeArrayVariable($outVars, $value, $name . ",$key");
            }
            return null;
        } else {
            $outVars[$name] = $vars;
            return $vars;
        }
    }

    public static function getValueByDot($vars, $name)
    {
        $items = explode(',', $name);
        if (count($items) > 1) {
            $name = $items[0];
            return self::getValueByDotSub($vars[$name], $items);
        } else {
            return isset($vars[$name]) ? $vars[$name] : null;
        }
    }

    public static function getValueByDotSub($vars, $items)
    {
        $items = array_slice($items, 1, count($items) - 1);
        if (count($items) > 0) {
            $index = $items[0];
            if (isset($vars[$index])) {
                return self::getValueByDotSub($vars[$index], $items);
            } else {
                return null;
            }
        } else {
            return $vars;
        }
    }

    public static function cli($command)
    {
        return exec('php ' . ROOT_DIR . '/cli.php ' . $command);
    }
}
