<?php

namespace ModStart\Test;

use ModStart\Admin\Config\AdminMenu;

/**
 * 路由目标检查 — 校验路由与后台菜单指向的「Class@method」真实存在
 *
 * 用于发现「控制器被删除 / 方法被改名 / 脚手架残留」造成的入口 500：
 * 请求命中不存在的控制器或方法时，框架只会抛异常，只有真实访问才会暴露。
 *
 * ⚠️ 命令行入口为 `vendor/modstart/modstart/scripts/route-target-smoke.php`，
 *    项目内 `test/biz/RouteTarget.php` 会调用本类在 `seed-test` 中自动校验。
 *
 * 用法（测试或脚本）：
 *   $problems = TestRouteTarget::scan();
 *   foreach ($problems as $p) { echo $p['type'] . ' ' . $p['uri'] . ' -> ' . $p['target']; }
 */
class TestRouteTarget
{
    /**
     * 扫描路由与后台菜单，返回所有失效的目标
     *
     * @return array [['type' => 'class'|'method'|'menu-class'|'menu-method'|'menu-url', 'uri' => string, 'target' => string], ...]
     */
    public static function scan()
    {
        $problems = array();
        $problems = array_merge($problems, self::scanRoutes());
        $problems = array_merge($problems, self::scanAdminMenus());
        return $problems;
    }

    /**
     * 扫描所有已注册路由的 Class@method 目标
     *
     * @return array
     */
    public static function scanRoutes()
    {
        $problems = array();
        foreach (app('router')->getRoutes() as $route) {
            $action = $route->getActionName();
            if (strpos($action, '@') === false) {
                // Closure 或其他非控制器动作
                continue;
            }
            list($class, $method) = explode('@', $action, 2);
            $class = ltrim($class, '\\');
            if (!class_exists($class)) {
                $problems[] = array('type' => 'class', 'uri' => $route->uri(), 'target' => $action);
                continue;
            }
            if (self::methodCallable($class, $method)) {
                continue;
            }
            $problems[] = array('type' => 'method', 'uri' => $route->uri(), 'target' => $action);
        }
        return $problems;
    }

    /**
     * 扫描后台菜单里指向的控制器方法与后台地址
     *
     * @return array
     */
    public static function scanAdminMenus()
    {
        $problems = array();
        $routes = self::adminRoutePatterns();
        $urls = array();
        self::collectMenuUrls(AdminMenu::get(), $urls);
        foreach ($urls as $url) {
            if (false !== strpos($url, '@')) {
                list($class, $method) = explode('@', $url, 2);
                $class = ltrim($class, '\\');
                if (!class_exists($class)) {
                    $problems[] = array('type' => 'menu-class', 'uri' => '(menu)', 'target' => $url);
                    continue;
                }
                if (self::methodCallable($class, $method)) {
                    continue;
                }
                $problems[] = array('type' => 'menu-method', 'uri' => '(menu)', 'target' => $url);
                continue;
            }
            $path = self::normalizeMenuUrl($url);
            if (null === $path) {
                continue;
            }
            if (!self::matchRoutePath($routes, $path)) {
                $problems[] = array('type' => 'menu-url', 'uri' => $path, 'target' => $url);
            }
        }
        return $problems;
    }

    /**
     * 收集真正可点击的后台菜单地址
     *
     * 分组（有 children）与 hide 项（仅作为权限点）不会打开页面，不参与检查
     *
     * @param array $menus
     * @param array $urls
     * @return void
     */
    private static function collectMenuUrls($menus, &$urls)
    {
        if (!is_array($menus)) {
            return;
        }
        foreach ($menus as $menu) {
            if (!is_array($menu)) {
                continue;
            }
            if (isset($menu['title'])) {
                $hasChildren = !empty($menu['children']);
                if (isset($menu['url']) && is_string($menu['url']) && !$hasChildren && empty($menu['hide'])) {
                    $urls[] = $menu['url'];
                }
                if ($hasChildren) {
                    self::collectMenuUrls($menu['children'], $urls);
                }
            } else {
                // 菜单项列表
                self::collectMenuUrls($menu, $urls);
            }
        }
    }

    /**
     * 把后台菜单地址与已注册路由比对（支持 {name} / {name?} 参数路由）
     *
     * @param array $patterns
     * @param string $path
     * @return bool
     */
    private static function matchRoutePath($patterns, $path)
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, '/' . $path)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 方法是否可调用（存在，或控制器自己实现了 __call 做动态分发）
     *
     * 注意：Laravel 的 Illuminate\Routing\Controller 自带 __call（只抛异常），
     * 不能当作动态分发，否则所有方法缺失都会被放过。
     *
     * @param string $class
     * @param string $method
     * @return bool
     */
    public static function methodCallable($class, $method)
    {
        if (method_exists($class, $method)) {
            return true;
        }
        $reflection = new \ReflectionClass($class);
        if ($reflection->hasMethod('__call')) {
            $declaring = $reflection->getMethod('__call')->getDeclaringClass()->getName();
            if (0 !== strpos($declaring, 'Illuminate\\')) {
                // 业务控制器自己实现了魔术分发
                return true;
            }
        }
        return false;
    }

    /**
     * 已注册路由地址集合（参数路由转为正则，用于匹配后台菜单地址）
     *
     * @return array
     */
    private static function adminRoutePatterns()
    {
        $patterns = array();
        foreach (app('router')->getRoutes() as $route) {
            $uri = trim($route->uri(), '/');
            if ('' === $uri) {
                continue;
            }
            $regex = '';
            foreach (explode('/', $uri) as $segment) {
                if (preg_match('/^\{.+\?\}$/', $segment)) {
                    // 可选参数段 /{name?}
                    $regex .= '(?:/[^/]+)?';
                } elseif (preg_match('/^\{.+\}$/', $segment)) {
                    // 必选参数段 /{name}
                    $regex .= '/[^/]+';
                } else {
                    $regex .= '/' . preg_quote($segment, '#');
                }
            }
            $patterns[] = '#^' . $regex . '$#';
        }
        return $patterns;
    }

    /**
     * 后台菜单地址归一化为路由 uri 形式，返回 null 表示无需检查
     * （外链、前台地址、带参数地址不在此检查范围）
     *
     * @param string $url
     * @return string|null
     */
    private static function normalizeMenuUrl($url)
    {
        $url = trim($url);
        if ('' === $url) {
            return null;
        }
        if (preg_match('#^https?://#i', $url)) {
            return null;
        }
        $url = preg_split('/[?#]/', $url);
        $url = trim($url[0], '/');
        if ('' === $url || false !== strpos($url, '{')) {
            return null;
        }
        $prefix = trim(config('modstart.admin.prefix'), '/');
        if ('' === $prefix) {
            return null;
        }
        if (0 !== strpos($url, $prefix . '/')) {
            // 不是后台地址（前台地址由前台页面检查覆盖）
            return null;
        }
        return $url;
    }
}
