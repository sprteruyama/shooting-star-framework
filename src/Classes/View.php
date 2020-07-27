<?php

namespace ShootingStar;

class View extends Base
{
    public static $LAYOUT_DIR = VIEW_DIR . '/Layouts';
    public static $CONTENTS_DIR = VIEW_DIR . '/Contents';
    public $layout = 'default';
    public $vars = [];
    public $errorBlockTemplate = '<span class="error">%s</span>';
    private $isRendering = false;
    private $currentViewDir;

    public function get($key, $default = null)
    {
        return isset($this->vars[$key]) ? $this->vars[$key] : $default;
    }

    public function set($key, $value)
    {
        $this->vars[$key] = $value;
    }

    public function sets($vars)
    {
        $this->vars = array_merge($this->vars, $vars);
    }

    public function url($path, $isFull = false)
    {
        echo Router::url($path, $isFull);
    }

    public function render($template, $layout = null)
    {
        if ($this->isRendering) {
            return;
        }
        $this->isRendering = true;
        if ($layout === null) {
            $layout = $this->layout;
        }
        $viewDir = dirname(self::$CONTENTS_DIR . '/' . $template);
        foreach ($this->vars as $key => $var) {
            if (strpos($key, '_') === 0) {
                continue;
            }
            $this->vars[$key] = $this->escape($var);
        }
        extract($this->vars);
        ob_start();
        $this->currentViewDir = $viewDir;
        $this->block($template);
        /** @noinspection PhpUnusedLocalVariableInspection */
        $contents = ob_get_clean();
        /** @noinspection PhpIncludeInspection */
        include(self::$LAYOUT_DIR . '/' . $layout . '.php');
        $this->isRendering = false;
    }

    public function block($template, $vars = [])
    {
        extract($this->vars);
        extract($vars);
        if (strpos($template, '/') === 0) {
            $viewDir = dirname(self::$CONTENTS_DIR . $template);
        } else {
            $viewDir = dirname($this->currentViewDir . '/' . $template);
        }
        $templateFilename = $viewDir . '/' . pathinfo($template, PATHINFO_FILENAME) . '.php';
        if (!file_exists($templateFilename)) {
            self::out500();
            exit();
        }
        $currentViewDir = $this->currentViewDir;
        $this->currentViewDir = $viewDir;
        /** @noinspection PhpIncludeInspection */
        include($templateFilename);
        $this->currentViewDir = $currentViewDir;
    }

    public function escape($values)
    {
        if (is_array($values)) {
            foreach ($values as $key => $value) {
                $values[$key] = $this->escape($value);
            }
            return $values;
        } else {
            return htmlspecialchars($values, ENT_QUOTES);
        }
    }

    public function outJson($data)
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }

    private function outHtmlTag($tag, $options, $innerText = null)
    {
        $html = '';
        $html .= "<{$tag} ";
        foreach ($options as $key => $value) {
            if ($value !== false) {
                $html .= "{$key}=\"{$value}\" ";
            } else {
                $html .= "{$key} ";
            }
        }
        $html .= '>';
        if ($innerText !== null) {
            echo $innerText;
        }
        if ($tag != 'input') {
            $html .= "</{$tag}>";
        }
        return $html;
    }

    private function outFormTag($tag, $name, $options, $innerText = null)
    {
        $value = self::getValueByDot($this->vars, $name);
        if ($innerText === true) {
            $innerText = $value;
        }
        if (!isset($options['name'])) {
            $options['name'] = $name;
        }
        if (!$innerText && !isset($options['value']) && $value !== null) {
            $options['value'] = $value;
        }
        return $this->outHtmlTag($tag, $options, $innerText);
    }

    public function startForm($action = '', $method = 'POST')
    {
        $token = Session::getInstance()->token();
        $html = "<form action='$action' method='$method'>";
        $html .= "<input name='__token__' type='hidden' value='$token'>";
        return $html;
    }

    public function endForm()
    {
        return '</form>';
    }

    public function input($name, $options = [], $type = 'text')
    {
        if (!isset($options['type'])) {
            $options['type'] = $type;
        }
        return $this->outFormTag('input', $name, $options);
    }

    public function password($name, $options = [])
    {
        return $this->input($name, $options, 'password');
    }

    public function radio($name, $index, $label, $options = [])
    {
        if (!isset($options['name'])) {
            $options['name'] = $name;
        }
        if (!isset($options['type'])) {
            $options['type'] = 'radio';
        }
        if (!isset($options['value'])) {
            $options['value'] = $index;
        }
        $value = self::getValueByDot($this->vars, $name);
        if ($value !== null && $index == $value) {
            $options['checked'] = 'checked';
        }
        return $this->outHtmlTag('input', $options, $label);
    }

    public function checkbox($name, $index, $label, $options = [])
    {
        if (!isset($options['name'])) {
            $options['name'] = $name . '[]';
        }
        if (!isset($options['type'])) {
            $options['type'] = 'checkbox';
        }
        if (!isset($options['value'])) {
            $options['value'] = $index;
        }
        $value = self::getValueByDot($this->vars, $name);
        if ($value !== null && is_array($value) && array_search($index, $value) !== false) {
            $options['checked'] = 'checked';
        }
        return $this->outHtmlTag('input', $options, $label);
    }

    public function file($name, $options = [])
    {
        return $this->input($name, $options, 'file');
    }

    public function hidden($name, $options = [])
    {
        return $this->input($name, $options, 'hidden');
    }

    public function textarea($name, $options = [])
    {
        return $this->outFormTag('textarea', $name, $options, true);
    }

    public function select($name, $labels, $items = [], $options = [])
    {
        $isMultiple = isset($items['multiple']);
        if (!isset($items['name'])) {
            $items['name'] = $name . ($isMultiple ? '[]' : '');
        }
        $value = self::getValueByDot($this->vars, $name);
        $innerHtml = '';
        foreach ($labels as $key => $label) {
            if ($isMultiple) {
                $selected = !empty($value) && array_search($key, $value) !== false ? 'selected' : '';
            } else {
                $selected = $key == $value ? 'selected' : '';
            }
            $innerHtml .= "<option value=\"{$key}\" label=\"{$label}\" {$selected}>{$label}</option>";
        }
        return $this->outHtmlTag('select', $options, $innerHtml);
    }

    public function selectMulti($name, $labels, $items = [], $options = [])
    {
        $items['multiple'] = false;
        return $this->select($name, $labels, $items, $options);
    }

    public function button($name, $label, $options = [])
    {
        return $this->input($name, ['value' => $label, 'type' => 'button'] + $options);
    }

    public function submit($label = 'Submit', $options = [])
    {
        return $this->input('submit', ['value' => $label, 'type' => 'submit'] + $options);
    }

    public function error($name, $template = null, $separator = '<br/>')
    {
        if (!$template) {
            $template = $this->errorBlockTemplate;
        }
        $errors = $this->get('errors', []);
        $messages = isset($errors[$name]) ? $errors[$name] : [];
        $outs = [];
        foreach ($messages as $message) {
            $outs[] = sprintf($template, $message);
        }
        return implode($separator, $outs);
    }

    public static function out404()
    {
        http_response_code(404);
        $view = new View();
        $view->render('404', 'error');
    }

    public static function out403()
    {
        http_response_code(403);
        $view = new View();
        $view->render('403', 'error');
    }

    public static function out401()
    {
        header('WWW-Authenticate: Basic realm="Test Member Only"');
        http_response_code(401);
        $view = new View();
        $view->render('401', 'error');
    }

    public static function out500()
    {
        http_response_code(500);
        $view = new View();
        $view->render('500', 'error');
    }

}
