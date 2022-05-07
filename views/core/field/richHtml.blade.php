<div class="line" id="{{$id}}">
    <div class="label">
        {!! str_contains($rules,'required')?'<span class="ub-text-danger ub-text-bold">*</span>':'' !!}
        {{$label}}:
    </div>
    <div class="field">
        <div>
            <textarea id="{{$id}}Editor" name="{{$name}}" style="height:0px;overflow:hidden;">{!! $value !!}</textarea>
        </div>
        @if(!empty($help))
            <div class="help">{!! $help !!}</div>
        @endif
    </div>
</div>
<script>
    $(function () {
        @if($editorMode=='simple')
        window.api.editor.simple('{{$id}}Editor', {
            server: "{{$server}}",
            ready: function () {
                // console.log('ready');
            }
        }, {topOffset: 0});
        @else
        window.api.editor.basic('{{$id}}Editor', {
            server: "{{$server}}",
            ready: function () {
                // console.log('ready');
            }
        }, {topOffset: 0});
        @endif
    });
</script>
