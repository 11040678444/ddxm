(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["pages2-address-chooses"],{"055a":function(a,t,e){"use strict";e.r(t);var i=e("f427"),n=e.n(i);for(var o in i)"default"!==o&&function(a){e.d(t,a,function(){return i[a]})}(o);t["default"]=n.a},"069b":function(a,t,e){"use strict";var i=e("e802"),n=e.n(i);n.a},"0f0f":function(a,t,e){"use strict";e.r(t);var i=e("f92f"),n=e("1151");for(var o in n)"default"!==o&&function(a){e.d(t,a,function(){return n[a]})}(o);e("069b");var l=e("2877"),s=Object(l["a"])(n["default"],i["a"],i["b"],!1,null,"5b3ab637",null);t["default"]=s.exports},1151:function(a,t,e){"use strict";e.r(t);var i=e("7206"),n=e.n(i);for(var o in i)"default"!==o&&function(a){e.d(t,a,function(){return i[a]})}(o);t["default"]=n.a},1634:function(a,t,e){var i=e("3a97");"string"===typeof i&&(i=[[a.i,i,""]]),i.locals&&(a.exports=i.locals);var n=e("4f06").default;n("0713a44c",i,!0,{sourceMap:!1,shadowMode:!1})},2536:function(a,t,e){"use strict";var i=function(){var a=this,t=a.$createElement,e=a._self._c||t;return e("v-uni-view",{staticClass:"container"},[e("v-uni-view",{staticClass:"list-all"},a._l(a.list,function(t,i){return e("v-uni-view",{key:i,staticClass:"a-list",on:{click:function(e){e=a.$handleEvent(e),a.choosesAddress(t)}}},[e("v-uni-view",{staticClass:"info"},[e("v-uni-view",{staticClass:"name-moblie"},[e("v-uni-view",{staticClass:"name"},[a._v(a._s(t.name)),t.attestation?e("span",{staticClass:"tag-real-name"},[a._v("已实名")]):a._e()]),e("v-uni-view",{staticClass:"mobile"},[a._v(a._s(t.phone))])],1),e("v-uni-view",{staticClass:"detail"},[e("v-uni-view",{staticClass:"detail-left"},[t.default?e("span",{staticClass:"tag-span"},[a._v("默认")]):a._e(),e("v-uni-view",{staticClass:"text"},[a._v(a._s(t.addres))])],1),e("v-uni-view",{staticClass:"detail-right",on:{click:function(e){e.stopPropagation(),e=a.$handleEvent(e),a.goToAddOrEdit(t)}}},[e("i",{staticClass:"iconfont icon-ddx-shop-bianji"})])],1)],1)],1)}),1),0===a.list.length?e("uni-load-more",{attrs:{status:"noMore"}}):a._e(),e("v-uni-view",{staticClass:"fixed",on:{click:function(t){t=a.$handleEvent(t),a.goToAddOrEdit()}}},[e("span",{staticClass:"iconfont icon-ddx-shop-anonymous-iconfont icon-color"}),a._v("新增收货地址")])],1)},n=[];e.d(t,"a",function(){return i}),e.d(t,"b",function(){return n})},3172:function(a,t,e){"use strict";e.r(t);var i=e("2536"),n=e("055a");for(var o in n)"default"!==o&&function(a){e.d(t,a,function(){return n[a]})}(o);e("a3da");var l=e("2877"),s=Object(l["a"])(n["default"],i["a"],i["b"],!1,null,"bed4f9ae",null);t["default"]=s.exports},"3a97":function(a,t,e){t=a.exports=e("2350")(!1),t.push([a.i,'@charset "UTF-8";\n/**\r\n * 这里是uni-app内置的常用样式变量\r\n *\r\n * uni-app 官方扩展插件及插件市场（https://ext.dcloud.net.cn）上很多三方插件均使用了这些样式变量\r\n * 如果你是插件开发者，建议你使用scss预处理，并在插件代码中直接使用这些变量（无需 import 这个文件），方便用户通过搭积木的方式开发整体风格一致的App\r\n *\r\n */\n/**\r\n * 如果你是App开发者（插件使用者），你可以通过修改这些变量来定制自己的插件主题，实现自定义主题功能\r\n *\r\n * 如果你的项目同样使用了scss预处理，你也可以直接在你的 scss 代码中使用如下变量，同时无需 import 这个文件\r\n */\n/* 颜色变量 */\n/* 行为相关颜色 */\n/* 文字基本颜色 */\n/* 背景颜色 */\n/* 边框颜色 */\n/* 尺寸变量 */\n/* 文字尺寸 */\n/* 图片尺寸 */\n/* Border Radius */\n/* 水平间距 */\n/* 垂直间距 */\n/* 透明度 */\n/* 文章场景相关 */\n/* 页面设置相关 */\n/* 边线相关 */\n/* 主要颜色 */\n/* 超出行数显示 省略号*/.container .list-all .a-list .info .detail .detail-left .text[data-v-bed4f9ae]{word-break:break-all;\n  /*属性规定自动换行的处理方法。normal(使用浏览器默认的换行规则。),break-all(允许在单词内换行。),keep-all(只能在半角空格或连字符处换行。)*/-o-text-overflow:ellipsis;text-overflow:ellipsis;display:-webkit-box;\n  /** 对象作为伸缩盒子模型显示 **/-webkit-box-orient:vertical;\n  /** 设置或检索伸缩盒对象的子元素的排列方式 **/-webkit-line-clamp:1;\n  /** 显示的行数 **/overflow:hidden\n  /** 隐藏超出的内容 **/}\n/* 阿里巴巴图标被选中状态的样式 */.icon-color[data-v-bed4f9ae]{color:#fc5a5a}.container *[data-v-bed4f9ae]{color:#1a1a1a}.container .list-all[data-v-bed4f9ae]{background:#fff;display:-webkit-box;display:-webkit-flex;display:-ms-flexbox;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;-ms-flex-direction:column;flex-direction:column;padding:0 %?20?%;margin-bottom:%?120?%}.container .list-all .a-list[data-v-bed4f9ae]{font-size:%?28?%;display:-webkit-box;display:-webkit-flex;display:-ms-flexbox;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;-ms-flex-pack:justify;justify-content:space-between;padding:%?30?% 0;width:100%}.container .list-all .a-list .info[data-v-bed4f9ae]{width:100%}.container .list-all .a-list .info .name-moblie[data-v-bed4f9ae]{display:-webkit-box;display:-webkit-flex;display:-ms-flexbox;display:flex;-webkit-box-orient:horizontal;-webkit-box-direction:normal;-webkit-flex-direction:row;-ms-flex-direction:row;flex-direction:row;-webkit-box-pack:justify;-webkit-justify-content:space-between;-ms-flex-pack:justify;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;-ms-flex-align:center;align-items:center;font-size:%?32?%;line-height:%?64?%}.container .list-all .a-list .info .name-moblie .name .tag-real-name[data-v-bed4f9ae]{margin-left:%?10?%;font-size:%?24?%;color:#fc5a5a;border:1px solid #fc5a5a;padding:%?4?% %?6?%;border-radius:%?4?%}.container .list-all .a-list .info .name-moblie .mobile[data-v-bed4f9ae]{margin-left:%?20?%;color:grey;font-size:%?24?%}.container .list-all .a-list .info .detail[data-v-bed4f9ae]{width:100%;display:-webkit-box;display:-webkit-flex;display:-ms-flexbox;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;-ms-flex-pack:justify;justify-content:space-between;color:#1a1a1a}.container .list-all .a-list .info .detail .detail-left[data-v-bed4f9ae]{overflow:hidden;display:-webkit-box;display:-webkit-flex;display:-ms-flexbox;display:flex;-webkit-box-align:center;-webkit-align-items:center;-ms-flex-align:center;align-items:center;width:80%}.container .list-all .a-list .info .detail .detail-left .tag-span[data-v-bed4f9ae]{color:#fc5a5a;background:#fce8e8;padding:%?4?% %?6?%;margin-right:%?10?%}.container .list-all .a-list .info .detail .detail-left .text[data-v-bed4f9ae]{width:80%}.container .list-all .a-list .info .detail .detail-right[data-v-bed4f9ae]{display:-webkit-box;display:-webkit-flex;display:-ms-flexbox;display:flex;-webkit-box-pack:end;-webkit-justify-content:flex-end;-ms-flex-pack:end;justify-content:flex-end;width:20%}.container .list-all .a-list .info .detail .detail-right .iconfont[data-v-bed4f9ae]{font-size:%?32?%}.container .fixed[data-v-bed4f9ae]{background:#fff;position:fixed;bottom:0;left:0;z-index:99;width:100%;height:%?100?%;line-height:%?100?%;text-align:center}.container .fixed span[data-v-bed4f9ae]{margin-right:%?10?%}.container .fixed .iconfont[data-v-bed4f9ae]{color:#fc5a5a}',""])},7206:function(a,t,e){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.default=void 0;var i={name:"UniLoadMore",props:{status:{type:String,default:"more"},showIcon:{type:Boolean,default:!0},color:{type:String,default:"#777777"},contentText:{type:Object,default:function(){return{contentdown:"上拉显示更多",contentrefresh:"正在加载...",contentnomore:"没有更多数据了"}}}},data:function(){return{}}};t.default=i},a3da:function(a,t,e){"use strict";var i=e("1634"),n=e.n(i);n.a},e58a:function(a,t,e){t=a.exports=e("2350")(!1),t.push([a.i,'@charset "UTF-8";\n/**\r\n * 这里是uni-app内置的常用样式变量\r\n *\r\n * uni-app 官方扩展插件及插件市场（https://ext.dcloud.net.cn）上很多三方插件均使用了这些样式变量\r\n * 如果你是插件开发者，建议你使用scss预处理，并在插件代码中直接使用这些变量（无需 import 这个文件），方便用户通过搭积木的方式开发整体风格一致的App\r\n *\r\n */\n/**\r\n * 如果你是App开发者（插件使用者），你可以通过修改这些变量来定制自己的插件主题，实现自定义主题功能\r\n *\r\n * 如果你的项目同样使用了scss预处理，你也可以直接在你的 scss 代码中使用如下变量，同时无需 import 这个文件\r\n */\n/* 颜色变量 */\n/* 行为相关颜色 */\n/* 文字基本颜色 */\n/* 背景颜色 */\n/* 边框颜色 */\n/* 尺寸变量 */\n/* 文字尺寸 */\n/* 图片尺寸 */\n/* Border Radius */\n/* 水平间距 */\n/* 垂直间距 */\n/* 透明度 */\n/* 文章场景相关 */\n/* 页面设置相关 */\n/* 边线相关 */\n/* 主要颜色 */\n/* 超出行数显示 省略号*/\n/* 阿里巴巴图标被选中状态的样式 */.icon-color[data-v-5b3ab637]{color:#fc5a5a}.uni-load-more[data-v-5b3ab637]{display:-webkit-box;display:-webkit-flex;display:-ms-flexbox;display:flex;-webkit-box-orient:horizontal;-webkit-box-direction:normal;-webkit-flex-direction:row;-ms-flex-direction:row;flex-direction:row;height:%?80?%;-webkit-box-align:center;-webkit-align-items:center;-ms-flex-align:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;-ms-flex-pack:center;justify-content:center}.uni-load-more__text[data-v-5b3ab637]{font-size:%?28?%;color:#999}.uni-load-more__img[data-v-5b3ab637]{height:24px;width:24px;margin-right:10px}.uni-load-more__img>.load[data-v-5b3ab637]{position:absolute}.uni-load-more__img>.load .uni-load-view_wrapper[data-v-5b3ab637]{width:6px;height:2px;border-top-left-radius:1px;border-bottom-left-radius:1px;background:#999;position:absolute;opacity:.2;-webkit-transform-origin:50%;-ms-transform-origin:50%;transform-origin:50%;-webkit-animation:load-data-v-5b3ab637 1.56s ease infinite;animation:load-data-v-5b3ab637 1.56s ease infinite}.uni-load-more__img>.load .uni-load-view_wrapper[data-v-5b3ab637]:first-child{-webkit-transform:rotate(90deg);-ms-transform:rotate(90deg);transform:rotate(90deg);top:2px;left:9px}.uni-load-more__img>.load .uni-load-view_wrapper[data-v-5b3ab637]:nth-child(2){-webkit-transform:rotate(180deg);-ms-transform:rotate(180deg);transform:rotate(180deg);top:11px;right:0}.uni-load-more__img>.load .uni-load-view_wrapper[data-v-5b3ab637]:nth-child(3){-webkit-transform:rotate(270deg);-ms-transform:rotate(270deg);transform:rotate(270deg);bottom:2px;left:9px}.uni-load-more__img>.load .uni-load-view_wrapper[data-v-5b3ab637]:nth-child(4){top:11px;left:0}.load1[data-v-5b3ab637],.load2[data-v-5b3ab637],.load3[data-v-5b3ab637]{height:24px;width:24px}.load2[data-v-5b3ab637]{-webkit-transform:rotate(30deg);-ms-transform:rotate(30deg);transform:rotate(30deg)}.load3[data-v-5b3ab637]{-webkit-transform:rotate(60deg);-ms-transform:rotate(60deg);transform:rotate(60deg)}.load1 .uni-load-view_wrapper[data-v-5b3ab637]:first-child{-webkit-animation-delay:0s;animation-delay:0s}.load2 .uni-load-view_wrapper[data-v-5b3ab637]:first-child{-webkit-animation-delay:.13s;animation-delay:.13s}.load3 .uni-load-view_wrapper[data-v-5b3ab637]:first-child{-webkit-animation-delay:.26s;animation-delay:.26s}.load1 .uni-load-view_wrapper[data-v-5b3ab637]:nth-child(2){-webkit-animation-delay:.39s;animation-delay:.39s}.load2 .uni-load-view_wrapper[data-v-5b3ab637]:nth-child(2){-webkit-animation-delay:.52s;animation-delay:.52s}.load3 .uni-load-view_wrapper[data-v-5b3ab637]:nth-child(2){-webkit-animation-delay:.65s;animation-delay:.65s}.load1 .uni-load-view_wrapper[data-v-5b3ab637]:nth-child(3){-webkit-animation-delay:.78s;animation-delay:.78s}.load2 .uni-load-view_wrapper[data-v-5b3ab637]:nth-child(3){-webkit-animation-delay:.91s;animation-delay:.91s}.load3 .uni-load-view_wrapper[data-v-5b3ab637]:nth-child(3){-webkit-animation-delay:1.04s;animation-delay:1.04s}.load1 .uni-load-view_wrapper[data-v-5b3ab637]:nth-child(4){-webkit-animation-delay:1.17s;animation-delay:1.17s}.load2 .uni-load-view_wrapper[data-v-5b3ab637]:nth-child(4){-webkit-animation-delay:1.3s;animation-delay:1.3s}.load3 .uni-load-view_wrapper[data-v-5b3ab637]:nth-child(4){-webkit-animation-delay:1.43s;animation-delay:1.43s}@-webkit-keyframes load-data-v-5b3ab637{0%{opacity:1}to{opacity:.2}}',""])},e802:function(a,t,e){var i=e("e58a");"string"===typeof i&&(i=[[a.i,i,""]]),i.locals&&(a.exports=i.locals);var n=e("4f06").default;n("0179c468",i,!0,{sourceMap:!1,shadowMode:!1})},f427:function(a,t,e){"use strict";var i=e("288e");Object.defineProperty(t,"__esModule",{value:!0}),t.default=void 0;var n=i(e("0f0f")),o={name:"address_choose",components:{uniLoadMore:n.default},data:function(){return{isChoosesAddress:!1,list:[]}},onLoad:function(){console.log("带过来的参数",this.$parseURL())},onUnload:function(){this.isChoosesAddress||(console.log("没有选择任何地址"),this.$eventHub.$emit("address",{}))},onShow:function(){this.loadData()},methods:{goToAddOrEdit:function(){var a=arguments.length>0&&void 0!==arguments[0]?arguments[0]:{};this.$openPage({name:"address_add",query:a})},loadData:function(){var a=this;this.$minApi.addressList().then(function(t){console.log(t),200===t.code&&(a.list=t.data)}).catch(function(a){console.log(a)})},choosesAddress:function(a){this.isChoosesAddress=!0,console.log("您选择的收货地址是：",a);var t=this;uni.navigateBack({delta:1,success:function(a){},fail:function(a){},complete:function(){t.$eventHub.$emit("address",a)}})}}};t.default=o},f92f:function(a,t,e){"use strict";var i=function(){var a=this,t=a.$createElement,e=a._self._c||t;return e("v-uni-view",{staticClass:"uni-load-more"},[e("v-uni-view",{directives:[{name:"show",rawName:"v-show",value:"loading"===a.status&&a.showIcon,expression:"status === 'loading' && showIcon"}],staticClass:"uni-load-more__img"},[e("v-uni-view",{staticClass:"load1 load"},[e("v-uni-view",{staticClass:"uni-load-view_wrapper",style:{background:a.color}}),e("v-uni-view",{staticClass:"uni-load-view_wrapper",style:{background:a.color}}),e("v-uni-view",{staticClass:"uni-load-view_wrapper",style:{background:a.color}}),e("v-uni-view",{staticClass:"uni-load-view_wrapper",style:{background:a.color}})],1),e("v-uni-view",{staticClass:"load2 load"},[e("v-uni-view",{staticClass:"uni-load-view_wrapper",style:{background:a.color}}),e("v-uni-view",{staticClass:"uni-load-view_wrapper",style:{background:a.color}}),e("v-uni-view",{staticClass:"uni-load-view_wrapper",style:{background:a.color}}),e("v-uni-view",{staticClass:"uni-load-view_wrapper",style:{background:a.color}})],1),e("v-uni-view",{staticClass:"load3 load"},[e("v-uni-view",{staticClass:"uni-load-view_wrapper",style:{background:a.color}}),e("v-uni-view",{staticClass:"uni-load-view_wrapper",style:{background:a.color}}),e("v-uni-view",{staticClass:"uni-load-view_wrapper",style:{background:a.color}}),e("v-uni-view",{staticClass:"uni-load-view_wrapper",style:{background:a.color}})],1)],1),e("v-uni-text",{staticClass:"uni-load-more__text",style:{color:a.color}},[a._v(a._s("more"===a.status?a.contentText.contentdown:"loading"===a.status?a.contentText.contentrefresh:a.contentText.contentnomore))])],1)},n=[];e.d(t,"a",function(){return i}),e.d(t,"b",function(){return n})}}]);