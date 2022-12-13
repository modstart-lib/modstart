<div class="line">
    <div class="label">
        {{$field['title']}}
    </div>
    <div class="field">
        @foreach($field['data']['option'] as $option)
            <div>
                <label class="tw-bg-white">
                    <input type="radio" name="{{$fieldName}}" value="{{$option}}" />
                    {{$option}}
                </label>
            </div>
        @endforeach
    </div>
</div>