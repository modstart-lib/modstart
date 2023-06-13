<?php


namespace ModStart\Field;


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
        return $value;
    }
}
