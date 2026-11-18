<?php

/**
 * 路由目标检查（modstart 核心自带脚本，随核心同步到各项目）
 *
 * 校验路由与后台菜单指向的「Class@method」是否真实存在：
 * 控制器被删除 / 方法被改名 / 脚手架残留都会让入口直接 500，
 * 而这些入口（尤其 POST）页面冒烟跑不到，只能静态核对。
 *
 * 默认只检查「本次改动涉及的范围」，避免把其他模块遗留的问题一起报出来：
 *   - 改动只涉及 `module/<X>/`：只检查模块 X 的路由与菜单
 *   - 改动涉及核心（vendor/modstart/modstart、app、config、bootstrap、routes、database）：自动全量
 *   - 没有路由相关改动：直接通过
 *
 * 用法（在项目目录内执行，脚本会自动向上查找项目根目录）：
 *   "$PHP_BIN" vendor/modstart/modstart/scripts/route-target-smoke.php
 *   "$PHP_BIN" vendor/modstart/modstart/scripts/route-target-smoke.php --all
 *   "$PHP_BIN" vendor/modstart/modstart/scripts/route-target-smoke.php --module=MemberOauthServer
 *   "$PHP_BIN" vendor/modstart/modstart/scripts/route-target-smoke.php --root=/data/soft/web/org.web
 *
 * 退出码：0 全部正常，1 存在失效入口，2 环境不满足（找不到项目目录 / 核心版本过低）
 *
 * 说明：扫描逻辑在 `ModStart\Test\TestRouteTarget`（与 test/biz/RouteTarget.php 共用同一份实现），
 * 本文件只负责定位项目、确定检查范围与输出报告。
 */

$root = null;
$moduleFilter = null;
$scanAll = false;

foreach (array_slice($argv, 1) as $arg) {
    if (0 === strpos($arg, '--module=')) {
        $moduleFilter = trim(substr($arg, strlen('--module=')));
    } elseif (0 === strpos($arg, '--root=')) {
        $root = rtrim(trim(substr($arg, strlen('--root='))), '/');
    } elseif ('--all' === $arg) {
        $scanAll = true;
    } elseif ('-h' === $arg || '--help' === $arg || 'help' === $arg) {
        echo "用法：\$PHP_BIN vendor/modstart/modstart/scripts/route-target-smoke.php [--all] [--module=模块名] [--root=项目路径]\n";
        echo "默认只检查本次改动涉及的路由 / 菜单；--all 全量；--module 指定模块；\n";
        echo "退出码 0 正常 / 1 有问题 / 2 环境不满足\n";
        exit(0);
    }
}

/**
 * 从给定目录向上查找项目根目录（含 bootstrap/app.php 与 vendor/autoload.php）
 *
 * @param string $from
 * @return string|null
 */
function routeTargetFindRoot($from)
{
    $dir = $from;
    while (!empty($dir)) {
        if (is_file($dir . '/bootstrap/app.php') && is_file($dir . '/vendor/autoload.php')) {
            return $dir;
        }
        $parent = dirname($dir);
        if ($parent === $dir) {
            break;
        }
        $dir = $parent;
    }
    return null;
}

/**
 * 读取 git 工作区的改动路径（含暂存、未暂存、重命名、未跟踪；已删除文件不入列）
 *
 * @param string $root
 * @return array
 */
function routeTargetGitChanges($root)
{
    $files = array();
    $command = 'cd ' . escapeshellarg($root) . ' && git status --porcelain -z --untracked-files=all 2>/dev/null';
    $output = shell_exec($command);
    if (null === $output || '' === trim($output)) {
        return $files;
    }
    $skipNext = false;
    foreach (explode("\0", $output) as $entry) {
        if ('' === $entry) {
            continue;
        }
        if ($skipNext) {
            $skipNext = false;
            continue;
        }
        if (strlen($entry) < 4) {
            continue;
        }
        $status = substr($entry, 0, 2);
        $path = substr($entry, 3);
        if (false !== strpos($status, 'R') || false !== strpos($status, 'C')) {
            // 重命名 / 复制：下一段是旧路径
            $skipNext = true;
        }
        if (false !== strpos($status, 'D')) {
            continue;
        }
        $files[] = $path;
    }
    return $files;
}

/**
 * 模块名转路由前缀（MemberOauthServer → member_oauth_server），用于匹配后台菜单地址
 *
 * @param string $name
 * @return string
 */
function routeTargetModuleSnake($name)
{
    return strtolower(preg_replace('/(?<!^)([A-Z])/', '_$1', $name));
}

if (empty($root)) {
    $root = routeTargetFindRoot(getcwd());
    if (empty($root) && !empty($_SERVER['PWD'])) {
        $root = routeTargetFindRoot($_SERVER['PWD']);
    }
}
if (empty($root) || !is_file($root . '/bootstrap/app.php')) {
    fwrite(STDERR, "未找到项目根目录（需包含 bootstrap/app.php 与 vendor/autoload.php），请在项目目录内执行或使用 --root=\n");
    exit(2);
}

// 确定检查范围
$scopeModules = array();
$scopeCore = false;
$scopeAll = $scanAll;
$scopeText = '全量';

if (null !== $moduleFilter && '' !== $moduleFilter) {
    $scopeModules[$moduleFilter] = true;
    $scopeText = '模块 ' . $moduleFilter;
} elseif (!$scanAll) {
    foreach (routeTargetGitChanges($root) as $path) {
        if (preg_match('#^module/([^/]+)/#', $path, $matched)) {
            $scopeModules[$matched[1]] = true;
            continue;
        }
        if (preg_match('#^(vendor/modstart/modstart|app|config|bootstrap|routes|database)/#', $path)) {
            $scopeCore = true;
        }
    }
    if ($scopeCore) {
        $scopeAll = true;
        $scopeText = '全量（改动涉及核心）';
    } elseif (!empty($scopeModules)) {
        $scopeText = '改动模块 ' . implode(', ', array_keys($scopeModules));
    } else {
        echo 'OK ' . $root . ' 没有涉及路由 / 菜单的改动' . "\n";
        exit(0);
    }
}

require $root . '/vendor/autoload.php';
$app = require_once $root . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

if (!class_exists('\ModStart\Test\TestRouteTarget')) {
    fwrite(STDERR, "当前项目的 modstart 核心版本过低（缺少 TestRouteTarget），请先同步 / 升级 vendor/modstart/modstart\n");
    exit(2);
}

$titles = array(
    'class' => '路由指向的控制器不存在',
    'method' => '路由指向的控制器方法不存在',
    'menu-class' => '后台菜单指向的控制器不存在',
    'menu-method' => '后台菜单指向的控制器方法不存在',
    'menu-url' => '后台菜单指向的地址没有对应路由',
);

$groups = array();
foreach (\ModStart\Test\TestRouteTarget::scan() as $problem) {
    if (!$scopeAll) {
        $hit = false;
        foreach (array_keys($scopeModules) as $name) {
            if (false !== strpos($problem['target'], 'Module\\' . $name . '\\')) {
                $hit = true;
                break;
            }
            if ('menu-url' === $problem['type']
                && preg_match('#(^|/)' . preg_quote(routeTargetModuleSnake($name), '#') . '(/|$)#', $problem['uri'])) {
                $hit = true;
                break;
            }
        }
        if (!$hit) {
            continue;
        }
    }
    $groups[$problem['type']][] = $problem;
}

$total = 0;
foreach ($titles as $type => $title) {
    if (empty($groups[$type])) {
        continue;
    }
    echo '[' . $title . ']' . "\n";
    foreach ($groups[$type] as $problem) {
        echo '  - ' . $problem['uri'] . ' -> ' . $problem['target'] . "\n";
        $total++;
    }
    echo "\n";
}

if (0 === $total) {
    echo 'OK ' . $root . ' 路由与后台菜单目标全部存在（' . $scopeText . '）' . "\n";
    exit(0);
}

echo 'FAIL ' . $root . ' 共 ' . $total . ' 处目标不存在（' . $scopeText . '）' . "\n";
exit(1);
