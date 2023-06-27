<template>
    <div>
        <div :id="id" style="width:100%;height:100px;">{{ currentData }}</div>
    </div>
</template>

<script>
import {FieldVModel} from "../lib/fields-config";

export default {
    name: "CodeEditor",
    mixins: [FieldVModel],
    props: {
        mode: {
            type: String,
            // 目前支持 json, css
            default: 'json',
        },
        maxHeight: {
            type: Number,
            default: -1
        }
    },
    data() {
        return {
            id: 'CodeEditor' + Math.random().toString(36).substr(2),
            editor: null,
        }
    },
    watch: {
        mode() {
            this.updateMode()
        }
    },
    mounted() {
        MS.util.loadScript(this.$url.cdn('asset/vendor/ace/ace.js'), () => {
            this.init()
        })
    },
    methods: {
        init() {
            if (!window.ace) {
                setTimeout(() => {
                    this.init()
                }, 100)
                return
            }

            const editor = window.ace.edit(this.id);
            // editor.setTheme("ace/theme/monokai");
            if (this.maxHeight < 0) {
                editor.setOptions({
                    maxLines: Infinity
                })
            }
            editor.session.on('change', () => {
                // console.log('CodeEditor.changed', editor.session.getValue());
                this.currentData = editor.session.getValue();
            })
            this.editor = editor
            this.updateMode()
        },
        updateMode() {
            if (!this.editor) {
                setTimeout(() => {
                    this.updateMode()
                }, 100)
                return
            }
            this.editor.session.setMode("ace/mode/" + this.mode);
        }
    }
}
</script>

<style scoped>

</style>
