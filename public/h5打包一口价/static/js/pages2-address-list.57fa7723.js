(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["pages2-address-list"],{"0d2d":function(t,a,i){"use strict";i.r(a);var e=i("43c0"),n=i("7642");for(var o in n)"default"!==o&&function(t){i.d(a,t,function(){return n[t]})}(o);i("da84");var l,r=i("f0c5"),s=Object(r["a"])(n["default"],e["b"],e["c"],!1,null,"272348f8",null,!1,e["a"],l);a["default"]=s.exports},"1dd6":function(t,a,i){"use strict";var e=i("4d1e"),n=i.n(e);n.a},"28d8":function(t,a,i){"use strict";Object.defineProperty(a,"__esModule",{value:!0}),a.default=void 0;var e={name:"UniLoadMore",props:{status:{type:String,default:"more"},showIcon:{type:Boolean,default:!0},color:{type:String,default:"#777777"},contentText:{type:Object,default:function(){return{contentdown:"上拉显示更多",contentrefresh:"正在加载...",contentnomore:"没有更多数据了"}}}},data:function(){return{}}};a.default=e},"33b8":function(t,a,i){a=t.exports=i("2350")(!1),a.push([t.i,'@charset "UTF-8";\n/**\r\n * 这里是uni-app内置的常用样式变量\r\n *\r\n * uni-app 官方扩展插件及插件市场（https://ext.dcloud.net.cn）上很多三方插件均使用了这些样式变量\r\n * 如果你是插件开发者，建议你使用scss预处理，并在插件代码中直接使用这些变量（无需 import 这个文件），方便用户通过搭积木的方式开发整体风格一致的App\r\n *\r\n */\n/**\r\n * 如果你是App开发者（插件使用者），你可以通过修改这些变量来定制自己的插件主题，实现自定义主题功能\r\n *\r\n * 如果你的项目同样使用了scss预处理，你也可以直接在你的 scss 代码中使用如下变量，同时无需 import 这个文件\r\n */\n/* 颜色变量 */\n/* 行为相关颜色 */\n/* 文字基本颜色 */\n/* 背景颜色 */\n/* 边框颜色 */\n/* 尺寸变量 */\n/* 文字尺寸 */\n/* 图片尺寸 */\n/* Border Radius */\n/* 水平间距 */\n/* 垂直间距 */\n/* 透明度 */\n/* 文章场景相关 */\n/* 页面设置相关 */\n/* 边线相关 */\n/* 主要颜色 */\n/* 超出行数显示 省略号*/.container .list-all .a-list .info .detail .detail-left .text[data-v-3080f515]{word-break:break-all;\n  /*属性规定自动换行的处理方法。normal(使用浏览器默认的换行规则。),break-all(允许在单词内换行。),keep-all(只能在半角空格或连字符处换行。)*/text-overflow:ellipsis;display:-webkit-box;\n  /** 对象作为伸缩盒子模型显示 **/-webkit-box-orient:vertical;\n  /** 设置或检索伸缩盒对象的子元素的排列方式 **/-webkit-line-clamp:1;\n  /** 显示的行数 **/overflow:hidden\n  /** 隐藏超出的内容 **/}\n/* 阿里巴巴图标被选中状态的样式 */.icon-color[data-v-3080f515]{color:#fc5a5a}.container *[data-v-3080f515]{color:#1a1a1a}.container .list-all[data-v-3080f515]{background:#fff;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;padding:0 %?20?%;margin-bottom:%?120?%}.container .list-all .a-list[data-v-3080f515]{font-size:%?28?%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;padding:%?30?% 0;width:100%}.container .list-all .a-list .info[data-v-3080f515]{width:100%}.container .list-all .a-list .info .name-moblie[data-v-3080f515]{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:horizontal;-webkit-box-direction:normal;-webkit-flex-direction:row;flex-direction:row;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;align-items:center;font-size:%?32?%;line-height:%?64?%}.container .list-all .a-list .info .name-moblie .name .tag-real-name[data-v-3080f515]{margin-left:%?10?%;font-size:%?24?%;color:#fc5a5a;border:1px solid #fc5a5a;padding:%?4?% %?6?%;border-radius:%?4?%}.container .list-all .a-list .info .name-moblie .mobile[data-v-3080f515]{margin-left:%?20?%;color:grey;font-size:%?24?%}.container .list-all .a-list .info .detail[data-v-3080f515]{width:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;color:#1a1a1a}.container .list-all .a-list .info .detail .detail-left[data-v-3080f515]{overflow:hidden;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;width:80%}.container .list-all .a-list .info .detail .detail-left .tag-span[data-v-3080f515]{color:#fc5a5a;background:#fce8e8;padding:%?4?% %?6?%;margin-right:%?10?%}.container .list-all .a-list .info .detail .detail-left .text[data-v-3080f515]{width:80%}.container .list-all .a-list .info .detail .detail-right[data-v-3080f515]{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:end;-webkit-justify-content:flex-end;justify-content:flex-end;width:20%}.container .list-all .a-list .info .detail .detail-right .iconfont[data-v-3080f515]{font-size:%?32?%}.container .fixed[data-v-3080f515]{background:#fff;position:fixed;bottom:0;left:0;z-index:99;width:100%;height:%?100?%;line-height:%?100?%;text-align:center}.container .fixed span[data-v-3080f515]{margin-right:%?10?%}.container .fixed .iconfont[data-v-3080f515]{color:#fc5a5a}',""])},"43c0":function(t,a,i){"use strict";var e,n=function(){var t=this,a=t.$createElement,i=t._self._c||a;return i("v-uni-view",{staticClass:"uni-load-more"},[i("v-uni-view",{directives:[{name:"show",rawName:"v-show",value:"loading"===t.status&&t.showIcon,expression:"status === 'loading' && showIcon"}],staticClass:"uni-load-more__img"},[i("v-uni-view",{staticClass:"load1 load"},[i("v-uni-view",{staticClass:"uni-load-view_wrapper",style:{background:t.color}}),i("v-uni-view",{staticClass:"uni-load-view_wrapper",style:{background:t.color}}),i("v-uni-view",{staticClass:"uni-load-view_wrapper",style:{background:t.color}}),i("v-uni-view",{staticClass:"uni-load-view_wrapper",style:{background:t.color}})],1),i("v-uni-view",{staticClass:"load2 load"},[i("v-uni-view",{staticClass:"uni-load-view_wrapper",style:{background:t.color}}),i("v-uni-view",{staticClass:"uni-load-view_wrapper",style:{background:t.color}}),i("v-uni-view",{staticClass:"uni-load-view_wrapper",style:{background:t.color}}),i("v-uni-view",{staticClass:"uni-load-view_wrapper",style:{background:t.color}})],1),i("v-uni-view",{staticClass:"load3 load"},[i("v-uni-view",{staticClass:"uni-load-view_wrapper",style:{background:t.color}}),i("v-uni-view",{staticClass:"uni-load-view_wrapper",style:{background:t.color}}),i("v-uni-view",{staticClass:"uni-load-view_wrapper",style:{background:t.color}}),i("v-uni-view",{staticClass:"uni-load-view_wrapper",style:{background:t.color}})],1)],1),i("v-uni-text",{staticClass:"uni-load-more__text",style:{color:t.color}},[t._v(t._s("more"===t.status?t.contentText.contentdown:"loading"===t.status?t.contentText.contentrefresh:t.contentText.contentnomore))])],1)},o=[];i.d(a,"b",function(){return n}),i.d(a,"c",function(){return o}),i.d(a,"a",function(){return e})},"4d1e":function(t,a,i){var e=i("33b8");"string"===typeof e&&(e=[[t.i,e,""]]),e.locals&&(t.exports=e.locals);var n=i("4f06").default;n("5c2e2e28",e,!0,{sourceMap:!1,shadowMode:!1})},7642:function(t,a,i){"use strict";i.r(a);var e=i("28d8"),n=i.n(e);for(var o in e)"default"!==o&&function(t){i.d(a,t,function(){return e[t]})}(o);a["default"]=n.a},"803c":function(t,a,i){a=t.exports=i("2350")(!1),a.push([t.i,'@charset "UTF-8";\n/**\r\n * 这里是uni-app内置的常用样式变量\r\n *\r\n * uni-app 官方扩展插件及插件市场（https://ext.dcloud.net.cn）上很多三方插件均使用了这些样式变量\r\n * 如果你是插件开发者，建议你使用scss预处理，并在插件代码中直接使用这些变量（无需 import 这个文件），方便用户通过搭积木的方式开发整体风格一致的App\r\n *\r\n */\n/**\r\n * 如果你是App开发者（插件使用者），你可以通过修改这些变量来定制自己的插件主题，实现自定义主题功能\r\n *\r\n * 如果你的项目同样使用了scss预处理，你也可以直接在你的 scss 代码中使用如下变量，同时无需 import 这个文件\r\n */\n/* 颜色变量 */\n/* 行为相关颜色 */\n/* 文字基本颜色 */\n/* 背景颜色 */\n/* 边框颜色 */\n/* 尺寸变量 */\n/* 文字尺寸 */\n/* 图片尺寸 */\n/* Border Radius */\n/* 水平间距 */\n/* 垂直间距 */\n/* 透明度 */\n/* 文章场景相关 */\n/* 页面设置相关 */\n/* 边线相关 */\n/* 主要颜色 */\n/* 超出行数显示 省略号*/\n/* 阿里巴巴图标被选中状态的样式 */.icon-color[data-v-272348f8]{color:#fc5a5a}.uni-load-more[data-v-272348f8]{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:horizontal;-webkit-box-direction:normal;-webkit-flex-direction:row;flex-direction:row;height:%?80?%;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center}.uni-load-more__text[data-v-272348f8]{font-size:%?28?%;color:#999}.uni-load-more__img[data-v-272348f8]{height:24px;width:24px;margin-right:10px}.uni-load-more__img>.load[data-v-272348f8]{position:absolute}.uni-load-more__img>.load .uni-load-view_wrapper[data-v-272348f8]{width:6px;height:2px;border-top-left-radius:1px;border-bottom-left-radius:1px;background:#999;position:absolute;opacity:.2;-webkit-transform-origin:50%;transform-origin:50%;-webkit-animation:load-data-v-272348f8 1.56s ease infinite;animation:load-data-v-272348f8 1.56s ease infinite}.uni-load-more__img>.load .uni-load-view_wrapper[data-v-272348f8]:first-child{-webkit-transform:rotate(90deg);transform:rotate(90deg);top:2px;left:9px}.uni-load-more__img>.load .uni-load-view_wrapper[data-v-272348f8]:nth-child(2){-webkit-transform:rotate(180deg);transform:rotate(180deg);top:11px;right:0}.uni-load-more__img>.load .uni-load-view_wrapper[data-v-272348f8]:nth-child(3){-webkit-transform:rotate(270deg);transform:rotate(270deg);bottom:2px;left:9px}.uni-load-more__img>.load .uni-load-view_wrapper[data-v-272348f8]:nth-child(4){top:11px;left:0}.load1[data-v-272348f8],.load2[data-v-272348f8],.load3[data-v-272348f8]{height:24px;width:24px}.load2[data-v-272348f8]{-webkit-transform:rotate(30deg);transform:rotate(30deg)}.load3[data-v-272348f8]{-webkit-transform:rotate(60deg);transform:rotate(60deg)}.load1 .uni-load-view_wrapper[data-v-272348f8]:first-child{-webkit-animation-delay:0s;animation-delay:0s}.load2 .uni-load-view_wrapper[data-v-272348f8]:first-child{-webkit-animation-delay:.13s;animation-delay:.13s}.load3 .uni-load-view_wrapper[data-v-272348f8]:first-child{-webkit-animation-delay:.26s;animation-delay:.26s}.load1 .uni-load-view_wrapper[data-v-272348f8]:nth-child(2){-webkit-animation-delay:.39s;animation-delay:.39s}.load2 .uni-load-view_wrapper[data-v-272348f8]:nth-child(2){-webkit-animation-delay:.52s;animation-delay:.52s}.load3 .uni-load-view_wrapper[data-v-272348f8]:nth-child(2){-webkit-animation-delay:.65s;animation-delay:.65s}.load1 .uni-load-view_wrapper[data-v-272348f8]:nth-child(3){-webkit-animation-delay:.78s;animation-delay:.78s}.load2 .uni-load-view_wrapper[data-v-272348f8]:nth-child(3){-webkit-animation-delay:.91s;animation-delay:.91s}.load3 .uni-load-view_wrapper[data-v-272348f8]:nth-child(3){-webkit-animation-delay:1.04s;animation-delay:1.04s}.load1 .uni-load-view_wrapper[data-v-272348f8]:nth-child(4){-webkit-animation-delay:1.17s;animation-delay:1.17s}.load2 .uni-load-view_wrapper[data-v-272348f8]:nth-child(4){-webkit-animation-delay:1.3s;animation-delay:1.3s}.load3 .uni-load-view_wrapper[data-v-272348f8]:nth-child(4){-webkit-animation-delay:1.43s;animation-delay:1.43s}@-webkit-keyframes load-data-v-272348f8{0%{opacity:1}to{opacity:.2}}',""])},9763:function(t,a,i){"use strict";i.r(a);var e=i("ec76"),n=i.n(e);for(var o in e)"default"!==o&&function(t){i.d(a,t,function(){return e[t]})}(o);a["default"]=n.a},af7b:function(t,a,i){var e=i("803c");"string"===typeof e&&(e=[[t.i,e,""]]),e.locals&&(t.exports=e.locals);var n=i("4f06").default;n("2482a995",e,!0,{sourceMap:!1,shadowMode:!1})},b9db:function(t,a,i){"use strict";var e,n=function(){var t=this,a=t.$createElement,i=t._self._c||a;return i("v-uni-view",{staticClass:"container"},[i("v-uni-view",{staticClass:"list-all"},t._l(t.list,function(a,e){return i("v-uni-view",{key:e,staticClass:"a-list"},[i("v-uni-view",{staticClass:"info"},[i("v-uni-view",{staticClass:"name-moblie"},[i("v-uni-view",{staticClass:"name"},[t._v(t._s(a.name)),a.attestation?i("span",{staticClass:"tag-real-name"},[t._v("已实名")]):t._e()]),i("v-uni-view",{staticClass:"mobile"},[t._v(t._s(a.phone))])],1),i("v-uni-view",{staticClass:"detail"},[i("v-uni-view",{staticClass:"detail-left"},[a.default?i("span",{staticClass:"tag-span"},[t._v("默认")]):t._e(),i("v-uni-view",{staticClass:"text"},[t._v(t._s(a.addres))])],1),i("v-uni-view",{staticClass:"detail-right",on:{click:function(i){arguments[0]=i=t.$handleEvent(i),t.goToAddOrEdit(a)}}},[i("i",{staticClass:"iconfont icon-ddx-shop-bianji"})])],1)],1)],1)}),1),0===t.list.length?i("uni-load-more",{attrs:{status:"noMore"}}):t._e(),i("v-uni-view",{staticClass:"fixed",on:{click:function(a){arguments[0]=a=t.$handleEvent(a),t.goToAddOrEdit()}}},[i("span",{staticClass:"iconfont icon-ddx-shop-anonymous-iconfont icon-color"}),t._v("新增收货地址")])],1)},o=[];i.d(a,"b",function(){return n}),i.d(a,"c",function(){return o}),i.d(a,"a",function(){return e})},c587:function(t,a,i){"use strict";i.r(a);var e=i("b9db"),n=i("9763");for(var o in n)"default"!==o&&function(t){i.d(a,t,function(){return n[t]})}(o);i("1dd6");var l,r=i("f0c5"),s=Object(r["a"])(n["default"],e["b"],e["c"],!1,null,"3080f515",null,!1,e["a"],l);a["default"]=s.exports},da84:function(t,a,i){"use strict";var e=i("af7b"),n=i.n(e);n.a},ec76:function(t,a,i){"use strict";var e=i("288e");Object.defineProperty(a,"__esModule",{value:!0}),a.default=void 0;var n=e(i("0d2d")),o={name:"address_list",components:{uniLoadMore:n.default},data:function(){return{list:[]}},onLoad:function(){console.log("带过来的参数",this.$parseURL())},onShow:function(){this.loadData()},methods:{goToAddOrEdit:function(){var t=arguments.length>0&&void 0!==arguments[0]?arguments[0]:{};this.$openPage({name:"address_add",query:t})},loadData:function(){var t=this;this.$minApi.addressList().then(function(a){console.log(a),200===a.code&&(t.list=a.data)}).catch(function(t){console.log(t)})}}};a.default=o}}]);