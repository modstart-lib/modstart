<div class="line">
    <div class="label">
        {!! str_contains($rules,'required')?'<span class="ub-text-danger ub-text-bold">*</span>':'' !!}
        @if(!empty($label))
            {{$label}}:
        @endif
    </div>
    <div class="field">
        <div class="value">{{$value}}</div>
        <input type="hidden" name="{{$name}}" value="{{$value}}"/>
        @if(!empty($help))
            <div class="help">{!! $help !!}</div>
        @endif
    </div>
</div>
