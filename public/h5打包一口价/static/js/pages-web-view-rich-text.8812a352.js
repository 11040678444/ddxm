(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["pages-web-view-rich-text"],{6931:function(t,n,e){"use strict";e.r(n);var i=e("b491"),a=e.n(i);for(var c in i)"default"!==c&&function(t){e.d(n,t,function(){return i[t]})}(c);n["default"]=a.a},b491:function(t,n,e){"use strict";Object.defineProperty(n,"__esModule",{value:!0}),n.default=void 0;var i={name:"rich-text",data:function(){return{content:""}},onLoad:function(t){console.log(t),console.log("带过来的参数",this.$parseURL()),this.content=this.formatRichText2(this.$parseURL().content),this.$parseURL().title&&uni.setNavigationBarTitle({title:this.$parseURL().title})},methods:{_goBack:function(){uni.navigateBack()}}};n.default=i},db0cb:function(t,n,e){"use strict";e.r(n);var i=e("e6fe"),a=e("6931");for(var c in a)"default"!==c&&function(t){e.d(n,t,function(){return a[t]})}(c);var o,r=e("f0c5"),u=Object(r["a"])(a["default"],i["b"],i["c"],!1,null,"67b3e5b3",null,!1,i["a"],o);n["default"]=u.exports},e6fe:function(t,n,e){"use strict";var i,a=function(){var t=this,n=t.$createElement,e=t._self._c||n;return e("v-uni-view",[e("div",{attrs:{id:"my-h5-back"},on:{click:function(n){arguments[0]=n=t.$handleEvent(n),t._goBack.apply(void 0,arguments)}}}),e("v-uni-rich-text",{attrs:{nodes:t.content}})],1)},c=[];e.d(n,"b",function(){return a}),e.d(n,"c",function(){return c}),e.d(n,"a",function(){return i})}}]);