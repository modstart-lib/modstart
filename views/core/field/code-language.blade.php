<input type="hidden"
       {{$readonly?'readonly':''}}
       class="form"
       id="{{$id}}Input"
       name="{{$name}}"
       value="{{null===$value?(null===$defaultValue?'':$defaultValue):$value}}"
       @if($styleFormField) style="{!! $styleFormField !!}" @endif
/>
<div class="pb-code-editor">
    <div id="{{$id}}Editor" style="width:100%;height:{{$editorHeight}};">{{null===$value?(null===$defaultValue?'':$defaultValue):$value}}</div>
</div>
<style>
    .pb-code-editor {
        border: 0.0625rem solid var(--color-body-line);
        border-radius: var(--size-radius-sm);
        overflow: hidden;
    }
    .pb-code-editor .ace_editor {
        border: none;
        border-radius: 0;
    }
</style>
{!! \ModStart\ModStart::js('asset/vendor/ace/ace.js') !!}
<script>
    $(function(){
        var editor = ace.edit("{{$id}}Editor");
        editor.session.setMode("ace/mode/{{$language}}");
        editor.setOptions({
            minLines: 3,
            maxLines: 20,
            fontSize: "var(--font-size)",
            // the default 80-column rule draws a vertical line that reads like a
            // divider splitting the editor in half
            showPrintMargin: false
        });
        editor.session.on('change',function(){
            $('#{{$id}}Input').val(editor.session.getValue());
        })
        $('#{{$id}}Input').data('editor',editor);
        //editor.setReadOnly(true);
    });
</script>
