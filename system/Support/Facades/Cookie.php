<?php
/**
 * Cookie - A Facade to \Cookie\CookieJar.
 *
 * @author Virgil-Adrian Teaca - virgil@giulianaeassociati.com
 * @version 3.0
 */

namespace Support\Facades;

use Cookie\CookieJar;
use Support\Facades\Request;

/**
 * @see \Cookie\CookieJar
 */
class Cookie
{
    const FIVEYEARS = 2628000;

    /**
     * The CookieJar instance being handled.
     *
     * @var \Cookie\CookieJar|null
     */
    protected static $cookieJar;

    /**
     * Return a CookieJar instance
     *
     * @return \Cookie\CookieJar
     */
    protected static function getCookieJar()
    {
        if (isset(static::$cookieJar)) {
            return static::$cookieJar;
        }

        return static::$cookieJar = new CookieJar();
    }

    /**
     * Determine if a cookie exists on the request.
     *
     * @param  string  $key
     * @return bool
     */
    public static function has($key)
    {
        // Get the Request instance.
        $request = Request::instance();

        return ! is_null($request->cookie($key, null));
    }

    /**
     * Retrieve a cookie from the request.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return string
     */
    public static function get($key = null, $default = null)
    {
        // Get the Request instance.
        $request = Request::instance();

        return $request->cookie($key, $default);
    }

    /**
     * Magic Method for calling the methods on the default CookieJar instance.
     *
     * @param $method
     * @param $params
     *
     * @return mixed
     */
    public static function __callStatic($method, $params)
    {
        // Get a \Http\Request instance.
        $instance = static::getCookieJar();

        // Call the non-static method from the Request instance.
        return call_user_func_array(array($instance, $method), $params);
    }
}
