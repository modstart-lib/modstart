<div class="line" data-field="{{$name}}" id="{{$id}}">
    <div class="label">
        {!! in_array('required',$rules)?'<span class="ub-text-danger ub-text-bold">*</span>':'' !!}
        @if($tip)
            <a class="ub-text-muted" href="javascript:;" data-tip-popover="{{$tip}}"><i class="iconfont icon-warning"></i></a>
        @endif
        {{$label}}
    </div>
    <div class="field">
        <div class="ub-color" id="{{$id}}ColorWrap">
            <input type="text"
                   {{$readonly?'readonly':''}}
                   class="form ub-color__input"
                   style="width:8em;"
                   name="{{$name}}"
                   placeholder="{{$placeholder?$placeholder:'#RRGGBB'}}"
                   value="{{null===$value?$defaultValue:$value}}" />
            <div id="{{$id}}Color" class="ub-color__trigger"></div>
        </div>
        @if(!empty($help))
            <div class="help">{!! $help !!}</div>
        @endif
    </div>
</div>
<style>
    .ub-color {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    /* the input itself is the swatch: it shows the hex code on the picked color */
    .ub-color__input {
        transition: background-color 0.15s var(--ease-out), color 0.15s var(--ease-out);
    }

    /* text contrast follows the swatch luminance (set by script) */
    .ub-color__input[data-tone="light"] {
        color: var(--color-ink-bg);
    }

    .ub-color__input[data-tone="dark"] {
        color: var(--color-text-reverse);
    }

    /* the trigger stays a plain white button on every swatch, so the chevron is
       always readable and does not repeat the color shown in the input */
    .ub-color__trigger .layui-colorpicker {
        background-color: var(--color-content-bg) !important;
    }

    /* layui paints the picked color inline on the trigger span; with the color
       moved into the input the span is only an icon box, so drop its inner
       border as well (it read as a second border inside the white button) */
    .ub-color__trigger .layui-colorpicker-trigger-span {
        background-color: transparent !important;
        border: 0;
    }

    .ub-color__trigger .layui-colorpicker-trigger-i {
        color: var(--color-tertiary);
        /* the adapt layer adds a dark halo for chevrons drawn on a swatch */
        text-shadow: none;
    }

    .ub-color__trigger .layui-colorpicker:hover .layui-colorpicker-trigger-i {
        color: var(--color-primary);
    }
</style>
<script>
    $(function () {
        var $field = $('#{{$id}}');
        var $input = $field.find('[name={{$name}}]');

        // normalize #abc / abc / #aabbcc / rgb(…) to #AABBCC
        function toHex(value) {
            value = String(value || '').trim();
            if (!value) {
                return '';
            }
            var short = value.match(/^#?([0-9a-f])([0-9a-f])([0-9a-f])$/i);
            if (short) {
                return ('#' + short[1] + short[1] + short[2] + short[2] + short[3] + short[3]).toUpperCase();
            }
            var full = value.match(/^#?([0-9a-f]{6})$/i);
            if (full) {
                return ('#' + full[1]).toUpperCase();
            }
            var rgb = value.match(/^rgba?\(\s*(\d+)[,\s]+(\d+)[,\s]+(\d+)/i);
            if (rgb) {
                var part = function (n) {
                    var s = parseInt(n, 10).toString(16);
                    return s.length === 1 ? '0' + s : s;
                };
                return ('#' + part(rgb[1]) + part(rgb[2]) + part(rgb[3])).toUpperCase();
            }
            return '';
        }

        // relative luminance, used to keep the hex code readable on any swatch
        function luminance(hex) {
            var channel = function (offset) {
                var c = parseInt(hex.substr(offset, 2), 16) / 255;
                return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
            };
            return 0.2126 * channel(1) + 0.7152 * channel(3) + 0.0722 * channel(5);
        }

        // the trigger is a picker button in this field: keep the down chevron in
        // every state (layui swaps in a close icon when no color is set, which
        // reads like a "clear" button on an empty field)
        function syncTriggerIcon() {
            $field.find('.layui-colorpicker-trigger-i')
                .removeClass('layui-icon-close')
                .addClass('layui-icon-down');
        }

        function paint() {
            var hex = toHex($input.val());
            syncTriggerIcon();
            if (!hex) {
                $input.css('backgroundColor', '').removeAttr('data-tone');
                return;
            }
            $input
                .css('backgroundColor', hex)
                .attr('data-tone', luminance(hex) > 0.45 ? 'light' : 'dark');
        }

        $input.on('input', paint);
        $input.on('change blur', function () {
            var hex = toHex($input.val());
            if (hex && hex !== $input.val()) {
                $input.val(hex);
            }
            paint();
        });
        paint();

        layui.use('colorpicker', function () {
            layui.colorpicker.render({
                elem: '#{{$id}}Color',
                color: toHex($input.val()) || '',
                format: 'hex',
                done: function (color) {
                    $input.val(toHex(color) || color);
                    paint();
                }
            });
            syncTriggerIcon();
        });
    });
</script>
