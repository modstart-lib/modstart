!function(i){var n={};function o(t){if(n[t])return n[t].exports;var e=n[t]={i:t,l:!1,exports:{}};return i[t].call(e.exports,e,e.exports,o),e.l=!0,e.exports}o.m=i,o.c=n,o.d=function(t,e,i){o.o(t,e)||Object.defineProperty(t,e,{enumerable:!0,get:i})},o.r=function(t){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(t,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(t,"__esModule",{value:!0})},o.t=function(e,t){if(1&t&&(e=o(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var i=Object.create(null);if(o.r(i),Object.defineProperty(i,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var n in e)o.d(i,n,function(t){return e[t]}.bind(null,n));return i},o.n=function(t){var e=t&&t.__esModule?function(){return t.default}:function(){return t};return o.d(e,"a",e),e},o.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},o.p="/asset//",o(o.s=328)}({328:function(t,e,i){!function(t){i(329),"api"in window||(window.api={}),window.api.timeago=function(){t(function(){t.timeago.settings.allowFuture=!0,t("[datetime]").timeago()})},window.api.timeago(),"MS"in window||(window.MS={}),window.MS.timeago=window.api.timeago}.call(this,i(6))},329:function(t,e,i){var n=[i(6)];void 0===(i="function"==typeof(i=function(d){d.timeago=function(t){return t instanceof Date?a(t):a("string"==typeof t?d.timeago.parse(t):"number"==typeof t?new Date(t):d.timeago.datetime(t))};var n=d.timeago;d.extend(d.timeago,{settings:{refreshMillis:6e4,allowPast:!0,allowFuture:!1,localeTitle:!1,cutoff:0,autoDispose:!0,strings:{prefixAgo:null,prefixFromNow:null,suffixAgo:"前",suffixFromNow:"from now",inPast:"any moment now",seconds:"少于1分钟",minute:"大约1分钟",minutes:"%d分钟",hour:"大约1小时",hours:"大约%d小时",day:"1天",days:"%d天",month:"大约1个月",months:"%d个月",year:"大约1年",years:"%d年",wordSeparator:" ",numbers:[]}},inWords:function(i){if(!this.settings.allowPast&&!this.settings.allowFuture)throw"timeago allowPast and allowFuture settings can not both be set to false.";var n=this.settings.strings,t=n.prefixAgo,e=n.suffixAgo;if(this.settings.allowFuture&&i<0&&(t=n.prefixFromNow,e=n.suffixFromNow),!this.settings.allowPast&&0<=i)return this.settings.strings.inPast;var o=Math.abs(i)/1e3,a=o/60,r=a/60,s=r/24,u=s/365;function l(t,e){t=d.isFunction(t)?t(e,i):t,e=n.numbers&&n.numbers[e]||e;return t.replace(/%d/i,e)}s=o<45&&l(n.seconds,Math.round(o))||o<90&&l(n.minute,1)||a<45&&l(n.minutes,Math.round(a))||a<90&&l(n.hour,1)||r<24&&l(n.hours,Math.round(r))||r<42&&l(n.day,1)||s<30&&l(n.days,Math.round(s))||s<45&&l(n.month,1)||s<365&&l(n.months,Math.round(s/30))||u<1.5&&l(n.year,1)||l(n.years,Math.round(u)),u=n.wordSeparator||"";return void 0===n.wordSeparator&&(u=" "),d.trim([t,s,e].join(u))},parse:function(t){t=(t=(t=(t=(t=(t=d.trim(t)).replace(/\.\d+/,"")).replace(/-/,"/").replace(/-/,"/")).replace(/T/," ").replace(/Z/," UTC")).replace(/([\+\-]\d\d)\:?(\d\d)/," $1$2")).replace(/([\+\-]\d\d)$/," $100");return new Date(t)},datetime:function(t){t=n.isTime(t)?d(t).attr("datetime"):d(t).attr("title");return n.parse(t)},isTime:function(t){return"time"===d(t).get(0).tagName.toLowerCase()}});var o={init:function(){var t=d.proxy(i,this);t();var e=n.settings;0<e.refreshMillis&&(this._timeagoInterval=setInterval(t,e.refreshMillis))},update:function(t){t=t instanceof Date?t:n.parse(t);d(this).data("timeago",{datetime:t}),n.settings.localeTitle&&d(this).attr("title",t.toLocaleString()),i.apply(this)},updateFromDOM:function(){d(this).data("timeago",{datetime:n.parse(n.isTime(this)?d(this).attr("datetime"):d(this).attr("title"))}),i.apply(this)},dispose:function(){this._timeagoInterval&&(window.clearInterval(this._timeagoInterval),this._timeagoInterval=null)}};function i(){var t=n.settings;if(t.autoDispose&&!d.contains(document.documentElement,this))return d(this).timeago("dispose"),this;var e,i,e=((e=d(e=this)).data("timeago")||(e.data("timeago",{datetime:n.datetime(e)}),i=d.trim(e.text()),n.settings.localeTitle?e.attr("title",e.data("timeago").datetime.toLocaleString()):!(0<i.length)||n.isTime(e)&&e.attr("title")||e.attr("title",i)),e.data("timeago"));return isNaN(e.datetime)||(0==t.cutoff||Math.abs(r(e.datetime))<t.cutoff?d(this).text(a(e.datetime)):0<d(this).attr("title").length&&d(this).text(d(this).attr("title"))),this}function a(t){return n.inWords(r(t))}function r(t){return(new Date).getTime()-t.getTime()}d.fn.timeago=function(t,e){var i=t?o[t]:o.init;if(!i)throw new Error("Unknown function name '"+t+"' for timeago");return this.each(function(){i.call(this,e)}),this},document.createElement("abbr"),document.createElement("time")})?i.apply(e,n):i)||(t.exports=i)},6:function(t,e){t.exports=window.$}});