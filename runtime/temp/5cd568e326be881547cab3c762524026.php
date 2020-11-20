<?php /*a:2:{s:70:"D:\PHPTutorial\WWW\ddxm_svn\application\admin\view\shop\shop_list.html";i:1601275830;s:68:"D:\PHPTutorial\WWW\ddxm_svn\application\admin\view\index_layout.html";i:1601275830;}*/ ?>
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
                <a class="layui-btn layui-btn-sm" id="AddShop" lay-event="AddShop">添加门店</a>
                <div class="layui-input-inline">
                    <input class="layui-input" id="demoReload" value="<?php echo htmlentities($data1['name']); ?>" name="name" placeholder="请输入门店名称" autocomplete="off">
                </div>
                <div class="layui-input-inline " style="width: 90px">
                    <button class="layui-btn" id="searchEmailCompany" data-type="reload">
                        <i class="layui-icon" style="font-size: 20px; "></i> 搜索
                    </button>
                </div>
            <table class="layui-hide" id="table" lay-filter="table"></table>
            <!-- <script type="text/html" id="toolbarDemo">
                <div class="layui-btn-container">
                    <a class="layui-btn layui-btn-sm" id="AddShop" lay-event="AddShop">添加门店</a>
              </div>
            </script> -->
            <script type="text/html" id="barTool">
                <a class="layui-btn layui-btn-xs" lay-event="edit">编辑</a>
                {{#  if(d.status == 1){ }}
               <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="del">禁用</a>
                {{#  } else { }}
                
                <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="start">启用</a>
                {{#  } }}
            </script>
        </div>
    </div>
</div>

    
<script type="text/javascript">
layui.use(["table","layer"], function() {
    var table = layui.table,
        layer = layui.layer,
        $ = layui.$;
        table.render({
        elem: '#table',
        toolbar: '#toolbarDemo',
        id:"tableTset",
        url: '<?php echo url("admin/Shop/shop_list"); ?>',
        /*limit:1,
        limits:[1,2,30,40,50,60,70,80,90],*/
        page: true,
        cols: [
            [
                { field: 'id', width: 80, title: 'ID'},
                { field: 'name',  title: '门店名称'},
                { field: 'addtime',  title: '创建时间'},
                { field: 'update_time', title: '更新时间'},
                { fixed: 'right',  title: '操作', toolbar: '#barTool' }
            ]
        ],
    });

    //添加门店
    $("#AddShop").on("click",function(){
        var url  ="<?php echo url('admin/Shop/add'); ?>";
        layer.open({
            type: 2,
            title: '添加门店',
            area: ['600px', '300px'],
            content: url,
            end: function(layero, index){
                table.reload('table', {
                });
            }
        });
    });

    //搜索
    $("#searchEmailCompany").on("click",function(){
        var name = $('#demoReload').val();
        table.reload('tableTset', {
          url: '/admin/shop/shop_list',
          where: {name:name} //设定异步数据接口的额外参数
        });
    });


    //监听行工具事件
    table.on('tool(table)', function(obj) {
        var data = obj.data;
        //console.log(obj);
        if (obj.event === 'del') {
            layer.confirm('确定禁用这条数据？', { icon: 3, title: '提示' }, function(index) {
                layer.close(index);
                $.post('<?php echo url("admin/Shop/del"); ?>', { 'id': data.id }, function(data) {
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
        }else if (obj.event === 'start') {
            layer.confirm('确定启用这条数据？', { icon: 3, title: '提示' }, function(index) {
                layer.close(index);
                $.post('<?php echo url("admin/Shop/start"); ?>', { 'id': data.id }, function(data) {
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
            var url = '<?php echo url("admin/Shop/add"); ?>' + "?id=" + data.id;
            layer.open({
                type: 2,
                title: '在线调试',
                area: ['600px', '300px'],
                content: url,
                end: function(layero, index){
                   table.reload('tableTset', {
                    });
                }
            });
        }
    });
});
</script>

</body>

</html>
