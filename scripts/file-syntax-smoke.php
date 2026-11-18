<?php

/**
 * 文件语法检查（modstart 核心自带脚本，随核心同步到各项目）
 *
 * 对「本次改动过的文件」按后缀自动选择检查方式：
 *
 *   *.blade.php  Blade 模板检查（先归类，避免被当成普通 PHP）
 *                1) 指令兼容性：按目标 Laravel 版本拦截不该出现的指令
 *                   （典型：Laravel 5.1 不支持 `@php` `@csrf` `@auth` `@vite`，会原样输出成字面量）
 *                2) 编译语法：用项目 Blade 编译模板，再用目标版本对应的 PHP 做 `php -l`
 *                   （可发现内联表达式里的跨版本语法，如 5.1 下的 `$a ?? 0`、`fn() =>`、`?->`）
 *   *.php        PHP 语法检查：`php -l`，并额外用环境中的 php81 / php85 各查一遍（同版本去重）
 *   其它后缀     不处理
 *
 * 用法（在项目目录内执行，脚本会自动向上查找项目根目录）：
 *   "$PHP_BIN" vendor/modstart/modstart/scripts/file-syntax-smoke.php
 *   "$PHP_BIN" vendor/modstart/modstart/scripts/file-syntax-smoke.php --path=module
 *   "$PHP_BIN" vendor/modstart/modstart/scripts/file-syntax-smoke.php --target=5.1 --no-extra
 *   "$PHP_BIN" vendor/modstart/modstart/scripts/file-syntax-smoke.php --lint=php81,php85
 *
 * 退出码：0 全部通过，1 存在问题，2 环境不满足（找不到项目目录 / 路径不存在 / 目标版本无法识别）
 */

$root = null;
$paths = array();
$target = 'auto';
$extraBins = array('php81', 'php85');
$useExtra = true;
$checkDirective = true;
$checkCompile = true;

foreach (array_slice($argv, 1) as $arg) {
    if (0 === strpos($arg, '--root=')) {
        $root = rtrim(trim(substr($arg, strlen('--root='))), '/');
    } elseif (0 === strpos($arg, '--path=')) {
        $path = trim(substr($arg, strlen('--path=')));
        if ('' !== $path) {
            $paths[] = rtrim($path, '/');
        }
    } elseif (0 === strpos($arg, '--target=')) {
        $target = trim(substr($arg, strlen('--target=')));
    } elseif (0 === strpos($arg, '--lint=')) {
        $extraBins = array_values(array_filter(array_map('trim', explode(',', substr($arg, strlen('--lint='))))));
    } elseif ('--no-extra' === $arg) {
        $useExtra = false;
    } elseif ('--no-compile' === $arg) {
        $checkCompile = false;
    } elseif ('--no-directive' === $arg) {
        $checkDirective = false;
    } elseif ('-h' === $arg || '--help' === $arg || 'help' === $arg) {
        echo "用法：\$PHP_BIN vendor/modstart/modstart/scripts/file-syntax-smoke.php [--path=路径 ...] [--target=5.1|9|13] [--lint=php81,php85] [--no-extra] [--no-compile] [--no-directive] [--root=项目路径]\n";
        echo "未传 --path 时检查工作区所有已修改 / 新增 / 未跟踪的 *.php 与 *.blade.php；\n";
        echo "  PHP：php -l，环境存在 php81 / php85 时附带这两个版本（--no-extra 关闭）\n";
        echo "  Blade：默认目标版本 auto（module/ 下按该模块声明的最低 Laravel 版本，其余按当前项目版本）\n";
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
function fileSyntaxFindRoot($from)
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
 * 递归收集目录下的 PHP / Blade 文件
 *
 * @param string $dir
 * @param array $files
 * @return void
 */
function fileSyntaxCollect($dir, &$files)
{
    $items = @scandir($dir);
    if (empty($items)) {
        return;
    }
    foreach ($items as $item) {
        if ('.' === $item || '..' === $item) {
            continue;
        }
        $full = $dir . '/' . $item;
        if (is_dir($full)) {
            fileSyntaxCollect($full, $files);
            continue;
        }
        if ('.php' === substr($item, -4)) {
            $files[] = $full;
        }
    }
}

/**
 * 读取 git 工作区改动的 PHP / Blade 文件（含暂存、未暂存、重命名、未跟踪）
 *
 * @param string $root
 * @return array
 */
function fileSyntaxGitFiles($root)
{
    $files = array();
    $output = shell_exec('cd ' . escapeshellarg($root) . ' && git status --porcelain -z --untracked-files=all 2>/dev/null');
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
            $skipNext = true;
        }
        if (false !== strpos($status, 'D')) {
            continue;
        }
        if ('.php' !== substr($path, -4)) {
            continue;
        }
        if (is_file($root . '/' . $path)) {
            $files[] = $root . '/' . $path;
        }
    }
    return $files;
}

/**
 * 探测解释器版本，不可用时返回 null
 *
 * @param string $bin
 * @return string|null
 */
function fileSyntaxBinVersion($bin)
{
    $output = trim((string)shell_exec(escapeshellarg($bin) . ' -r "echo PHP_VERSION;" 2>/dev/null'));
    return preg_match('/^\d+\.\d+\.\d+/', $output) ? $output : null;
}

/**
 * PHP 语法检查用的解释器列表（当前解释器 + 可用的额外版本，按主次版本去重）
 *
 * @param array $extraBins
 * @param bool $useExtra
 * @return array [label => bin]
 */
function fileSyntaxInterpreters($extraBins, $useExtra)
{
    $interpreters = array();
    $versions = array();
    $currentShort = preg_match('/^(\d+\.\d+\.\d+)/', PHP_VERSION, $matched) ? $matched[1] : PHP_VERSION;
    $interpreters['php' . $currentShort] = PHP_BINARY;
    if (preg_match('/^(\d+\.\d+)/', PHP_VERSION, $matched)) {
        $versions[$matched[1]] = true;
    }
    if (!$useExtra) {
        return $interpreters;
    }
    foreach ($extraBins as $bin) {
        $version = fileSyntaxBinVersion($bin);
        if (null === $version) {
            continue;
        }
        if (!preg_match('/^(\d+\.\d+)/', $version, $matched)) {
            continue;
        }
        if (isset($versions[$matched[1]])) {
            continue;
        }
        $versions[$matched[1]] = true;
        $short = preg_match('/^(\d+\.\d+\.\d+)/', $version, $versionMatched) ? $versionMatched[1] : $version;
        $interpreters[$bin . '(' . $short . ')'] = $bin;
    }
    return $interpreters;
}

/**
 * 版本号归一化为可比较整数（5.1 → 501、9 → 900、13 → 1300）
 *
 * @param string $version
 * @return int|null
 */
function fileSyntaxVersionValue($version)
{
    $version = trim($version);
    if (!preg_match('/^(\d+)(?:\.(\d+))?/', $version, $matched)) {
        return null;
    }
    $major = intval($matched[1]);
    $minor = isset($matched[2]) ? intval($matched[2]) : 0;
    return $major * 100 + $minor;
}

/**
 * 版本整数转显示文本（501 → 5.1）
 *
 * @param int $value
 * @return string
 */
function fileSyntaxVersionLabel($value)
{
    return intval(floor($value / 100)) . '.' . intval($value % 100);
}

/**
 * 模块模板的目标版本：取模块 config.json 中 env 声明的最低 Laravel 版本（默认 5.1）
 *
 * @param string $root
 * @param string $module
 * @param array $cache
 * @return int
 */
function fileSyntaxModuleTarget($root, $module, &$cache)
{
    if (isset($cache[$module])) {
        return $cache[$module];
    }
    $map = array('laravel5' => 501, 'laravel9' => 900, 'laravel13' => 1300);
    $target = 501;
    $file = $root . '/module/' . $module . '/config.json';
    if (is_file($file)) {
        $config = json_decode((string)file_get_contents($file), true);
        if (!empty($config['env']) && is_array($config['env'])) {
            $min = null;
            foreach ($config['env'] as $env) {
                if (isset($map[$env]) && (null === $min || $map[$env] < $min)) {
                    $min = $map[$env];
                }
            }
            if (null !== $min) {
                $target = $min;
            }
        }
    }
    $cache[$module] = $target;
    return $target;
}

if (empty($root)) {
    $root = fileSyntaxFindRoot(getcwd());
    if (empty($root) && !empty($_SERVER['PWD'])) {
        $root = fileSyntaxFindRoot($_SERVER['PWD']);
    }
}
if (empty($root) || !is_file($root . '/bootstrap/app.php')) {
    fwrite(STDERR, "未找到项目根目录（需包含 bootstrap/app.php 与 vendor/autoload.php），请在项目目录内执行或使用 --root=\n");
    exit(2);
}

// 收集待检查文件
$files = array();
if (empty($paths)) {
    $files = fileSyntaxGitFiles($root);
} else {
    foreach ($paths as $path) {
        $full = (0 === strpos($path, '/')) ? $path : $root . '/' . $path;
        if (is_file($full)) {
            $files[] = $full;
        } elseif (is_dir($full)) {
            fileSyntaxCollect($full, $files);
        } else {
            fwrite(STDERR, "路径不存在：" . $path . "\n");
            exit(2);
        }
    }
}
$files = array_values(array_unique($files));
sort($files);

$phpFiles = array();
$bladeFiles = array();
foreach ($files as $file) {
    if ('.blade.php' === substr($file, -10)) {
        $bladeFiles[] = $file;
    } elseif ('.php' === substr($file, -4)) {
        $phpFiles[] = $file;
    }
}

if (empty($phpFiles) && empty($bladeFiles)) {
    echo 'OK 没有需要检查的 PHP / Blade 改动（' . $root . '）' . "\n";
    exit(0);
}

require $root . '/vendor/autoload.php';
$app = require_once $root . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$totalIssues = 0;

// ---------- PHP 语法检查 ----------
$interpreters = fileSyntaxInterpreters($extraBins, $useExtra);
$phpFailed = array();
foreach ($phpFiles as $file) {
    foreach ($interpreters as $label => $bin) {
        $output = trim((string)shell_exec(escapeshellarg($bin) . ' -l ' . escapeshellarg($file) . ' 2>&1'));
        if (false === strpos($output, 'No syntax errors detected')) {
            $phpFailed[$file][$label] = preg_replace('/\s+Errors parsing.*$/s', '', $output);
        }
    }
}
foreach ($phpFailed as $file => $messages) {
    echo '[PHP 语法错误] ' . str_replace($root . '/', '', $file) . "\n";
    foreach ($messages as $label => $message) {
        echo '  [' . $label . '] ' . trim($message) . "\n";
    }
    $totalIssues += count($messages);
}

// ---------- Blade 模板检查 ----------
$projectTarget = fileSyntaxVersionValue($app->version());
if (null === $projectTarget) {
    $projectTarget = 1300;
}

// 指令最小支持版本（@use 与 CSS/SCSS 的 @use 冲突，不拦截）
$minDirectiveVersion = array(
    'php' => 505, 'endphp' => 505,
    'canany' => 505, 'endcanany' => 505,
    'includeif' => 505, 'includewhen' => 505, 'includeunless' => 505, 'includefirst' => 505,
    'csrf' => 506, 'method' => 506,
    'auth' => 506, 'endauth' => 506, 'guest' => 506, 'endguest' => 506,
    'json' => 506,
    'env' => 507, 'endenv' => 507, 'production' => 507, 'endproduction' => 507,
    'error' => 600, 'enderror' => 600,
    'once' => 700, 'endonce' => 700, 'props' => 700, 'aware' => 700,
    'class' => 800,
    'checked' => 900, 'selected' => 900, 'disabled' => 900, 'readonly' => 900, 'required' => 900,
    'style' => 900, 'vite' => 900, 'vitereactrefresh' => 900,
    'fragment' => 1000, 'endfragment' => 1000,
    'fonts' => 1300,
);

$moduleTargetCache = array();
$checkedTargets = array();

foreach ($bladeFiles as $file) {
    $relative = str_replace($root . '/', '', $file);
    $content = @file_get_contents($file);
    if (false === $content) {
        continue;
    }

    if ('auto' === $target) {
        if (preg_match('#^module/([^/]+)/#', $relative, $moduleMatched)) {
            $fileTarget = fileSyntaxModuleTarget($root, $moduleMatched[1], $moduleTargetCache);
        } else {
            $fileTarget = $projectTarget;
        }
    } else {
        $fileTarget = fileSyntaxVersionValue($target);
        if (null === $fileTarget) {
            fwrite(STDERR, '无法识别的 --target：' . $target . "（可用 5.1 / 9 / 13）\n");
            exit(2);
        }
    }
    $checkedTargets[fileSyntaxVersionLabel($fileTarget)] = true;

    $issues = array();

    // 指令兼容性
    if ($checkDirective) {
        // 屏蔽不参与解析的区域：原生 PHP 块、Blade 注释、@verbatim 原文块
        $ranges = array();
        if (preg_match_all('/<\?php.*?\?>|<\?=.*?\?>|\{\{--.*?--\}\}|\@verbatim.*?\@endverbatim/s', $content, $rangeMatch, PREG_OFFSET_CAPTURE)) {
            foreach ($rangeMatch[0] as $rangeItem) {
                $ranges[] = array($rangeItem[1], $rangeItem[1] + strlen($rangeItem[0]) - 1);
            }
        }
        $inSingle = false;
        $inDouble = false;
        $rangeIndex = 0;
        $length = strlen($content);
        for ($i = 0; $i < $length; $i++) {
            while ($rangeIndex < count($ranges) && $i > $ranges[$rangeIndex][1]) {
                $rangeIndex++;
            }
            if ($rangeIndex < count($ranges) && $i >= $ranges[$rangeIndex][0]) {
                $i = $ranges[$rangeIndex][1];
                $inSingle = false;
                $inDouble = false;
                continue;
            }
            $char = $content[$i];
            if ($inSingle) {
                if ('\\' === $char) {
                    $i++;
                } elseif ("'" === $char) {
                    $inSingle = false;
                }
                continue;
            }
            if ($inDouble) {
                if ('\\' === $char) {
                    $i++;
                } elseif ('"' === $char) {
                    $inDouble = false;
                }
                continue;
            }
            if ("'" === $char) {
                $inSingle = true;
                continue;
            }
            if ('"' === $char) {
                $inDouble = true;
                continue;
            }
            if ('@' !== $char) {
                continue;
            }
            // @@ 为 Blade 转义，不当作指令
            if (isset($content[$i + 1]) && '@' === $content[$i + 1]) {
                $i++;
                continue;
            }
            if (!preg_match('/@([a-zA-Z_][a-zA-Z0-9_]*)/A', $content, $matched, 0, $i)) {
                continue;
            }
            $name = strtolower($matched[1]);
            $line = substr_count(substr($content, 0, $i), "\n") + 1;
            $i += strlen($matched[0]) - 1;
            if (!isset($minDirectiveVersion[$name])) {
                continue;
            }
            if ($minDirectiveVersion[$name] <= $fileTarget) {
                continue;
            }
            $issues[] = '指令 @' . $matched[1] . ' 需要 Laravel ' . fileSyntaxVersionLabel($minDirectiveVersion[$name])
                . '+，目标 Laravel ' . fileSyntaxVersionLabel($fileTarget) . ' 不支持（第 ' . $line . ' 行）';
        }
    }

    // 编译 + 语法检查
    if ($checkCompile) {
        try {
            $compiled = app('blade.compiler')->compileString($content);
        } catch (\Exception $e) {
            $compiled = null;
            $issues[] = '模板编译失败：' . $e->getMessage();
        }
        if (null !== $compiled) {
            $temp = tempnam(sys_get_temp_dir(), 'file_syntax_smoke_');
            file_put_contents($temp, $compiled);
            // 用「目标版本对应的 PHP」检查编译产物；该解释器不存在时退回当前解释器
            $targetBin = ($fileTarget <= 501) ? 'php56' : (($fileTarget < 1300) ? 'php81' : 'php85');
            $targetVersion = fileSyntaxBinVersion($targetBin);
            if (null !== $targetVersion) {
                $shortVersion = preg_match('/^(\d+\.\d+\.\d+)/', $targetVersion, $versionMatched) ? $versionMatched[1] : $targetVersion;
                $lintBins = array($targetBin . '(' . $shortVersion . ')' => $targetBin);
            } else {
                $lintBins = $interpreters;
            }
            foreach ($lintBins as $label => $bin) {
                $output = trim((string)shell_exec(escapeshellarg($bin) . ' -l ' . escapeshellarg($temp) . ' 2>&1'));
                if (false === strpos($output, 'No syntax errors detected')) {
                    $output = str_replace($temp, '编译产物', $output);
                    $output = preg_replace('/\s+Errors parsing.*$/s', '', $output);
                    $issues[] = '编译产物语法错误（' . $label . '）：' . trim($output);
                }
            }
            @unlink($temp);
        }
    }

    if (!empty($issues)) {
        echo '[Blade 模板问题] ' . $relative . '（目标 Laravel ' . fileSyntaxVersionLabel($fileTarget) . '）' . "\n";
        foreach ($issues as $issue) {
            echo '  - ' . $issue . "\n";
        }
        $totalIssues += count($issues);
    }
}

$targets = array_keys($checkedTargets);
sort($targets);
$summary = count($phpFiles) . ' 个 PHP 文件（解释器 ' . implode(' / ', array_keys($interpreters)) . '）';
if (!empty($bladeFiles)) {
    $summary .= '，' . count($bladeFiles) . ' 个 Blade 模板';
    if (!empty($targets)) {
        $summary .= '（目标 Laravel ' . implode(' / ', $targets) . '）';
    }
}

if (0 === $totalIssues) {
    echo 'OK 文件语法检查通过（' . $summary . '，' . $root . '）' . "\n";
    exit(0);
}

echo 'FAIL ' . $totalIssues . ' 处问题（' . $summary . '）' . "\n";
exit(1);
