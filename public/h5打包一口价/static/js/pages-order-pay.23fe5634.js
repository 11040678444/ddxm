(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["pages-order-pay"],{"06a5":function(e,t,n){"use strict";var a,r=function(){var e=this,t=e.$createElement,n=e._self._c||t;return n("v-uni-view",[n("v-uni-view",{staticClass:"sum-money"},[n("v-uni-text",{staticClass:"icon"},[e._v("￥")]),n("v-uni-text",{staticClass:"money"},[e._v(e._s(e.orderData.amount))])],1),n("v-uni-form",{attrs:{"report-submit":!0},on:{submit:function(t){arguments[0]=t=e.$handleEvent(t),e.myDebounce.apply(void 0,arguments)}}},[n("v-uni-view",{staticClass:"box"},[n("v-uni-radio-group",{on:{change:function(t){arguments[0]=t=e.$handleEvent(t),e.radioChange.apply(void 0,arguments)}}},[n("v-uni-label",{staticClass:"item",on:{click:function(t){arguments[0]=t=e.$handleEvent(t),e.radioChange({target:{value:"3"}})}}},[n("v-uni-view",[n("v-uni-view",[e._v("钱包")]),n("v-uni-view",{staticClass:"has-money"},[e._v("可用余额（包括已激活的限时余额）：¥"+e._s(e.userInfo.usable_money))])],1),n("v-uni-view",[n("v-uni-radio",{attrs:{value:"3",checked:"3"===e.payWay,disabled:e.disabledMoney,color:"#FC5A5A"}})],1)],1),n("v-uni-label",{staticClass:"item",on:{click:function(t){arguments[0]=t=e.$handleEvent(t),e.radioChange({target:{value:"1"}})}}},[n("v-uni-view",[n("v-uni-view",[e._v("微信支付")])],1),n("v-uni-view",[n("v-uni-radio",{attrs:{value:"1",checked:"1"===e.payWay,color:"#FC5A5A"}})],1)],1)],1)],1),n("v-uni-button",{staticClass:"my-btn",attrs:{"form-type":"submit"}},[e._v("确认支付")])],1)],1)},i=[];n.d(t,"b",function(){return r}),n.d(t,"c",function(){return i}),n.d(t,"a",function(){return a})},2869:function(e,t,n){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t._debounce=r;var a=null;function r(e){var t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:300;null!==a&&(clearTimeout(a),a=null),a=setTimeout(e,t)}},"3dd3":function(e,t,n){"use strict";var a=n("288e");Object.defineProperty(t,"__esModule",{value:!0}),t.default=void 0,n("96cf");var r=a(n("3b8d")),i=a(n("cebc")),o=a(n("59ad")),s=n("2f62"),u=n("2869"),c={name:"order_pay",data:function(){return{orderData:{amount:0,order_id:0},payWay:"0",disabledMoney:!1}},onLoad:function(){var e=this;this.getPlatform().isIOS&&(uni.getStorageSync("refresh")?uni.removeStorageSync("refresh"):(uni.setStorageSync("refresh","ios进入支付页面需要强制刷新一波"),location.reload())),console.log("其他页面带过来的参数：",this.$parseURL()),this.orderData=this.$parseURL(),setTimeout(function(){(0,o.default)(e.$parseURL().amount)<=(0,o.default)(e.userInfo.usable_money)?e.payWay="3":(e.disabledMoney=!0,e.payWay="1")},500),this.getPlatform().isAndroid&&this.wxConfig()},onShow:function(){this.userInfo.id&&this.asyncGetUserInfo()},methods:(0,i.default)({},(0,s.mapActions)(["asyncGetUserInfo"]),{_goPage:function(e){var t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:{};this.$openPage({name:e,query:t})},radioChange:function(e){switch(console.log(e),e.target.value){case"3":console.log("支付方式（钱包）: ",e.target.value),(0,o.default)(this.$parseURL().amount)>(0,o.default)(this.userInfo.usable_money)?(this.msg("钱包余额不足"),this.payWay="1",this.disabledMoney=!0):this.payWay=e.target.value;break;case"1":console.log("支付方式（微信）: ",e.target.value),this.payWay=e.target.value;break}},myDebounce:function(e){var t=this;(0,u._debounce)(function(){var n=arguments.length>0&&void 0!==arguments[0]?arguments[0]:e,a=arguments.length>1&&void 0!==arguments[1]?arguments[1]:t;a.formSubmit(n)},1e3)},formSubmit:function(){var e=(0,r.default)(regeneratorRuntime.mark(function e(t){var n,a=this;return regeneratorRuntime.wrap(function(e){while(1)switch(e.prev=e.next){case 0:return n={order_id:this.orderData.order_id,pay_way:this.payWay},e.next=3,this.$minApi.payWay(n).then(function(){var e=(0,r.default)(regeneratorRuntime.mark(function e(t){return regeneratorRuntime.wrap(function(e){while(1)switch(e.prev=e.next){case 0:if(console.log("需要支付的订单信息：",t),200!==t.code){e.next=13;break}if("3"!==a.payWay){e.next=10;break}if(1!==t.data.order_distinguish){e.next=8;break}return e.next=6,a._goPage("group_buy_group_redirect",{id:t.data.id});case 6:e.next=10;break;case 8:return e.next=10,a._goPage("order_result",t.data);case 10:"1"===a.payWay&&a.$wx.ready(function(){a.$wx.chooseWXPay({timestamp:t.data.timeStamp,nonceStr:t.data.nonceStr,package:t.data.package,signType:t.data.signType,paySign:t.data.paySign,success:function(){var e=(0,r.default)(regeneratorRuntime.mark(function e(n){return regeneratorRuntime.wrap(function(e){while(1)switch(e.prev=e.next){case 0:if(console.log("用户支付成功：",n),1!==t.data.order_distinguish){e.next=6;break}return e.next=4,a._goPage("group_buy_group_redirect",{id:t.data.id});case 4:e.next=8;break;case 6:return e.next=8,a._goPage("order_result",t.data);case 8:case"end":return e.stop()}},e,this)}));function n(t){return e.apply(this,arguments)}return n}(),fail:function(){var e=(0,r.default)(regeneratorRuntime.mark(function e(n){return regeneratorRuntime.wrap(function(e){while(1)switch(e.prev=e.next){case 0:return console.log("用户支付失败：",n),e.next=3,a._goPage("order_detail_redirect",{order_id:t.data.id});case 3:case"end":return e.stop()}},e,this)}));function n(t){return e.apply(this,arguments)}return n}(),cancel:function(){var e=(0,r.default)(regeneratorRuntime.mark(function e(n){return regeneratorRuntime.wrap(function(e){while(1)switch(e.prev=e.next){case 0:return console.log("用户取消支付：",n),e.next=3,a._goPage("order_detail_redirect",{order_id:t.data.id});case 3:case"end":return e.stop()}},e,this)}));function n(t){return e.apply(this,arguments)}return n}(),complete:function(){var e=(0,r.default)(regeneratorRuntime.mark(function e(t){return regeneratorRuntime.wrap(function(e){while(1)switch(e.prev=e.next){case 0:console.log("无论支付结果为是成功/失败/取消：",t);case 1:case"end":return e.stop()}},e,this)}));function t(t){return e.apply(this,arguments)}return t}()})}),e.next=15;break;case 13:a.msg(t.msg),setTimeout(function(){a._goPage("order_detail_redirect",{order_id:t.data.id})},1500);case 15:case"end":return e.stop()}},e,this)}));return function(t){return e.apply(this,arguments)}}()).catch(function(){var e=(0,r.default)(regeneratorRuntime.mark(function e(t){return regeneratorRuntime.wrap(function(e){while(1)switch(e.prev=e.next){case 0:console.log(t),a.msg("系统繁忙，请稍后支付~");case 2:case"end":return e.stop()}},e,this)}));return function(t){return e.apply(this,arguments)}}());case 3:case"end":return e.stop()}},e,this)}));function t(t){return e.apply(this,arguments)}return t}()}),computed:(0,i.default)({},(0,s.mapState)(["userInfo"]))};t.default=c},"6dc4":function(e,t,n){"use strict";var a=n("feea"),r=n.n(a);r.a},"6f08":function(e,t,n){t=e.exports=n("2350")(!1),t.push([e.i,'@charset "UTF-8";\n/**\r\n * 这里是uni-app内置的常用样式变量\r\n *\r\n * uni-app 官方扩展插件及插件市场（https://ext.dcloud.net.cn）上很多三方插件均使用了这些样式变量\r\n * 如果你是插件开发者，建议你使用scss预处理，并在插件代码中直接使用这些变量（无需 import 这个文件），方便用户通过搭积木的方式开发整体风格一致的App\r\n *\r\n */\n/**\r\n * 如果你是App开发者（插件使用者），你可以通过修改这些变量来定制自己的插件主题，实现自定义主题功能\r\n *\r\n * 如果你的项目同样使用了scss预处理，你也可以直接在你的 scss 代码中使用如下变量，同时无需 import 这个文件\r\n */\n/* 颜色变量 */\n/* 行为相关颜色 */\n/* 文字基本颜色 */\n/* 背景颜色 */\n/* 边框颜色 */\n/* 尺寸变量 */\n/* 文字尺寸 */\n/* 图片尺寸 */\n/* Border Radius */\n/* 水平间距 */\n/* 垂直间距 */\n/* 透明度 */\n/* 文章场景相关 */\n/* 页面设置相关 */\n/* 边线相关 */.box .item[data-v-6f47053f]{border-bottom:1px #f2f2f2 solid}\n/* 主要颜色 */\n/* 超出行数显示 省略号*/\n/* 阿里巴巴图标被选中状态的样式 */.icon-color[data-v-6f47053f]{color:#fc5a5a}uni-page-body[data-v-6f47053f]{background:#fff;color:#1a1a1a;font-size:%?28?%}.sum-money[data-v-6f47053f]{text-align:center;padding:%?100?% 0;height:%?54?%;line-height:%?54?%;color:#1a1a1a}.sum-money .icon[data-v-6f47053f]{font-size:%?24?%}.sum-money .money[data-v-6f47053f]{font-size:%?56?%}.box[data-v-6f47053f]{width:100%;padding:%?20?%}.box .item[data-v-6f47053f]{display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;align-items:center;width:100%;padding:%?36?% 0}.box .item .has-money[data-v-6f47053f]{color:grey;font-size:%?24?%;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between}.my-btn[data-v-6f47053f]{position:fixed;bottom:0;left:0;width:100%;text-align:center;height:%?98?%;line-height:%?98?%;color:#fff;background:#fc5a5a;font-size:%?32?%;border-radius:0;z-index:999}body.?%PAGE?%[data-v-6f47053f]{background:#fff}',""])},"82af":function(e,t,n){"use strict";n.r(t);var a=n("3dd3"),r=n.n(a);for(var i in a)"default"!==i&&function(e){n.d(t,e,function(){return a[e]})}(i);t["default"]=r.a},edfd:function(e,t,n){"use strict";n.r(t);var a=n("06a5"),r=n("82af");for(var i in r)"default"!==i&&function(e){n.d(t,e,function(){return r[e]})}(i);n("6dc4");var o,s=n("f0c5"),u=Object(s["a"])(r["default"],a["b"],a["c"],!1,null,"6f47053f",null,!1,a["a"],o);t["default"]=u.exports},feea:function(e,t,n){var a=n("6f08");"string"===typeof a&&(a=[[e.i,a,""]]),a.locals&&(e.exports=a.locals);var r=n("4f06").default;r("7d76b332",a,!0,{sourceMap:!1,shadowMode:!1})}}]);