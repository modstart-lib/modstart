<?php


namespace ModStart\Field;


use ModStart\Core\Util\ConvertUtil;
use ModStart\Core\Util\SerializeUtil;
use ModStart\ModStart;

class Images extends AbstractField
{
    protected $width = 80;

    protected function setup()
    {
        ModStart::lang(['CompressingImage']);
        $this->addVariables([
            'server' => modstart_admin_url('data/file_manager/image'),
        ]);
    }

    public function server($server)
    {
        $this->addVariables(['server' => $server]);
        return $this;
    }

    /**
     * 上传频道（业务场景标记，如 ad 广告图、content 内容图）
     * 声明后上传上下文会带上 channel，供水印等上传后处理能力按频道过滤
     * @param string $channel
     * @return $this
     */
    public function channel($channel)
    {
        $server = $this->getVariable('server');
        $server .= (false === strpos($server, '?') ? '?' : '&') . 'channel=' . urlencode($channel);
        $this->setVariable('server', $server);
        return $this;
    }

    public function unserializeValue($value, AbstractField $field)
    {
        if (null === $value) {
            return $value;
        }
        return ConvertUtil::toArray($value);
    }

    public function serializeValue($value, $model)
    {
        return SerializeUtil::jsonEncode($value);
    }

    public function prepareInput($value, $model)
    {
        return ConvertUtil::toArray($value);
    }
}
