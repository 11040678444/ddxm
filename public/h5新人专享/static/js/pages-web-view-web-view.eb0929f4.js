(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["pages-web-view-web-view"],{"5fcc":function(t,e,n){"use strict";Object.defineProperty(e,"__esModule",{value:!0}),e.default=void 0;var i={name:"web-view",data:function(){return{url:""}},onLoad:function(){console.log("带过来的参数",this.$parseURL()),this.url=this.$parseURL().url,this.$parseURL().title&&uni.setNavigationBarTitle({title:this.$parseURL().title})},methods:{_goBack:function(){uni.navigateBack()}}};e.default=i},b78d:function(t,e,n){"use strict";n.r(e);var i=n("d8a8"),a=n("fdb9");for(var u in a)"default"!==u&&function(t){n.d(e,t,function(){return a[t]})}(u);var r=n("2877"),c=Object(r["a"])(a["default"],i["a"],i["b"],!1,null,"d9cdae8c",null);e["default"]=c.exports},d8a8:function(t,e,n){"use strict";var i=function(){var t=this,e=t.$createElement,n=t._self._c||e;return n("v-uni-view",[n("div",{attrs:{id:"my-h5-back"},on:{click:function(e){e=t.$handleEvent(e),t._goBack(e)}}}),n("v-uni-web-view",{attrs:{src:t.url}})],1)},a=[];n.d(e,"a",function(){return i}),n.d(e,"b",function(){return a})},fdb9:function(t,e,n){"use strict";n.r(e);var i=n("5fcc"),a=n.n(i);for(var u in i)"default"!==u&&function(t){n.d(e,t,function(){return i[t]})}(u);e["default"]=a.a}}]);