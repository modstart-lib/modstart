<?php


namespace ModStart\Core\Util;


class SerializeUtil
{
    public static function jsonObject($data)
    {
        if (empty($data)) {
            $data = new \stdClass();
        }
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function jsonEncode($data)
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function jsonEncodePretty($data)
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    public static function jsonDecode($data)
    {
        return json_decode($data, true);
    }

}
