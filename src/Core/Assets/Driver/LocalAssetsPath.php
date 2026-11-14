<?php

namespace ModStart\Core\Assets\Driver;

use Illuminate\Support\Facades\Cache;
use ModStart\Core\Assets\AssetsPath;
use ModStart\Core\Util\PathUtil;

class LocalAssetsPath implements AssetsPath
{
    const CACHE_PREFIX = 'modstart:asset-file:';

    public function getPathWithHash($file)
    {
        if (PathUtil::isPublicNetPath($file)) {
            return $file;
        }
        $joiner = (false === strpos($file, '?')) ? '?' : '&';
        $localFile = $file;
        if ('&' == $joiner) {
            $localFile = substr($file, 0, strpos($file, '?'));
        }
        // 缓存 key 带上文件修改时间：文件内容更新后自动换 key 重新计算，
        // 避免永久缓存住旧 hash 导致浏览器一直请求旧 CSS / JS
        $mtime = @file_exists($localFile) ? @filemtime($localFile) : 0;
        $flag = self::CACHE_PREFIX . $file . ':' . $mtime;
        $hash = Cache::get($flag, null);
        if (null !== $hash) {
            return '' === $hash ? $file : $file . $joiner . $hash;
        }
        if (@file_exists($localFile)) {
            $hash = '' . crc32(md5_file($localFile));
            Cache::put($flag, $hash, 0);
            return $file . $joiner . $hash;
        }
        Cache::put($flag, '', 0);
        return $file;
    }

    public function getCDN($file)
    {
        return config('modstart.asset.cdn');
    }
}
