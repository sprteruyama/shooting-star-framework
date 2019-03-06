<?php

namespace ShootingStar;

/**
 * Class Controller
 * @package Core
 *
 * @property Request $request
 * @property Session $session
 * @property Validator $validator
 */
class Controller extends Base
{
    public $request;
    public $session;
    public $action = null;
    public $path = null;
    public $layout = 'default';
    public $autoRender = true;
    public $noBasicProtectedActions = [];
    public $viewClass = null;
    public $errors = [];

    private $view;
    private $isRendered = false;

    public function __construct()
    {
        parent::__construct();
        $viewClass = $this->viewClass;
        if ($viewClass) {
            ClassLoader::loadClass($viewClass, VIEW_DIR);
            $this->view = new $viewClass();
        } else {
            $this->view = new View();
        }
        $this->view->layout = $this->layout;
        $this->validator = new Validator();
    }

    public function basicProtect()
    {
        if (Config::get('url.auth.enabled') && array_search($this->action, $this->noBasicProtectedActions) === false) {
            if (!isset($_SERVER['PHP_AUTH_USER'])) {
                View::out401();
                exit;
            } else {
                if (!($_SERVER['PHP_AUTH_USER'] == Config::get('url.auth.user') && $_SERVER['PHP_AUTH_PW'] == Config::get('url.auth.password'))) {
                    echo 'None';
                    exit();
                }
            }
        }
    }

    public function beforeHook()
    {
        $this->basicProtect();
        $this->errors = [];
    }

    public function afterHook()
    {
        $this->view->layout = $this->layout;
        if ($this->autoRender) {
            $this->render();
        }
    }

    public function get($key)
    {
        return $this->view->get($key);
    }

    public function set($key, $value)
    {
        $this->view->set($key, $value);
    }

    public function sets($vars)
    {
        $this->view->sets($vars);
    }

    public function validate($rules, $vars)
    {
        foreach ($vars as $name => $value) {
            $vars[$name] = $this->request->data($name, $value);
        }
        $this->errors = array_merge($this->errors, $this->validator->validate($rules, $vars));
        $this->sets($vars);
        $this->set('errors', $this->errors);
        return $vars;
    }

    public function validatePost($rules, $vars)
    {
        if ($this->request->isPost()) {
            $base = $vars;
            foreach ($vars as $name => $value) {
                $vars[$name] = $this->request->posts($name, $value);
                if (is_array($vars[$name])) {
                    $this->completionArray($vars[$name], $base[$name]);
                }
            }
            $this->errors = array_merge($this->errors, $this->validator->validate($rules, $vars));
        }
        $this->sets($vars);
        $this->set('errors', $this->errors);
        return $vars;
    }


    public function validateGet($rules, $vars)
    {
        foreach ($vars as $name => $value) {
            $vars[$name] = $this->request->gets($name, $value);
        }
        $this->errors = array_merge($this->errors, $this->validator->validate($rules, $vars));
        $this->sets($vars);
        $this->set('errors', $this->errors);
        return $vars;
    }

    private function completionArray(&$array, $base)
    {
        if (is_array($array)) {
            foreach ($base as $key => $value) {
                if (is_array($value)) {
                    $this->completionArray($array[$key], $value);
                } else {
                    if (!isset($array[$key])) {
                        $array[$key] = null;
                    }
                }
            }
            ksort($array);
        }
    }

    public function render($template = null, $layout = null)
    {
        if ($this->isRendered) {
            return;
        }
        $this->isRendered = true;
        if (!$template) {
            if ($this->path) {
                $template = '/' . substr($this->path, 1) . '/' . str_replace('Controller', '', get_class($this)) . '/' . $this->action;
            } else {
                $template = '/' . str_replace('Controller', '', get_class($this)) . '/' . $this->action;
            }
        } else {
            if (strpos($template, '/') !== 0) {
                $template = '/' . substr($this->path, 1) . '/' . str_replace('Controller', '', get_class($this)) . '/' . $template;
            }
        }
        $this->view->render($template, $layout);
    }

    public function outJson($data = [])
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        $this->isRendered = true;
    }

    public function getCookie($key, $default = null)
    {
        if (isset($_COOKIE[$key])) {
            return $_COOKIE[$key];
        } else {
            setcookie($key, $default);
            return $default;
        }
    }

    public function setCookie($key, $value)
    {
        setcookie($key, $value);
    }

    public function redirect($url)
    {
        if (!preg_match('/^https?:\/\//', $url)) {
            $url = Router::url($url, true);
        }
        header('Location: ' . $url);
        exit();
    }
}
