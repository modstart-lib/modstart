<div class="line" data-field="{{$name}}" id="{{$id}}">
    <div class="label">
        {!! in_array('required',$rules)?'<span class="ub-text-danger ub-text-bold">*</span>':'' !!}
        @if($tip)
            <a class="ub-text-muted" href="javascript:;" data-tip-popover="{{$tip}}"><i class="iconfont icon-warning"></i></a>
        @endif
        {{$label}}
    </div>
    <div class="field">
        <div class="ub-captcha-input">
            <input type="text"
                   {{$readonly?'readonly':''}}
                   class="form"
                   name="{{$name}}"
                   placeholder="{{$placeholder}}"
                   autocomplete="off"
                   value="{{null===$value?$defaultValue:$value}}" />
            <img data-captcha src="{{$url}}"
                 title="{{L('ClickToRefresh')}}"
                 onclick="this.src='{{$url}}?'+Math.random();"/>
        </div>
        @if(!empty($help))
            <div class="help">{!! $help !!}</div>
        @endif
    </div>
</div>
<style>
    .ub-captcha-input {
        position: relative;
        max-width: 14rem;
    }

    /* keep the text clear of the image that overlays the right side */
    .ub-captcha-input input.form {
        padding-right: 6.75rem;
    }

    /* the image sits inside the input's border as its right-hand suffix: same
       content height, same corner radius, nothing sticking out of the control */
    .ub-captcha-input img[data-captcha] {
        position: absolute;
        top: 0.0625rem;
        right: 0.0625rem;
        height: calc(100% - 0.125rem);
        box-sizing: border-box;
        border: 0;
        border-left: 0.0625rem solid var(--color-body-line);
        border-radius: 0 calc(var(--size-radius-sm) - 0.0625rem) calc(var(--size-radius-sm) - 0.0625rem) 0;
        background-color: var(--color-body-block-bg);
        cursor: pointer;
    }
</style>
