<?php /*a:2:{s:66:"D:\PHPTutorial\WWW\ddxm_svn\application\admin\view\item\index.html";i:1601275830;s:68:"D:\PHPTutorial\WWW\ddxm_svn\application\admin\view\index_layout.html";i:1601275830;}*/ ?>
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
    <div class="layui-card-header">商品列表</div>
    <div class="layui-card-body">
        <div class="layui-form">
            
            <a class="layui-btn layui-btn-sm" id="Add" >添加商品</a>
            <div class="layui-input-inline">
                <select name="type_id" id="type_id" lay-filter="type_id">
                    <option value="0">请选择一级分类</option>
                    <?php if(is_array($typeId) || $typeId instanceof \think\Collection || $typeId instanceof \think\Paginator): $i = 0; $__LIST__ = $typeId;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?>
                        <option value="<?php echo htmlentities($vo['id']); ?>" <?php if($vo['id'] == $list['type_id']): ?> selected="selected" <?php endif; ?> ><?php echo htmlentities($vo['cname']); ?></option>
                    <?php endforeach; endif; else: echo "" ;endif; ?> 
                </select>
            </div>
            <div class="layui-input-inline">
                <select name="type" lay-filter="type" id="type">
                    <option value="0">请先选择二级分类</option>
                </select>
            </div>
            <div class="layui-input-inline">
                <input class="layui-input" id="demoReload" name="name" placeholder="商品名称" autocomplete="off">
            </div>
            <div class="layui-input-inline">
                <input class="layui-input" id="bar_code" name="bar_code" placeholder="条形码" autocomplete="off">
            </div>
            <div class="layui-input-inline " style="width: 90px">
                <button class="layui-btn" id="searchEmailCompany" data-type="reload">
                    <i class="layui-icon" style="font-size: 20px; "></i> 搜索
                </button>
            </div>
            <table class="layui-hide" id="table" lay-filter="table"></table>
            <!-- <script type="text/html" id="toolbarDemo"> -->
                
            <!-- </script> -->
            <script type="text/html" id="barTool">
                {{#  if(d.status == 1){ }}
                <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="xiajia">下架</a>
                {{#  } else if(d.status == 0) { }}
                <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="shangjia">上架</a>
                {{#  } }}

                <a class="layui-btn layui-btn-xs" lay-event="edit">编辑</a>
                <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="del">删除</a>
            </script>
            
            <script type="text/html" id="statusTool">
                {{# if(d.status ==1){}}
                    上架
                {{#}else if(d.status==0){}}
                    下架
                {{#}else{}}
                    删除
                {{#}}}
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
        skin: 'row', //行边框风格
        even: true ,//开启隔行背景
        id:"tableTset",
        url: '<?php echo url("admin/item/index"); ?>',
        height: 'full-128',
        /*limit:1,
        limits:[1,2,30,40,50,60,70,80,90],*/
        page: true,
        cols: [
            [
                { field: 'id', width: 80, title: 'ID'},
                { field: 'title', /*width: 80,*/ title: '商品名称'},
                { field: 'type_id', /*width: 120,*/ title: '一级分类'},
                { field: 'type', title: '二级分类' },
                { field: 'item_type', width: 120, title: '商品库' },
                { field: 'cate_id',width: 120, title: '分区'},
                { field: 'unit_id', title: '单位' },
                { field: 'bar_code', title: '条形码' },
                { field: 'status',width: 80, title: '上下架',toolbar:'#statusTool' },
                { field: 'price', title: '销售价格' },
                // { field: 'create_time', title: '更新时间' },
                { fixed: 'right', /*width: 160,*/ title: '操作', toolbar: '#barTool' }
            ]
        ],
    });

    //添加
    $("#Add").on("click",function(){
        var url  ="<?php echo url('admin/Item/item_add'); ?>";
        layer.open({
            type: 2,
            title: '添加规格',
            area: ['800px', '800px'],
            content: url,
            end: function(layero, index){
                table.reload('tableTset', {
                });
            }
        });
    });

    //搜索
    $("#searchEmailCompany").on("click",function(){
        var name = $('#demoReload').val();
        var type_id = $('#type_id').val();
        var type = $('#type').val();
        var bar_code = $('#bar_code').val();
        table.reload('tableTset', {
          url: '/admin/item/index',
          where: {name:name,type_id:type_id,type:type,bar_code:bar_code} //设定异步数据接口的额外参数
        });
    });

    //监听行工具事件
    table.on('tool(table)', function(obj) {
        var data = obj.data;
        //console.log(obj);
        if (obj.event === 'del') {
            layer.confirm('确定删除这条数据？', { icon: 3, title: '提示' }, function(index) {
                layer.close(index);
                $.post('<?php echo url("admin/item/item_del"); ?>', { 'id': data.id }, function(data) {
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
            var url = '<?php echo url("admin/item/item_add"); ?>' + "?id=" + data.id;
            layer.open({
                type: 2,
                title: '编辑商品',
                area: ['800px', '800px'],
                content: url,
                end: function(layero, index){
                   table.reload('tableTset', {
                    });
                }
            });
        }else if (obj.event === 'xiajia') {
            layer.confirm('确定下架这条数据？', { icon: 3, title: '提示' }, function(index) {
                layer.close(index);
                $.post('<?php echo url("admin/item/item_xiajia"); ?>', { 'id': data.id }, function(data) {
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
        }
        else if (obj.event === 'shangjia') {
            layer.confirm('确定上架这条数据？', { icon: 3, title: '提示' }, function(index) {
                layer.close(index);
                $.post('<?php echo url("admin/item/item_shangjia"); ?>', { 'id': data.id }, function(data) {
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
        }
    });
});

layui.use(["form"], function(){
        var layer = layui.layer;
        form = layui.form;
        //筛选分类
        form.on('select(type_id)', function(data){   
            var val=data.value;
            $.ajax({
                type : "post",
                url : "/admin/Item/category_select",
                data: {pid:val},
                dataType: 'json',
                success:function(res){
                    if( res.code == 1 ){
                        var category = res.data;
                        console.log(category);
                        var option = '';
                        $('#type').empty();
                        $('#type').append("<option value=''>请选择</option>");
                        for( var k in category){
                            $("#type").append("<option value=" + category[k].id + ">" + category[k].cname + "</option>");
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
