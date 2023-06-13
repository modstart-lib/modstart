<div class="line">
    <div class="label">
        {!! in_array('required',$rules)?'<span class="ub-text-danger ub-text-bold">*</span>':'' !!}
        @if($tip)
            <a class="ub-text-muted" href="javascript:;" data-tip-popover="{{$tip}}"><i
                    class="iconfont icon-warning"></i></a>
        @endif
        {{$label}}
    </div>
    <div class="field">
        <div id="{{$id}}Input">
            <input type="hidden" name="{{$name}}" :value="jsonValue"/>
            <table class="ub-table border">
                <thead>
                <tr>
                    <th width="100">标题</th>
                    <th width="150">标识</th>
                    <th width="100">类型</th>
                    <th>参数</th>
                    <th width="100">&nbsp;</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="(v,vIndex) in value">
                    <td>
                        <el-input v-model="value[vIndex].title" plaleholder="中文提示"/>
                    </td>
                    <td>
                        <el-input v-model="value[vIndex].name" placeholder="字母数字下划线"/>
                    </td>
                    <td>
                        <el-select v-model="value[vIndex].type" placeholder="请选择">
                            <el-option label="文本" value="text"></el-option>
                            <el-option label="开关" value="switch"></el-option>
                            {{--                            <el-option label="图标" value="icon"></el-option>--}}
                            {{--                            <el-option label="数字" value="number"></el-option>--}}
                        </el-select>
                    </td>
                    <td>
                        <div v-if="value[vIndex].type==='text'">
                            <div>
                                <el-input placeholder="" v-model="value[vIndex].defaultValue" disabled>
                                    <template slot="prepend">默认值</template>
                                </el-input>
                            </div>
                        </div>
                        <div class="tw-mt-2">
                            <el-input placeholder="" v-model="value[vIndex].placeholder">
                                <template slot="prepend">提示</template>
                            </el-input>
                        </div>
                        <div class="tw-mt-2">
                            <el-checkbox v-model="value[vIndex].isRequired"><span class="tw-text-sm">必填</span></el-checkbox>
                        </div>
                    </td>
                    {{--                        @if(0)--}}
                    {{--                        @foreach($fields as $f)--}}
                    {{--                            <td>--}}
                    {{--                                @if($f['type']=='switch')--}}
                    {{--                                    <el-switch v-model="value[vIndex]['{{$f['name']}}']" />--}}
                    {{--                                @elseif($f['type']=='text')--}}
                    {{--                                    <el-input v-model="value[vIndex]['{{$f['name']}}']" placeholder="{{empty($f['placeholder'])?'':$f['placeholder']}}" size="mini" />--}}
                    {{--                                @elseif($f['type']=='icon')--}}
                    {{--                                    <icon-input v-model="value[vIndex]['{{$f['name']}}']" :icons="icons" :inline="true" />--}}
                    {{--                                @elseif($f['type']=='number')--}}
                    {{--                                    <el-input-number v-model="value[vIndex]['{{$f['name']}}']" size="mini" />--}}
                    {{--                                @endif--}}
                    {{--                            </td>--}}
                    {{--                        @endforeach--}}
                    {{--                        @endif--}}
                    <td>
                        <a href="javascript:;" class="ub-text-muted" @click="value.splice(vIndex,1)">
                            <i class="iconfont icon-trash"></i>
                        </a>
                    </td>
                </tr>
                </tbody>
                <tbody>
                <tr>
                    <td colspan="5">
                        <a href="javascript:;" class="ub-text-muted" @click="doValueAdd">
                            <i class="iconfont icon-plus"></i>
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
{{ \ModStart\ModStart::js('asset/entry/basic.js') }}
<script>
    $(function () {
        var app = new Vue({
            el: '#{{$id}}Input',
            data: {
                value: {!! $value?json_encode($value):($defaultValue?json_encode($defaultValue):'[]') !!},
            },
            computed: {
                jsonValue: function () {
                    return JSON.stringify(this.value);
                }
            },
            methods: {
                doValueAdd() {
                    this.value.push({
                        name: '',
                        title: '',
                        type: 'text',
                        data: {
                            options: []
                        },
                        isRequired: false,
                        placeholder: '',
                        defaultValue: null
                    })
                }
            }
        });
    });
</script>
