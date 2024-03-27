<?php

namespace ModStart\Widget\Traits;

use ModStart\Core\Util\ReUtil;
use ModStart\ModStart;

trait HasVueFileTrait
{
    public function content()
    {
        $reflector = new \ReflectionClass(get_class($this));
        $filePath = $reflector->getFileName();
        $filePath = preg_replace('/\.php$/', '.vue', $filePath);
        if (!file_exists($filePath)) {
            return '<div class="ub-alert danger">Vue file not found: ' . $filePath . '</div>';
        }
        $content = file_get_contents($filePath);
        $vueScript = trim(ReUtil::group1('/<script>([\s\S]+)<\/script>/', $content));
        $vueTemplate = trim(ReUtil::group1('/<template>([\s\S]+)<\/template>/', $content));
        if (empty($vueScript)) {
            return '<div class="ub-alert danger">Vue script parse fail: ' . $filePath . '</div>';
        }
        if (empty($vueTemplate)) {
            return '<div class="ub-alert danger">Vue template parse fail: ' . $filePath . '</div>';
        }
        $vueScript = preg_replace('/export default/', 'let _widget = ', $vueScript) . ';';

        ModStart::js([
            'asset/vendor/vue.js',
            'asset/vendor/element-ui/index.js',
        ]);
        ModStart::script(join('', [
            "Vue.use(ELEMENT, {size: 'mini', zIndex: 3000});",
            $vueScript,
            "_widget.el = '#{$this->id}';",
            "new Vue(_widget);",
        ]));
        return $vueTemplate;
    }
}
