<div class="line">
    <div class="label">
        {{$label}}:
    </div>
    <div class="field">
        @if(is_array($value))
            <div class="tw-bg-gray-100 tw-p-1 tw-rounded">
                <table class="ub-table mini" style="white-space:normal;word-break:break-all;">
                    @foreach($value as $k=>$v)
                        <tr>
                            <td width="50">{{$k}}</td>
                            <td>{{$v}}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @elseif(!empty($value))
            <pre style="margin:0;overflow:auto;">{{json_encode($value,JSON_PRETTY_PRINT)}}</pre>
        @else
            <span class="ub-text-muted">-</span>
        @endif

    </div>
</div>
