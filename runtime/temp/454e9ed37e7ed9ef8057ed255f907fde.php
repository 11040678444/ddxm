<?php /*a:1:{s:67:"D:\PHPTutorial\WWW\ddxm_svn\application\admin\view\order\index.html";i:1601275829;}*/ ?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>后台管理系统</title>
    <meta name="author" content="YZNCMS">
    <link rel="stylesheet" href="/public/static/libs/layui/css/layui.css">
    <link rel="stylesheet" href="/public/static/admin/css/admin.css">
    <link rel="stylesheet" href="/public/static/admin/css/ddxm.css">
    <link rel="stylesheet" href="/public/static/admin/font/iconfont.css">
    <script src="/public/static/libs/layui/layui.js"></script>
    <script src="/public/static/libs/jquery/jquery.min.js"></script>
    <style type="text/css">
        .layui-table-cell{
            height:auto !important;
        }
        .layui-layer-title{
            text-align: center;
            padding: 0 ;
            font-size:20px;
        
        }
        .width150{
            width:70px;
        }
        .width200{
            width:70px;
        }
    </style>
    
<script type="text/javascript">
//全局变量
var GV = {
    'image_upload_url': '<?php echo !empty($image_upload_url) ? htmlentities($image_upload_url) :  url("attachment/attachments/upload", ["dir" => "images", "module" => request()->module()]); ?>',
    'file_upload_url': '<?php echo !empty($file_upload_url) ? htmlentities($file_upload_url) :  url("attachment/attachments/upload", ["dir" => "files", "module" => request()->module()]); ?>',
    'WebUploader_swf': '/public/static/webuploader/Uploader.swf',
    'upload_check_url': '<?php echo !empty($upload_check_url) ? htmlentities($upload_check_url) :  url("attachment/Attachments/check"); ?>',
    'ueditor_upload_url': '<?php echo !empty($ueditor_upload_url) ? htmlentities($ueditor_upload_url) :  url("attachment/Ueditor/run"); ?>',
};
</script>
</head>
<body class="childrenBody">
<div class="layui-card">
    <div class="layui-card-header">
        <fieldset class="layui-elem-field layui-field-title">
            <legend>门店订单</legend>
        </fieldset>
    </div>
    <div class="layui-card-body">
        <div class="layui-form">
            <form class="layui-form" action="">
            <div class="layui-form-item">
                <div class="layui-input-inline" style="width:180px;">
                    <select name="shop" lay-verify="" lay-filter="shop">
                        <option value="">全部门店</option>
                        <?php if(is_array($data['shop']) || $data['shop'] instanceof \think\Collection || $data['shop'] instanceof \think\Paginator): $i = 0; $__LIST__ = $data['shop'];if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?>
                        <option value="<?php echo htmlentities($vo['id']); ?>"><?php echo htmlentities($vo['name']); ?></option>
                        <?php endforeach; endif; else: echo "" ;endif; ?>
                    </select>  
                </div>
                <!-- <div class="layui-input-inline" style="width:120px;">
                    <select name="worker" lay-verify="" lay-filter="worker">
                        <option value="">服务人员</option>
                        <?php if(is_array($data['worker']) || $data['worker'] instanceof \think\Collection || $data['worker'] instanceof \think\Paginator): $i = 0; $__LIST__ = $data['worker'];if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$wo): $mod = ($i % 2 );++$i;?>
                        <option value="<?php echo htmlentities($wo['id']); ?>"><?php echo htmlentities($wo['name']); ?></option>
                        <?php endforeach; endif; else: echo "" ;endif; ?>
                    </select>  
                </div> -->
                <div class="layui-input-inline" style="width:180px;">
                    <select name="pay_way" lay-verify="" lay-filter="pay_way">
                        <option value="">支付方式</option>
                        <option value="1">微信支付</option>
                        <option value="2">支付宝</option>
                        <option value="3">余额</option>
                        <option value="4">银行卡</option>
                        <option value="5">现金</option>
                        <option value="6">美团</option>
                        <option value="7">赠送</option>
                        <option value="8">门店自用</option>
                        <option value="9">兑换</option>
                        <option value="12">超级汇买</option>
                        <option value="13">限时余额</option>
                        <option value="15">框框宝</option>
                    </select>
                </div>
                <div class="layui-input-inline">
                    <select name="is_examine" lay-verify="" lay-filter="is_examine">
                        <option value="">对账状态</option>
                        <option value="0">未对账</option>
                        <option value="1">已对账</option>
                    </select>  
                </div>
                <div class="layui-input-inline" style="width:180px;">
                    <select name="status" lay-verify="" lay-filter="status">
                        <option value="">订单状态</option>
                        <option value="2">正常</option>
                        <option value="-3">有退单</option>
                        <option value="-6">已退单</option>
                    </select>  
                </div>
                <div class="layui-input-inline" style="width:180px;">
                    <input type="text" name="start_time" readonly  class="layui-input" id="start_time" placeholder="开始时间">
                </div>
                <div class="layui-input-inline" style="width:180px;">
                    <input type="text" name="end_time" readonly  class="layui-input" id="end_time" placeholder="结束时间">
                </div>
                <div class="layui-input-inline" style="width:250px;">
                    <input type="text" name="search" class="layui-input" id="search" placeholder="请输入需查询手机号/订单号/商品名称">
                </div>
                 <button class="layui-btn" lay-submit lay-filter="formDemo">搜索</button>
                 <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                 <a class="layui-btn layui-btn-sm" id="duizhang">批量对账</a>
                 <a class="layui-btn layui-btn-sm" lay-submit lay-filter="excel">订单导出</a>
            </div>
            </form>
            <table class="layui-hide" id="table" lay-filter="table"></table>
            <script type="text/html" id="typeTool">
                {{# if(d.mobile ==0){}}
                    非会员
                {{#}else{}}
                    <a lay-event="member" style="cursor:pointer">{{d.mobile}}</a>
                {{#}}}
            </script>
            <script type="text/html" id="workerTool">

                <a lay-event="worker" style="cursor:pointer">{{d.waiter}}</a>

            </script>
            <script type="text/html" id="refundTool">
                <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="refund_details">退单详情</a>
            </script>
            <script type="text/html" id="statusTool">
                {{# if(d.order_status =="正常"){}}
                    <div class="layui-btn layui-btn-xs" style="cursor:default">
                        {{d.order_status}}
                    </div>
                {{#}else if(d.order_status=="有退单"){}}
                    <div class="layui-btn layui-btn-xs layui-btn-normal" style="cursor:default">
                        {{d.order_status}}
                    </div>
                {{#}else if(d.order_status=="已退单" || d.order_status=="已退款" ){}}
                    <div class="layui-btn layui-btn-xs layui-btn-danger" style="cursor:default">
                        {{d.order_status}}
                    </div>
                {{#}}}
            </script>
            
            <script type="text/html" id="barTool">

                {{# if(d.refund_num >0){}}
                    <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="refund">退单列表</a>
                {{#}}}
                <a class="layui-btn layui-btn-xs" lay-event="details">订单详情</a>
            </script>
            <script type="text/html" id="examineTool">
                {{# if(d.is_examine == 1){}}
                    <div class="layui-btn layui-btn-xs" style="cursor:default" lay-event="quxiao">已对账</div>
                {{#  } else { }}
                    <div class="layui-btn layui-btn-xs  layui-btn-normal" style="cursor:default" lay-event="duizhang">未对账</div>
                {{#}}}
            </script>
        </div>
    </div>
</div>

<script type="text/javascript">
layui.use(["table","layer","laydate",'form'], function() {
    var table = layui.table,
        layer = layui.layer,
        laydate = layui.laydate,
        form = layui.form,
        $ = layui.$;
    table.render({
        elem: '#table',
        toolbar: '#toolbarDemo',
        skin: 'row', //行边框风格
        even: true ,//开启隔行背景
        id:"tableTest",
        url: '<?php echo url("admin/order/index"); ?>',
        height: 'full-148',
        page: true,
        cols: [
            [
                {type:'checkbox'},
                { field: 'message', minWidth:250, title: '订单信息'},
                { field: 'item_list',minWidth:250,  title: '商品名称'},
                { field: 'num_list',  title: '数量'},
                { field: 'price_list',  title: '价格'},
                { field: 'waiter_list', title: '服务人员' },
                { field: 'cost_list',title: '单商品总成本' },
                { field: 'remarks',title: '订单备注' },
                { field: 'overtime', minWidth:180,title: '交易时间'},
                { field: 'order_status', title: '状态',toolbar:"#statusTool" },
                { field: 'is_examine', title: '对账',toolbar:"#examineTool" },
                { fixed: 'right', minWidth: 205, title: '操作', toolbar: '#barTool' }
            ]
        ],
    });
    //时间选择器
    laydate.render({
        elem: '#start_time',
        type: 'date'
    });
    laydate.render({
        elem: '#end_time',
        type: 'date'
    });
  
    form.on("submit(excel)",function(data){
        let field = data.field;
        const url = `http://localhost:82/admin/Excel/exportShopOrderExcel?end_time=${field.end_time}&is_examine=${field.is_examine}&pay_way=${field.pay_way}&search=${field.search}&shop=${field.shop}&start_time=${field.start_time}&status=${field.status}`;        // location.href = url;
        location.href=url;
        return false;
    });

    form.on("submit(formDemo)",function(data){
        var field = data.field;
        console.log(field);
        table.reload('tableTest', {
            where: { //设定异步数据接口的额外参数，任意设
                field
            }
            ,page: {
                curr: 1 //重新从第 1 页开始
            }
        });
        return false;
    })

    $('#duizhang').on("click",function(){
        //批量对账
        var tt = layui.table.checkStatus('tableTest').data;
        let data = [];
        tt.map(item => {
            data.push(item.id);
        })
        if( data.length == 0 ){
            return false;
        }
        $.ajax({
            url: "/admin/Order/edit_status",    // 提交到controller的url路径
            type: "post",    // 提交方式
            data: {ids:data},  // data为String类型，必须为 Key/Value 格式。
            dataType: "json",    // 服务器端返回的数据类型
            success: function (data) { 
                if( data.code == 1 ){
                    layer.msg(data.msg, {time: 3000});
                    setTimeout(function(){
                        location.reload();
                    }, 1000); 
                    
                }else{
                    layer.msg(data.msg);
                }
            },
        });
    });
    //监听行工具事件
    table.on('tool(table)', function(obj) {
        var data = obj.data;
        if (obj.event === 'del') {
            layer.confirm('确定删除这条数据？', { icon: 3, title: '提示' }, function(index) {
                layer.close(index);
                $.post('<?php echo url("admin/order/del"); ?>', { 'id': data.order_id }, function(data) {
                    if (data.code == 1) {
                        if (data.url) {
                            layer.msg(data.msg + ' 页面即将自动跳转~');
                        } else {
                            layer.msg(data.msg);
                        }
                        setTimeout(function() {
                            if (data.url) {
                                location.href = data.url;
                            } else {
                                location.reload();
                            }
                        }, 1500);
                    } else {
                        layer.msg(data.msg);
                        setTimeout(function() {
                            if (data.url) {
                                location.href = data.url;
                            }
                        }, 1500);
                    }

                });
            });
        }else if (obj.event === 'quxiao') {
            layer.confirm('确认此条数据改为未对账？', { icon: 3, title: '提示' }, function(index) {
                layer.close(index);
                $.post('<?php echo url("admin/Order/quxiao"); ?>', { 'id': data.id }, function(data) {
                    if (data.code == 1) {
                        if (data.url) {
                            layer.msg(data.msg + ' 页面即将自动跳转~');
                        } else {
                            layer.msg(data.msg);
                        }
                        setTimeout(function() {
                            if (data.url) {
                                location.href = data.url;
                            } else {
                                location.reload();
                            }
                        }, 1500);
                    } else {
                        layer.msg(data.msg);
                        setTimeout(function() {
                            if (data.url) {
                                location.href = data.url;
                            }
                        }, 1500);
                    }

                });
            });
        }else if (obj.event === 'duizhang') {
            layer.confirm('确定这条数据已对账？', { icon: 3, title: '提示' }, function(index) {
                layer.close(index);
                $.post('<?php echo url("admin/Order/duizhang"); ?>', { 'id': data.id }, function(data) {
                    if (data.code == 1) {
                        if (data.url) {
                            layer.msg(data.msg + ' 页面即将自动跳转~');
                        } else {
                            layer.msg(data.msg);
                        }
                        setTimeout(function() {
                            if (data.url) {
                                location.href = data.url;
                            } else {
                                location.reload();
                            }
                        }, 1500);
                    } else {
                        layer.msg(data.msg);
                        setTimeout(function() {
                            if (data.url) {
                                location.href = data.url;
                            }
                        }, 1500);
                    }

                });
            });
        }else if (obj.event === 'edit') {
            var url = '<?php echo url("admin/item/edit"); ?>' + "?id=" + data.id;
            layer.open({
                type: 2,
                title: '在线调试',
                area: ['500px', '300px'],
                content: url,
                end: function(layero, index){
                   table.reload('tableTest', {
                    });
                }
            });
        }else if(obj.event === 'member'){
            var loadingFlag;
            $.ajax({
                url: '<?php echo url("admin/order/user"); ?>',
                type: "POST" ,
                data: {id: data.member_id,title:"member"},
                beforeSend: function (XMLHttpRequest) {
                    loadingFlag= layer.msg('正在读取数据，请稍候……',
                        {icon: 16,
                        shade: 0.01,
                        shadeClose:false,
                        time:60000
                    });
                },
                success:function(data){
                    layer.close(loadingFlag);
                    layer.open({
                        type:1,
                        title:"会员信息",
                        area:['300px','300px'],
                        content:`
                        <div class="layui-row">
                            <div class="layui-col-md12 " style="margin-top:10px;">
                                <label class="layui-form-label">会员昵称</label>
                                <div class="layui-form-mid layui-word-aux">
                                    ${data.data.nickname}
                                </div>
                            </div>
                            <div class="layui-col-md12">
                                <label class="layui-form-label">手机号码</label>
                                <div class="layui-form-mid layui-word-aux">
                                    ${data.data.mobile}
                                </div>
                            </div>
                            <div class="layui-col-md12">
                                <label class="layui-form-label">所属门店</label>
                                <div class="layui-form-mid layui-word-aux">
                                    ${data.data.name}
                                </div>
                            </div>
                            <div class="layui-col-md12">
                                <label class="layui-form-label">会员等级</label>
                                <div class="layui-form-mid layui-word-aux">
                                    ${data.data.level_name}
                                </div>
                            </div>
                            <div class="layui-col-md12">
                                <label class="layui-form-label">加入时间</label>
                                <div class="layui-form-mid layui-word-aux">
                                    ${data.data.regtime}
                                </div>
                            </div>
                        </div>
                        `,
                    });
                }
            })
        }else if(obj.event==="worker"){
            var loadingFlag;
                $.ajax({
                url: '<?php echo url("admin/order/user"); ?>',
                type: "POST" ,
                data: {id: data.waiter_id,title:"worker"},
                beforeSend: function (XMLHttpRequest) {
                    loadingFlag= layer.msg('正在读取数据，请稍候……',
                        {icon: 16,
                        shade: 0.01,
                        shadeClose:false,
                        time:60000
                    });
                },
                success: function(data) {
                    layer.close(loadingFlag);
                    layer.open({
                        type:1,
                        title:"服务人员信息",
                        area:['300px','300px'],
                        content:`
                        <div class="layui-row">
                            <div class="layui-col-md12 " style="margin-top:10px;">
                                <label class="layui-form-label">员工姓名</label>
                                <div class="layui-form-mid layui-word-aux">
                                    ${data.data.name}
                                </div>
                            </div>
                            <div class="layui-col-md12">
                                <label class="layui-form-label">手机号码</label>
                                <div class="layui-form-mid layui-word-aux">
                                    ${data.data.mobile}
                                </div>
                            </div>
                            <div class="layui-col-md12">
                                <label class="layui-form-label">所属门店</label>
                                <div class="layui-form-mid layui-word-aux">
                                    ${data.data.shop_name}
                                </div>
                            </div>
                            <div class="layui-col-md12">
                                <label class="layui-form-label">所属职位</label>
                                <div class="layui-form-mid layui-word-aux">
                                    ${data.data.type}
                                </div>
                            </div>
                            <div class="layui-col-md12">
                                <label class="layui-form-label">加入时间</label>
                                <div class="layui-form-mid layui-word-aux">
                                    ${data.data.addtime}
                                </div>
                            </div>
                        </div>
                        `,
                    });
                }
            })
        }else if(obj.event==="details"){
            var shop_id = data.id;
            layer.open({
                type:2,
                title:"订单详情",
                area:['900px','700px'],
                content:"<?php echo url('admin/order/details'); ?>?id="+shop_id,
                end: function(layero, index){
                   table.reload('tableTest', {
                    });
                }
            })
        }else if(obj.event === "refund"){
            var data =  obj.data;
            layer.open({
                type:1,
                title:"退货订单",
                area:['960px','600px'],
                content:`<table class="layui-hide" id="refund_list" lay-filter="refund_list"></table>`,
                success:function(layero,index){
                    table.render({
                        elem: '#refund_list',
                        /*toolbar: '#toolbarDemo',*/
                        skin: 'row', //行边框风格
                        even: true ,//开启隔行背景
                        id:"refund_list",
                        url: '<?php echo url("admin/order/refund_list"); ?>?id='+data.id,
                        height: '530',
                        page: true,
                        cols: [[
                            { field: 'r_sn',align: 'center',minWidth:150,title: '退货订单'},
                            { field: 'r_number',align: 'center',minWidth:90,  title: '数量'},
                            { field: 'r_amount',align: 'center',minWidth:100,  title: '总金额', },
                            { field: 'r_type',align: 'center',  title: '付款方式'},
                            { field: 'otype',align: 'center',  title: '退款方式'},
                            { field: 'dealwith_time',align: 'center',minWidth:180,  title: '处理时间',},
                            { field: 'r_status',align: 'center', title: '处理状态' },
                            { align: 'center',  title: '操作', toolbar: '#refundTool' },
                        ]],
                    });
                    table.on('tool(refund_list)', function(obj) {
                        var r_data = obj.data;
                        if(obj.event === "refund_details"){
                            layer.open({
                                type:2,
                                title:"退单明细",
                                area:['780px',"530px"],
                                content:"<?php echo url('admin/order/refund'); ?>?id="+r_data.id,
                                end:function(layeroo,indexo){
                                    table.reload('refund_list', {
                                    }); 
                                }
                            })
                        }
                    })
                },
                end: function(layero, index){
                   table.reload('tableTest', {
                    });
                }
            })
        }
    });
});
</script>

