<div class="line" data-field="{{$name}}" id="{{$id}}">
    <div class="label">
        {!! in_array('required',$rules)?'<span class="ub-text-danger ub-text-bold">*</span>':'' !!}
        @if($tip)
            <a class="ub-text-muted" href="javascript:;" data-tip-popover="{{$tip}}"><i class="iconfont icon-warning"></i></a>
        @endif
        {{$label}}
    </div>
    <div class="field">
        <input type="hidden"
               {{$readonly?'readonly':''}}
               class="form"
               data-value-holder
               name="{{$name}}"
               placeholder="{{$placeholder}}"
               value="{{null===$value?$defaultValue:$value}}"/>
        <div class="ub-file-selector" style="border:0.0625rem solid var(--color-body-line);position:relative;margin-bottom:0.625rem;border-radius:var(--size-radius-sm);padding:0 2.5rem 0 0.625rem;display:inline-block;min-height:var(--size-control-height);line-height:calc(var(--size-control-height) - 0.125rem);">
            <div data-value></div>
            <a data-close href="javascript:;" style="position:absolute;right:0;top:0;display:inline-block;line-height:var(--size-control-height);width:1.25rem;text-align:center;color:var(--color-tertiary);"><i class="iconfont icon-close"></i></a>
        </div>
        <div id="{{$id}}Uploader" class="ub-upload-trigger"></div>
        @if(!empty($help))
            <div class="help">{!! $help !!}</div>
        @endif
    </div>
</div>
<script>
    $(function () {
        var $field = $('#{{$id}}');
        var $input = $field.find('input');
        var $selector = $field.find('.ub-file-selector');
        var fileValue = $input.val();
        var fileName = fileValue;
        var render = function(){
            if(!fileValue){
                $selector.hide();
                return;
            }
            $selector.find('[data-value]').html(fileName);
            $field.find('[data-value-holder]').val(fileValue);
            $selector.show();
        };
        render();
        $selector.on('click','[data-close]',function(){
            fileValue = '';
            fileName = '';
            render();
            return false;
        });
        MS.uploadButton('#{{$id}}Uploader', {
            text: '<div class="ub-upload-trigger-pick"><span class="iconfont icon-plus"></span> 上传</div>',
            server: "{{$server}}",
            extensions: {!! \ModStart\Core\Util\SerializeUtil::jsonEncode(join(',',config('data.upload.file.extensions'))) !!},
            sizeLimit: {!! \ModStart\Core\Util\SerializeUtil::jsonEncode(config('data.upload.file.maxSize')) !!},
            chunkSize: {!! \ModStart\Core\Util\SerializeUtil::jsonEncode(\ModStart\Core\Util\EnvUtil::env('uploadMaxSize')) !!},
            callback: function (file, me) {
                // console.log('file',file);
                fileValue = file.path;
                fileName = file.name;
                render();
            },
            finish: function () {
            }
        });
    });
</script>
