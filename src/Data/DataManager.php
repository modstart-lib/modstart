<?php


namespace ModStart\Data;


use Illuminate\Support\Facades\Input;
use Illuminate\Support\Str;
use ModStart\Admin\Model\Data;
use ModStart\Core\Assets\AssetsUtil;
use ModStart\Core\Exception\BizException;
use ModStart\Core\Input\InputPackage;
use ModStart\Core\Input\Response;
use ModStart\Core\Util\ArrayUtil;
use ModStart\Core\Util\EnvUtil;
use ModStart\Core\Util\FileUtil;
use ModStart\Core\Util\LogUtil;
use ModStart\Core\Util\PathUtil;
use ModStart\Core\Util\SerializeUtil;
use ModStart\Data\Event\DataDeletedEvent;
use ModStart\Data\Event\DataFileUploadedEvent;
use ModStart\Data\Storage\FileDataStorage;
use ModStart\Data\Support\DummyUploadedFile;

/**
 * Class DataManager
 * @package ModStart\Data
 *
 * @example
 * 测试新存储
 * var_dump(DataManager::upload('file', 'test111.txt', 'test', ['driver' => 'DataStorage_DataSFtp']));
 */
class DataManager
{
    /** @var AbstractDataStorage[] */
    private static $storages = [];
    private static $config = null;

    public static function uploadConfig($category)
    {
        if (!is_array($category)) {
            $category = [$category];
        }
        $categoryConfigs = [];
        foreach ($category as $cat) {
            $categoryConfigs[$cat] = config('data.upload.' . $cat);
        }
        return [
            'chunkSize' => EnvUtil::env('uploadMaxSize'),
            'category' => $categoryConfigs,
        ];
    }

    /**
     * 从用户配置中获取文件上传相关配置
     * @return array|null
     */
    public static function getConfigOption()
    {
        static $option = null;
        if (null === $option) {
            $option ['driver'] = modstart_config('DataStorageDefaultDriver', '');
            if (empty($option['driver'])) {
                $option ['driver'] = DataStorageProvider::defaultEnabledDriver();
            }
            if (empty($option ['driver'])) {
                $option ['driver'] = app()->config->get('DataStorageDriver');
            }
            if (empty($option ['driver'])) {
                $option ['driver'] = 'DataStorage_DataFile';
                app()->bind('DataStorage_DataFile', function () {
                    return new FileDataStorage(null);
                });
            }
        }
        return $option;
    }

    /**
     * 上传频道参数（业务场景标记，如 ad / content），由上传地址的 channel 参数声明
     *
     * 声明后会写入 eventOpt.param.channel，供水印等上传后处理能力按频道过滤
     * @return array
     */
    public static function uploadChannelParam()
    {
        $channel = InputPackage::buildFromInput()->getTrimString('channel', '');
        $channel = preg_replace('/[^a-zA-Z0-9_\-]/', '', $channel);
        if (strlen($channel) > 32) {
            $channel = substr($channel, 0, 32);
        }
        if ('' === $channel) {
            return [];
        }
        return ['channel' => $channel];
    }

    public static function prepareOption($option = null)
    {
        if (empty($option)) {
            $option = self::getConfigOption();
        }
        if (null === self::$config) {
            self::$config = config('data.upload', []);
        }
        $hash = md5(SerializeUtil::jsonEncode($option));
        if (isset(self::$storages[$hash])) {
            return $option;
        }
        $map[$option['driver']] = app($option['driver']);
        self::$storages[$hash] = $map[$option['driver']];
        return $option;
    }

    /**
     * @param null $option
     * @return AbstractDataStorage
     * @throws BizException
     */
    public static function storage($option = null)
    {
        if (empty($option)) {
            $option = self::prepareOption();
        }
        $hash = md5(SerializeUtil::jsonEncode($option));
        BizException::throwsIf('Storage empty', !isset(self::$storages[$hash]));
        return self::$storages[$hash];
    }

    /**
     * 文件上传
     * @param $category string 上传分类
     * @param $input array 上传参数
     * @param $extra array 附加参数
     * @param $option null|array 上传驱动配置
     * @param $param array 其他参数
     * @return array
     * @throws \Exception
     */
    public static function uploadHandle($category, $input, $extra = [], $option = null, $param = [])
    {
        if (!empty($input['fileBase64'])) {
            $binary = @base64_decode($input['fileBase64']);
            if (empty($binary)) {
                return Response::generate(-1, 'fileBase64 decode fail');
            }
            unset($input['fileBase64']);
            $input['file'] = DummyUploadedFile::create($binary);
            InputPackage::mergeToInput('fileBase64', null);
            //LogUtil::info('DataManager.uploadHandle.fileBase64', [
            //    'chunkSize' => strlen($binary),
            //    'input' => $input,
            //]);
        }
        $action = empty($input['action']) ? '' : $input['action'];
        $file = [];
        foreach (['name', 'type', 'lastModifiedDate', 'size'] as $k) {
            if (!isset($input[$k])) {
                return Response::generate(-1, $k . ' empty');
            }
            if (!is_scalar($input[$k])) {
                return Response::generate(-1, 'Upload param ' . $k . ' invalid ( ' . gettype($input[$k]) . ' )');
            }
            $file[$k] = $input[$k] . '';
        }
        // 分片参数来自请求，各存储驱动会直接对它做 `$chunk + 1` 等算术运算。
        // PHP 7.1+ 对「数字开头带尾巴」的字符串（如 `0-1048575`、`1/5`、`12 MB`、`12abc`）
        // 会抛出 "A non-numeric value encountered" 告警，框架转为异常后即成 500 并上报。
        // 这里在入口统一校验并转为整型，非法值返回明确的业务错误，避免各驱动重复处理。
        foreach (['chunk', 'chunks'] as $k) {
            if (!isset($input[$k])) {
                continue;
            }
            if (!is_scalar($input[$k]) || !preg_match('/^\d+(\.0+)?$/', trim($input[$k] . ''))) {
                return Response::generate(-1, 'Upload param ' . $k . ' invalid ( ' . self::uploadParamDebugText($input[$k]) . ' )');
            }
            $input[$k] = intval($input[$k]);
        }
        if (!empty($input['md5'])) {
            $file['md5'] = $input['md5'];
            if (strlen($file['md5']) != 32 || !preg_match('/^[A-Za-z0-9]+$/', $file['md5'])) {
                return Response::generate(-1, 'md5 invalid');
            }
        }
        $file = array_merge($file, $extra);
        // 输入校验通过后再初始化存储驱动，非法请求不会触发云端驱动初始化
        if (empty($option)) {
            $option = self::getConfigOption();
        }
        $option = self::prepareOption($option);
        $storage = self::storage($option);
        if (empty(self::$config[$category])) {
            return Response::generate(-2, 'Unknown category : ' . $category);
        }
        $config = self::$config[$category];
        if (strlen($file['name']) > $storage->repository()->maxFilenameByte()) {
            return Response::generate(-3, 'Filename too long ( max 200 bytes )');
        }
        $extension = FileUtil::extension($file['name']);
        if (!in_array($extension, $config['extensions'])) {
            if ($config['extensions'][0] != '*') {
                return Response::generate(-4, L('File extension %s not permit', $extension));
            }
        }
        if ($file['size'] > $config['maxSize']) {
            return Response::generate(-5, L('File Size Limit %s', FileUtil::formatByte($config['maxSize'])));
        }
        $callParam = [
            'category' => $category,
            'file' => $file,
            'param' => $param,
        ];
        if ('uploadEnd' == $action) {
            return $storage->multiPartUploadEnd($callParam);
        }
        if ('init' == $action) {
            return $storage->multiPartInit($callParam);
        }
        $callParam['input'] = $input;
        $ret = $storage->multiPartUpload($callParam);
        if (!empty($ret['data']['finished'])) {
            if (Input::get('fullPath', false)) {
                $ret['data']['preview'] = PathUtil::fixFull($ret['data']['preview']);
            }
        }
        return $ret;
    }

    /**
     * 上传参数调试文本：标量截断输出，非标量只给类型，便于定位是哪个客户端传了非法值
     * @param mixed $value
     * @return string
     */
    private static function uploadParamDebugText($value)
    {
        if (!is_scalar($value)) {
            return gettype($value);
        }
        $text = $value . '';
        if (function_exists('mb_substr') && mb_strlen($text, 'UTF-8') > 32) {
            return mb_substr($text, 0, 32, 'UTF-8') . '...';
        }
        if (strlen($text) > 32) {
            return substr($text, 0, 32) . '...';
        }
        return $text;
    }

    /**
     * 上传文件到 data_temp
     * @param $category string
     * @param $filename string
     * @param $content string
     * @param $option array|null
     * @param $param array
     * @return array
     */
    public static function uploadToTemp($category, $filename, $content, $option = null, $param = [])
    {
        if (empty($option)) {
            $option = self::getConfigOption();
        }
        if (!isset($param['eventOpt'])) {
            $param['eventOpt'] = [];
        }
        $option = self::prepareOption($option);
        $storage = self::storage($option);
        if (empty(self::$config[$category])) {
            return Response::generate(-1, 'Unknown category : ' . $category);
        }
        $config = self::$config[$category];
        if (empty($filename)) {
            return Response::generate(-2, 'Filename empty');
        }
        if (strlen($filename) > $storage->repository()->maxFilenameByte()) {
            return Response::generate(-3, 'Filename too long ( max 200 bytes )');
        }
        $extension = FileUtil::extension($filename);
        if (!in_array($extension, $config['extensions'])) {
            return Response::generate(-4, L('File extension %s not permit', $extension));
        }
        $content = self::sanitizeDataContent($extension, $content);
        $size = strlen($content);
        if ($size == 0) {
            return Response::generate(-5, 'File content empty');
        }
        if ($size > $config['maxSize']) {
            return Response::generate(-5, L('File Size Limit %s', FileUtil::formatByte($config['maxSize'])));
        }
        $updateTimestamp = time();
        $retry = 0;
        do {
            $path = strtolower(Str::random(32)) . '.' . $extension;
            $fullPath = AbstractDataStorage::DATA_TEMP . '/' . $category . '/' . $path;
        } while ($retry++ < 10 && $storage->has($fullPath));
        if ($retry >= 10) {
            return Response::generate(-7, 'Upload fail');
        }
        $storage->put($fullPath, $content);
        DataFileUploadedEvent::fire($storage->driverName(), $category, $fullPath, $param['eventOpt']);
        $dataTemp = $storage->repository()->addTemp($category, $path, $filename, $size);
        $path = config('data.baseUrl', '/') . AbstractDataStorage::DATA_TEMP . '/' . $dataTemp['category'] . '/' . $dataTemp['path'];
        $fullPath = $path;
        if (!empty($option['domain'])) {
            $fullPath = $option['domain'] . $path;
        }
        return Response::generateSuccessData([
            'dataTemp' => $dataTemp,
            'path' => $path,
            'fullPath' => $fullPath,
        ]);
    }

    /**
     * 上传文件内容
     * @param string $category
     * @param string $url
     * @param array|null $option
     * @param array $param
     * @return array [
     *     'data' => [
     *         'id'=>1,
     *     ],
     *     'path' => 'data/image/xxxx.jpg',
     *     'fullPath' => '/data/image/xxx.jpg',
     * ]
     */
    public static function uploadFromUrl($category, $url, $option = null, $param = [])
    {
        $ext = PathUtil::getExtention($url, 'bin');
        $filename = PathUtil::getFilename($url);
        $localPath = FileUtil::savePathToLocalTemp($url, $ext, true);
        if (empty($localPath)) {
            return Response::generate(-1, 'Download file fail');
        }
        $ret = DataManager::upload($category, $filename, file_get_contents($localPath), $option, $param);
        if (Response::isError($ret)) {
            return $ret;
        }
        FileUtil::safeCleanLocalTemp($localPath);
        return $ret;
    }

    /**
     * 按扩展名清洗文件内容（目前仅处理 SVG 存储型 XSS）
     * @param $extension string
     * @param $content string
     * @return string
     */
    private static function sanitizeDataContent($extension, $content)
    {
        if ('svg' === strtolower($extension)) {
            return FileUtil::sanitizeSvg($content);
        }
        return $content;
    }

    /**
     * 上传文件内容
     * @param string $category 上传分类，image｜file｜video｜audio
     * @param string $filename 包含后缀名的文件
     * @param string $content 文件内容
     * @param array $option 上传驱动配置
     * @param array $param 其他参数
     * @return array [
     *   'code' => 0,
     *   'msg' => 'ok',
     *   'data' => [
     *     'uploadStatus' => 'success|exists',
     *     'data' => [
     *         'id'=>1,
     *     ],
     *     'path' => 'data/image/xxxx.jpg',
     *     'fullPath' => '/data/image/xxx.jpg',
     *   ]
     * ]
     */
    public static function upload($category, $filename, $content, $option = null, $param = [])
    {
        if (empty($option)) {
            $option = self::getConfigOption();
        }
        if (!isset($param['eventOpt'])) {
            $param['eventOpt'] = [];
        }
        if (!isset($param['md5DuplicateIgnore'])) {
            $param['md5DuplicateIgnore'] = false;
        }
        $option = self::prepareOption($option);
        $storage = self::storage($option);
        if (empty(self::$config[$category])) {
            return Response::generate(-1, 'Unknown category : ' . $category);
        }

        $config = self::$config[$category];
        if (empty($filename)) {
            return Response::generate(-2, 'Filename empty');
        }
        if (strlen($filename) > $storage->repository()->maxFilenameByte()) {
            return Response::generate(-3, 'Filename too long ( max 200 bytes )');
        }
        $extension = FileUtil::extension($filename);
        if (!in_array($extension, $config['extensions'])) {
            return Response::generate(-4, L('File extension %s not permit', $extension));
        }
        $content = self::sanitizeDataContent($extension, $content);
        $size = strlen($content);
        if ($size == 0) {
            return Response::generate(-5, 'File content empty');
        }
        if ($size > $config['maxSize']) {
            return Response::generate(-5, L('File Size Limit %s', FileUtil::formatByte($config['maxSize'])));
        }

        $md5 = md5($content);

        if ($param['md5DuplicateIgnore']) {
            $data = $storage->repository()->getDataBy([
                'md5' => $md5,
                'category' => $category,
                'size' => $size,
            ]);
            if ($data) {
                return Response::generateSuccessData([
                    'uploadStatus' => 'exists',
                    'data' => $data,
                    'path' => config('data.baseUrl', '/') . AbstractDataStorage::DATA . '/' . $data['category'] . '/' . $data['path'],
                    'fullPath' => self::fixDataFull($data, $option),
                ]);
            }
        }

        $updateTimestamp = time();
        $retry = 0;
        do {
            $path = date('Y/m/d/', $updateTimestamp) . (time() % 86400) . '_' . strtolower(Str::random(4)) . '_' . mt_rand(1000, 9999) . '.' . $extension;
            $fullPath = AbstractDataStorage::DATA . '/' . $category . '/' . $path;
        } while ($retry++ < 10 && $storage->has($fullPath));
        if ($retry >= 10) {
            return Response::generate(-7, 'Upload fail');
        }
        $storage->put($fullPath, $content);
        DataFileUploadedEvent::fire($storage->driverName(), $category, $fullPath, $param['eventOpt']);
        $data = $storage->repository()->addData($category, $path, $filename, $size, $md5);
        $data = $storage->updateDriverDomain($data);
        $path = config('data.baseUrl', '/') . AbstractDataStorage::DATA . '/' . $data['category'] . '/' . $data['path'];
        $fullPath = $path;
        if (!empty($data['domain'])) {
            $fullPath = $data['domain'] . $path;
        } else {
            $fullPath = self::fixFull($fullPath);
        }
        return Response::generateSuccessData([
            'uploadStatus' => 'success',
            'data' => $data,
            'path' => $path,
            'fullPath' => $fullPath,
        ]);
    }

    /**
     * 根据TempData完整路径存储
     * @param $dataTempFullPath string
     * @param $option null
     * @return array
     * @throws \Exception
     */
    public static function storeTempDataByPath($dataTempFullPath, $option = null)
    {
        if (empty($option)) {
            $option = self::getConfigOption();
        }
        $option = self::prepareOption($option);
        $dataTempFullPath = trim($dataTempFullPath, '/');
        if (preg_match(AbstractDataStorage::PATTERN_DATA_TEMP, $dataTempFullPath, $mat)) {
            return self::storeTempData($mat[1], $mat[2], $option);
        }
        return Response::generate(-1, 'TempPath Invalid', null);
    }

    /**
     * 根据Category和TempData路径存储
     * @param $category
     * @param $tempPath
     * @param null $option
     * @return array
     * @throws \Exception
     */
    public static function storeTempData($category, $dataTempPath, $option = null)
    {
        if (empty($option)) {
            $option = self::getConfigOption();
        }
        $option = self::prepareOption($option);
        $storage = self::storage($option);
        $dataTemp = $storage->repository()->getTemp($category, $dataTempPath);
        if (empty($dataTemp)) {
            return Response::generate(-1, L('TempPathNotExists'));
        }
        $extension = FileUtil::extension($dataTemp['filename']);
        $updateTimestamp = time();
        $path = date('Y/m/d/', $updateTimestamp) . (time() % 86400) . '_' . strtolower(Str::random(4)) . '_' . mt_rand(1000, 9999) . '.' . $extension;
        $fullPath = AbstractDataStorage::DATA . '/' . $category . '/' . $path;

        $from = AbstractDataStorage::DATA_TEMP . '/' . $dataTemp['category'] . '/' . $dataTemp['path'];
        $to = AbstractDataStorage::DATA . '/' . $dataTemp['category'] . '/' . $path;

        if (!$storage->has($from)) {
            $storage->repository()->deleteTempById($dataTemp['id']);
            return Response::generate(-3, L('TempPathNotExists'));
        }

        // 临时文件转正式存储前清洗 SVG 内容，避免分片上传绕过清洗
        if ('svg' === strtolower($extension)) {
            $rawContent = $storage->get($from);
            if (null !== $rawContent && false !== $rawContent) {
                $cleanContent = self::sanitizeDataContent($extension, $rawContent);
                if ($cleanContent !== $rawContent) {
                    $storage->put($from, $cleanContent);
                    $dataTemp['size'] = strlen($cleanContent);
                    $dataTemp['md5'] = md5($cleanContent);
                }
            }
        }

        $storage->move($from, $to);
        $data = $storage->repository()->addData($dataTemp['category'], $path, $dataTemp['filename'], $dataTemp['size'], $dataTemp['md5']);
        $data = $storage->updateDriverDomain($data);
        $storage->repository()->deleteTempById($dataTemp['id']);
        $path = config('data.baseUrl', '/') . AbstractDataStorage::DATA . '/' . $data['category'] . '/' . $data['path'];
        $fullPath = $path;
        if (!empty($data['domain'])) {
            $fullPath = $data['domain'] . $path;
        } else {
            $fullPath = self::fixFull($fullPath);
        }
        return Response::generate(0, 'ok', [
            'data' => $data,
            'path' => $path,
            'fullPath' => $fullPath,
        ]);
    }

    /**
     * 根据ID删除文件（包括物理软删除）
     * @param $id
     * @param $option
     */
    public static function deleteById($id, $option = null)
    {
        if (empty($option)) {
            $option = self::getConfigOption();
        }
        $option = self::prepareOption($option);
        $storage = self::storage($option);
        $data = $storage->repository()->getDataById($id);
        if (empty($data)) {
            return null;
        }
        if ($storage->driverName() != $data['driver']) {
            $driver = DataStorageType::toDriverName($data['driver']);
            $option = self::prepareOption(['driver' => $driver]);
            $storage = self::storage($option);
        }
        $file = AbstractDataStorage::DATA . '/' . $data['category'] . '/' . $data['path'];
        if ($storage->has($file)) {
            $storage->softDelete($file);
        }
        $storage->repository()->deleteDataById($id);
        DataDeletedEvent::fire($data);
        return $data;
    }

    /**
     * 根据路径删除
     *
     * @param $path string
     * @throws \Exception
     * @example path
     * /data/image/2025/03/03/6207_ohau_8710.wav
     * http://example.com/data/image/2025/03/03/6207_ohau_8710.wav
     * http://example.com/data/image/2025/03/03/6207_ohau_8710.wav?foo=bar
     */
    public static function deleteByPath($path)
    {
        if (empty($option)) {
            $option = self::getConfigOption();
        }
        $option = self::prepareOption($option);
        $storage = self::storage($option);
        $data = $storage->repository()->getDataByPath($path);
        if (empty($data)) {
            return null;
        }
        if ($storage->driverName() != $data['driver']) {
            $driver = DataStorageType::toDriverName($data['driver']);
            $option = self::prepareOption(['driver' => $driver]);
            $storage = self::storage($option);
        }
        $file = AbstractDataStorage::DATA . '/' . $data['category'] . '/' . $data['path'];
        if ($storage->has($file)) {
            $storage->softDelete($file);
        }
        $storage->repository()->deleteDataById($data['id']);
        DataDeletedEvent::fire($data);
        return $data;
    }

    /**
     * 根据路径查找数据
     *
     * @param $path
     * @param null $option
     * @throws \Exception
     */
    public static function getByPath($path, $option = null)
    {
        if (empty($option)) {
            $option = self::getConfigOption();
        }
        $option = self::prepareOption($option);
        $storage = self::storage($option);
        $data = $storage->repository()->getDataByPath($path);
        if (empty($data)) {
            return Response::generateError('Data Not Found');
        }
        $path = AbstractDataStorage::DATA . '/' . $data['category'] . '/' . $data['path'];
        return Response::generateSuccessData([
            'path' => $path,
            'data' => $data,
            'fullPath' => self::fixDataFull($data),
        ]);
    }

    public static function getById($id, $option = null)
    {
        if (empty($option)) {
            $option = self::getConfigOption();
        }
        $option = self::prepareOption($option);
        $storage = self::storage($option);
        $data = $storage->repository()->getDataById($id);
        if (empty($data)) {
            return Response::generateError('Data Not Found');
        }
        $path = AbstractDataStorage::DATA . '/' . $data['category'] . '/' . $data['path'];
        return Response::generateSuccessData([
            'path' => $path,
            'data' => $data,
            'fullPath' => self::fixDataFull($data),
        ]);
    }

    /**
     * 根据路径删除DataTemp
     *
     * @param $tempDataPath
     * @param null $option
     * @throws \Exception
     */
    public static function deleteDataTempByPath($tempDataPath, $option = null)
    {
        if (empty($option)) {
            $option = self::getConfigOption();
        }
        $option = self::prepareOption($option);
        $storage = self::storage($option);
        $dataTemp = $storage->repository()->getTempByPath($tempDataPath);
        if (empty($dataTemp)) return;
        try {
            $storage->delete($tempDataPath);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $ignores = [
                'File not found at path'
            ];
            $found = false;
            foreach ($ignores as $ignore) {
                if (strstr($msg, $ignore)) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                throw $e;
            }
        }
        $storage->repository()->deleteTempById($dataTemp['id']);
    }


    /**
     * 解析已上传文件路径
     *
     * @param $url string 文件路径 /data/xxxxxxx.xxx http://xxx.com/data/xxxxxxx.xxx
     * @return array
     */
    public static function parseDataUrl($url)
    {
        if (preg_match(AbstractDataStorage::PATTERN_DATA_STRING, $url, $mat)) {
            return Response::generateSuccessData([
                'url' => $mat[0],
                'category' => $mat[1],
                'path' => $mat[2],
            ]);
        }
        return Response::generateError('parse error');
    }

    /**
     * 准备文件到本地可用
     * @param $path string 文件路径 /data/xxxxxxx.xxx data_temp/xxxxxx.xxx /data_temp/xxxxxxx.xxx http://www.example.com/data/xxxxx.xxx
     * @param $option
     * @return array
     */
    public static function preparePathForLocal($path, $option = null)
    {
        if (empty($option)) {
            $option = self::getConfigOption();
        }
        $option = self::prepareOption($option);
        $storage = self::storage($option);
        $fileFullPath = $storage->getDriverFullPath($path);
        $localFile = FileUtil::savePathToLocalTemp($fileFullPath, '.' . FileUtil::extension($path));
        if (!file_exists($localFile)) {
            return Response::generate(-1, L('SaveFileError') . ' - ' . $path);
        }
        $base = public_path('');
        return Response::generate(0, null, [
            'path' => $localFile,
            'baseUrl' => ltrim(str_replace('\\', '/', substr($localFile, strlen($base))), '/\\'),
            'name' => basename($localFile),
        ]);
    }


    /**
     * 准备文件到本地可用（使用内网域名）
     * @param $path string 文件路径 /data/xxxxxxx.xxx /data_temp/xxxxxxx.xxx http://www.example.com/data/xxxxx.xxx
     * @param $option
     * @return array
     * @throws
     * @deprecated delete at 2024-04-25 使用 preparePathForLocal 即可
     */
    public static function preparePathInternalForLocal($path, $option = null)
    {
        if (empty($option)) {
            $option = self::getConfigOption();
        }
        $option = self::prepareOption($option);
        $storage = self::storage($option);
        $fileFullPath = $storage->getDriverFullPathInternal($path);
        $localFile = FileUtil::savePathToLocalTemp($fileFullPath, '.' . FileUtil::extension($path));
        if (!file_exists($localFile)) {
            return Response::generate(-1, L('SaveFileError') . ' - ' . $path);
        }
        $base = public_path('');
        return Response::generate(0, null, [
            'path' => $localFile,
            'baseUrl' => ltrim(str_replace('\\', '/', substr($localFile, strlen($base))), '/\\'),
            'name' => basename($localFile),
        ]);
    }

    public static function getDataTempFileContent($tempDataPath, $option = null)
    {
        if (empty($option)) {
            $option = self::getConfigOption();
        }
        $option = self::prepareOption($option);
        $storage = self::storage($option);
        $fileFullPath = $storage->getDriverFullPath($tempDataPath);
        $localFile = FileUtil::savePathToLocalTemp($fileFullPath, '.' . FileUtil::extension($tempDataPath));
        if (!file_exists($localFile)) {
            return null;
        }
        $content = file_get_contents($localFile);
        @unlink($localFile);
        return $content;
    }

    public static function isDataTemp($path)
    {
        return preg_match(AbstractDataStorage::PATTERN_DATA_TEMP, $path);
    }

    public static function fix($path, $option = null)
    {
        if (Str::startsWith($path, 'http://') || Str::startsWith($path, 'https://') || Str::startsWith($path, '//')) {
            return $path;
        }
        if (Str::startsWith($path, '/')) {
            $path = substr($path, 1);
        }
        if (empty($option)) {
            $option = self::getConfigOption();
        }
        $option = self::prepareOption($option);
        $storage = self::storage($option);
        return AssetsUtil::fix($storage->getDriverFullPath($path), false);
    }

    public static function fixFull($path, $option = null)
    {
        if (Str::startsWith($path, 'http://') || Str::startsWith($path, 'https://') || Str::startsWith($path, '//')) {
            return $path;
        }
        if (Str::startsWith($path, '/')) {
            $path = substr($path, 1);
        }
        if (empty($option)) {
            $option = self::getConfigOption();
        }
        $option = self::prepareOption($option);
        $storage = self::storage($option);
        return AssetsUtil::fixFull($storage->getDriverFullPath($path), false);
    }

    public static function fixDataFull($data, $option = null)
    {
        $path = join('/', [
            AbstractDataStorage::DATA,
            $data['category'],
            $data['path'],
        ]);
        $fullPath = $path;
        if (!empty($data['domain'])) {
            $fullPath = $data['domain'] . '/' . $path;
        } else {
            $fullPath = self::fixFull($fullPath);
        }
        return $fullPath;
    }

    public static function mergeData(&$list, $key = 'dataId', $mergeDataKey = '_data')
    {
        if (empty($list)) {
            return;
        }
        $dataIds = ArrayUtil::flatItemsByKey($list, $key);
        if (empty($dataIds)) {
            return;
        }
        $data = Data::whereIn('id', $dataIds)->get()->toArray();
        $dataMap = ArrayUtil::mapItemsByKey($data, 'id');
        foreach ($list as &$item) {
            $dataId = $item[$key];
            if (isset($dataMap[$dataId])) {
                $item[$mergeDataKey] = $dataMap[$dataId];
            } else {
                $item[$mergeDataKey] = null;
            }
        }
    }

    public static function mergeDataUrl(&$list, $key = 'dataId', $mergeDataKey = '_dataUrl')
    {
        if (empty($list)) {
            return;
        }
        self::mergeData($list, $key, $mergeDataKey);
        foreach ($list as &$item) {
            if (empty($item[$mergeDataKey])) {
                continue;
            }
            $item[$mergeDataKey] = self::fixDataFull($item[$mergeDataKey]);
        }
    }

}
