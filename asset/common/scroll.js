!function(n){var r={};function o(e){if(r[e])return r[e].exports;var t=r[e]={i:e,l:!1,exports:{}};return n[e].call(t.exports,t,t.exports,o),t.l=!0,t.exports}o.m=n,o.c=r,o.d=function(e,t,n){o.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:n})},o.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},o.t=function(t,e){if(1&e&&(t=o(t)),8&e)return t;if(4&e&&"object"==typeof t&&t&&t.__esModule)return t;var n=Object.create(null);if(o.r(n),Object.defineProperty(n,"default",{enumerable:!0,value:t}),2&e&&"string"!=typeof t)for(var r in t)o.d(n,r,function(e){return t[e]}.bind(null,r));return n},o.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return o.d(t,"a",t),t},o.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},o.p="/asset//",o(o.s=334)}({334:function(e,t,n){!function(l){window.MS=window.MS||{},window.MS.scroll={board:function(e,t){var n=l.extend({itemSelecor:".item",itemHeight:null,interval:3e3},t),e=l(e),r=!1;null===n.itemHeight&&(n.itemHeight=e.find(n.itemSelecor).eq(0).height());var o=e.find(n.itemSelecor).parent();o.append(e.find(n.itemSelecor).eq(0).clone()),o.on("mouseover",function(){r=!0}).on("mouseout",function(){r=!1});var i=e.find(n.itemSelecor),u=0;setInterval(function(){r||(++u>=i.length&&(o.css({marginTop:"0"}),u=1),o.animate({marginTop:"-"+n.itemHeight*u+"px"}))},n.interval)}}}.call(this,n(6))},6:function(e,t){e.exports=window.$}});