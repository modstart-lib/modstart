function preg_quote(e,t){return(e+"").replace(new RegExp("[.\\\\+*?\\[\\^\\]$(){}=!<>|:\\"+(t||"")+"-]","g"),"\\$&")}function loadScript(e,t){var a=document.createElement("script");a.src=e,a.onload=function(){t&&t({isNew:!0})},document.getElementsByTagName("head")[0].appendChild(a)}var Formula={mode:"plain",latexeasy:null,init:function(){Formula.initMode(),Formula.initEvent(),Formula.initSubmit()},renderPlain:function(){var e=$("#preview"),t=$("#editor").val();t?(t=encodeURIComponent(t),t=editor.getOpt("formulaConfig").imageUrlTemplate.replace(/\{\}/,t),$("#previewImage").attr("src",t),e.show()):e.hide()},setValuePlain:function(e){$("#editor").val(e),Formula.renderPlain()},setValueLive:function(e){Formula.latexeasy?Formula.latexeasy.call("set.latex",{latex:e}):setTimeout(function(){Formula.setValueLive(e)},100)},initMode:function(){var e=editor.getOpt("formulaConfig"),e=("live"===e.editorMode?($("#liveEditor").attr("src",e.editorLiveServer+"/editor"),$("#modeLive").show(),Formula.mode="live"):($("#modePlain").show(),Formula.mode="plain"),editor.selection.getRange().getClosedNode());e&&null!==e.getAttribute("data-formula-image")&&(e=e.getAttribute("data-formula-image"))&&Formula.setValue(decodeURIComponent(e))},setValue:function(e){switch(Formula.mode){case"plain":Formula.setValuePlain(e);break;case"live":Formula.setValueLive(e)}},getValue:function(t){switch(Formula.mode){case"plain":t($.trim($("#editor").val()));break;case"live":Formula.latexeasy.call("get.latex",{},function(e){t(e.latex)})}},initEvent:function(){var e,t=null;switch(Formula.mode){case"plain":$("#editor").on("change keypress",function(){t&&clearTimeout(t),t=setTimeout(function(){Formula.renderPlain()},1e3)}),$("#inputDemo").on("click",function(){$("#editor").val("f(a) = \\frac{1}{2\\pi i} \\oint\\frac{f(z)}{z-a}dz"),Formula.renderPlain()});break;case"live":loadScript(editor.getOpt("formulaConfig").editorLiveServer+"/vendor/LatexEasyEditor/editor/sdk.js",function(){(e=new window.LatexEasy(document.getElementById("liveEditor"))).on("ready",function(){Formula.latexeasy=e}),e.init()})}},initSubmit:function(){dialog.onclose=function(e,t){return!t||(Formula.getValue(function(e){editor.execCommand("formula",e),editor.fireEvent("saveScene"),dialog.close(!1)}),!1)}}};