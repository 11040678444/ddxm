(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["chunk-01046153"],{"2a3de":function(t,e,a){"use strict";var n=a("4d75");e["a"]={data:function(){return{typeList:[]}},methods:{getTypeList:function(){var t=this;Object(n["g"])().then((function(e){200===e.code&&(t.typeList=e.data.data)})).catch((function(t){console.log(t)}))}},created:function(){this.getTypeList()}}},"2a7a":function(t,e,a){"use strict";a.r(e);var n=function(){var t=this,e=t.$createElement,a=t._self._c||e;return a("el-card",{staticClass:"box-card",attrs:{shadow:"hover"}},[a("div",{staticClass:"clearfix",staticStyle:{"line-height":"32px"},attrs:{slot:"header"},slot:"header"},[a("span",[t._v("专题管理")])]),t._v(" "),a("div",{staticClass:"app-container",staticStyle:{"margin-bottom":"20px"}},[a("el-form",{staticClass:"app-head"},[a("div",{staticClass:"ipt"},[a("div",{staticClass:"iptTitle"},[t._v("专题分类")]),t._v(" "),a("el-select",{staticClass:"search-input",attrs:{clearable:"",placeholder:"请选择专题分类",size:"small"},model:{value:t.requestData.st_id,callback:function(e){t.$set(t.requestData,"st_id",e)},expression:"requestData.st_id"}},t._l(t.typeList,(function(t){return a("el-option",{key:t.id,attrs:{label:t.title,value:t.id}})})),1)],1),t._v(" "),a("div",{staticClass:"ipt"},[a("div",{staticClass:"iptTitle"},[t._v("商品标题")]),t._v(" "),a("el-input",{attrs:{clearable:"",placeholder:"请输入商品标题",size:"small"},model:{value:t.requestData.item_name,callback:function(e){t.$set(t.requestData,"item_name",e)},expression:"requestData.item_name"}})],1),t._v(" "),a("div",[a("el-radio-group",{model:{value:t.requestData.hot,callback:function(e){t.$set(t.requestData,"hot",e)},expression:"requestData.hot"}},[a("el-radio",{attrs:{label:"1"}},[t._v("热门")]),t._v(" "),a("el-radio",{attrs:{label:""}},[t._v("普通")])],1),t._v(" "),a("el-button",{staticClass:"search-input",staticStyle:{"margin-left":"20px"},attrs:{size:"small",icon:"el-icon-search",type:"primary",plain:""},on:{click:t.search}},[t._v("搜索")])],1)])],1),t._v(" "),a("el-table",{directives:[{name:"loading",rawName:"v-loading",value:t.requestData.isLoad,expression:"requestData.isLoad"}],attrs:{data:t.responseData,border:"","header-cell-style":{backgroundColor:"#F5F7FA"},"element-loading-text":"Loading",stripe:"","max-height":"600"}},[a("el-table-column",{attrs:{label:"ID",align:"center",prop:"id"}}),t._v(" "),a("el-table-column",{attrs:{label:"商品ID",align:"center"},scopedSlots:t._u([{key:"default",fn:function(e){return[t._v("\n          "+t._s(e.row.item_info.id)+"\n        ")]}}])}),t._v(" "),a("el-table-column",{attrs:{label:"分类",align:"center",prop:"title"}}),t._v(" "),a("el-table-column",{attrs:{label:"商品图片",align:"center"},scopedSlots:t._u([{key:"default",fn:function(t){return[a("img",{directives:[{name:"img",rawName:"v-img:group",arg:"group"}],staticStyle:{height:"50px"},attrs:{src:t.row.item_info.pic}})]}}])}),t._v(" "),a("el-table-column",{attrs:{label:"商品标题",align:"center"},scopedSlots:t._u([{key:"default",fn:function(e){return[t._v("\n          "+t._s(e.row.item_info.title)+"\n        ")]}}])}),t._v(" "),a("el-table-column",{attrs:{label:"商品原价",align:"center"},scopedSlots:t._u([{key:"default",fn:function(e){return[t._v("\n          "+t._s(e.row.item_info.max_price)+"\n        ")]}}])}),t._v(" "),a("el-table-column",{attrs:{label:"商品售价",align:"center"},scopedSlots:t._u([{key:"default",fn:function(e){return[t._v("\n          "+t._s(e.row.item_info.min_price)+"\n        ")]}}])}),t._v(" "),a("el-table-column",{attrs:{label:"是否热门",align:"center"},scopedSlots:t._u([{key:"default",fn:function(e){return[t._v("\n          "+t._s(1===e.row.hot?"是":"否")+"\n        ")]}}])}),t._v(" "),a("el-table-column",{attrs:{label:"操作",align:"center"},scopedSlots:t._u([{key:"default",fn:function(e){return[a("el-button",{attrs:{type:"primary",plain:"",size:"mini"},on:{click:function(a){return t.deleteGoods(e.$index)}}},[t._v("删除")]),t._v(" "),a("el-button",{attrs:{type:"primary",plain:"",size:"mini"},on:{click:function(a){return t.setHot(e.$index)}}},[t._v("\n            "+t._s(1===e.row.hot?"取消热门":"设置热门")+"\n          ")])]}}])})],1),t._v(" "),a("div",{staticClass:"footer"},[a("el-pagination",{attrs:{"current-page":t.requestData.page,"page-sizes":[5,10,15,20],"page-size":t.requestData.limit,layout:"total, sizes, prev, pager, next, jumper",total:t.totalCount,"hide-on-single-page":!0},on:{"size-change":t.handleSizeChange,"current-change":t.handleCurrentChange}})],1)],1)},i=[],s=a("4d75"),r=a("2a3de"),l={name:"index",data:function(){return{requestData:{isLoad:!1,page:1,limit:10,st_id:"",hot:"",item_name:""},totalCount:0,responseData:[]}},methods:{search:function(){this.requestData.page=1,this.getListData()},getListData:function(){var t=this;this.requestData.isLoad=!0;var e={page:this.requestData.page,limit:this.requestData.limit,st_id:this.requestData.st_id,hot:this.requestData.hot,item_name:this.requestData.item_name};Object(s["d"])(e).then((function(e){200===e.code&&(t.totalCount=e.data.count,t.responseData=e.data.data),t.requestData.isLoad=!1})).catch((function(e){t.requestData.isLoad=!1,console.log(e)}))},handleCurrentChange:function(t){this.requestData.page=t,this.getListData()},handleSizeChange:function(t){this.requestData.limit=t,this.getListData()},deleteGoods:function(t){var e=this,a={id:this.responseData[t].id};this.$confirm("此操作将永久删除, 是否继续?","提示",{confirmButtonText:"确定",cancelButtonText:"取消",type:"warning"}).then((function(){Object(s["b"])(a).then((function(t){200===t.code&&(e.getListData(),e.$message({message:"删除成功",type:"success"}))}))})).catch((function(){e.$message({type:"info",message:"已取消删除"})}))},setHot:function(t){var e=this,a={id:this.responseData[t].id};this.$confirm("是否继续热门操作?","提示",{confirmButtonText:"确定",cancelButtonText:"取消",type:"warning"}).then((function(){Object(s["c"])(a).then((function(t){200===t.code&&(e.getListData(),e.$message({type:"success",message:"设置成功"}))}))})).catch((function(){e.$message({type:"info",message:"已取消操作"})}))}},mixins:[r["a"]],created:function(){this.getListData()}},o=l,c=(a("f79f"),a("5511")),u=Object(c["a"])(o,n,i,!1,null,"52e5b713",null);e["default"]=u.exports},"4d75":function(t,e,a){"use strict";a.d(e,"g",(function(){return i})),a.d(e,"e",(function(){return s})),a.d(e,"f",(function(){return r})),a.d(e,"a",(function(){return l})),a.d(e,"d",(function(){return o})),a.d(e,"b",(function(){return c})),a.d(e,"c",(function(){return u}));var n=a("b775");function i(t){return Object(n["a"])({url:"/mall_admin_market/Special/getTypeList",method:"post",data:t})}function s(t){return Object(n["a"])({url:"/mall_admin_market/Special/typeAdd",method:"post",data:t})}function r(t){return Object(n["a"])({url:"/mall_admin_market/Special/deleteType",method:"post",data:t})}function l(t){return Object(n["a"])({url:"/mall_admin_market/Special/add_item",method:"post",data:t})}function o(t){return Object(n["a"])({url:"/mall_admin_market/Special/getItemList",method:"post",data:t})}function c(t){return Object(n["a"])({url:"/mall_admin_market/Special/deleteItem",method:"post",data:t})}function u(t){return Object(n["a"])({url:"/mall_admin_market/Special/setHot",method:"post",data:t})}},bca6:function(t,e,a){},f79f:function(t,e,a){"use strict";var n=a("bca6"),i=a.n(n);i.a}}]);