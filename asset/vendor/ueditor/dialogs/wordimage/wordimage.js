var flashObj,flashContainer,wordImage={},g=baidu.g;function hideFlash(){flashObj=null,flashContainer.innerHTML=""}function addOkListener(){dialog.onok=function(){if(imageUrls.length){var e=editor.getOpt("imageUrlPrefix"),t=domUtils.getElementsByTagName(editor.document,"img");editor.fireEvent("saveScene");for(var a,n=0;a=t[n++];){var i=a.getAttribute("word_img");if(i)for(var r,o=0;r=imageUrls[o++];)if(-1!=i.indexOf(r.original.replace(" ",""))){a.src=e+r.url,a.setAttribute("_src",e+r.url),a.setAttribute("title",r.title),domUtils.removeAttributes(a,["word_img","style","width","height"]),editor.fireEvent("selectionchange");break}}editor.fireEvent("saveScene"),hideFlash()}},dialog.oncancel=function(){hideFlash()}}function addUploadListener(){g("upload").onclick=function(){flashObj.upload(),this.style.display="none"}}function showLocalPath(e){var t=editor.selection.getRange().getClosedNode(),a=editor.execCommand("wordimage");1==a.length||t&&"IMG"==t.tagName?g(e).value=a[0]:(a=(t=a[0]).lastIndexOf("/")||0,a=(t.lastIndexOf("\\")||0)<a?"/":"\\",t=t.substring(0,t.lastIndexOf(a)+1),g(e).value=t)}function createFlashUploader(e,t){var a,n=utils.extend({},lang.flashI18n);for(a in n)a in{lang:1,uploadingTF:1,imageTF:1,textEncoding:1}||!n[a]||(n[a]=encodeURIComponent(editor.options.langPath+editor.options.lang+"/images/"+n[a]));t=extendProperty(t,t={createOptions:{id:"flash",url:(e=utils.extend(e,n,!1)).flashUrl,width:e.width,height:e.height,errorMessage:lang.flashError,wmode:browser.safari?"transparent":"window",ver:"10.0.0",vars:e,container:e.container}});flashObj=new baidu.flash.imageUploader(t),flashContainer=$G(e.container)}function extendProperty(e,t){for(var a in e)t[a]||(t[a]=e[a]);return t}function getPasteData(e){return baidu.g("msg").innerHTML=lang.copySuccess+"</br>",setTimeout(function(){baidu.g("msg").innerHTML=""},5e3),baidu.g(e).value}function createCopyButton(e,t){baidu.swf.create({id:"copyFlash",url:"fClipboard_ueditor.swf",width:"58",height:"25",errorMessage:"",bgColor:"#CBCBCB",wmode:"transparent",ver:"10.0.0",vars:{tid:t}},e);var a=baidu.swf.getMovie("copyFlash"),n=setInterval(function(){a&&a.flashInit&&(clearInterval(n),a.setHandCursor(!0),a.setContentFuncName("getPasteData"))},500)}wordImage.init=function(e,t){showLocalPath("localPath"),createFlashUploader(e,t),addUploadListener(),addOkListener()},createCopyButton("clipboard","localPath");