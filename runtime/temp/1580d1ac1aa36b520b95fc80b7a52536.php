<?php /*a:2:{s:75:"D:\PHPTutorial\WWW\ddxm_svn\application\admin\view\order\recharge_list.html";i:1601275829;s:68:"D:\PHPTutorial\WWW\ddxm_svn\application\admin\view\index_layout.html";i:1601275830;}*/ ?>
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
    <div class="layui-card-header">
        <fieldset class="layui-elem-field layui-field-title">
            <legend>门店充值订单</legend>
        </fieldset>
 </div>
    <div class="layui-card-body">
        <div class="layui-form">
          <a class="layui-btn layui-btn-sm" id="Add">充值</a>
            <div class="layui-input-inline">
                <select name="shop_id" id="shop_id" lay-filter="shop_id">
                    <option value="0">全部门店</option>
                    <?php if(is_array($shop) || $shop instanceof \think\Collection || $shop instanceof \think\Paginator): $i = 0; $__LIST__ = $shop;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?>
                        <option value="<?php echo htmlentities($vo['id']); ?>"><?php echo htmlentities($vo['name']); ?></option>
                    <?php endforeach; endif; else: echo "" ;endif; ?> 
                </select>
            </div>
            <div class="layui-input-inline">
                <select name="waiter_id" id="waiter_id" lay-filter="waiter_id">
                    <option value="0">选择服务人员</option>
                </select>
            </div>
            <div class="layui-input-inline">
                <select name="pay_way" id="pay_way" lay-filter="pay_way">
                    <option value="0">选择支付方式</option>
                    <option value="5">现金</option>
                    <option value="4">银行卡</option>
                    <option value="1">微信</option>
                    <option value="2">支付宝</option>
                </select>
            </div>
            <div class="layui-input-inline">
                    <select name="is_examine" lay-verify="" id="is_examine" lay-filter="is_examine">
                        <option value="">对账状态</option>
                        <option value="0">未对账</option>
                        <option value="1">已对账</option>
                    </select>  
                </div>
            <div class="layui-input-inline">
                <input class="layui-input" id="name" name="name" placeholder="订单号/会员手机号" autocomplete="off">
            </div>
            <div class="layui-input-inline">
                <input class="layui-input" id="time" name="time" placeholder="开始时间" autocomplete="off">
            </div>
            <div class="layui-input-inline">
                <input class="layui-input" id="end_time" name="end_time" placeholder="结束时间" autocomplete="off">
            </div>
            <div class="layui-input-inline " style="width: 90px">
                <button class="layui-btn" id="searchEmailCompany" data-type="reload">
                    <i class="layui-icon" style="font-size: 20px; "></i> 搜索
                </button>
            </div>
            <div class="layui-input-inline " style="width: 90px">
                <button class="layui-btn" id="duizhang" data-type="reload">
                    批量对账
                </button>
            </div>
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
            <script type="text/html" id="barTool">
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

    
<script>
    layui.use('laydate', function(){
      var laydate = layui.laydate;
      //执行第一个laydate实例
      laydate.render({
        elem: '#time', //指定元素
        type: 'datetime'
      });
      //执行第二个laydate实例
      laydate.render({
        elem: '#end_time', //指定元素
        type: 'datetime'
      });
    });
</script>
<script type="text/javascript">
layui.use(["table","layer"], function() {
    var table = layui.table,
        layer = layui.layer,
        $ = layui.$;
    table.render({
        elem: '#table',
        toolbar: '#toolbarDemo',
        skin: 'row', //行边框风格
        even: true ,//开启隔行背景
        id:"tableTest",
        url: '<?php echo url("admin/order/recharge_list"); ?>',
        height: 'full-148',
        page: true,
        cols: [
            [
                {type:'checkbox'},
                { field: 'sn',  title: '订单号'},
                { field: 'shop_name',  title: '门店'},
                { field: 'member_id',  title: '会员账号', toolbar: '#typeTool'},
                { field: 'nickname',  title: '会员昵称'},
                { field: 'amount',  title: '充值金额'},
                { field: 'pay_way', title: '付款方式' },
                { field: 'overtime',title: '交易时间' },
                { field: 'waiter', title: '服务人员',toolbar:"#workerTool"},
                { field: 'remarks', title: '备注' },
                { field: 'is_examine', title: '对账',toolbar:"#examineTool" },
                { fixed: 'right', minWidth: 205, title: '操作', toolbar: '#barTool' }
            ]
        ],
    });

    //充值
    $("#Add").on("click",function(){
        var url  ="<?php echo url('admin/Order/recharge'); ?>";
        layer.open({
            type: 2,
            title: '充值',
            area: ['800px', '650px'],
            content: url,
            end: function(layero, index){
                table.reload('tableTset', {
                });
            }
        });
    });

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
                        // location.reload();
                    }, 1000); 
                    
                }else{
                    layer.msg(data.msg);
                }
            },
        });
    });

    //搜索
    $("#searchEmailCompany").on("click",function(){
        var name = $('#name').val();
        var shop_id = $('#shop_id').val();
        var pay_way = $('#pay_way').val();
        var waiter_id = $('#waiter_id').val();
        var time = $('#time').val();
        var end_time = $('#end_time').val();
        var is_examine = $('#is_examine').val();
        if( time != '' && end_time=='' ){
            layer.msg('必须选择起始时间');
            return false;
        }
        if( time == '' && end_time !='' ){
            layer.msg('必须选择起始时间');
            return false;
        }
        table.reload('tableTest', {
          url: '/admin/Order/recharge_list',
          where:{  name:name,
                    waiter_id:waiter_id,
                    shop_id:shop_id,
                    pay_way:pay_way,
                    time:time,
                    end_time:end_time,
                    is_examine:is_examine
                } //设定异步数据接口的额外参数
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
                                // location.href = data.url;
                            } else {
                                // location.reload();
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
                                // location.href = data.url;
                            } else {
                                // location.reload();
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
                content:"<?php echo url('admin/order/order_info'); ?>?id="+shop_id,
                end: function(layero, index){
                   table.reload('tableTest', {
                    });
                }
            })
        }else if(obj.event === "refund"){
            var data =  obj.data;
            layer.open({
                type:2,
                title:"退货订单",
                area:['760px','640px'],
                content:"<?php echo url('admin/order/s_refund'); ?>?id="+data.id,
                end: function(layero, index){
                   table.reload('tableTest', {
                    });
                }
            })
        }
    });
});

layui.use(["form"], function(){
        var layer = layui.layer;
        form = layui.form;

        //筛选分类
        form.on('select(shop_id)', function(data){   
            var val=data.value;
            if( val == 0 ){
                $('#waiter_id').empty();
                $('#waiter_id').append("<option value='0'>请选择</option>");
                form.render("select");
                return false;
            }
            $.ajax({
                type : "post",
                url : "/admin/Order/waiter_select",
                data: {shop_id:val},
                dataType: 'json',
                success:function(res){
                    if( res.code == 1 ){
                        var category = res.data;
                        console.log(category);
                        var option = '';
                        $('#waiter_id').empty();
                        $('#waiter_id').append("<option value='0'>请选择</option>");
                        for( var k in category){
                            $("#waiter_id").append("<option value=" + category[k].id + ">" + category[k].name + "</option>");
                        }
                        form.render("select");
                    }
                }
            });
        });
    });
</script>

</body>

</html>
