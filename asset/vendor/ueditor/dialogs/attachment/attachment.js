!function(){var uploadFile,onlineFile;function initTabs(){for(var e=$G("tabhead").children,t=0;t<e.length;t++)domUtils.on(e[t],"click",function(e){setTabFocus((e.target||e.srcElement).getAttribute("data-content-id"))});setTabFocus("upload")}function setTabFocus(e){if(e){for(var t,i=$G("tabhead").children,s=0;s<i.length;s++)(t=i[s].getAttribute("data-content-id"))==e?(domUtils.addClass(i[s],"focus"),domUtils.addClass($G(t),"focus")):(domUtils.removeClasses(i[s],"focus"),domUtils.removeClasses($G(t),"focus"));switch(e){case"upload":uploadFile=uploadFile||new UploadFile("queueList");break;case"online":onlineFile=onlineFile||new OnlineFile("fileList")}}}function initButtons(){dialog.onok=function(){for(var e,t=[],i=$G("tabhead").children,s=0;s<i.length;s++)if(domUtils.hasClass(i[s],"focus")){e=i[s].getAttribute("data-content-id");break}switch(e){case"upload":var t=uploadFile.getInsertList(),a=uploadFile.getQueueCount();if(a)return $(".info","#queueList").html('<span style="color:red;">'+"还有2个未上传文件".replace(/[\d]/,a)+"</span>"),!1;break;case"online":t=onlineFile.getInsertList()}editor.execCommand("insertfile",t)}}function UploadFile(e){this.$wrap=e.constructor==String?$("#"+e):$(e),this.init()}function OnlineFile(e){this.container=utils.isString(e)?document.getElementById(e):e,this.init()}window.onload=function(){initTabs(),initButtons()},UploadFile.prototype={init:function(){this.fileList=[],this.initContainer(),this.initUploader()},initContainer:function(){this.$queue=this.$wrap.find(".filelist")},initUploader:function(){var d,a=this,u=jQuery,e=a.$wrap,i=e.find(".filelist"),s=e.find(".statusBar"),n=s.find(".info"),r=e.find(".uploadBtn"),t=(e.find(".filePickerBtn"),e.find(".filePickerBlock")),o=e.find(".placeholder"),l=s.find(".progress").hide(),p=0,c=0,f=window.devicePixelRatio||1,h=113*f,g=113*f,m="",v={},x=(f="transition"in(e=document.createElement("p").style)||"WebkitTransition"in e||"MozTransition"in e||"msTransition"in e||"OTransition"in e,e=null,f),C=editor.getActionUrl(editor.getOpt("fileActionName")),b=editor.getOpt("fileMaxSize"),w=(editor.getOpt("fileAllowFiles")||[]).join("").replace(/\./g,",").replace(/^[,]/,"");function U(i){function s(e){switch(e){case"exceed_size":text=lang.errorExceedSize;break;case"interrupt":text=lang.errorInterrupt;break;case"http":text=lang.errorHttp;break;case"not_allow_type":text=lang.errorFileType;break;default:text=lang.errorUploadRetry}l.text(text).show()}var a=u('<li id="'+i.id+'"><p class="title">'+i.name+'</p><p class="imgWrap"></p><p class="progress"><span></span></p></li>'),n=u('<div class="file-panel"><span class="cancel">'+lang.uploadDelete+'</span><span class="rotateRight">'+lang.uploadTurnRight+'</span><span class="rotateLeft">'+lang.uploadTurnLeft+"</span></div>").appendTo(a),r=a.find("p.progress span"),o=a.find("p.imgWrap"),l=u('<p class="error"></p>').hide().appendTo(a);"invalid"===i.getStatus()?s(i.statusText):(o.text(lang.uploadPreview),-1=="|png|jpg|jpeg|bmp|gif|".indexOf("|"+i.ext.toLowerCase()+"|")?o.empty().addClass("notimage").append('<i class="file-preview file-type-'+i.ext.toLowerCase()+'"></i><span class="file-title" title="'+i.name+'">'+i.name+"</span>"):browser.ie&&browser.version<=7?o.text(lang.uploadNoPreview):d.makeThumb(i,function(e,t){e||!t?o.text(lang.uploadNoPreview):(t=u('<img src="'+t+'">'),o.empty().append(t),t.on("error",function(){o.text(lang.uploadNoPreview)}))},h,g),v[i.id]=[i.size,0],i.rotation=0,i.ext&&-1!=w.indexOf(i.ext.toLowerCase())||(s("not_allow_type"),d.removeFile(i))),i.on("statuschange",function(e,t){"progress"===t?r.hide().width(0):"queued"===t&&(a.off("mouseenter mouseleave"),n.remove()),"error"===e||"invalid"===e?(s(i.statusText),v[i.id][1]=1):"interrupt"===e?s("interrupt"):"queued"===e?v[i.id][1]=0:"progress"===e&&(l.hide(),r.css("display","block")),a.removeClass("state-"+t).addClass("state-"+e)}),a.on("mouseenter",function(){n.stop().animate({height:30})}),a.on("mouseleave",function(){n.stop().animate({height:0})}),n.on("click","span",function(){var e;switch(u(this).index()){case 0:return void d.removeFile(i);case 1:i.rotation+=90;break;case 2:i.rotation-=90}x?(e="rotate("+i.rotation+"deg)",o.css({"-webkit-transform":e,"-mos-transform":e,"-o-transform":e,transform:e})):o.css("filter","progid:DXImageTransform.Microsoft.BasicImage(rotation="+~~(i.rotation/90%4+4)%4+")")}),a.insertBefore(t)}function F(){var e,i=0,s=0,t=l.children();u.each(v,function(e,t){s+=t[0],i+=t[0]*t[1]}),e=s?i/s:0,t.eq(0).text(Math.round(100*e)+"%"),t.eq(1).css("width",Math.round(100*e)+"%"),y()}function S(e){if(e!=m){var t=d.getStats();switch(r.removeClass("state-"+m),r.addClass("state-"+e),e){case"pedding":i.addClass("element-invisible"),s.addClass("element-invisible"),o.removeClass("element-invisible"),l.hide(),n.hide(),d.refresh();break;case"ready":o.addClass("element-invisible"),i.removeClass("element-invisible"),s.removeClass("element-invisible"),l.hide(),n.show(),r.text(lang.uploadStart),d.refresh();break;case"uploading":l.show(),n.hide(),r.text(lang.uploadPause);break;case"paused":l.show(),n.hide(),r.text(lang.uploadContinue);break;case"confirm":if(l.show(),n.hide(),r.text(lang.uploadStart),(t=d.getStats()).successNum&&!t.uploadFailNum)return void S("finish");break;case"finish":l.hide(),n.show(),t.uploadFailNum?r.text(lang.uploadRetry):r.text(lang.uploadStart)}m=e,y()}a.getQueueCount()?r.removeClass("disabled"):r.addClass("disabled")}function y(){var e,t="";"ready"===m?t=lang.updateStatusReady.replace("_",p).replace("_KB",WebUploader.formatSize(c)):"confirm"===m?(e=d.getStats()).uploadFailNum&&(t=lang.updateStatusConfirm.replace("_",e.successNum).replace("_",e.successNum)):(e=d.getStats(),t=lang.updateStatusFinish.replace("_",p).replace("_KB",WebUploader.formatSize(c)).replace("_",e.successNum),e.uploadFailNum&&(t+=lang.updateStatusError.replace("_",e.uploadFailNum))),n.html(t)}WebUploader.Uploader.support()?editor.getOpt("fileActionName")?(f={pick:{id:"#filePickerReady",label:lang.uploadSelectFile},swf:"../../third-party/webuploader/Uploader.swf",server:C,fileVal:editor.getOpt("fileFieldName"),duplicate:!0,fileSingleSizeLimit:b,headers:editor.getOpt("serverHeaders")||{},compress:!1},editor.getOpt("uploadServiceEnable")&&(f.customUpload=function(t,i){editor.getOpt("uploadServiceUpload")("attachment",t,{success:function(e){i.onSuccess(t,{_raw:JSON.stringify(e)})},error:function(e){i.onError(t,e)},progress:function(e){i.onProgress(t,e)}},{from:"attachment"})}),(d=a.uploader=WebUploader.create(f)).addButton({id:"#filePickerBlock"}),d.addButton({id:"#filePickerBtn",label:lang.uploadAddFile}),S("pedding"),d.on("fileQueued",function(e){e.ext&&-1!=w.indexOf(e.ext.toLowerCase())&&e.size<=b&&(p++,c+=e.size),1===p&&(o.addClass("element-invisible"),s.show()),U(e)}),d.on("fileDequeued",function(e){var t;e.ext&&-1!=w.indexOf(e.ext.toLowerCase())&&e.size<=b&&(p--,c-=e.size),e=u("#"+(t=e).id),delete v[t.id],F(),e.off().find(".file-panel").off().end().remove(),F()}),d.on("filesQueued",function(e){d.isInProgress()||"pedding"!=m&&"finish"!=m&&"confirm"!=m&&"ready"!=m||S("ready"),F()}),d.on("all",function(e,t){switch(e){case"uploadFinished":S("confirm");break;case"startUpload":var i=utils.serializeParam(editor.queryCommandValue("serverparam"))||"",i=utils.formatUrl(C+(-1==C.indexOf("?")?"?":"&")+"encode=utf-8&"+i);d.option("server",i),S("uploading");break;case"stopUpload":S("paused")}}),d.on("uploadBeforeSend",function(e,t,i){-1!=C.toLowerCase().indexOf("jsp")&&(i.X_Requested_With="XMLHttpRequest")}),d.on("uploadProgress",function(e,t){u("#"+e.id).find(".progress span").css("width",100*t+"%"),v[e.id][1]=t,F()}),d.on("uploadSuccess",function(t,e){t=u("#"+t.id);try{var i=e._raw||e,s=utils.str2json(i);"SUCCESS"==(s=editor.getOpt("serverResponsePrepare")(s)).state?(a.fileList.push(s),t.append('<span class="success"></span>'),editor.fireEvent("uploadsuccess",{res:s,type:"file"})):t.find(".error").text(s.state).show()}catch(e){t.find(".error").text(lang.errorServerUpload).show()}}),d.on("uploadError",function(e,t){}),d.on("error",function(e,t,i){"F_EXCEED_SIZE"===e?editor.getOpt("tipError")(lang.errorExceedSize+" "+(t/1024/1024).toFixed(1)+"MB"):console.log("error",e,t,i)}),d.on("uploadComplete",function(e,t){}),r.on("click",function(){return!u(this).hasClass("disabled")&&void("ready"===m||"paused"===m?d.upload():"uploading"===m&&d.stop())}),r.addClass("state-"+m),F()):u("#filePickerReady").after(u("<div>").html(lang.errorLoadConfig)).hide():u("#filePickerReady").after(u("<div>").html(lang.errorNotSupport)).hide()},getQueueCount:function(){for(var e,t=0,i=this.uploader.getFiles(),s=0;e=i[s++];)"queued"!=(e=e.getStatus())&&"uploading"!=e&&"progress"!=e||t++;return t},getInsertList:function(){for(var e,t,i=[],s=editor.getOpt("fileUrlPrefix"),a=0;a<this.fileList.length;a++)e=(t=this.fileList[a]).url,i.push({title:t.original||e.substr(e.lastIndexOf("/")+1),url:s+e});return i}},OnlineFile.prototype={init:function(){this.initContainer(),this.initEvents(),this.initData()},initContainer:function(){this.container.innerHTML="",this.list=document.createElement("ul"),this.clearFloat=document.createElement("li"),domUtils.addClass(this.list,"list"),domUtils.addClass(this.clearFloat,"clearFloat"),this.list.appendChild(this.clearFloat),this.container.appendChild(this.list)},initEvents:function(){var t=this;domUtils.on($G("fileList"),"scroll",function(e){this.scrollHeight-(this.offsetHeight+this.scrollTop)<10&&t.getFileData()}),domUtils.on(this.list,"click",function(e){e=(e.target||e.srcElement).parentNode;"li"==e.tagName.toLowerCase()&&(domUtils.hasClass(e,"selected")?domUtils.removeClasses(e,"selected"):domUtils.addClass(e,"selected"))})},initData:function(){this.state=0,this.listSize=editor.getOpt("fileManagerListSize"),this.listIndex=0,this.listEnd=!1,this.getFileData()},getFileData:function(){var _this=this;_this.listEnd||this.isLoadingData||(this.isLoadingData=!0,ajax.request(editor.getActionUrl(editor.getOpt("fileManagerActionName")),{timeout:1e5,data:utils.extend({start:this.listIndex,size:this.listSize},editor.queryCommandValue("serverparam")),headers:editor.options.serverHeaders||{},method:"get",onsuccess:function(r){try{var json=eval("("+r.responseText+")");"SUCCESS"==json.state&&(_this.pushData(json.list),_this.listIndex=parseInt(json.start)+parseInt(json.list.length),_this.listIndex>=json.total&&(_this.listEnd=!0),_this.isLoadingData=!1)}catch(e){var list;-1!=r.responseText.indexOf("ue_separate_ue")&&(list=r.responseText.split(r.responseText),_this.pushData(list),_this.listIndex=parseInt(list.length),_this.listEnd=!0,_this.isLoadingData=!1)}},onerror:function(){_this.isLoadingData=!1}}))},pushData:function(e){for(var t,i,s,a,n,r,o=this,l=editor.getOpt("fileManagerUrlPrefix"),d=0;d<e.length;d++)e[d]&&e[d].url&&(a=document.createElement("li"),n=document.createElement("span"),r=e[d].url.substr(e[d].url.lastIndexOf(".")+1),-1!="png|jpg|jpeg|gif|bmp".indexOf(r)?(t=document.createElement("img"),domUtils.on(t,"load",function(e){return function(){o.scale(e,e.parentNode.offsetWidth,e.parentNode.offsetHeight)}}(t)),t.width=113,t.setAttribute("src",l+e[d].url+(-1==e[d].url.indexOf("?")?"?noCache=":"&noCache=")+(+new Date).toString(36))):(i=document.createElement("i"),(s=document.createElement("span")).innerHTML=e[d].original||e[d].url.substr(e[d].url.lastIndexOf("/")+1),(t=document.createElement("div")).appendChild(i),t.appendChild(s),domUtils.addClass(t,"file-wrapper"),domUtils.addClass(s,"file-title"),domUtils.addClass(i,"file-type-"+r),domUtils.addClass(i,"file-preview")),domUtils.addClass(n,"icon"),a.setAttribute("data-url",l+e[d].url),e[d].original&&a.setAttribute("data-title",e[d].original),a.appendChild(t),a.appendChild(n),this.list.insertBefore(a,this.clearFloat))},scale:function(e,t,i,s){var a=e.width,n=e.height;"justify"==s?n<=a?(e.width=t,e.height=i*n/a,e.style.marginLeft="-"+parseInt((e.width-t)/2)+"px"):(e.width=t*a/n,e.height=i,e.style.marginTop="-"+parseInt((e.height-i)/2)+"px"):n<=a?(e.width=t*a/n,e.height=i,e.style.marginLeft="-"+parseInt((e.width-t)/2)+"px"):(e.width=t,e.height=i*n/a,e.style.marginTop="-"+parseInt((e.height-i)/2)+"px")},getInsertList:function(){for(var e,t,i=this.list.children,s=[],a=0;a<i.length;a++)domUtils.hasClass(i[a],"selected")&&(e=i[a].getAttribute("data-url"),t=i[a].getAttribute("data-title")||e.substr(e.lastIndexOf("/")+1),s.push({title:t,url:e}));return s}}}();