(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["pages2-user-distribution-invite-friends"],{"02f5":function(n,t,i){"use strict";var e=i("62d5"),o=i.n(e);o.a},"59d1":function(n,t,i){"use strict";var e,o=function(){var n=this,t=n.$createElement,i=n._self._c||t;return i("div",{staticClass:"invite-friends"},[i("div",{attrs:{id:"my-h5-back"},on:{click:function(t){arguments[0]=t=n.$handleEvent(t),n._goBack.apply(void 0,arguments)}}}),i("img",{staticStyle:{width:"100%",height:"100%"},attrs:{src:n.bgImg,alt:""}})])},a=[];i.d(t,"b",function(){return o}),i.d(t,"c",function(){return a}),i.d(t,"a",function(){return e})},"62d5":function(n,t,i){var e=i("f97a");"string"===typeof e&&(e=[[n.i,e,""]]),e.locals&&(n.exports=e.locals);var o=i("4f06").default;o("6de392c2",e,!0,{sourceMap:!1,shadowMode:!1})},"68dd":function(n,t,i){"use strict";var e=i("e54b"),o=i("288e");Object.defineProperty(t,"__esModule",{value:!0}),t.default=void 0;var a=o(i("cebc")),r=i("2f62"),c=e(i("a195")),s={name:"invite-friends",data:function(){return{bgImg:""}},methods:{_goPage:function(n){var t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:{};this.$openPage({name:n,query:t})},_goBack:function(){uni.navigateBack()}},onLoad:function(){var n=this;this.getPlatform().isAndroid&&this.wxConfig(),this.$minApi.becomeADistributorGetBackGroundImg().then(function(t){n.bgImg=t}).catch(function(n){console.log(n)});var t=c[c.NODE_ENV].inviteFriends;this.userInfo.id&&(t+="?user_id=".concat(this.userInfo.id,"&user_name=").concat(this.userInfo.nickname)),t=c[c.NODE_ENV].shareRedirectURL+encodeURIComponent(t);var i={title:"捣蛋熊商城-邀请你成为分销员",desc:"高品质、一站式服务平台",link:t,imgUrl:"".concat(window.location.origin,"/h5/static/images/pandalogo.png"),success:function(){}},e={title:"捣蛋熊商城-邀请你成为分销员",link:t,imgUrl:"".concat(window.location.origin,"/h5/static/images/pandalogo.png"),success:function(){}};this.wxConigShareGoods(i,e)},computed:(0,a.default)({},(0,r.mapState)(["userInfo"]))};t.default=s},"9e50":function(n,t,i){"use strict";i.r(t);var e=i("68dd"),o=i.n(e);for(var a in e)"default"!==a&&function(n){i.d(t,n,function(){return e[n]})}(a);t["default"]=o.a},d3a4:function(n,t,i){"use strict";i.r(t);var e=i("59d1"),o=i("9e50");for(var a in o)"default"!==a&&function(n){i.d(t,n,function(){return o[n]})}(a);i("02f5");var r,c=i("f0c5"),s=Object(c["a"])(o["default"],e["b"],e["c"],!1,null,"2784585e",null,!1,e["a"],r);t["default"]=s.exports},f97a:function(n,t,i){t=n.exports=i("2350")(!1),t.push([n.i,'@charset "UTF-8";\n/**\r\n * 这里是uni-app内置的常用样式变量\r\n *\r\n * uni-app 官方扩展插件及插件市场（https://ext.dcloud.net.cn）上很多三方插件均使用了这些样式变量\r\n * 如果你是插件开发者，建议你使用scss预处理，并在插件代码中直接使用这些变量（无需 import 这个文件），方便用户通过搭积木的方式开发整体风格一致的App\r\n *\r\n */\n/**\r\n * 如果你是App开发者（插件使用者），你可以通过修改这些变量来定制自己的插件主题，实现自定义主题功能\r\n *\r\n * 如果你的项目同样使用了scss预处理，你也可以直接在你的 scss 代码中使用如下变量，同时无需 import 这个文件\r\n */\n/* 颜色变量 */\n/* 行为相关颜色 */\n/* 文字基本颜色 */\n/* 背景颜色 */\n/* 边框颜色 */\n/* 尺寸变量 */\n/* 文字尺寸 */\n/* 图片尺寸 */\n/* Border Radius */\n/* 水平间距 */\n/* 垂直间距 */\n/* 透明度 */\n/* 文章场景相关 */\n/* 页面设置相关 */\n/* 边线相关 */\n/* 主要颜色 */\n/* 超出行数显示 省略号*/\n/* 阿里巴巴图标被选中状态的样式 */.icon-color[data-v-2784585e]{color:#fc5a5a}.invite-friends[data-v-2784585e]{width:100vw;height:100vh;overflow:hidden}',""])}}]);