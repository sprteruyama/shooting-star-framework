<?php

namespace ShootingStar;

/**
 * Class Session
 * @package Core
 *
 * @property Model $SessionModel
 */
class Session
{
    public static $instance;
    public $id = null;

    public static function getInstance()
    {
        if (!self::$instance) {
            ini_set('session.save_path', SHARE_DIR . '/session');
            ini_set('session.gc_probability', 1);
            ini_set('session.gc_divisor', Config::get('session.divisor', 1000));
            ini_set('session.gc_maxlifetime', Config::get('session.lifetime', 30 * 60));
            ini_set('session.use_cookies', 1);
            ini_set('session.name', Config::get('session.cookie_name'));
            ini_set('session.cookie_lifetime', Config::get('session.lifetime', 30 * 60));
            session_start();
            self::$instance = new Session();
            self::$instance->id = session_id();
        }
        return self::$instance;
    }

    public function get($key, $default = null)
    {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
    }

    public function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public function delete($key)
    {
        unset($_SESSION[$key]);
    }

    public function destroy()
    {
        session_destroy();
    }

    public function token($key = '')
    {
        $token = md5(Config::get('salt') . date('YmdHis') . rand(0, 9999));
        $this->set('__token__' . $key, $token);
        return $token;
    }

    public function validateToken($token)
    {
        foreach ($_SESSION as $key => $value) {
            if (strpos($key, '__token__') === 0 && $value == $token) {
                unset($_SESSION[$key]);
                return $key;
            }
        }
        return false;
    }
}
