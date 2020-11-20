<?php /*a:2:{s:73:"D:\PHPTutorial\WWW\ddxm_svn\application\admin\view\order\ticket_list.html";i:1601275829;s:68:"D:\PHPTutorial\WWW\ddxm_svn\application\admin\view\index_layout.html";i:1601275830;}*/ ?>
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
    <div class="layui-card-body">
        <div class="layui-form">
            <form class="layui-form" action="">
            <div class="layui-form-item">
                <div class="layui-input-inline" style="width:150px;">
                    <select name="shop" lay-verify="" lay-filter="shop">
                        <option value="">全部门店</option>
                        <?php if(is_array($data['shop']) || $data['shop'] instanceof \think\Collection || $data['shop'] instanceof \think\Paginator): $i = 0; $__LIST__ = $data['shop'];if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?>
                        <option value="<?php echo htmlentities($vo['id']); ?>"><?php echo htmlentities($vo['name']); ?></option>
                        <?php endforeach; endif; else: echo "" ;endif; ?>
                    </select>  
                </div>
                <div class="layui-input-inline" style="width:120px;">
                    <select name="worker" lay-verify="" lay-filter="worker">
                        <option value="">服务人员</option>
                        <?php if(is_array($data['worker']) || $data['worker'] instanceof \think\Collection || $data['worker'] instanceof \think\Paginator): $i = 0; $__LIST__ = $data['worker'];if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$wo): $mod = ($i % 2 );++$i;?>
                        <option value="<?php echo htmlentities($wo['id']); ?>"><?php echo htmlentities($wo['name']); ?></option>
                        <?php endforeach; endif; else: echo "" ;endif; ?>
                    </select>  
                </div>
                <div class="layui-input-inline" style="width:150px;">
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
                    </select>  
                </div>
                <div class="layui-input-inline" style="width:150px;">
                    <select name="status" lay-verify="" lay-filter="status">
                        <option value="">订单状态</option>
                        <option value="2">正常</option>
                        <option value="-6">退单</option>
                    </select>  
                </div>
                <div class="layui-input-inline">
                    <select name="is_examine" lay-verify="" lay-filter="is_examine">
                        <option value="">对账状态</option>
                        <option value="0">未对账</option>
                        <option value="1">已对账</option>
                    </select>  
                </div>
                <div class="layui-input-inline" style="width:150px;">
                    <input type="text" name="start_time" readonly  class="layui-input" id="start_time" placeholder="开始时间">
                </div>
                <div class="layui-input-inline" style="width:150px;">
                    <input type="text" name="end_time" readonly  class="layui-input" id="end_time" placeholder="结束时间">
                </div>
                <div class="layui-input-inline" style="width:250px;">
                    <input type="text" name="search" class="layui-input" id="search" placeholder="请输入需查询的会员手机号/订单号">
                </div>
                <button class="layui-btn" lay-submit lay-filter="formDemo">搜索</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                <a class="layui-btn layui-btn-sm" id="duizhang">批量对账</a>
            </div>
            </form>
            <table class="layui-hide" id="table" lay-filter="table"></table>
            <script type="text/html" id="typeTool">                
                <a lay-event="member" style="cursor:pointer">{{d.mobile}}</a>                
            </script>
            <script type="text/html" id="workerTool">
                <a lay-event="worker" style="cursor:pointer">{{d.waiter}}</a>
            </script>
            <script type="text/html" id="barTool">
                {{# if(d.refund_num > 0){}}
                    <!-- <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="refund">退单</a> -->
                    <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="refund_list">退单列表</a>
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
layui.use(["table","layer","form","laydate"], function() {
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
        url: '<?php echo url("admin/order/ticket_list"); ?>',
        height: 'full-108',
        page: true,
        cols: [[
            {type:'checkbox'},
            { field: 'shop_name',align: 'center',width:120,  title: '所属门店'},
            { field: "sn",align: 'center',width:160,  title: '订单号'},
            { field: 'mobile', align: 'center',width:120, title: '会员账号', toolbar: '#typeTool'},
            { field: 'nickname', align: 'center',width:120, title: '会员昵称'},
            { field: 'real_price', align: 'center', title: '付款金额'},
            { field: 'services', align: 'center', title: '服务项目'},
            { field: 'pay_way', align: 'center',title: '付款方式' },
            { field: 'overtime',align: 'center',width:160,title: '交易时间' },
            { field: 'waiter', align: 'center',title: '服务人员',toolbar:"#workerTool"},
            { field: 'status', align: 'center',title: '使用情况'},
            { field: 'order_status',align: 'center', title: '状态'},
            { field: 'is_examine', title: '对账',toolbar:"#examineTool" },
            { fixed: 'right',align: 'right', minWidth: 165, title: '操作', toolbar: '#barTool' }
        ]],
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
    form.on("submit(formDemo)",function(data){
        var field = data.field;
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
                console.log(data) 
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
        //console.log(obj);
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
                content:"<?php echo url('admin/order/ticket_details'); ?>?id="+shop_id,
                end: function(layero, index){
                   table.reload('tableTest', {
                    });
                }
            })
        }/*else if(obj.event === "refund"){
            var data =  obj.data;
            layer.open({
                type:2,
                title:"退货订单",
                area:['760px','560px'],
                content:"<?php echo url('admin/order/ticketrefund'); ?>?id="+data.p_id,
                end: function(layero, index){
                   table.reload('tableTest', {
                    });
                }
            })
        }*/else if (obj.event === 'quxiao') {
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
        }else if(obj.event === "refund_list"){
            /*$.post("<?php echo url('admin/order/refund_list_details'); ?>",{"id":data.id},function(datas){*/
            layer.open({
                type:2,
                title:"订单详情",
                area:['760px','560px'],
                content:"<?php echo url('admin/order/ticket_list_details'); ?>?id="+data.id,
                success:function(){

                },
                end: function(layero, index){
                    table.reload('tableTest', {
                    });
                }
            })
            /*})*/
        }
    });
});
</script>

</body>

</html>
