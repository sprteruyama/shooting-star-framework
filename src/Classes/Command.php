<?php

namespace ShootingStar;

class Command extends Base
{
    public $args = [];
    public $helps = [];

    public function __construct()
    {
        parent::__construct();
    }

    public function main()
    {
        $this->help();
    }

    public function out($message, $noLF = false)
    {
        echo $message . ($noLF ? '' : "\n");
    }

    public function help()
    {
        $selfMethods = get_class_methods('ShootingStar\\Command');
        $methods = get_class_methods(get_class($this));
        $availableMethods = [];
        foreach ($methods as $method) {
            if (!is_callable([$this, $method])) {
                continue;
            }
            if (array_search($method, $selfMethods) !== false) {
                continue;
            }
            if (strpos($method, '_') === 0) {
                continue;
            }
            $availableMethods[$method] = isset($this->helps[$method]) ? $this->helps[$method] : '<No Information>';
        }
        if (empty($availableMethods)) {
            $this->out('No commands is available.');
        } else {
            $this->out('Following commands are available.');
            foreach ($availableMethods as $method => $message) {
                $method = $this::pascalToSnake($method);
                $this->out("{$method}: {$message}");
            }
        }
    }

    public function getInput($count = 0)
    {
        $result = '';
        while (!feof(STDIN)) {
            $result .= fgets(STDIN);
            if ($count && strlen($result) >= $count) {
                break;
            }
        }
        return $result;
    }
}
