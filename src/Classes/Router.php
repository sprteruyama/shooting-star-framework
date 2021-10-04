<?php

namespace ShootingStar;

class Router
{
    private static $routes = [
        '/' => false,
    ];

    public static function startWeb()
    {
        if (!isset($_SERVER['REQUEST_URI'])) {
            return;
        }
        $urlRoot = Config::get('url.root');
        if ($urlRoot == '/') {
            $urlRoot = '';
        }
        require_once CORE_DIR . '/Classes/Controller.php';
        $parsed = parse_url($_SERVER['REQUEST_URI']);
        if ($urlRoot) {
            $parsedPath = str_replace($urlRoot, '', $parsed['path']);
        } else {
            $parsedPath = $parsed['path'];
        }
        self::$routes = array_merge(self::$routes, Config::get('routes', []));
        foreach (self::$routes as $route => $to) {
            $route = str_replace('/', '\\/', $route);
            $regexp = "/^{$route}$/";
            if (preg_match($regexp, $parsedPath)) {
                if (!$to) {
                    View::out404();
                    return;
                }
                $parsedPath = preg_replace($regexp, $to, $parsedPath);
                break;
            }
        }
        $paths = explode('/', $parsedPath);
        $controllerPath = CONTROLLER_DIR;
        $methodIndex = 0;
        foreach ($paths as $path) {
            $methodIndex++;
            if (empty($path)) {
                continue;
            }
            $path = Base::snakeToPascal($path);
            if (isset($paths[$methodIndex]) && $paths[$methodIndex] && is_dir("{$controllerPath}/{$path}")) {
                $controllerPath .= "/{$path}";
                continue;
            }
            $controllerName = $path . 'Controller';
            if (ClassLoader::loadClass($controllerName, $controllerPath)) {
                $method = isset($paths[$methodIndex]) && $paths[$methodIndex] ? $paths[$methodIndex] : 'index';
                if (strpos($method, '_') === 0) {
                    View::out404();
                    return;
                }
                $vars = array_slice($paths, $methodIndex + 1, count($paths) - ($methodIndex + 1));
                require_once CORE_DIR . '/Classes/Request.php';
                $request = new Request();
                $request->vars = $vars;
                $request->query = implode('/', $vars) . (isset($request->gets) ? '?' . http_build_query($request->gets) : '');
                require_once CORE_DIR . '/Classes/Session.php';
                $session = Session::getInstance();
                /** @var Controller $controller */
                $controller = new $controllerName();
                $controller->request = $request;
                $controller->session = $session;
                $controller->action = $method;
                $actionMethod = $method . '__' . strtoupper($request->method);
                $controller->path = str_replace(CONTROLLER_DIR, '', $controllerPath);
                $primitiveMethods = get_class_methods('ShootingStar\\Controller');
                if (array_search($method, $primitiveMethods) !== false) {
                    View::out404();
                    return;
                }
                if (method_exists($controller, $actionMethod) && is_callable([$controller, $actionMethod])) {
                    $method = $actionMethod;
                } else if (!method_exists($controller, $method) || !is_callable([$controller, $method])) {
                    View::out404();
                    return;
                }
                $controller->beforeHook();
                call_user_func_array([$controller, $method], $vars);
                $controller->afterHook();
                return;
            }
        }
        View::out404();
        return;
    }

    public static function url($path, $isFull = false, $versionName = null)
    {
        $path = str_replace(PUBLIC_DIR, '', $path);
        $url = '';
        if ($isFull) {
            $url .= Config::get('url.base');
        } else {
            $urlRoot = Config::get('url.root', '');
            if ($urlRoot == '/') {
                $urlRoot = '';
            }
            $url .= $urlRoot;
        }
        if (strpos($path, '/') !== 0) {
            $path = '/' . $path;
        }
        $url .= $path;
        if ($url && $url[strlen($url) - 1] == '/') {
            $url = substr($url, 0, -1);
        }
        $filepath = PUBLIC_DIR . $url;
        if ($versionName != null && file_exists($filepath)) {
            $version = filemtime($filepath);
            if (strpos($url, '?') !== false) {
                $url .= "&{$versionName}={$version}";
            } else {
                $url .= "?{$versionName}={$version}";
            }
        }
        return $url;
    }
}
