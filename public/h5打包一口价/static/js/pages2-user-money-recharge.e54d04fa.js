(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["pages2-user-money-recharge"],{"26ca":function(e,n,t){"use strict";var i=t("288e");Object.defineProperty(n,"__esModule",{value:!0}),n.default=void 0,t("96cf");var a=i(t("3b8d")),o=i(t("cebc")),r=t("2f62"),s=t("2869"),c={name:"recharge",data:function(){return{money:""}},onLoad:function(){this.getPlatform().isIOS&&(uni.getStorageSync("refresh")?uni.removeStorageSync("refresh"):(uni.setStorageSync("refresh","ios进入支付页面需要强制刷新一波"),location.reload())),this.getPlatform().isAndroid&&this.wxConfig()},methods:(0,o.default)({},(0,r.mapActions)(["asyncGetUserInfo"]),{_goPage:function(e){var n=arguments.length>1&&void 0!==arguments[1]?arguments[1]:{};this.$openPage({name:e,query:n})},_goBack:function(){1===getCurrentPages().length?this._goPage("user_money_redirect"):uni.navigateBack()},inputMoney:function(){var e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:0;this.money=e},addMoney:function(){var e=this;(0,s._debounce)(function(){var n=arguments.length>0&&void 0!==arguments[0]?arguments[0]:e;n._investMoney()},1e3)},_investMoney:function(){var e=this;if(""!==this.money){var n={money:this.money},t=this;t.$minApi.investMoney(n).then(function(e){if(console.log(n),200===e.code){var i=JSON.parse(e.data);t.$wx.ready(function(){t.$wx.chooseWXPay({timestamp:i.timeStamp,nonceStr:i.nonceStr,package:i.package,signType:i.signType,paySign:i.paySign,success:function(){var e=(0,a.default)(regeneratorRuntime.mark(function e(n){return regeneratorRuntime.wrap(function(e){while(1)switch(e.prev=e.next){case 0:console.log("用户支付成功：",n),t._goPage("user_money_redirect");case 2:case"end":return e.stop()}},e,this)}));function n(n){return e.apply(this,arguments)}return n}(),fail:function(){var e=(0,a.default)(regeneratorRuntime.mark(function e(n){return regeneratorRuntime.wrap(function(e){while(1)switch(e.prev=e.next){case 0:console.log("支付失败：",n);case 1:case"end":return e.stop()}},e,this)}));function n(n){return e.apply(this,arguments)}return n}(),cancel:function(){var e=(0,a.default)(regeneratorRuntime.mark(function e(n){return regeneratorRuntime.wrap(function(e){while(1)switch(e.prev=e.next){case 0:console.log("用户取消支付：",n);case 1:case"end":return e.stop()}},e,this)}));function n(n){return e.apply(this,arguments)}return n}(),complete:function(){var e=(0,a.default)(regeneratorRuntime.mark(function e(n){return regeneratorRuntime.wrap(function(e){while(1)switch(e.prev=e.next){case 0:console.log("无论支付结果为是成功/失败/取消：",n),t.asyncGetUserInfo();case 2:case"end":return e.stop()}},e,this)}));function n(n){return e.apply(this,arguments)}return n}()})})}}).catch(function(n){console.log(n),e.msg("服务器繁忙，请稍后重试。")})}else this.msg("请输入充值金额")}}),computed:(0,o.default)({},(0,r.mapState)(["userInfo"]))};n.default=c},2869:function(e,n,t){"use strict";Object.defineProperty(n,"__esModule",{value:!0}),n._debounce=a;var i=null;function a(e){var n=arguments.length>1&&void 0!==arguments[1]?arguments[1]:300;null!==i&&(clearTimeout(i),i=null),i=setTimeout(e,n)}},"37a9":function(e,n,t){n=e.exports=t("2350")(!1),n.push([e.i,'@charset "UTF-8";\n/**\r\n * 这里是uni-app内置的常用样式变量\r\n *\r\n * uni-app 官方扩展插件及插件市场（https://ext.dcloud.net.cn）上很多三方插件均使用了这些样式变量\r\n * 如果你是插件开发者，建议你使用scss预处理，并在插件代码中直接使用这些变量（无需 import 这个文件），方便用户通过搭积木的方式开发整体风格一致的App\r\n *\r\n */\n/**\r\n * 如果你是App开发者（插件使用者），你可以通过修改这些变量来定制自己的插件主题，实现自定义主题功能\r\n *\r\n * 如果你的项目同样使用了scss预处理，你也可以直接在你的 scss 代码中使用如下变量，同时无需 import 这个文件\r\n */\n/* 颜色变量 */\n/* 行为相关颜色 */\n/* 文字基本颜色 */\n/* 背景颜色 */\n/* 边框颜色 */\n/* 尺寸变量 */\n/* 文字尺寸 */\n/* 图片尺寸 */\n/* Border Radius */\n/* 水平间距 */\n/* 垂直间距 */\n/* 透明度 */\n/* 文章场景相关 */\n/* 页面设置相关 */\n/* 边线相关 */.privilege .privilege-title[data-v-a2bfbf26]{border-bottom:1px #f2f2f2 solid}\n/* 主要颜色 */\n/* 超出行数显示 省略号*/\n/* 阿里巴巴图标被选中状态的样式 */.icon-color[data-v-a2bfbf26]{color:#fc5a5a}*[data-v-a2bfbf26]{font-size:%?28?%}.privilege[data-v-a2bfbf26]{background-color:#fff;margin-bottom:%?20?%}.privilege .privilege-title[data-v-a2bfbf26]{padding:%?20?%}.privilege .privilege-box[data-v-a2bfbf26]{padding:0 %?20?%}.privilege .privilege-box .privilege-box-item[data-v-a2bfbf26]{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center;padding:%?20?% 0}.privilege .privilege-box .privilege-box-item .left[data-v-a2bfbf26]{margin-right:%?10?%}.privilege .privilege-box .privilege-box-item .left .circle[data-v-a2bfbf26]{background:#000;border-radius:50%;color:#fff;width:%?30?%;text-align:center;height:%?30?%;line-height:%?30?%;font-size:%?24?%}.privilege .privilege-box .privilege-box-item .right[data-v-a2bfbf26]{text-align:justify}.my-money .my-money-money[data-v-a2bfbf26]{background:#fff;padding:%?80?% 0 %?100?% 0}.my-money .my-money-money .my-money-title[data-v-a2bfbf26]{text-align:center;font-size:%?32?%;margin-bottom:%?100?%}.my-money .my-money-money .my-money-num[data-v-a2bfbf26]{color:#fc5a5a;font-size:%?64?%;text-align:center}.my-money .money-input-box[data-v-a2bfbf26]{background:#fff;padding:%?20?%}.my-money .money-input-box .title[data-v-a2bfbf26]{font-size:%?32?%;margin-bottom:%?40?%}.my-money .money-input-box .box[data-v-a2bfbf26]{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-justify-content:space-around;justify-content:space-around}.my-money .money-input-box .box .item[data-v-a2bfbf26]{width:%?126?%;height:%?68?%;line-height:%?68?%;text-align:center;background:#f2f2f2}.my-money .money-input-box .input[data-v-a2bfbf26]{font-size:%?32?%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-align:center;-webkit-align-items:center;align-items:center}.my-money .money-input-box .input .fh[data-v-a2bfbf26]{margin-right:20px;font-size:%?64?%}.my-money .money-input-box .input uni-input[data-v-a2bfbf26]{font-size:%?32?%}.my-btn[data-v-a2bfbf26]{padding:%?20?%;margin:%?20?% 0}.my-btn .btn[data-v-a2bfbf26]{height:%?98?%;line-height:%?98?%;text-align:center;background:#fc5a5a;border-radius:%?8?%;color:#fff;font-size:%?32?%}',""])},4342:function(e,n,t){"use strict";t.r(n);var i=t("607f"),a=t("8b69");for(var o in a)"default"!==o&&function(e){t.d(n,e,function(){return a[e]})}(o);t("45d7");var r,s=t("f0c5"),c=Object(s["a"])(a["default"],i["b"],i["c"],!1,null,"a2bfbf26",null,!1,i["a"],r);n["default"]=c.exports},"45d7":function(e,n,t){"use strict";var i=t("dfa4"),a=t.n(i);a.a},"607f":function(e,n,t){"use strict";var i,a=function(){var e=this,n=e.$createElement,t=e._self._c||n;return t("div",[t("div",{attrs:{id:"my-h5-back"},on:{click:function(n){arguments[0]=n=e.$handleEvent(n),e._goBack.apply(void 0,arguments)}}}),t("div",{staticClass:"my-money"},[t("div",{staticClass:"my-money-money"},[t("div",{staticClass:"my-money-title"},[e._v("我的余额")]),t("div",{staticClass:"my-money-num"},[e._v("￥"+e._s(e._f("moneyToFixed")(e.userInfo.money)))])]),t("div",{staticClass:"money-input-box"},[t("div",{staticClass:"title"},[e._v("充值方式：微信支付")]),t("div",{staticClass:"box"},[t("div",{staticClass:"item",on:{click:function(n){arguments[0]=n=e.$handleEvent(n),e.inputMoney(1e3)}}},[e._v("1000")]),t("div",{staticClass:"item",on:{click:function(n){arguments[0]=n=e.$handleEvent(n),e.inputMoney(2e3)}}},[e._v("2000")]),t("div",{staticClass:"item",on:{click:function(n){arguments[0]=n=e.$handleEvent(n),e.inputMoney(3e3)}}},[e._v("3000")])])]),t("div",{staticClass:"money-input-box"},[e._m(0),t("div",{staticClass:"input"},[t("span",{staticClass:"fh"},[e._v("￥")]),t("v-uni-input",{attrs:{type:"number",placeholder:"请输入充值金额"},model:{value:e.money,callback:function(n){e.money=n},expression:"money"}})],1)])]),t("div",{staticClass:"my-btn"},[t("div",{staticClass:"btn",on:{click:function(n){arguments[0]=n=e.$handleEvent(n),e.addMoney.apply(void 0,arguments)}}},[e._v("充值")])])])},o=[function(){var e=this,n=e.$createElement,t=e._self._c||n;return t("div",{staticClass:"title"},[e._v("充值金额"),t("span",{staticStyle:{"font-size":"28upx",color:"#666"}},[e._v("（充值金额仅限微信商城使用）")])])}];t.d(n,"b",function(){return a}),t.d(n,"c",function(){return o}),t.d(n,"a",function(){return i})},"8b69":function(e,n,t){"use strict";t.r(n);var i=t("26ca"),a=t.n(i);for(var o in i)"default"!==o&&function(e){t.d(n,e,function(){return i[e]})}(o);n["default"]=a.a},dfa4:function(e,n,t){var i=t("37a9");"string"===typeof i&&(i=[[e.i,i,""]]),i.locals&&(e.exports=i.locals);var a=t("4f06").default;a("a104163a",i,!0,{sourceMap:!1,shadowMode:!1})}}]);