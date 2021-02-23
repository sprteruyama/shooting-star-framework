<?php

namespace ShootingStar;

define('HTTP_GET', 'GET');
define('HTTP_POST', 'POST');
define('HTTP_PUT', 'PUT');
define('HTTP_DELETE', 'DELETE');
define('HTTP_HEAD', 'HEAD');

class Request extends Base
{
    public $method = null;
    public $vars = [];
    public $posts = [];
    public $gets = [];
    public $data = [];
    public $body = null;
    public $query = null;
    public $files = [];

    public function __construct()
    {
        parent::__construct();
        $this->method = $method = $_SERVER['REQUEST_METHOD'];
        $this->posts = self::decodeArrayVariable($_POST);
        $this->gets = self::decodeArrayVariable($_GET);
        $this->data = array_merge($this->posts, $this->gets);
        $this->body = file_get_contents('php://input');
        $this->files = $_FILES;
    }

    public function gets($key, $default = null)
    {
        if (isset($this->gets[$key])) {
            return $this->gets[$key];
        } else {
            return $default;
        }
    }

    public function posts($key, $default = null)
    {
        if (isset($this->posts[$key])) {
            return $this->posts[$key];
        } else {
            return $default;
        }
    }

    public function files($key, $default = null)
    {
        if (isset($this->files[$key])) {
            $item = $this->files[$key];
            if (is_uploaded_file($item['tmp_name'])) {
                return $item;
            } else {
                return $default;
            }
        } else {
            return $default;
        }
    }

    public function data($key, $default = null)
    {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        } else {
            return $default;
        }
    }

    public function body()
    {
        return $this->body;
    }

    public function isPost()
    {
        return $this->method == HTTP_POST;
    }

    public function isDelete()
    {
        return $this->method == HTTP_DELETE;
    }

    public function json()
    {
        return json_decode($this->body, true);
    }

}
