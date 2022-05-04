!function(){var onlineImage,backupStyle=editor.queryCommandValue("background");function initTabs(){for(var n=$G("tabHeads").children,t=0;t<n.length;t++)domUtils.on(n[t],"click",function(t){for(var e,i=t.target||t.srcElement,o=0;o<n.length;o++)n[o]==i?(n[o].className="focus",e=n[o].getAttribute("data-content-id"),$G(e).style.display="block"):(n[o].className="",$G(n[o].getAttribute("data-content-id")).style.display="none")})}function initColorSelector(){var t,e,i,o,n=editor.queryCommandValue("background");n?(t=n["background-color"],e=n["background-repeat"]||"repeat",i=n["background-image"]||"",o=(n["background-position"]||"center center").split(" "),n=parseInt(o[0])||0,o=parseInt(o[1])||0,"no-repeat"==e&&(n||o)&&(e="self"),updateFormState("colored",t,i=(i=i.match(/url[\s]*\(([^\)]*)\)/))?i[1]:"",e,n,o)):updateFormState();function a(){updateFormState(),updateBackground()}domUtils.on($G("nocolorRadio"),"click",updateBackground),domUtils.on($G("coloredRadio"),"click",a),domUtils.on($G("url"),"keyup",function(){$G("url").value&&"none"==$G("alignment").style.display&&utils.each($G("repeatType").children,function(t){t.selected="repeat"==t.getAttribute("value")&&"selected"}),a()}),domUtils.on($G("repeatType"),"change",a),domUtils.on($G("x"),"keyup",updateBackground),domUtils.on($G("y"),"keyup",updateBackground),initColorPicker()}function initColorPicker(){var t=editor,e=$G("colorPicker"),i=new UE.ui.Popup({content:new UE.ui.ColorPicker({noColorText:t.getLang("clearColor"),editor:t,onpickcolor:function(t,e){updateFormState("colored",e),updateBackground(),UE.ui.Popup.postHide()},onpicknocolor:function(t,e){updateFormState("colored","transparent"),updateBackground(),UE.ui.Popup.postHide()}}),editor:t,onhide:function(){}});domUtils.on(e,"click",function(){i.showAnchor(this)}),domUtils.on(document,"mousedown",function(t){t=t.target||t.srcElement;UE.ui.Popup.postHide(t)}),domUtils.on(window,"scroll",function(){UE.ui.Popup.postHide()})}function updateFormState(t,e,i,o,n,a){var r=$G("nocolorRadio"),s=$G("coloredRadio");t&&(r.checked="colored"!=t&&"checked",s.checked="colored"==t&&"checked"),e&&domUtils.setStyle($G("colorPicker"),"background-color",e),i&&/^\//.test(i)&&((e=document.createElement("a")).href=i,browser.ie&&(e.href=e.href),i=browser.ie?e.href:e.protocol+"//"+e.host+e.pathname+e.search+e.hash),!i&&""!==i||($G("url").value=i),o&&utils.each($G("repeatType").children,function(t){t.selected=o==t.getAttribute("value")&&"selected"}),(n||a)&&($G("x").value=parseInt(n)||0,$G("y").value=parseInt(a)||0),$G("alignment").style.display=s.checked&&$G("url").value?"":"none",$G("custom").style.display=s.checked&&$G("url").value&&"self"==$G("repeatType").value?"":"none"}function updateBackground(){var t,e,i,o;$G("coloredRadio").checked?(t=domUtils.getStyle($G("colorPicker"),"background-color"),e=$G("url").value,i=$G("repeatType").value,o={"background-repeat":"no-repeat","background-position":"center center"},t&&(o["background-color"]=t),e&&(o["background-image"]="url("+e+")"),"self"==i?o["background-position"]=$G("x").value+"px "+$G("y").value+"px":"repeat-x"!=i&&"repeat-y"!=i&&"repeat"!=i||(o["background-repeat"]=i),editor.execCommand("background",o)):editor.execCommand("background",null)}function OnlineImage(t){this.container=utils.isString(t)?document.getElementById(t):t,this.init()}window.onload=function(){initTabs(),initColorSelector()},OnlineImage.prototype={init:function(){this.reset(),this.initEvents()},initContainer:function(){this.container.innerHTML="",this.list=document.createElement("ul"),this.clearFloat=document.createElement("li"),domUtils.addClass(this.list,"list"),domUtils.addClass(this.clearFloat,"clearFloat"),this.list.id="imageListUl",this.list.appendChild(this.clearFloat),this.container.appendChild(this.list)},initEvents:function(){var i=this;domUtils.on($G("imageList"),"scroll",function(t){var e=this;e.scrollHeight-(e.offsetHeight+e.scrollTop)<10&&i.getImageData()}),domUtils.on(this.container,"click",function(t){var e=(t.target||t.srcElement).parentNode,i=$G("imageListUl").childNodes;if("li"==e.tagName.toLowerCase()){updateFormState("nocolor",null,"");for(var o,n=0;o=i[n++];)o!=e||domUtils.hasClass(o,"selected")?domUtils.removeClasses(o,"selected"):(domUtils.addClass(o,"selected"),updateFormState("colored",null,e.firstChild.getAttribute("_src"),"repeat"));updateBackground()}})},initData:function(){this.state=0,this.listSize=editor.getOpt("imageManagerListSize"),this.listIndex=0,this.listEnd=!1,this.getImageData()},reset:function(){this.initContainer(),this.initData()},getImageData:function(){var _this=this,url,isJsonp;_this.listEnd||this.isLoadingData||(this.isLoadingData=!0,url=editor.getActionUrl(editor.getOpt("imageManagerActionName")),isJsonp=utils.isCrossDomainUrl(url),ajax.request(url,{timeout:1e5,dataType:isJsonp?"jsonp":"",data:utils.extend({start:this.listIndex,size:this.listSize},editor.queryCommandValue("serverparam")),method:"get",onsuccess:function(r){try{var json=isJsonp?r:eval("("+r.responseText+")");"SUCCESS"==json.state&&(_this.pushData(json.list),_this.listIndex=parseInt(json.start)+parseInt(json.list.length),_this.listIndex>=json.total&&(_this.listEnd=!0),_this.isLoadingData=!1)}catch(e){var list;-1!=r.responseText.indexOf("ue_separate_ue")&&(list=r.responseText.split(r.responseText),_this.pushData(list),_this.listIndex=parseInt(list.length),_this.listEnd=!0,_this.isLoadingData=!1)}},onerror:function(){_this.isLoadingData=!1}}))},pushData:function(t){for(var e,i,o,n=this,a=editor.getOpt("imageManagerUrlPrefix"),r=0;r<t.length;r++)t[r]&&t[r].url&&(e=document.createElement("li"),i=document.createElement("img"),o=document.createElement("span"),domUtils.on(i,"load",function(t){return function(){n.scale(t,t.parentNode.offsetWidth,t.parentNode.offsetHeight)}}(i)),i.width=113,i.setAttribute("src",a+t[r].url+(-1==t[r].url.indexOf("?")?"?noCache=":"&noCache=")+(+new Date).toString(36)),i.setAttribute("_src",a+t[r].url),domUtils.addClass(o,"icon"),e.appendChild(i),e.appendChild(o),this.list.insertBefore(e,this.clearFloat))},scale:function(t,e,i,o){var n=t.width,a=t.height;"justify"==o?a<=n?(t.width=e,t.height=i*a/n,t.style.marginLeft="-"+parseInt((t.width-e)/2)+"px"):(t.width=e*n/a,t.height=i,t.style.marginTop="-"+parseInt((t.height-i)/2)+"px"):a<=n?(t.width=e*n/a,t.height=i,t.style.marginLeft="-"+parseInt((t.width-e)/2)+"px"):(t.width=e,t.height=i*a/n,t.style.marginTop="-"+parseInt((t.height-i)/2)+"px")},getInsertList:function(){for(var t,e=this.list.children,i=[],o=getAlign(),n=0;n<e.length;n++)domUtils.hasClass(e[n],"selected")&&(t=e[n].firstChild.getAttribute("_src"),i.push({src:t,_src:t,floatStyle:o}));return i}},dialog.onok=function(){updateBackground(),editor.fireEvent("saveScene")},dialog.oncancel=function(){editor.execCommand("background",backupStyle)}}();