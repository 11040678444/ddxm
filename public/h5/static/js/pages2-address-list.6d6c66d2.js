(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["pages2-address-list"],{"0663":function(e,t,a){t=e.exports=a("2350")(!1),t.push([e.i,'@charset "UTF-8";\n/**\r\n * 这里是uni-app内置的常用样式变量\r\n *\r\n * uni-app 官方扩展插件及插件市场（https://ext.dcloud.net.cn）上很多三方插件均使用了这些样式变量\r\n * 如果你是插件开发者，建议你使用scss预处理，并在插件代码中直接使用这些变量（无需 import 这个文件），方便用户通过搭积木的方式开发整体风格一致的App\r\n *\r\n */\n/**\r\n * 如果你是App开发者（插件使用者），你可以通过修改这些变量来定制自己的插件主题，实现自定义主题功能\r\n *\r\n * 如果你的项目同样使用了scss预处理，你也可以直接在你的 scss 代码中使用如下变量，同时无需 import 这个文件\r\n */\n/* 颜色变量 */\n/* 行为相关颜色 */\n/* 文字基本颜色 */\n/* 背景颜色 */\n/* 边框颜色 */\n/* 尺寸变量 */\n/* 文字尺寸 */\n/* 图片尺寸 */\n/* Border Radius */\n/* 水平间距 */\n/* 垂直间距 */\n/* 透明度 */\n/* 文章场景相关 */\n/* 页面设置相关 */\n/* 边线相关 */\n/* 主要颜色 */\n/* 超出行数显示 省略号*/.container .list-all .a-list .info .detail .detail-left .text[data-v-12eed1bf]{word-break:break-all;\n  /*属性规定自动换行的处理方法。normal(使用浏览器默认的换行规则。),break-all(允许在单词内换行。),keep-all(只能在半角空格或连字符处换行。)*/text-overflow:ellipsis;display:-webkit-box;\n  /** 对象作为伸缩盒子模型显示 **/-webkit-box-orient:vertical;\n  /** 设置或检索伸缩盒对象的子元素的排列方式 **/-webkit-line-clamp:1;\n  /** 显示的行数 **/overflow:hidden\n  /** 隐藏超出的内容 **/}\n/* 阿里巴巴图标被选中状态的样式 */.icon-color[data-v-12eed1bf]{color:#fc5a5a}.container *[data-v-12eed1bf]{color:#1a1a1a}.container .list-all[data-v-12eed1bf]{background:#fff;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-webkit-flex-direction:column;flex-direction:column;padding:0 %?20?%;margin-bottom:%?120?%}.container .list-all .a-list[data-v-12eed1bf]{font-size:%?28?%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;padding:%?30?% 0;width:100%}.container .list-all .a-list .info[data-v-12eed1bf]{width:100%}.container .list-all .a-list .info .name-moblie[data-v-12eed1bf]{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:horizontal;-webkit-box-direction:normal;-webkit-flex-direction:row;flex-direction:row;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;align-items:center;font-size:%?32?%;line-height:%?64?%}.container .list-all .a-list .info .name-moblie .name .tag-real-name[data-v-12eed1bf]{margin-left:%?10?%;font-size:%?24?%;color:#fc5a5a;border:1px solid #fc5a5a;padding:%?4?% %?6?%;border-radius:%?4?%}.container .list-all .a-list .info .name-moblie .mobile[data-v-12eed1bf]{margin-left:%?20?%;color:grey;font-size:%?24?%}.container .list-all .a-list .info .detail[data-v-12eed1bf]{width:100%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;color:#1a1a1a}.container .list-all .a-list .info .detail .detail-left[data-v-12eed1bf]{overflow:hidden;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;width:80%}.container .list-all .a-list .info .detail .detail-left .tag-span[data-v-12eed1bf]{color:#fc5a5a;background:#fce8e8;padding:%?4?% %?6?%;margin-right:%?10?%}.container .list-all .a-list .info .detail .detail-left .text[data-v-12eed1bf]{width:80%}.container .list-all .a-list .info .detail .detail-right[data-v-12eed1bf]{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:end;-webkit-justify-content:flex-end;justify-content:flex-end;width:20%}.container .list-all .a-list .info .detail .detail-right .iconfont[data-v-12eed1bf]{font-size:%?32?%}.container .fixed[data-v-12eed1bf]{background:#fff;position:fixed;bottom:0;left:0;z-index:99;width:100%;height:%?100?%;line-height:%?100?%;text-align:center}.container .fixed span[data-v-12eed1bf]{margin-right:%?10?%}.container .fixed .iconfont[data-v-12eed1bf]{color:#fc5a5a}',""])},"0c7b":function(e,t,a){"use strict";var i,n=function(){var e=this,t=e.$createElement,a=e._self._c||t;return a("v-uni-view",{staticClass:"container"},[a("v-uni-view",{staticClass:"list-all"},e._l(e.list,function(t,i){return a("v-uni-view",{key:i,staticClass:"a-list"},[a("v-uni-view",{staticClass:"info"},[a("v-uni-view",{staticClass:"name-moblie"},[a("v-uni-view",{staticClass:"name"},[e._v(e._s(t.name)),t.attestation?a("span",{staticClass:"tag-real-name"},[e._v("已实名")]):e._e()]),a("v-uni-view",{staticClass:"mobile"},[e._v(e._s(t.phone))])],1),a("v-uni-view",{staticClass:"detail"},[a("v-uni-view",{staticClass:"detail-left"},[t.default?a("span",{staticClass:"tag-span"},[e._v("默认")]):e._e(),a("v-uni-view",{staticClass:"text"},[e._v(e._s(t.addres))])],1),a("v-uni-view",{staticClass:"detail-right",on:{click:function(a){arguments[0]=a=e.$handleEvent(a),e.goToAddOrEdit(t)}}},[a("i",{staticClass:"iconfont icon-ddx-shop-bianji"})])],1)],1)],1)}),1),0===e.list.length?a("uni-load-more",{attrs:{status:"noMore"}}):e._e(),a("v-uni-view",{staticClass:"fixed",on:{click:function(t){arguments[0]=t=e.$handleEvent(t),e.goToAddOrEdit()}}},[a("span",{staticClass:"iconfont icon-ddx-shop-anonymous-iconfont icon-color"}),e._v("新增收货地址")])],1)},o=[];a.d(t,"b",function(){return n}),a.d(t,"c",function(){return o}),a.d(t,"a",function(){return i})},2713:function(e,t,a){"use strict";a.r(t);var i=a("6173"),n=a.n(i);for(var o in i)"default"!==o&&function(e){a.d(t,e,function(){return i[e]})}(o);t["default"]=n.a},"49ea":function(e,t,a){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.default=void 0;var i={name:"UniLoadMore",props:{status:{type:String,default:"more"},showIcon:{type:Boolean,default:!0},color:{type:String,default:"#777777"},contentText:{type:Object,default:function(){return{contentdown:"上拉显示更多",contentrefresh:"正在加载...",contentnomore:"没有更多数据了"}}}},data:function(){return{}}};t.default=i},5756:function(e,t,a){var i=a("0663");"string"===typeof i&&(i=[[e.i,i,""]]),i.locals&&(e.exports=i.locals);var n=a("4f06").default;n("0fdd1fd8",i,!0,{sourceMap:!1,shadowMode:!1})},6173:function(e,t,a){"use strict";var i=a("288e");Object.defineProperty(t,"__esModule",{value:!0}),t.default=void 0;var n=i(a("f66d")),o={name:"address_list",components:{uniLoadMore:n.default},data:function(){return{list:[]}},onLoad:function(){console.log("带过来的参数",this.$parseURL())},onShow:function(){this.loadData()},methods:{goToAddOrEdit:function(){var e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:{};this.$openPage({name:"address_add",query:e})},loadData:function(){var e=this;this.$minApi.addressList().then(function(t){console.log(t),200===t.code&&(e.list=t.data)}).catch(function(e){console.log(e)})}}};t.default=o},"674b":function(e,t,a){"use strict";var i=a("f324"),n=a.n(i);n.a},ba60:function(e,t,a){"use strict";a.r(t);var i=a("0c7b"),n=a("2713");for(var o in n)"default"!==o&&function(e){a.d(t,e,function(){return n[e]})}(o);a("bce1");var l,d=a("f0c5"),r=Object(d["a"])(n["default"],i["b"],i["c"],!1,null,"12eed1bf",null,!1,i["a"],l);t["default"]=r.exports},bce1:function(e,t,a){"use strict";var i=a("5756"),n=a.n(i);n.a},cfc7:function(e,t,a){t=e.exports=a("2350")(!1),t.push([e.i,'@charset "UTF-8";\n/**\r\n * 这里是uni-app内置的常用样式变量\r\n *\r\n * uni-app 官方扩展插件及插件市场（https://ext.dcloud.net.cn）上很多三方插件均使用了这些样式变量\r\n * 如果你是插件开发者，建议你使用scss预处理，并在插件代码中直接使用这些变量（无需 import 这个文件），方便用户通过搭积木的方式开发整体风格一致的App\r\n *\r\n */\n/**\r\n * 如果你是App开发者（插件使用者），你可以通过修改这些变量来定制自己的插件主题，实现自定义主题功能\r\n *\r\n * 如果你的项目同样使用了scss预处理，你也可以直接在你的 scss 代码中使用如下变量，同时无需 import 这个文件\r\n */\n/* 颜色变量 */\n/* 行为相关颜色 */\n/* 文字基本颜色 */\n/* 背景颜色 */\n/* 边框颜色 */\n/* 尺寸变量 */\n/* 文字尺寸 */\n/* 图片尺寸 */\n/* Border Radius */\n/* 水平间距 */\n/* 垂直间距 */\n/* 透明度 */\n/* 文章场景相关 */\n/* 页面设置相关 */\n/* 边线相关 */\n/* 主要颜色 */\n/* 超出行数显示 省略号*/\n/* 阿里巴巴图标被选中状态的样式 */.icon-color[data-v-613fee9d]{color:#fc5a5a}.uni-load-more[data-v-613fee9d]{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-orient:horizontal;-webkit-box-direction:normal;-webkit-flex-direction:row;flex-direction:row;height:%?80?%;-webkit-box-align:center;-webkit-align-items:center;align-items:center;-webkit-box-pack:center;-webkit-justify-content:center;justify-content:center}.uni-load-more__text[data-v-613fee9d]{font-size:%?28?%;color:#999}.uni-load-more__img[data-v-613fee9d]{height:24px;width:24px;margin-right:10px}.uni-load-more__img>.load[data-v-613fee9d]{position:absolute}.uni-load-more__img>.load .uni-load-view_wrapper[data-v-613fee9d]{width:6px;height:2px;border-top-left-radius:1px;border-bottom-left-radius:1px;background:#999;position:absolute;opacity:.2;-webkit-transform-origin:50%;transform-origin:50%;-webkit-animation:load-data-v-613fee9d 1.56s ease infinite;animation:load-data-v-613fee9d 1.56s ease infinite}.uni-load-more__img>.load .uni-load-view_wrapper[data-v-613fee9d]:first-child{-webkit-transform:rotate(90deg);transform:rotate(90deg);top:2px;left:9px}.uni-load-more__img>.load .uni-load-view_wrapper[data-v-613fee9d]:nth-child(2){-webkit-transform:rotate(180deg);transform:rotate(180deg);top:11px;right:0}.uni-load-more__img>.load .uni-load-view_wrapper[data-v-613fee9d]:nth-child(3){-webkit-transform:rotate(270deg);transform:rotate(270deg);bottom:2px;left:9px}.uni-load-more__img>.load .uni-load-view_wrapper[data-v-613fee9d]:nth-child(4){top:11px;left:0}.load1[data-v-613fee9d],.load2[data-v-613fee9d],.load3[data-v-613fee9d]{height:24px;width:24px}.load2[data-v-613fee9d]{-webkit-transform:rotate(30deg);transform:rotate(30deg)}.load3[data-v-613fee9d]{-webkit-transform:rotate(60deg);transform:rotate(60deg)}.load1 .uni-load-view_wrapper[data-v-613fee9d]:first-child{-webkit-animation-delay:0s;animation-delay:0s}.load2 .uni-load-view_wrapper[data-v-613fee9d]:first-child{-webkit-animation-delay:.13s;animation-delay:.13s}.load3 .uni-load-view_wrapper[data-v-613fee9d]:first-child{-webkit-animation-delay:.26s;animation-delay:.26s}.load1 .uni-load-view_wrapper[data-v-613fee9d]:nth-child(2){-webkit-animation-delay:.39s;animation-delay:.39s}.load2 .uni-load-view_wrapper[data-v-613fee9d]:nth-child(2){-webkit-animation-delay:.52s;animation-delay:.52s}.load3 .uni-load-view_wrapper[data-v-613fee9d]:nth-child(2){-webkit-animation-delay:.65s;animation-delay:.65s}.load1 .uni-load-view_wrapper[data-v-613fee9d]:nth-child(3){-webkit-animation-delay:.78s;animation-delay:.78s}.load2 .uni-load-view_wrapper[data-v-613fee9d]:nth-child(3){-webkit-animation-delay:.91s;animation-delay:.91s}.load3 .uni-load-view_wrapper[data-v-613fee9d]:nth-child(3){-webkit-animation-delay:1.04s;animation-delay:1.04s}.load1 .uni-load-view_wrapper[data-v-613fee9d]:nth-child(4){-webkit-animation-delay:1.17s;animation-delay:1.17s}.load2 .uni-load-view_wrapper[data-v-613fee9d]:nth-child(4){-webkit-animation-delay:1.3s;animation-delay:1.3s}.load3 .uni-load-view_wrapper[data-v-613fee9d]:nth-child(4){-webkit-animation-delay:1.43s;animation-delay:1.43s}@-webkit-keyframes load-data-v-613fee9d{0%{opacity:1}to{opacity:.2}}',""])},ebfc:function(e,t,a){"use strict";var i,n=function(){var e=this,t=e.$createElement,a=e._self._c||t;return a("v-uni-view",{staticClass:"uni-load-more"},[a("v-uni-view",{directives:[{name:"show",rawName:"v-show",value:"loading"===e.status&&e.showIcon,expression:"status === 'loading' && showIcon"}],staticClass:"uni-load-more__img"},[a("v-uni-view",{staticClass:"load1 load"},[a("v-uni-view",{staticClass:"uni-load-view_wrapper",style:{background:e.color}}),a("v-uni-view",{staticClass:"uni-load-view_wrapper",style:{background:e.color}}),a("v-uni-view",{staticClass:"uni-load-view_wrapper",style:{background:e.color}}),a("v-uni-view",{staticClass:"uni-load-view_wrapper",style:{background:e.color}})],1),a("v-uni-view",{staticClass:"load2 load"},[a("v-uni-view",{staticClass:"uni-load-view_wrapper",style:{background:e.color}}),a("v-uni-view",{staticClass:"uni-load-view_wrapper",style:{background:e.color}}),a("v-uni-view",{staticClass:"uni-load-view_wrapper",style:{background:e.color}}),a("v-uni-view",{staticClass:"uni-load-view_wrapper",style:{background:e.color}})],1),a("v-uni-view",{staticClass:"load3 load"},[a("v-uni-view",{staticClass:"uni-load-view_wrapper",style:{background:e.color}}),a("v-uni-view",{staticClass:"uni-load-view_wrapper",style:{background:e.color}}),a("v-uni-view",{staticClass:"uni-load-view_wrapper",style:{background:e.color}}),a("v-uni-view",{staticClass:"uni-load-view_wrapper",style:{background:e.color}})],1)],1),a("v-uni-text",{staticClass:"uni-load-more__text",style:{color:e.color}},[e._v(e._s("more"===e.status?e.contentText.contentdown:"loading"===e.status?e.contentText.contentrefresh:e.contentText.contentnomore))])],1)},o=[];a.d(t,"b",function(){return n}),a.d(t,"c",function(){return o}),a.d(t,"a",function(){return i})},ef9d:function(e,t,a){"use strict";a.r(t);var i=a("49ea"),n=a.n(i);for(var o in i)"default"!==o&&function(e){a.d(t,e,function(){return i[e]})}(o);t["default"]=n.a},f324:function(e,t,a){var i=a("cfc7");"string"===typeof i&&(i=[[e.i,i,""]]),i.locals&&(e.exports=i.locals);var n=a("4f06").default;n("091ded68",i,!0,{sourceMap:!1,shadowMode:!1})},f66d:function(e,t,a){"use strict";a.r(t);var i=a("ebfc"),n=a("ef9d");for(var o in n)"default"!==o&&function(e){a.d(t,e,function(){return n[e]})}(o);a("674b");var l,d=a("f0c5"),r=Object(d["a"])(n["default"],i["b"],i["c"],!1,null,"613fee9d",null,!1,i["a"],l);t["default"]=r.exports}}]);