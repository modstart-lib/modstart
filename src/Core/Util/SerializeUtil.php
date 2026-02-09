<?php


namespace ModStart\Core\Util;


use Illuminate\Support\Facades\Log;

class SerializeUtil
{

    private static function utf8ize($mixed)
    {
        if (is_array($mixed)) {
            foreach ($mixed as $key => $value) {
                $mixed[$key] = self::utf8ize($value);
            }
        } elseif (is_string($mixed)) {
            if (!mb_check_encoding($mixed, 'UTF-8')) {
                $base64 = base64_encode($mixed);
                $mixed = 'base64:' . $base64;
            }
        }
        return $mixed;
    }

    private static function safeJsonEncode($data, $options)
    {
        $value = json_encode($data, $options);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $data = self::utf8ize($data);
            $error = json_encode([
                'error' => json_last_error_msg(),
                'data' => $data,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            Log::error('SerializeUtil.jsonEncode.error - ' . $error);
            $value = json_encode($data, $options);
        }
        return $value;
    }

    /**
     * @param $data
     * @return false|string
     * @deprecated delete at 2024-04-26
     */
    public static function jsonObject($data)
    {
        if (empty($data)) {
            $data = new \stdClass();
        }
        return self::safeJsonEncode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function jsonEncodeObject($data, $options = 0)
    {
        return self::safeJsonEncode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_FORCE_OBJECT | $options);
    }

    public static function jsonEncode($data, $options = 0)
    {
        return self::safeJsonEncode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | $options);
    }

    public static function jsonEncodePretty($data, $options = 0)
    {
        return self::safeJsonEncode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | $options);
    }

    public static function jsonDecode($data)
    {
        return @json_decode($data, true);
    }

    public static function objectArray($array)
    {
        if (empty($array)) {
            return new \stdClass();
        }
        return $array;
    }

}
