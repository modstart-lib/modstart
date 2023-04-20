<?php


namespace ModStart\Core\Util;


use Illuminate\Support\Facades\Session;

class SessionUtil
{
    public static function get($sessionId, $key)
    {
        $oldSessionId = Session::getId();
        Session::save();

        Session::setId($sessionId);
        Session::start();
        $value = Session::get($key);
        Session::clear();

        Session::setId($oldSessionId);
        Session::start();

        return $value;
    }

    public static function delete($sessionId, $key)
    {
        $oldSessionId = Session::getId();
        Session::save();

        Session::setId($sessionId);
        Session::start();
        Session::forget($key);
        Session::save();
        Session::clear();

        Session::setId($oldSessionId);
        Session::start();
    }

    public static function clear($sessionId)
    {
        $oldSessionId = Session::getId();
        Session::save();

        Session::setId($sessionId);
        Session::start();
        Session::flush();
        Session::save();
        Session::clear();

        Session::setId($oldSessionId);
        Session::start();
    }
}
