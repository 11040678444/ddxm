webpackJsonp([45],{b8nQ:function(n,e,t){"use strict";Object.defineProperty(e,"__esModule",{value:!0});var o=t("Cz8s"),a=t.n(o),i=t("KgXo"),r=t.n(i),d=t("i84Q");e.default={name:"customerService",data:function(){return{showLoading:!1,orderid:0,orderAfData:{}}},mounted:function(){this.orderid=this.$route.params.id,this.getAsList()},components:{"head-top":a.a,loading:r.a},computed:{},methods:{getAsList:function(){var n=this;Object(d.z)(this.orderid).then(function(e){console.log(e),200===e.code?n.orderAfData=e.data:n.$vux.toast.text(e.msg,"middle")})},toAfterSale:function(n,e){this.$router.push({name:"afterSale",query:{orderid:n,attrid:e}})}}}},bxIM:function(n,e,t){e=n.exports=t("UTlt")(!1),e.push([n.i,'/**\n* actionsheet\n*/\n/**\n* en: primary type text color of menu item\n* zh-CN: 菜单项primary类型的文本颜色\n*/\n/**\n* en: warn type text color of menu item\n* zh-CN: 菜单项warn类型的文本颜色\n*/\n/**\n* en: default type text color of menu item\n* zh-CN: 菜单项default类型的文本颜色\n*/\n/**\n* en: disabled type text color of menu item\n* zh-CN: 菜单项disabled类型的文本颜色\n*/\n/**\n* datetime\n*/\n/**\n* tabbar\n*/\n/**\n* tab\n*/\n/**\n* dialog\n*/\n/**\n* en: title and content\'s padding-left and padding-right\n* zh-CN: 标题及内容区域的 padding-left 和 padding-right\n*/\n/**\n* x-number\n*/\n/**\n* checkbox\n*/\n/**\n* check-icon\n*/\n/**\n* Cell\n*/\n/**\n* Mask\n*/\n/**\n* Range\n*/\n/**\n* Tabbar\n*/\n/**\n* Header\n*/\n/**\n* Timeline\n*/\n/**\n* Switch\n*/\n/**\n* Button\n*/\n/**\n* en: border radius\n* zh-CN: 圆角边框\n*/\n/**\n* en: font color\n* zh-CN: 字体颜色\n*/\n/**\n* en: margin-top value between previous button, not works when there is only one button\n* zh-CN: 与相邻按钮的 margin-top 间隙，只有一个按钮时不生效\n*/\n/**\n* en: button height\n* zh-CN: 按钮高度\n*/\n/**\n* en: the font color in disabled\n* zh-CN: disabled状态下的字体颜色\n*/\n/**\n* en: the font color in disabled\n* zh-CN: disabled状态下的字体颜色\n*/\n/**\n* en: font size\n* zh-CN: 字体大小\n*/\n/**\n* en: the font size of the mini type\n* zh-CN: mini类型的字体大小\n*/\n/**\n* en: the line height of the mini type\n* zh-CN: mini类型的行高\n*/\n/**\n* en: the background color of the warn type\n* zh-CN: warn类型的背景颜色\n*/\n/**\n* en: the background color of the warn type in active\n* zh-CN: active状态下，warn类型的背景颜色\n*/\n/**\n* en: the background color of the warn type in disabled\n* zh-CN: disabled状态下，warn类型的背景颜色\n*/\n/**\n* en: the background color of the default type\n* zh-CN: default类型的背景颜色\n*/\n/**\n* en: the font color of the default type\n* zh-CN: default类型的字体颜色\n*/\n/**\n* en: the background color of the default type in active\n* zh-CN: active状态下，default类型的背景颜色\n*/\n/**\n* en: the font color of the default type in disabled\n* zh-CN: disabled状态下，default类型的字体颜色\n*/\n/**\n* en: the background color of the default type in disabled\n* zh-CN: disabled状态下，default类型的背景颜色\n*/\n/**\n* en: the font color of the default type in active\n* zh-CN: active状态下，default类型的字体颜色\n*/\n/**\n* en: the background color of the primary type\n* zh-CN: primary类型的背景颜色\n*/\n/**\n* en: the background color of the primary type in active\n* zh-CN: active状态下，primary类型的背景颜色\n*/\n/**\n* en: the background color of the primary type in disabled\n* zh-CN: disabled状态下，primary类型的背景颜色\n*/\n/**\n* en: the font color of the plain primary type\n* zh-CN: plain的primary类型的字体颜色\n*/\n/**\n* en: the border color of the plain primary type\n* zh-CN: plain的primary类型的边框颜色\n*/\n/**\n* en: the font color of the plain primary type in active\n* zh-CN: active状态下，plain的primary类型的字体颜色\n*/\n/**\n* en: the border color of the plain primary type in active\n* zh-CN: active状态下，plain的primary类型的边框颜色\n*/\n/**\n* en: the font color of the plain default type\n* zh-CN: plain的default类型的字体颜色\n*/\n/**\n* en: the border color of the plain default type\n* zh-CN: plain的default类型的边框颜色\n*/\n/**\n* en: the font color of the plain default type in active\n* zh-CN: active状态下，plain的default类型的字体颜色\n*/\n/**\n* en: the border color of the plain default type in active\n* zh-CN: active状态下，plain的default类型的边框颜色\n*/\n/**\n* en: the font color of the plain warn type\n* zh-CN: plain的warn类型的字体颜色\n*/\n/**\n* en: the border color of the plain warn type\n* zh-CN: plain的warn类型的边框颜色\n*/\n/**\n* en: the font color of the plain warn type in active\n* zh-CN: active状态下，plain的warn类型的字体颜色\n*/\n/**\n* en: the border color of the plain warn type in active\n* zh-CN: active状态下，plain的warn类型的边框颜色\n*/\n/**\n* swipeout\n*/\n/**\n* Cell\n*/\n/**\n* Badge\n*/\n/**\n* en: badge background color\n* zh-CN: badge的背景颜色\n*/\n/**\n* Popover\n*/\n/**\n* Button tab\n*/\n/**\n* en: not used\n* zh-CN: 未被使用\n*/\n/**\n* en: border radius color\n* zh-CN: 圆角边框的半径\n*/\n/**\n* en: border color\n* zh-CN: 边框的颜色\n*/\n/**\n* en: not used\n* zh-CN: 默认状态下圆角边框的颜色\n*/\n/**\n* en: not used\n* zh-CN: 未被使用\n*/\n/**\n* en: default background color\n* zh-CN: 默认状态下的背景颜色\n*/\n/**\n* en: selected background color\n* zh-CN: 选中状态下的背景颜色\n*/\n/**\n* en: not used\n* zh-CN: 未被使用\n*/\n/* alias */\n/**\n* en: not used\n* zh-CN: 未被使用\n*/\n/**\n* en: default text color\n* zh-CN: 默认状态下的文本颜色\n*/\n/**\n* en: height\n* zh-CN: 元素高度\n*/\n/**\n* en: line height\n* zh-CN: 元素行高\n*/\n/**\n* Swiper\n*/\n/**\n* checklist\n*/\n/**\n* popup-picker\n*/\n/**\n* popup\n*/\n/**\n* popup-header\n*/\n/**\n* form-preview\n*/\n/**\n* sticky\n*/\n/**\n* group\n*/\n/**\n* en: margin-top of title\n* zh-CN: 标题的margin-top\n*/\n/**\n* en: margin-bottom of title\n* zh-CN: 标题的margin-bottom\n*/\n/**\n* en: margin-top of footer title\n* zh-CN: 底部标题的margin-top\n*/\n/**\n* en: margin-bottom of footer title\n* zh-CN: 底部标题的margin-bottom\n*/\n/**\n* toast\n*/\n/**\n* en: text size of content\n* zh-CN: 内容文本大小\n*/\n/**\n* en: default top\n* zh-CN: 默认状态下距离顶部的高度\n*/\n/**\n* en: position top\n* zh-CN: 顶部显示的高度\n*/\n/**\n* en: position bottom\n* zh-CN: 底部显示的高度\n*/\n/**\n* en: z-index\n* zh-CN: z-index\n*/\n/**\n* icon\n*/\n/**\n* calendar\n*/\n/**\n* en: forward and backward arrows color\n* zh-CN: 前进后退的箭头颜色\n*/\n/**\n* en: text color of week highlight\n* zh-CN: 周末高亮的文本颜色\n*/\n/**\n* en: background color when selected\n* zh-CN: 选中时的背景颜色\n*/\n/**\n* en: text color when disabled\n* zh-CN: 禁用时的文本颜色\n*/\n/**\n* en: text color of today\n* zh-CN: 今天的文本颜色\n*/\n/**\n* en: font size of cell\n* zh-CN: 单元格的字号\n*/\n/**\n* en: background color\n* zh-CN: 背景颜色\n*/\n/**\n* en: size of date cell\n* zh-CN: 日期单元格尺寸大小\n*/\n/**\n* en: line height of date cell\n* zh-CN: 日期单元格的行高\n*/\n/**\n* en: text color of header\n* zh-CN: 头部的文本颜色\n*/\n/**\n* week-calendar\n*/\n/**\n* search\n*/\n/**\n* en: text color of cancel button\n* zh-CN: 取消按钮文本颜色\n*/\n/**\n* en: background color\n* zh-CN: 背景颜色\n*/\n/**\n* en: text color of placeholder\n* zh-CN: placeholder文本颜色\n*/\n/**\n* radio\n*/\n/**\n* en: checked icon color\n* zh-CN: 选中状态的图标颜色\n*/\n/**\n* loadmore\n*/\n/**\n* en: not used\n* zh-CN: 未被使用\n*/\n/**\n* loading\n*/\n/**\n* en: z-index\n* zh-CN: z-index\n*/\nhtml[data-v-76e5cd34] {\n  -ms-text-size-adjust: 100%;\n  -webkit-text-size-adjust: 100%;\n}\nbody[data-v-76e5cd34] {\n  line-height: 1.6;\n  font-family: -apple-system-font, "Helvetica Neue", sans-serif;\n}\n*[data-v-76e5cd34] {\n  margin: 0;\n  padding: 0;\n}\na img[data-v-76e5cd34] {\n  border: 0;\n}\na[data-v-76e5cd34] {\n  text-decoration: none;\n  -webkit-tap-highlight-color: rgba(0, 0, 0, 0);\n}\n/** env = windows **/\n[data-v-76e5cd34]::-webkit-input-placeholder {\n  font-family: -apple-system-font, "Helvetica Neue", sans-serif;\n}\n/** prevent default menu callout **/\na[data-v-76e5cd34] {\n  -webkit-touch-callout: none;\n}\n.sxzy-center[data-v-76e5cd34] {\n  position: absolute;\n  top: 50%;\n  left: 50%;\n  -webkit-transform: translate(-50%, -50%);\n          transform: translate(-50%, -50%);\n}\n.ct[data-v-76e5cd34] {\n  position: absolute;\n  top: 50%;\n  -webkit-transform: translateY(-50%);\n          transform: translateY(-50%);\n}\n.cl[data-v-76e5cd34] {\n  position: absolute;\n  left: 50%;\n  -webkit-transform: translateX(-50%);\n          transform: translateX(-50%);\n}\n.bjt[data-v-76e5cd34] {\n  background-repeat: no-repeat;\n  background-size: 100% 100%;\n}\n.box-shadow[data-v-76e5cd34] {\n  -webkit-box-shadow: 0 0.2rem 0.2rem -0.1rem #eff1f1;\n          box-shadow: 0 0.2rem 0.2rem -0.1rem #eff1f1;\n}\n.icon-buy[data-v-76e5cd34] {\n  display: inline-block;\n  width: 0.72rem;\n  height: 0.72rem;\n  border-radius: 100%;\n  background-color: #50c1f4;\n  text-align: center;\n  line-height: 0.72rem;\n}\n.icon-buy i[data-v-76e5cd34] {\n  font-size: 0.36rem;\n  color: #fff;\n}\n.tip-num-single[data-v-76e5cd34] {\n  width: 0.32rem;\n  height: 0.32rem;\n  line-height: 0.32rem;\n  border-radius: 100%;\n  background-color: #fb6d74;\n  font-size: 0.18rem;\n  color: #fff;\n  position: absolute;\n  right: -0.1rem;\n  top: 0;\n}\n.tip-num-double[data-v-76e5cd34] {\n  padding: 0 0.08rem 0 0.1rem;\n  border-radius: 0.3rem;\n  background-color: #fb6d74;\n  font-size: 0.18rem;\n  color: #fff;\n  position: absolute;\n  right: -0.1rem;\n  top: 0;\n}\n.panel-tip[data-v-76e5cd34] {\n  width: 100%;\n  padding: 0.2rem 0.35rem;\n  background-color: #c1ebf9;\n  font-size: 0.22rem;\n  color: #008ec8;\n  vertical-align: top;\n  display: -webkit-box;\n  display: -ms-flexbox;\n  display: flex;\n  -webkit-box-orient: horizontal;\n  -webkit-box-direction: normal;\n      -ms-flex-direction: row;\n          flex-direction: row;\n  -webkit-box-pack: start;\n      -ms-flex-pack: start;\n          justify-content: flex-start;\n}\n.panel-tip i[data-v-76e5cd34] {\n  font-size: 0.22rem;\n  color: #008ec8;\n  margin-right: 0.1rem;\n}\n.panel-tip p[data-v-76e5cd34] {\n  color: #008ec8;\n  text-align: justify;\n}\n.item-tag[data-v-76e5cd34] {\n  position: absolute;\n  left: 0;\n  top: 0.2rem;\n  z-index: 101;\n}\n.item-tag span[data-v-76e5cd34] {\n  margin-bottom: 0.08rem;\n}\n.store-goods[data-v-76e5cd34] {\n  display: block;\n  padding: 0.02rem 0.12rem;\n  font-size: 0.2rem;\n  color: #fff;\n  color: #fff!important;\n  background-color: #f71054 !important;\n  border-top-left-radius: 0.16rem;\n  border-bottom-right-radius: 0.16rem;\n}\n.tip-stock[data-v-76e5cd34] {\n  margin-bottom: 0.2rem;\n  display: inline;\n  padding: 0.02rem 0.26rem;\n  height: 0.4rem;\n  line-height: 0.4rem;\n  font-size: 0.2rem;\n  color: #fb9b6c;\n  background-color: #f9f9f9;\n  border-radius: 0.2rem;\n}\n.vux-tab[data-v-76e5cd34] {\n  height: 0.82rem!important;\n  /*border-top: 0.01rem solid #e5e5e5;*/\n}\n.vux-tab .vux-tab-item[data-v-76e5cd34] {\n  font-size: 0.26rem!important;\n  color: #333!important;\n  line-height: 0.76rem!important;\n}\n.vux-tab-bar-inner[data-v-76e5cd34] {\n  background-color: #50c0f4!important;\n  border-radius: 0.12rem!important;\n}\n.vux-tab-ink-bar[data-v-76e5cd34] {\n  height: 0.08rem!important;\n}\n.vux-tab .vux-tab-item.vux-tab-selected[data-v-76e5cd34] {\n  color: #008ec8!important;\n  border-width: 0.1rem!important;\n}\n.vux-tab .vux-tab-item[data-v-76e5cd34] {\n  background: none!important;\n  background-color: #fff!important;\n}\n.vux-slider .vux-indicator[data-v-76e5cd34],\n.vux-slider .vux-indicator-right[data-v-76e5cd34] {\n  right: 0.25rem!important;\n  bottom: 0.3rem!important;\n  font-size: 0!important;\n}\n.vux-actionsheet .weui-actionsheet__menu[data-v-76e5cd34] {\n  max-height: 5rem!important;\n  overflow-y: auto!important;\n}\n.ellipsis[data-v-76e5cd34] {\n  overflow: hidden;\n  text-overflow: ellipsis;\n  white-space: nowrap;\n}\n.ellipsis2[data-v-76e5cd34] {\n  overflow: hidden;\n  text-overflow: ellipsis;\n  display: -webkit-box;\n  -webkit-line-clamp: 2;\n  -webkit-box-orient: vertical;\n}\n.btn-tag[data-v-76e5cd34] {\n  display: block;\n  height: 0.3rem;\n  padding: 0 0.08rem 0.2rem !important;\n  line-height: 0.3rem;\n  text-align: center;\n  color: #ffffff;\n  font-size: 0.2rem;\n}\n.grad-org[data-v-76e5cd34] {\n  background: -webkit-gradient(linear, left top, right top, from(#fe6f6a), to(#f93dae));\n  background: linear-gradient(to right, #fe6f6a, #f93dae);\n}\n.grad-red[data-v-76e5cd34] {\n  background: -webkit-gradient(linear, left top, right top, from(#f7833e), to(#e52216));\n  background: linear-gradient(to right, #f7833e, #e52216);\n}\n.mp[data-v-76e5cd34] {\n  font-size: 0.36rem;\n  color: #f71054;\n}\n.mp span[data-v-76e5cd34] {\n  font-size: 0.28rem;\n  color: #f71054;\n}\n.np[data-v-76e5cd34] {\n  font-size: 0.28rem;\n  color: #a9a9a9;\n}\n.fmp[data-v-76e5cd34] {\n  font-size: 0.36rem;\n  color: #fa6d74;\n}\n.fmp span[data-v-76e5cd34] {\n  font-size: 0.28rem;\n  color: #fa6d74;\n}\n.fnp[data-v-76e5cd34] {\n  font-size: 0.28rem;\n  color: #a8a8a8;\n  text-decoration: line-through;\n}\n.member-icon-img_s[data-v-76e5cd34] {\n  width: 0.56rem;\n  height: 0.5rem;\n}\n.member-icon-img[data-v-76e5cd34] {\n  width: 2.1rem;\n  height: 1.84rem;\n}\n/***\n  TODO  捣蛋熊猫线上商城字体和颜色规范\n */\n/* 淡蓝 */\n/* 淡青 */\n/* 淡粉 */\n/* 白色 */\n/* 浅蓝 */\n/* 蓝色 */\n/* 鹅黄 */\n/* 浅粉 */\n/* 灰色度 */\n/* 姜黄色 */\n/* 亮黄 */\n/* 紫色 */\n/* 浅蓝色 */\n.cst-box[data-v-76e5cd34] {\n  padding-top: 1rem;\n}\n.cst-box .cst-list .goods[data-v-76e5cd34] {\n  background-color: #fff;\n  margin-top: 0.15rem;\n}\n.cst-box .cst-list .goods .title[data-v-76e5cd34] {\n  display: -webkit-box;\n  display: -ms-flexbox;\n  display: flex;\n  -webkit-box-orient: horizontal;\n  -webkit-box-direction: normal;\n      -ms-flex-direction: row;\n          flex-direction: row;\n  -webkit-box-pack: justify;\n      -ms-flex-pack: justify;\n          justify-content: space-between;\n  -webkit-box-align: center;\n      -ms-flex-align: center;\n          align-items: center;\n  height: 0.76rem;\n  padding: 0 0.3rem;\n}\n.cst-box .cst-list .goods .title p[data-v-76e5cd34] {\n  font-size: 0.22rem;\n}\n.cst-box .cst-list .goods .detail-box a[data-v-76e5cd34] {\n  width: 100%;\n  display: block;\n  position: relative;\n}\n.cst-box .cst-list .goods .detail-box a dl[data-v-76e5cd34] {\n  display: -webkit-box;\n  display: -ms-flexbox;\n  display: flex;\n  -webkit-box-orient: horizontal;\n  -webkit-box-direction: normal;\n      -ms-flex-direction: row;\n          flex-direction: row;\n  -webkit-box-align: center;\n      -ms-flex-align: center;\n          align-items: center;\n  border-top: 0.02rem solid #f5f5f5;\n  border-bottom: 0.01rem solid #f5f5f5;\n}\n.cst-box .cst-list .goods .detail-box a dl dt img[data-v-76e5cd34] {\n  height: 2.26rem;\n  width: 2.26rem;\n  margin: 0.2rem;\n}\n.cst-box .cst-list .goods .detail-box a dl dd[data-v-76e5cd34] {\n  padding: 0 0.3rem 0 0.2rem;\n}\n.cst-box .cst-list .goods .detail-box a dl dd p.text[data-v-76e5cd34] {\n  font-size: 0.28rem;\n  color: #333;\n  width: 4.32rem;\n}\n.cst-box .cst-list .goods .detail-box a dl dd p.color[data-v-76e5cd34] {\n  font-size: 0.21rem;\n  color: #999;\n  margin-top: 0.1rem;\n}\n.cst-box .cst-list .goods .detail-box a dl dd button[data-v-76e5cd34] {\n  margin: 0.15rem 0;\n}\n.cst-box .cst-list .goods .detail-box a dl dd div.price[data-v-76e5cd34] {\n  display: -webkit-box;\n  display: -ms-flexbox;\n  display: flex;\n  -webkit-box-orient: horizontal;\n  -webkit-box-direction: normal;\n      -ms-flex-direction: row;\n          flex-direction: row;\n  -webkit-box-pack: justify;\n      -ms-flex-pack: justify;\n          justify-content: space-between;\n  -webkit-box-align: center;\n      -ms-flex-align: center;\n          align-items: center;\n}\n.cst-box .cst-list .goods .detail-box a dl dd div.price > p[data-v-76e5cd34] {\n  color: #f45061;\n  font-size: 0.32rem;\n}\n.cst-box .cst-list .goods .detail-box a dl dd div.price > p span[data-v-76e5cd34] {\n  color: #f45061;\n  display: inline-block;\n  font-size: 0.18rem;\n}\n.cst-box .cst-list .goods .detail-box a dl dd div.price > span[data-v-76e5cd34] {\n  font-size: 0.32rem;\n}\n.cst-box .cst-list .goods .detail-box a dl dd div.price > span i[data-v-76e5cd34] {\n  font-size: 0.18rem;\n}\n.cst-box .cst-list .goods .sum[data-v-76e5cd34] {\n  display: -webkit-box;\n  display: -ms-flexbox;\n  display: flex;\n  -webkit-box-orient: horizontal;\n  -webkit-box-direction: normal;\n      -ms-flex-direction: row;\n          flex-direction: row;\n  -webkit-box-pack: justify;\n      -ms-flex-pack: justify;\n          justify-content: space-between;\n  -webkit-box-align: center;\n      -ms-flex-align: center;\n          align-items: center;\n  padding: 0.2rem 0.3rem;\n  font-size: 0;\n}\n.cst-box .cst-list .goods .sum p[data-v-76e5cd34] {\n  color: #999;\n  font-size: 0.28rem;\n}\n.cst-box .cst-list .goods .sum p > i[data-v-76e5cd34] {\n  font-size: 0.4rem;\n  vertical-align: middle;\n}\n.cst-box .cst-list .goods .sum-right[data-v-76e5cd34] {\n  padding: 0.2rem 0.3rem;\n  text-align: right;\n  font-size: 0;\n}\n.loading-enter-active[data-v-76e5cd34],\n.loading-leave-active[data-v-76e5cd34] {\n  -webkit-transition: opacity 0.7s;\n  transition: opacity 0.7s;\n}\n.loading-enter[data-v-76e5cd34],\n.loading-leave-active[data-v-76e5cd34] {\n  opacity: 0;\n}\n',""])},"e+gs":function(n,e,t){t("m2j2");var o=t("mEwh")(t("b8nQ"),t("nw95"),"data-v-76e5cd34",null);n.exports=o.exports},m2j2:function(n,e,t){var o=t("bxIM");"string"==typeof o&&(o=[[n.i,o,""]]),o.locals&&(n.exports=o.locals);t("FIqI")("038a42b1",o,!0,{})},nw95:function(n,e){n.exports={render:function(){var n=this,e=n.$createElement,t=n._self._c||e;return t("div",{staticClass:"cst-box"},[t("head-top",{attrs:{"go-return":"true",title:"售后申请"}}),n._v(" "),t("div",{staticClass:"scroll-content"},[t("div",{staticClass:"cst"},[t("div",{staticClass:"cst-list"},[t("div",{staticClass:"goods"},[t("div",{staticClass:"title"},[t("p",[n._v("订单编号："+n._s(n.orderAfData.sn))]),n._v(" "),n.orderAfData.overtime?t("p",[n._v("完成时间："+n._s(n.orderAfData.overtime))]):n._e()]),n._v(" "),t("div",{staticClass:"detail-box"},n._l(n.orderAfData.more_order_goods,function(e){return t("a",{key:e.goods_id},[t("dl",[t("dt",[t("img",{directives:[{name:"lazy",rawName:"v-lazy",value:e.attr_pic,expression:"item.attr_pic"}],attrs:{src:""}})]),n._v(" "),t("dd",[t("p",{staticClass:"text ellipsis2"},[n._v(n._s(e.subtitle))]),n._v(" "),t("p",{staticClass:"color"},[n._v(n._s(e.attr_name))]),n._v(" "),t("div",{staticClass:"price"},[t("p",[t("span",[n._v("￥")]),n._v(n._s(e.price))]),t("span",[t("i",[n._v("x")]),n._v(n._s(e.num))])])])]),n._v(" "),0===n.orderAfData.is_shouhou?t("div",{staticClass:"sum"},[n._m(0,!0),n._v(" "),t("button",{staticClass:"btn-disable-border",attrs:{disabled:"disabled"}},[n._v("申请售后")])]):t("div",{staticClass:"sum-right"},[t("button",{staticClass:"btn-war-border",on:{click:function(t){return n.toAfterSale(n.orderAfData.id,e.attr_id)}}},[n._v("申请售后")])])])}),0)])])])]),n._v(" "),t("transition",{attrs:{name:"loading"}},[t("loading",{directives:[{name:"show",rawName:"v-show",value:n.showLoading,expression:"showLoading"}]})],1)],1)},staticRenderFns:[function(){var n=this,e=n.$createElement,t=n._self._c||e;return t("p",[t("i",{staticClass:"iconfont icon-tishi"}),n._v("该商品已超过售后期")])}]}}});