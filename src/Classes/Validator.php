<?php

namespace ShootingStar;

use Exception;

define('VALIDATOR_NOT_EXISTS', '$_self===null');
define('VALIDATOR_NOT_EMPTY', '/^$/');
define('VALIDATOR_NOT_NUMERIC', '/[^0-9]+/');

class Validator extends Base
{
    private $conditionAllowFunction = [
        'return',
        'if',
        'then',
        'else',
        'while',
        'round',
        'md5',
        'sha1',
        'date',
        'strtotime',
        'null',
    ];
    public $errors = [];

    public function validate($rules, $vars, $isAll = false)
    {
        if ($isAll) {
            foreach ($rules as $key => $value) {
                if (!isset($vars[$key])) {
                    $vars[$key] = null;
                }
            }
        }
        $errors = [];
        foreach ($vars as $name => $value) {
            foreach ($rules as $key => $items) {
                $values = [$name => $value];
                $isTargetValue = $key == $name || strpos($key, $name . '_') === 0;
                if (strpos($key, '[]') !== false) {
                    $key = str_replace('[]', '', $key);
                    $isTargetValue = $key == $name || strpos($name, $key . ',') === 0;
                    if ($isTargetValue) {
                        $values = [];
                        self::encodeArrayVariable($values, $value, $name);
                    }
                }
                foreach ($values as $name => $value) {
                    foreach ($items as $item) {
                        $isHit = false;
                        if (strpos($key, '*') === 0 || $isTargetValue) {
                            if (strpos($item[0], '/') === 0) {
                                if (is_array($value)) {
                                    $tempValue = implode(',', $value);
                                } else {
                                    $tempValue = $value;
                                }
                                $isHit = preg_match($item[0], $tempValue);
                            } else if (strpos($item[0], '!/') === 0) {
                                if (is_array($value)) {
                                    $tempValue = implode(',', $value);
                                } else {
                                    $tempValue = $value;
                                }
                                $regexp = substr($item[0], 1);
                                $isHit = !preg_match($regexp, $tempValue);
                            } else {
                                try {
                                    $isHit = $this->safeEval($item[0], array_merge(['_self' => $value], $vars));
                                } catch (Exception $e) {
                                    $errors['system'] = ['System Error.'];
                                }
                            }
                            if ($isHit) {
                                if (!isset($errors[$name])) {
                                    $errors[$name] = [];
                                }
                                $errors[$name][] = isset($item[1]) ? $item[1] : 'ERROR';
                                if (isset($item[2]) && $item[2]) {
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $errors;
    }

    public function safeEval($code, $vars, $allowFunctions = [], $isRaw = false)
    {
        $allowFunctions = array_merge($allowFunctions, $this->conditionAllowFunction);
        if (!$isRaw) {
            $code = "return ({$code});";
        }
        $tempCode = preg_replace('/".*?"/', '', $code);
        $tempCode = preg_replace('/\'.*?\'/', '', $tempCode);
        $tempCode = preg_replace('/".*?"/', '', $tempCode);
        $tempCode = preg_replace('/@"+?"/', '', $tempCode);
        $tempCode = str_replace(['\\n', '\\r', '\\t'], "\n", $tempCode);
        if (preg_match_all('/(\$?)([A-Za-z][a-zA-Z_0-9]+)(|\()/', $tempCode, $matches)) {
            foreach ($matches[2] as $key => $value) {
                if ($matches[1][$key] == '$') {
                    if (preg_match('/\\$' . $value . '=[$a-zA-Z0-9]/', $tempCode)) {
                        continue;
                    }
                    if (!array_key_exists($value, $vars)) {
                        $message = "condition error(\${$value} is not defined):\n{$code}";
                        /** @noinspection PhpUnhandledExceptionInspection */
                        throw new Exception($message);
                    }
                } else {
                    if ($matches[1][$key] == '(' && array_search($value, $allowFunctions) === false) {
                        $message = "condition error(function {$value} is not allowed):\n{$code}";
                        /** @noinspection PhpUnhandledExceptionInspection */
                        throw new Exception($message);
                    }
                }
            }
        }
        extract($vars);
        return eval($code);
    }


}
