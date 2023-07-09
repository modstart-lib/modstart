<?php

namespace ModStart\Core\Util;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Jenssegers\Agent\Facades\Agent;

/**
 * @Util
 */
class AgentUtil
{
    /**
     * @Util 获取浏览器UserAgent
     * @return string
     */
    public static function getUserAgent()
    {
        return Request::header('User-Agent');
    }

    /**
     * @Util 判断浏览器类型
     * @return string pc, mobile
     */
    public static function device()
    {
        if (self::isMobile()) {
            return 'mobile';
        }
        return 'pc';
    }

    /**
     * @Util 判断是否是微信浏览器
     * @return bool
     */
    public static function isWechat()
    {
        static $isWechat = null;
        if (null === $isWechat) {
            $userAgent = Request::header('User-Agent');
            if (strpos($userAgent, 'MicroMessenger') !== false) {
                $isWechat = true;
            } else {
                $isWechat = false;
            }
        }
        return $isWechat;
    }

    /**
     * @Util 判断是否是手机浏览器
     * @return bool
     */
    public static function isMobile()
    {
        return Agent::isPhone() || Agent::isTablet();
    }

    /**
     * @Util 判断是否是电脑浏览器
     * @return bool
     */
    public static function isPC()
    {
        return !Agent::isPhone() && !Agent::isTablet();
    }

    private static $robots = [
        '/Googlebot/' => 'Google',
        '/Baiduspider/' => 'Baidu',
        '/360Spider/' => '360',
        '/Sogou/' => 'Sogou',
        '/Yisouspider/' => 'Yisou',
        '/bingbot/' => 'Bing',
        // '/Yahoo! Slurp/' => 'Yahoo',
        // '/DuckDuckBot/' => 'DuckDuckGo',
        // '/YandexBot/' => 'Yandex',
        // '/Exabot/' => 'Exalead',
        // '/Facebot/' => 'Facebook',
        // '/Twitterbot/' => 'Twitter',
        // '/LinkedInBot/' => 'LinkedIn',
        // '/Pinterest/' => 'Pinterest',
        // '/Slackbot/' => 'Slack',
        // '/TelegramBot/' => 'Telegram',
        // '/WhatsApp/' => 'WhatsApp',
        // '/Discordbot/' => 'Discord',
        // '/WeChat/' => 'WeChat',
        // '/Screaming Frog SEO Spider/' => 'Screaming Frog',
        // '/MJ12bot/' => 'Majestic',
        '/DotBot/' => 'Moz',
        '/AhrefsBot/' => 'Ahrefs',
        '/SemrushBot/' => 'Semrush',
        // '/UptimeRobot/' => 'Uptime Robot',
        // '/ArchiveBot/' => 'Archive.org',
        // '/Embedly/' => 'Embedly',
        // '/ZoominfoBot/' => 'Zoominfo',
    ];

    public static function detectRobot()
    {
        $ua = AgentUtil::getUserAgent();
        foreach (self::$robots as $regex => $robot) {
            if (preg_match($regex, $ua)) {
                return $robot;
            }
        }
        return null;
    }

}
