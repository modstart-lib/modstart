<?php

namespace ModStart\Core\Assets\Driver;

use ModStart\Core\Assets\AssetsPath;
use Illuminate\Support\Facades\Cache;

class CdnAssetsPath implements AssetsPath
{
    const CACHE_PREFIX = 'modstart:asset-file:';

    public function getPathWithHash($file)
    {
        // 缓存 key 带上文件修改时间：文件内容更新后自动换 key 重新计算，
        // 避免永久缓存住旧 hash 导致浏览器一直请求旧 CSS / JS
        $mtime = @file_exists($file) ? @filemtime($file) : 0;
        $flag = self::CACHE_PREFIX . $file . ':' . $mtime;
        $hash = Cache::get($flag, null);
        if (null !== $hash) {
            return '' === $hash ? $file : $file . '?' . $hash;
        }
        if (file_exists($file)) {
            $hash = '' . crc32(md5_file($file));
            Cache::put($flag, $hash, 0);
            return $file . '?' . $hash;
        }
        Cache::put($flag, '', 0);
        return $file;
    }

    public function getCDN($file)
    {
        $cdnArray = config('modstart.asset.cdn_array', ['/']);
        $cdnIndex = abs(crc32($file) % count($cdnArray));
        return $cdnArray[$cdnIndex];
    }
}
