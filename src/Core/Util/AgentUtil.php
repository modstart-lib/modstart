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
        '/Googlebot/i' => 'Google',
        '/Baiduspider/i' => 'Baidu',
        '/360Spider/i' => '360',
        '/Sogou/i' => 'Sogou',
        '/YisouSpider/i' => 'Yisou',
        '/bingbot/i' => 'Bing',
        '/Bytespider/i' => 'TouTiao',
        // '/Yahoo! Slurp/i' => 'Yahoo',
        // '/DuckDuckBot/i' => 'DuckDuckGo',
        // '/YandexBot/i' => 'Yandex',
        // '/Exabot/i' => 'Exalead',
        // '/Facebot/i' => 'Facebook',
        // '/Twitterbot/i' => 'Twitter',
        // '/LinkedInBot/i' => 'LinkedIn',
        // '/Pinterest/i' => 'Pinterest',
        // '/Slackbot/i' => 'Slack',
        // '/TelegramBot/i' => 'Telegram',
        // '/WhatsApp/i' => 'WhatsApp',
        // '/Discordbot/i' => 'Discord',
        // '/WeChat/i' => 'WeChat',
        // '/Screaming Frog SEO Spider/i' => 'Screaming Frog',
        // '/MJ12bot/i' => 'Majestic',
        '/DotBot/i' => 'Moz',
        '/AhrefsBot/i' => 'Ahrefs',
        '/SemrushBot/i' => 'Semrush',
        // '/UptimeRobot/i' => 'Uptime Robot',
        // '/ArchiveBot/i' => 'Archive.org',
        // '/Embedly/i' => 'Embedly',
        // '/ZoominfoBot/i' => 'Zoominfo',
    ];

    public static function detectRobot($userAgent = null)
    {
        if (null === $userAgent) {
            $userAgent = AgentUtil::getUserAgent();
        }
        foreach (self::$robots as $regex => $robot) {
            if (preg_match($regex, $userAgent)) {
                return $robot;
            }
        }
        return null;
    }

}
