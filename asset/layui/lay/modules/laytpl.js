layui.define(function(e){"use strict";function r(e){this.tpl=e}var o={open:"{{",close:"}}"},p=function(e,r,n){return u((r||"")+o.open+["#([\\s\\S])+?","([^{#}])*?"][e||0]+o.close+(n||""))},a=function(e){return String(e||"").replace(/&(?!#?[a-zA-Z0-9]+;)/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/'/g,"&#39;").replace(/"/g,"&quot;")},l=function(e,r){var n="Laytpl Error：";return"object"==typeof console&&console.error(n+e+"\n"+(r||"")),n+e},u=function(e){return new RegExp(e,"g")};r.pt=r.prototype,window.errors=0,r.pt.parse=function(e,r){var n=e,c=u("^"+o.open+"#",""),t=u(o.close+"$","");e='"use strict";var view = "'+(e=e.replace(/\s+|\r|\t|\n/g," ").replace(u(o.open+"#"),o.open+"# ").replace(u(o.close+"}"),"} "+o.close).replace(/\\/g,"\\\\").replace(u(o.open+"!(.+?)!"+o.close),function(e){return e.replace(u("^"+o.open+"!"),"").replace(u("!"+o.close),"").replace(u(o.open+"|"+o.close),function(e){return e.replace(/(.)/g,"\\$1")})}).replace(/(?="|')/g,"\\").replace(p(),function(e){return'";'+(e=e.replace(c,"").replace(t,"")).replace(/\\/g,"")+';view+="'}).replace(p(1),function(e){var r='"+(';return e.replace(/\s/g,"")===o.open+o.close?"":(e=e.replace(u(o.open+"|"+o.close),""),/^=/.test(e)&&(e=e.replace(/^=/,""),r='"+_escape_('),r+e.replace(/\\/g,"")+')+"')}))+'";return view;';try{return this.cache=e=new Function("d, _escape_",e),e(r,a)}catch(e){return delete this.cache,l(e,n)}},r.pt.render=function(e,r){var n=this;return e?(e=n.cache?n.cache(e,a):n.parse(n.tpl,e),r?void r(e):e):l("no data")};function n(e){return"string"!=typeof e?l("Template not found"):new r(e)}n.config=function(e){for(var r in e=e||{})o[r]=e[r]},n.v="1.2.0",e("laytpl",n)});