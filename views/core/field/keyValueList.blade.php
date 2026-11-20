<div class="line" data-field="{{$name}}">
    <div class="label">
        {!! in_array('required',$rules)?'<span class="ub-text-danger ub-text-bold">*</span>':'' !!}
        @if($tip)
            <a class="ub-text-muted" href="javascript:;" data-tip-popover="{{$tip}}"><i class="iconfont icon-warning"></i></a>
        @endif
        {{$label}}
    </div>
    <div class="field">
        <div id="{{$id}}Input" v-cloak>
            <input type="hidden" name="{{$name}}" :value="jsonValue" />
            <table class="ub-table border border-all tw-bg-white ub-key-value-list">
                <thead>
                <tr>
                    <th width="200">{{$keyTitle}}</th>
                    <th>{{$valueTitle}}</th>
                    <th width="50">&nbsp;</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="(v,vIndex) in value">
                    <td>
                        <input type="text" v-model="value[vIndex].{{$keyLabel}}" placeholder="{{$keyPlaceholder}}" />
                    </td>
                    <td>
                        <input type="text" v-model="value[vIndex].{{$valueLabel}}" placeholder="{{$valuePlaceholder}}" />
                    </td>
                    <td class="tw-text-center">
                        <a href="javascript:;" class="ub-text-danger" @click="value.splice(vIndex,1)">
                            <i class="iconfont icon-trash"></i>
                        </a>
                    </td>
                </tr>
                </tbody>
                <tbody>
                <tr>
                    <td colspan="3">
                        <a href="javascript:;" class="ub-text-muted" @click="doValueAdd">
                            <i class="iconfont icon-plus"></i>
                            {{L('Add')}}
                        </a>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        @if(!empty($help))
            <div class="help">{!! $help !!}</div>
        @endif
    </div>
</div>
{{ \ModStart\ModStart::js('asset/vendor/vue.js') }}
{{ \ModStart\ModStart::js('asset/vendor/element-ui/index.js') }}
{{ \ModStart\ModStart::css('asset/vendor/element-ui/index.css') }}
<script>
    $(function () {
        var app = new Vue({
            el: '#{{$id}}Input',
            data: {
                value: {!! \ModStart\Core\Util\SerializeUtil::jsonEncode(null===$value?(null===$defaultValue?[]:$defaultValue):$value) !!}
            },
            computed:{
                jsonValue:function(){
                    return JSON.stringify(this.value);
                }
            },
            methods:{
                doValueAdd(){
                    this.value.push({ {{$keyLabel}} :'', {{$valueLabel}}:'' });
                }
            }
        });
    });
</script>
<style>
    /* 窄屏下键列 200px + 操作列 50px 会把值列压到只剩几十像素，
       这里让两列按比例分配，值仍可读可编辑 */
    @media (max-width: 50rem) {
        .ub-key-value-list th[width="200"] {
            width: 35% !important;
        }

        .ub-key-value-list th[width="50"] {
            width: 2.5rem !important;
        }

        .ub-key-value-list th,
        .ub-key-value-list td {
            padding-left: 0.375rem;
            padding-right: 0.375rem;
        }
    }
</style>
