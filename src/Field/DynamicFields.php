<?php


namespace ModStart\Field;


use ModStart\Core\Exception\BizException;
use ModStart\Core\Util\StrUtil;

/**
 * 动态自选
 * Class ComplexFields
 * @package ModStart\Field
 */
class DynamicFields extends AbstractField
{
    protected $value = [];
    protected $width = 300;
    protected $listable = false;

    protected function setup()
    {
        // $this->addVariables([
        //
        // ]);
    }

    public function unserializeValue($value, AbstractField $field)
    {
        $value = @json_decode($value, true);
        if (empty($value)) {
            $value = [];
        }
        return $value;
    }

    public function serializeValue($value, $model)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    public function prepareInput($value, $model)
    {
        $value = @json_decode($value, true);
        if (empty($value)) {
            $value = [];
        }
        $nameMap = [];
        foreach ($value as $i => $v) {
            $no = $i + 1;
            if (isset($v['name'])) {
                $value[$i]['name'] = trim(StrUtil::filterSpecialChars($v['name']));
            }
            if (isset($v['title'])) {
                $value[$i]['title'] = trim(StrUtil::filterSpecialChars($v['title']));
            }
            $prefix = "{$this->label} 第{$no}个字段";
            BizException::throwsIf("$prefix 标题不能为空", empty($v['title']));
            BizException::throwsIf("$prefix 标识不能为空", empty($v['name']));
            BizException::throwsIf("$prefix 标识格式不正确", !preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $v['name']));
            BizException::throwsIf("$prefix 标识重复", isset($nameMap[$v['name']]));
            $nameMap[$v['name']] = true;
        }
        return $value;
    }
}
