(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["pages2-address-add"],{"00ac":function(t,e,n){"use strict";var i=n("2cbf"),a=n.n(i);a.a},"03b8":function(t,e,n){"use strict";n.r(e);var i=n("2791"),a=n("6c3d");for(var s in a)"default"!==s&&function(t){n.d(e,t,function(){return a[t]})}(s);n("df32");var r,o=n("f0c5"),l=Object(o["a"])(a["default"],i["b"],i["c"],!1,null,"85addd90",null,!1,i["a"],r);e["default"]=l.exports},"12a0":function(t,e,n){"use strict";n.r(e);var i=n("d647"),a=n("840d");for(var s in a)"default"!==s&&function(t){n.d(e,t,function(){return a[t]})}(s);n("bab7");var r,o=n("f0c5"),l=Object(o["a"])(a["default"],i["b"],i["c"],!1,null,"67108411",null,!1,i["a"],r);e["default"]=l.exports},"26cb":function(t,e,n){"use strict";var i,a=function(){var t=this,e=t.$createElement,n=t._self._c||e;return n("v-uni-view",{class:["uni-collapse-cell",{"uni-collapse-cell--disabled":t.disabled,"uni-collapse-cell--open":t.isOpen}],attrs:{"hover-class":t.disabled?"":"uni-collapse-cell--hover"}},[n("v-uni-view",{staticClass:"uni-collapse-cell__title header",on:{click:function(e){arguments[0]=e=t.$handleEvent(e),t.onClick.apply(void 0,arguments)}}},[t.thumb?n("v-uni-view",{staticClass:"uni-collapse-cell__title-extra"},[n("v-uni-image",{staticClass:"uni-collapse-cell__title-img",attrs:{src:t.thumb}})],1):t._e(),n("v-uni-view",{staticClass:"uni-collapse-cell__title-inner"},[n("v-uni-view",{staticClass:"uni-collapse-cell__title-text"},[t._v(t._s(t.title))])],1),n("v-uni-view",{staticClass:"uni-collapse-cell__title-arrow",class:{"uni-active":t.isOpen,"uni-collapse-cell--animation":!0===t.showAnimation}},[n("uni-icon",{attrs:{color:"#bbb",size:"20",type:"arrowdown"}})],1)],1),n("v-uni-view",{staticClass:"uni-collapse-cell__content",class:{"uni-collapse-cell--animation":!0===t.showAnimation},style:{height:t.isOpen?t.height:"0px"}},[n("v-uni-view",{attrs:{id:t.elId}},[t._t("default")],2)],1)],1)},s=[];n.d(e,"b",function(){return a}),n.d(e,"c",function(){return s}),n.d(e,"a",function(){return i})},2791:function(t,e,n){"use strict";var i,a=function(){var t=this,e=t.$createElement,n=t._self._c||e;return n("v-uni-view",{staticClass:"container"},[n("v-uni-view",{staticClass:"address"},[n("v-uni-view",{staticClass:"a-input"},[n("v-uni-view",{staticClass:"name"},[t._v("收货人")]),n("v-uni-view",{staticClass:"value"},[n("v-uni-input",{attrs:{type:"text",placeholder:"请输入收货人姓名"},model:{value:t.address.name,callback:function(e){t.$set(t.address,"name",e)},expression:"address.name"}})],1)],1),n("v-uni-view",{staticClass:"a-input"},[n("v-uni-view",{staticClass:"name"},[t._v("手机号码")]),n("v-uni-view",{staticClass:"value"},[n("v-uni-input",{attrs:{type:"number",placeholder:"请输入收货人手机号码",maxlength:"11"},model:{value:t.address.phone,callback:function(e){t.$set(t.address,"phone",e)},expression:"address.phone"}})],1)],1),n("v-uni-view",{staticClass:"a-input"},[n("v-uni-view",{staticClass:"name"},[t._v("所在地区")]),n("v-uni-picker",{staticClass:"value",attrs:{mode:"multiSelector",value:t.multiIndex,range:t.multiArray,"range-key":"area_name"},on:{change:function(e){arguments[0]=e=t.$handleEvent(e),t.bindMultiPickerChange.apply(void 0,arguments)},columnchange:function(e){arguments[0]=e=t.$handleEvent(e),t.bindMultiPickerColumnChange.apply(void 0,arguments)}}},[n("v-uni-view",[t._v(t._s(t.address.area_names||"请选择地址"))])],1)],1),n("v-uni-view",{staticClass:"a-input"},[n("v-uni-view",{staticClass:"name"},[t._v("详细地址")]),n("v-uni-view",{staticClass:"value",staticStyle:{width:"70%"}},[n("v-uni-input",{attrs:{type:"text",placeholder:"请输入详细地址"},model:{value:t.address.address,callback:function(e){t.$set(t.address,"address",e)},expression:"address.address"}})],1)],1),n("v-uni-view",{staticClass:"a-input"},[n("v-uni-view",{staticClass:"name"},[t._v("默认地址")]),n("v-uni-view",{staticClass:"value"},[n("v-uni-input",{attrs:{type:"text",placeholder:"设置为默认地址",disabled:"true"}})],1),n("v-uni-view",{staticClass:"value2"},[n("v-uni-switch",{staticStyle:{transform:"scale(0.7)"},attrs:{checked:!!t.address.default,color:"#31BF1A"},on:{change:function(e){arguments[0]=e=t.$handleEvent(e),t.switch1Change.apply(void 0,arguments)}}})],1)],1)],1),n("v-uni-view",{staticClass:"btns"},[n("v-uni-button",{staticClass:"save",attrs:{type:"warn"},on:{click:function(e){arguments[0]=e=t.$handleEvent(e),t.saveAddress.apply(void 0,arguments)}}},[t._v("保存地址")]),t.address.id?n("v-uni-button",{staticClass:"del",attrs:{type:"warn"},on:{click:function(e){arguments[0]=e=t.$handleEvent(e),t.detAddress(t.address.id)}}},[t._v("删除地址")]):t._e()],1)],1)},s=[];n.d(e,"b",function(){return a}),n.d(e,"c",function(){return s}),n.d(e,"a",function(){return i})},"2cbf":function(t,e,n){var i=n("fe93");"string"===typeof i&&(i=[[t.i,i,""]]),i.locals&&(t.exports=i.locals);var a=n("4f06").default;a("3a225a1a",i,!0,{sourceMap:!1,shadowMode:!1})},"376c":function(t,e,n){var i=n("4c91");"string"===typeof i&&(i=[[t.i,i,""]]),i.locals&&(t.exports=i.locals);var a=n("4f06").default;a("b1bacf88",i,!0,{sourceMap:!1,shadowMode:!1})},"37aa":function(t,e,n){var i=n("5868");"string"===typeof i&&(i=[[t.i,i,""]]),i.locals&&(t.exports=i.locals);var a=n("4f06").default;a("738a1b3d",i,!0,{sourceMap:!1,shadowMode:!1})},"4c91":function(t,e,n){e=t.exports=n("2350")(!1),e.push([t.i,'@charset "UTF-8";\n/**\r\n * 这里是uni-app内置的常用样式变量\r\n *\r\n * uni-app 官方扩展插件及插件市场（https://ext.dcloud.net.cn）上很多三方插件均使用了这些样式变量\r\n * 如果你是插件开发者，建议你使用scss预处理，并在插件代码中直接使用这些变量（无需 import 这个文件），方便用户通过搭积木的方式开发整体风格一致的App\r\n *\r\n */\n/**\r\n * 如果你是App开发者（插件使用者），你可以通过修改这些变量来定制自己的插件主题，实现自定义主题功能\r\n *\r\n * 如果你的项目同样使用了scss预处理，你也可以直接在你的 scss 代码中使用如下变量，同时无需 import 这个文件\r\n */\n/* 颜色变量 */\n/* 行为相关颜色 */\n/* 文字基本颜色 */\n/* 背景颜色 */\n/* 边框颜色 */\n/* 尺寸变量 */\n/* 文字尺寸 */\n/* 图片尺寸 */\n/* Border Radius */\n/* 水平间距 */\n/* 垂直间距 */\n/* 透明度 */\n/* 文章场景相关 */\n/* 页面设置相关 */\n/* 边线相关 */\n/* 主要颜色 */\n/* 超出行数显示 省略号*/\n/* 阿里巴巴图标被选中状态的样式 */.icon-color[data-v-67108411]{color:#fc5a5a}.uni-collapse[data-v-67108411]{background-color:#fff;position:relative;width:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column}.uni-collapse[data-v-67108411]:after{position:absolute;z-index:10;right:0;bottom:0;left:0;height:1px;content:"";-webkit-transform:scaleY(.5);transform:scaleY(.5);background-color:#f2f2f2}.uni-collapse[data-v-67108411]:before{position:absolute;z-index:10;right:0;top:0;left:0;height:1px;content:"";-webkit-transform:scaleY(.5);transform:scaleY(.5);background-color:#f2f2f2}',""])},"542c":function(t,e,n){"use strict";var i=n("288e");Object.defineProperty(e,"__esModule",{value:!0}),e.default=void 0,n("6b54"),n("ac6a"),n("28a5"),n("96cf");var a=i(n("3b8d")),s=i(n("12a0")),r=i(n("6220")),o={name:"address_add",data:function(){return{multiIndex:[0,0,0],multiArray:[[],[],[]],address:{id:"",name:"",phone:"",area_ids:"",area_names:"",default:0,address:"",id_info:[]}}},onLoad:function(){var t=(0,a.default)(regeneratorRuntime.mark(function t(){var e,n,i,a,s,r,o,l=this;return regeneratorRuntime.wrap(function(t){while(1)switch(t.prev=t.next){case 0:if(console.log("带过来的参数",this.$parseURL()),!this.$parseURL().id){t.next=22;break}return uni.setNavigationBarTitle({title:"编辑收货地址"}),t.next=5,this.getAddressInfo(this.$parseURL().id);case 5:return e=this.$parseURL().area_ids.split(","),t.next=8,this.getCity();case 8:return n=t.sent,t.next=11,this.getCity(e[0]);case 11:return i=t.sent,t.next=14,this.getCity(e[1]);case 14:a=t.sent,this.multiArray=[n,i,a],n.forEach(function(t,n){t.id.toString()===e[0]&&(console.log(n),l.multiIndex[0]=n,l.$set(l.multiIndex,0,n))}),i.forEach(function(t,n){t.id.toString()===e[1]&&(console.log(n),l.multiIndex[1]=n,l.$set(l.multiIndex,1,n))}),a.forEach(function(t,n){t.id.toString()===e[2]&&(console.log(n),l.multiIndex[2]=n,l.$set(l.multiIndex,2,n))}),this.$forceUpdate(),t.next=33;break;case 22:return t.next=24,this.getCity();case 24:return s=t.sent,t.next=27,this.getCity(s[0].id);case 27:return r=t.sent,t.next=30,this.getCity(r[0].id);case 30:o=t.sent,this.multiArray=[s,r,o],this.$forceUpdate();case 33:case"end":return t.stop()}},t,this)}));function e(){return t.apply(this,arguments)}return e}(),methods:{_goPage:function(t){var e=arguments.length>1&&void 0!==arguments[1]?arguments[1]:{};this.$openPage({name:t,query:e})},detAddress:function(){var t=(0,a.default)(regeneratorRuntime.mark(function t(e){var n;return regeneratorRuntime.wrap(function(t){while(1)switch(t.prev=t.next){case 0:return t.prev=0,t.next=3,this.$minApi.addressDel({id:e});case 3:n=t.sent,200===n.code&&uni.navigateBack(),t.next=10;break;case 7:t.prev=7,t.t0=t["catch"](0),console.log(t.t0);case 10:case"end":return t.stop()}},t,this,[[0,7]])}));function e(e){return t.apply(this,arguments)}return e}(),getAddressInfo:function(){var t=(0,a.default)(regeneratorRuntime.mark(function t(e){var n;return regeneratorRuntime.wrap(function(t){while(1)switch(t.prev=t.next){case 0:return t.prev=0,t.next=3,this.$minApi.addressInfo({id:e});case 3:n=t.sent,200===n.code&&(this.address=n.data),t.next=10;break;case 7:t.prev=7,t.t0=t["catch"](0),console.log(t.t0);case 10:case"end":return t.stop()}},t,this,[[0,7]])}));function e(e){return t.apply(this,arguments)}return e}(),getCity:function(){var t=(0,a.default)(regeneratorRuntime.mark(function t(e){var n,i;return regeneratorRuntime.wrap(function(t){while(1)switch(t.prev=t.next){case 0:return n=[],t.prev=1,t.next=4,this.$minApi.city({id:e});case 4:i=t.sent,200===i.code&&(n=i.data),t.next=11;break;case 8:t.prev=8,t.t0=t["catch"](1),console.log(t.t0);case 11:return t.abrupt("return",n);case 12:case"end":return t.stop()}},t,this,[[1,8]])}));function e(e){return t.apply(this,arguments)}return e}(),bindMultiPickerColumnChange:function(){var t=(0,a.default)(regeneratorRuntime.mark(function t(e){return regeneratorRuntime.wrap(function(t){while(1)switch(t.prev=t.next){case 0:this.multiIndex[e.detail.column]=e.detail.value,t.t0=e.detail.column,t.next=0===t.t0?4:1===t.t0?13:2===t.t0?18:19;break;case 4:return t.next=6,this.getCity(this.multiArray[0][e.detail.value].id);case 6:return this.multiArray[1]=t.sent,t.next=9,this.getCity(this.multiArray[1][0].id);case 9:return this.multiArray[2]=t.sent,this.multiIndex[1]=0,this.multiIndex[2]=0,t.abrupt("break",19);case 13:return t.next=15,this.getCity(this.multiArray[1][e.detail.value].id);case 15:return this.multiArray[2]=t.sent,this.multiIndex[2]=0,t.abrupt("break",19);case 18:return t.abrupt("break",19);case 19:this.$forceUpdate(),console.log(this.multiIndex);case 21:case"end":return t.stop()}},t,this)}));function e(e){return t.apply(this,arguments)}return e}(),bindMultiPickerChange:function(t){this.multiArray[2].length?this.address.area_ids="".concat(this.multiArray[0][this.multiIndex[0]].id,",").concat(this.multiArray[1][this.multiIndex[1]].id,",").concat(this.multiArray[2][this.multiIndex[2]].id):this.address.area_ids="".concat(this.multiArray[0][this.multiIndex[0]].id,",").concat(this.multiArray[1][this.multiIndex[1]].id),this.multiArray[2].length?this.address.area_names="".concat(this.multiArray[0][this.multiIndex[0]].area_name,",").concat(this.multiArray[1][this.multiIndex[1]].area_name,",").concat(this.multiArray[2][this.multiIndex[2]].area_name):this.address.area_names="".concat(this.multiArray[0][this.multiIndex[0]].area_name,",").concat(this.multiArray[1][this.multiIndex[1]].area_name),console.log(this.multiIndex)},switch1Change:function(t){console.log("switch1 发生 change 事件，携带值为",t.target.value),t.target.value?this.address.default=1:this.address.default=0},saveAddress:function(){var t=(0,a.default)(regeneratorRuntime.mark(function t(){var e,n;return regeneratorRuntime.wrap(function(t){while(1)switch(t.prev=t.next){case 0:if(this.isEmpty(this.address.name,"输入收货人姓名")&&this.isPoneAvailable(this.address.phone,!0)&&this.isEmpty(this.address.area_ids,"请选择地址")&&this.isEmpty(this.address.address,"输入详细地址")){t.next=2;break}return t.abrupt("return");case 2:return e={name:this.address.name,phone:this.address.phone,area_ids:this.address.area_ids,area_names:this.address.area_names,address:this.address.address,id:this.address.id,default:this.address.default},t.prev=3,t.next=6,this.$minApi.addressAddOrEdit(e);case 6:n=t.sent,console.log(n),200===n.code&&uni.navigateBack(),t.next=14;break;case 11:t.prev=11,t.t0=t["catch"](3),console.log(t.t0);case 14:case"end":return t.stop()}},t,this,[[3,11]])}));function e(){return t.apply(this,arguments)}return e}(),upIdInfo:function(){this.$parseURL().id?(console.log("编辑的时候，编辑身份证信息"),this._goPage("id_card_authentication",{address_id:this.$parseURL().id})):this._goPage("id_card_authentication")}},components:{uniCollapse:s.default,uniCollapseItem:r.default}};e.default=o},5868:function(t,e,n){e=t.exports=n("2350")(!1),e.push([t.i,'@charset "UTF-8";\n/**\r\n * 这里是uni-app内置的常用样式变量\r\n *\r\n * uni-app 官方扩展插件及插件市场（https://ext.dcloud.net.cn）上很多三方插件均使用了这些样式变量\r\n * 如果你是插件开发者，建议你使用scss预处理，并在插件代码中直接使用这些变量（无需 import 这个文件），方便用户通过搭积木的方式开发整体风格一致的App\r\n *\r\n */\n/**\r\n * 如果你是App开发者（插件使用者），你可以通过修改这些变量来定制自己的插件主题，实现自定义主题功能\r\n *\r\n * 如果你的项目同样使用了scss预处理，你也可以直接在你的 scss 代码中使用如下变量，同时无需 import 这个文件\r\n */\n/* 颜色变量 */\n/* 行为相关颜色 */\n/* 文字基本颜色 */\n/* 背景颜色 */\n/* 边框颜色 */\n/* 尺寸变量 */\n/* 文字尺寸 */\n/* 图片尺寸 */\n/* Border Radius */\n/* 水平间距 */\n/* 垂直间距 */\n/* 透明度 */\n/* 文章场景相关 */\n/* 页面设置相关 */\n/* 边线相关 */\n/* 主要颜色 */\n/* 超出行数显示 省略号*/\n/* 阿里巴巴图标被选中状态的样式 */.icon-color[data-v-85addd90]{color:#fc5a5a}*[data-v-85addd90]{font-size:%?28?%;overflow:hidden}.container[data-v-85addd90]{font-size:%?28?%}.container .address[data-v-85addd90]{margin-top:%?16?%;margin-bottom:%?48?%;background:#fff;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-flex:1;-webkit-flex:1;flex:1;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;padding-left:%?16?%;padding-right:%?16?%}.container .address .a-input[data-v-85addd90]{border-bottom:%?1?% solid #ccc;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:horizontal;-webkit-box-direction:normal;-webkit-flex-direction:row;flex-direction:row;-webkit-box-pack:start;-webkit-justify-content:flex-start;justify-content:flex-start;-webkit-box-align:center;-webkit-align-items:center;align-items:center;width:100%;height:%?100?%;line-height:%?100?%}.container .address .a-input .name[data-v-85addd90]{width:30%}.container .address .a-input .value[data-v-85addd90]{width:60%}.container .address .a-input[data-v-85addd90]:last-child{border:none}.container .address .a-input:last-child .value[data-v-85addd90]{width:50%}.container .address .a-input:last-child .value2[data-v-85addd90]{width:20%}.container .address[data-v-85addd90]:last-child{margin-bottom:0;border:none}.container .address .tips[data-v-85addd90]{padding:%?46?% 0;font-size:%?28?%}.container .address .tips .title[data-v-85addd90]{width:100%;text-align:left;color:#f82b2b;margin-bottom:%?18?%}.container .address .tips .text[data-v-85addd90]{width:100%;text-align:justify;color:grey}.container .btns[data-v-85addd90]{margin-top:%?48?%;padding:%?16?%}.container .btns .save[data-v-85addd90]{background:#fc5a5a;color:#fff;margin-bottom:%?20?%;height:%?80?%;line-height:%?80?%;text-align:center;font-size:%?28?%}.container .btns .del[data-v-85addd90]{background:#fff;color:#fc5a5a;border:%?1?% solid #fc5a5a;box-sizing:border-box;height:%?80?%;line-height:%?80?%;text-align:center;font-size:%?28?%}',""])},6220:function(t,e,n){"use strict";n.r(e);var i=n("26cb"),a=n("f286");for(var s in a)"default"!==s&&function(t){n.d(e,t,function(){return a[t]})}(s);n("00ac");var r,o=n("f0c5"),l=Object(o["a"])(a["default"],i["b"],i["c"],!1,null,"53080147",null,!1,i["a"],r);e["default"]=l.exports},"6c3d":function(t,e,n){"use strict";n.r(e);var i=n("542c"),a=n.n(i);for(var s in i)"default"!==s&&function(t){n.d(e,t,function(){return i[t]})}(s);e["default"]=a.a},"7a52":function(t,e,n){"use strict";var i=n("288e");Object.defineProperty(e,"__esModule",{value:!0}),e.default=void 0,n("ac6a"),n("6b54"),n("c5f6");var a=i(n("3b58")),s={name:"UniCollapseItem",components:{uniIcon:a.default},props:{title:{type:String,default:""},name:{type:[Number,String],default:0},disabled:{type:[Boolean,String],default:!1},showAnimation:{type:Boolean,default:!1},open:{type:[Boolean,String],default:!1},thumb:{type:String,default:""}},data:function(){var t="Uni_".concat(Math.ceil(1e6*Math.random()).toString(36));return{isOpen:!1,height:"auto",elId:t}},watch:{open:function(t){this.isOpen=t}},inject:["collapse"],created:function(){if(this.isOpen=this.open,this.nameSync=this.name?this.name:this.collapse.childrens.length,this.collapse.childrens.push(this),"true"===String(this.collapse.accordion)&&this.isOpen){var t=this.collapse.childrens[this.collapse.childrens.length-2];t&&(this.collapse.childrens[this.collapse.childrens.length-2].isOpen=!1)}},mounted:function(){this._getSize()},methods:{_getSize:function(){var t=this;this.showAnimation&&uni.createSelectorQuery().in(this).select("#".concat(this.elId)).boundingClientRect().exec(function(e){t.height=e[0].height+"px",console.log(t.height)})},onClick:function(){var t=this;this.disabled||("true"===String(this.collapse.accordion)&&this.collapse.childrens.forEach(function(e){e!==t&&(e.isOpen=!1)}),this.isOpen=!this.isOpen,this.collapse.onChange&&this.collapse.onChange())}}};e.default=s},"840d":function(t,e,n){"use strict";n.r(e);var i=n("9e0f"),a=n.n(i);for(var s in i)"default"!==s&&function(t){n.d(e,t,function(){return i[t]})}(s);e["default"]=a.a},"9e0f":function(t,e,n){"use strict";Object.defineProperty(e,"__esModule",{value:!0}),e.default=void 0,n("ac6a");var i={name:"UniCollapse",props:{accordion:{type:[Boolean,String],default:!1}},data:function(){return{}},provide:function(){return{collapse:this}},created:function(){this.childrens=[]},methods:{onChange:function(){var t=[];this.childrens.forEach(function(e,n){e.isOpen&&t.push(e.nameSync)}),this.$emit("change",t)},resize:function(){this.childrens.forEach(function(t){console.log("更新"),t._getSize()})}}};e.default=i},bab7:function(t,e,n){"use strict";var i=n("376c"),a=n.n(i);a.a},d647:function(t,e,n){"use strict";var i,a=function(){var t=this,e=t.$createElement,n=t._self._c||e;return n("v-uni-view",{staticClass:"uni-collapse"},[t._t("default")],2)},s=[];n.d(e,"b",function(){return a}),n.d(e,"c",function(){return s}),n.d(e,"a",function(){return i})},df32:function(t,e,n){"use strict";var i=n("37aa"),a=n.n(i);a.a},f286:function(t,e,n){"use strict";n.r(e);var i=n("7a52"),a=n.n(i);for(var s in i)"default"!==s&&function(t){n.d(e,t,function(){return i[t]})}(s);e["default"]=a.a},fe93:function(t,e,n){e=t.exports=n("2350")(!1),e.push([t.i,'@charset "UTF-8";\n/**\r\n * 这里是uni-app内置的常用样式变量\r\n *\r\n * uni-app 官方扩展插件及插件市场（https://ext.dcloud.net.cn）上很多三方插件均使用了这些样式变量\r\n * 如果你是插件开发者，建议你使用scss预处理，并在插件代码中直接使用这些变量（无需 import 这个文件），方便用户通过搭积木的方式开发整体风格一致的App\r\n *\r\n */\n/**\r\n * 如果你是App开发者（插件使用者），你可以通过修改这些变量来定制自己的插件主题，实现自定义主题功能\r\n *\r\n * 如果你的项目同样使用了scss预处理，你也可以直接在你的 scss 代码中使用如下变量，同时无需 import 这个文件\r\n */\n/* 颜色变量 */\n/* 行为相关颜色 */\n/* 文字基本颜色 */\n/* 背景颜色 */\n/* 边框颜色 */\n/* 尺寸变量 */\n/* 文字尺寸 */\n/* 图片尺寸 */\n/* Border Radius */\n/* 水平间距 */\n/* 垂直间距 */\n/* 透明度 */\n/* 文章场景相关 */\n/* 页面设置相关 */\n/* 边线相关 */\n/* 主要颜色 */\n/* 超出行数显示 省略号*/\n/* 阿里巴巴图标被选中状态的样式 */.icon-color[data-v-53080147]{color:#fc5a5a}.uni-collapse-cell[data-v-53080147]{position:relative}.uni-collapse-cell--hover[data-v-53080147]{background-color:#f5f5f5}.uni-collapse-cell--open[data-v-53080147]{background-color:#f5f5f5}.uni-collapse-cell--disabled[data-v-53080147]{opacity:.3}.uni-collapse-cell--animation[data-v-53080147]{-webkit-transition:all .3s;transition:all .3s}.uni-collapse-cell[data-v-53080147]:after{position:absolute;z-index:3;right:0;bottom:0;left:0;height:1px;content:"";-webkit-transform:scaleY(.5);transform:scaleY(.5);background-color:#f2f2f2}.uni-collapse-cell__title[data-v-53080147]{padding:%?24?% %?30?%;width:100%;box-sizing:border-box;-webkit-box-flex:1;-webkit-flex:1;flex:1;position:relative;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:horizontal;-webkit-box-direction:normal;-webkit-flex-direction:row;flex-direction:row;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.uni-collapse-cell__title-extra[data-v-53080147]{margin-right:%?18?%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:horizontal;-webkit-box-direction:normal;-webkit-flex-direction:row;flex-direction:row;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.uni-collapse-cell__title-img[data-v-53080147]{height:%?52?%;width:%?52?%}.uni-collapse-cell__title-arrow[data-v-53080147]{width:20px;height:20px;-webkit-transform:rotate(0deg);transform:rotate(0deg);-webkit-transform-origin:center center;transform-origin:center center}.uni-collapse-cell__title-arrow.uni-active[data-v-53080147]{-webkit-transform:rotate(-180deg);transform:rotate(-180deg)}.uni-collapse-cell__title-inner[data-v-53080147]{-webkit-box-flex:1;-webkit-flex:1;flex:1;overflow:hidden;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column}.uni-collapse-cell__title-text[data-v-53080147]{font-size:%?32?%;text-overflow:ellipsis;white-space:nowrap;color:inherit;line-height:1.5;overflow:hidden}.uni-collapse-cell__content[data-v-53080147]{position:relative;width:100%;overflow:hidden;background:#fff}.uni-collapse-cell__content .view[data-v-53080147]{font-size:%?28?%}',""])}}]);