<?php

namespace ModStart\Admin\Widget;

use ModStart\Core\Util\RenderUtil;
use ModStart\ModStart;
use ModStart\Widget\AbstractWidget;

class DashboardItem extends AbstractWidget
{
    private $type = 1;
    private $color;
    private $icon;
    private $title;
    private $value;
    private $param;


    public static function makeTitleDataList($icon, $title, $dataList, $color = null, $param = [])
    {
        // Keep $color optional and null by default: the view falls back to the
        // theme primary accent, so all dashboard cards stay visually consistent
        // instead of each one picking a random saturated color.
        $item = new DashboardItem();
        $item->icon = $icon;
        $item->title = $title;
        $item->value = $dataList;
        $item->color = $color;
        $item->param = $param;
        $item->type = 1;
        return $item;
    }

    /**
     * Numbers get thousands separators; other values (e.g. "50M") stay untouched.
     * @param mixed $value
     * @return string
     */
    public static function formatValue($value)
    {
        if (is_int($value) || is_float($value)) {
            return number_format((float)$value);
        }
        if (is_string($value) && preg_match('/^\d+$/', $value)) {
            return number_format((float)$value);
        }
        return $value;
    }

    /**
     * Percentage deltas: negative values are shown as a downward (danger) change.
     * @param string $delta
     * @return string
     */
    public static function deltaTone($delta)
    {
        if (strlen($delta) > 0 && substr($delta, 0, 1) === '-') {
            return 'var(--color-danger)';
        }
        return 'var(--color-success)';
    }

    public function render()
    {
        ModStart::js('asset/common/countUp.js');
        $viewData = [
            'icon' => $this->icon,
            'title' => $this->title,
            'value' => $this->value,
            'color' => $this->color,
            'param' => $this->param,
        ];
        switch ($this->type) {
            case 1:
                return RenderUtil::view('modstart::admin.widget.dashboardItem', $viewData);
        }
        return 'Unknown type';
    }

}
