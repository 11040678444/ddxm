<?php /*a:1:{s:70:"D:\PHPTutorial\WWW\ddxm_svn\application\mall\view\items\item_list.html";i:1601275823;}*/ ?>
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
    <div class="layui-card-header">商品列表</div>
    <div class="layui-card-body">
        <div class="layui-form">

            <a class="layui-btn layui-btn-sm" id="Add" href="<?php echo url('Items/item_add'); ?>" >添加商品</a>
            <div class="layui-input-inline">
                <input class="layui-input" id="demoReload" name="name" placeholder="商品名称" autocomplete="off">
            </div>
            <div class="layui-input-inline">
                <input class="layui-input" id="bar_code" name="bar_code" placeholder="条形码" autocomplete="off">
            </div>
            <div class="layui-input-inline">
                <select name="brand_id" id="brand_id" lay-filter="brand_id" lay-search="">
                    <option value="0">请选择品牌</option>
                    <option value="t1">未选择品牌</option>
                    <?php if(is_array($brand) || $brand instanceof \think\Collection || $brand instanceof \think\Paginator): $i = 0; $__LIST__ = $brand;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$brand): $mod = ($i % 2 );++$i;?>
                    <option value="<?php echo htmlentities($brand['id']); ?>"  ><?php echo htmlentities($brand['title']); ?></option>
                    <?php endforeach; endif; else: echo "" ;endif; ?>
                </select>
            </div>
            <div class="layui-input-inline">
                <select name="brand_id" id="sup" lay-filter="brand_id" lay-search="">
                    <option value="0">请选择供应商</option>
                    <option value="t1">未选择供应商</option>
                    <?php if(is_array($sup) || $sup instanceof \think\Collection || $sup instanceof \think\Paginator): $i = 0; $__LIST__ = $sup;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$sup): $mod = ($i % 2 );++$i;?>
                    <option value="<?php echo htmlentities($sup['id']); ?>"  ><?php echo htmlentities($sup['title']); ?></option>
                    <?php endforeach; endif; else: echo "" ;endif; ?>
                </select>
            </div>
            <div class="layui-input-inline">
                <select name="type_id" id="type_id" lay-filter="type_id" lay-search="">
                    <option value="0">请选择一级分类</option>
                    <?php if(is_array($category) || $category instanceof \think\Collection || $category instanceof \think\Paginator): $i = 0; $__LIST__ = $category;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$category): $mod = ($i % 2 );++$i;?>
                    <option value="<?php echo htmlentities($category['id']); ?>"  ><?php echo htmlentities($category['cname']); ?></option>
                    <?php endforeach; endif; else: echo "" ;endif; ?>
                </select>
            </div>
            <div class="layui-input-inline">
                <select name="type" id="type" lay-filter="type" lay-search="">
                    <option value="0">先选择一级分类</option>
                </select>
            </div>
            <div class="layui-input-inline">
                <select name="type_three" id="type_three" lay-filter="type_three" lay-search="">
                    <option value="0">先选择二级分类</option>
                </select>
            </div>
            <div class="layui-input-inline">
                <select name="type_three" id="status" lay-filter="status">
                    <option value="1">上架</option>
                    <option value="2">下架</option>
                </select>
            </div>
            <div class="layui-input-inline " style="width: 90px">
                <button class="layui-btn" id="searchEmailCompany" data-type="reload">
                    <i class="layui-icon" style="font-size: 20px; "></i> 搜索
                </button>
            </div>

            <div class="layui-input-inline " style="width: 90px">
                <button class="layui-btn" id="all_up" data-type="reload">
                    <!--<i class="layui-icon" style="font-size: 20px; "></i> -->
                    批量上架
                </button>
            </div>

            <div class="layui-input-inline " style="width: 90px">
                <button class="layui-btn" id="all_down" data-type="reload">
                    <!--<i class="layui-icon" style="font-size: 20px; "></i> -->
                    批量下架
                </button>
            </div>

            <table class="layui-hide" id="table" lay-filter="table"></table>
            <script type="text/html" id="barTool">
                {{#  if(d.status == 1){ }}
                <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="xiajia">下架</a>
                {{#  } else if(d.status == 0) { }}
                <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="shangjia">上架</a>
                {{#  } }}

                <a class="layui-btn layui-btn-xs" lay-event="edit">编辑</a>
                <!--                <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="del">删除</a>-->
            </script>

            <script type="text/html" id="showTool">
                {{# if(d.show ==1){}}
                <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="no_show">不显示</a>
                {{#  } else if(d.show == 0) { }}
                <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="show">显示</a>
                {{#}}}

            </script>
        </div>
    </div>
</div>
<script type="text/javascript">
    var istg = false;

    layui.use(["table","layer"], function() {
        var table = layui.table,
            layer = layui.layer,
            $ = layui.$;

        var page2 = "<?php echo htmlentities($page2); ?>";

        table.render({
            elem: '#table',
            toolbar: '#toolbarDemo',
            skin: 'row', //行边框风格
            even: true ,//开启隔行背景
            id:"tableTset",
            url: '<?php echo url("mall/items/item_list"); ?>',
            height: 'full-128',
            /*limit:1,
            limits:[1,2,30,40,50,60,70,80,90],*/
            page: true,
            cols: [
                [
                    { field: 'id',type:'checkbox', title: ''},
                    { field: 'id', width: 80, title: 'ID'},
                    { field: 'pic_src', /*width: 80,*/ title: '商品主图'},
                    { field: 'title', /*width: 80,*/ title: '商品名称', edit: 'text'},
                    { field: "key_name", /*width: 120,*/ title: '规格'},
                    { field: "yuanjia", /*width: 120,*/ title: '原价', edit: 'text'},
                    { field: "price", /*width: 120,*/ title: '现价', edit: 'text'},
                    { field: "bar_code", /*width: 120,*/ title: '条形码', edit: 'text'},
                    { field: 'mold_id', title: '分区' },
                    { field: 'brand', title: '品牌' },
                    { field: 'show', title: '是否显示',toolbar:'#showTool' },
                    { fixed: 'right', /*width: 160,*/ title: '操作', toolbar: '#barTool' }
                ]
            ],
            done: function(res, curr, count){
                //如果是异步请求数据方式，res即为你接口返回的信息。
                //如果是直接赋值的方式，res即为：{data: [], count: 99} data为当前页数据、count为数据总长度
                // console.log(res);

                //得到当前页码
                // console.log(curr);

                //得到数据总量
                // console.log(count);
                //调转指定页
                if(istg){
                    return;
                }
                if(page2 != -1 && page2 !=curr ){
                    $(".layui-laypage-skip").find("input").val(page2);
                    $(".layui-laypage-btn").click();
                    istg = true;
                }
            }
        });

        //监听单元格编辑
        table.on('edit(table)', function(obj){
            var value = obj.value //得到修改后的值
                ,data = obj.data //得到所在行所有键值
                ,field = obj.field; //得到字段
            $.ajax({
                type : "post",
                url : "/mall/Items/update",
                data: {id:data.id,field:field,value,value},
                dataType: 'json',
                success:function(res){
                    if( res.code == 1 ){
                        // layer.msg('[ID: '+ data.id +'] ' + field + ' 字段更改为：'+ value);
                    }else if( res.code==2 ){
                        layer.msg(res.msg);
                    }else{
                        layer.msg('编辑失败,请联系管理员');
                    }
                }
            });
        });

        //搜索
        $("#searchEmailCompany").on("click",function(){
            var name = $('#demoReload').val();
            var bar_code = $('#bar_code').val();
            var status = $('#status').val();
            var type_id = $('#type_id').val();
            var type = $('#type').val();
            var type_three = $('#type_three').val();
            if( type_three != 0 ){
                var class_id = type_three;
            }else if( type_three == 0 && type != 0 ){
                var class_id = type;
            }else if( type == 0 && type_three == 0 && type_id != 0 ){
                var class_id = type_id;
            }else{
                var class_id = 0;
            }
            var brand_id = $('#brand_id').val();
            var sup_id = $('#sup').val();
            table.reload('tableTset', {
                url: '/mall/items/item_list',
                where: {name:name,brand_id:brand_id,class_id:class_id,status:status,bar_code:bar_code,sup_id:sup_id} //设定异步数据接口的额外参数
            });
        });

        //批量上架
        $("#all_up").click(function () {
           var obj = table.checkStatus('tableTset').data;

           if(!obj.length)
           {
               layer.msg('至少选择一个对象！');
               return false;
           }else{
               var ids='';
               $.each(obj,function (k,v) {
                   ids+=v.id+',';
               })
           }

           layer.confirm('确定批量上架吗？',{ icon: 3, title: '提示' },function (index) {
               layer.close(index);
               $.post('<?php echo url("admin/item/item_shangjia"); ?>',{'id':ids},function (data) {
                   layer.msg(data.msg);
                   table.reload('tableTset', {
                   });
               },'json');
           });
        });

        //批量下架
        $("#all_down").click(function () {
            var obj = table.checkStatus('tableTset').data;

            if(!obj.length)
            {
                layer.msg('至少选择一个对象！');
                return false;
            }else{
                var ids='';
                $.each(obj,function (k,v) {
                    ids+=v.id+',';
                })
            }

            layer.confirm('确定批量下架吗？',{ icon: 3, title: '提示' },function (index) {
                layer.close(index);
                $.post('<?php echo url("admin/item/item_xiajia"); ?>',{'id':ids},function (data) {
                    layer.msg(data.msg);
                    table.reload('tableTset', {
                    });
                },'json');
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
                                    table.reload('tableTset', {
                                    });
                                } else {
                                    table.reload('tableTset', {
                                    });
                                }
                            }, 1500);
                        } else {
                            layer.msg(data.msg);
                            setTimeout(function() {
                                if (data.url) {
                                    table.reload('tableTset', {
                                    });
                                }
                            }, 1500);
                        }

                    });
                });
            }else if (obj.event === 'edit') {
                var recodePage = $(".layui-laypage-skip .layui-input").val();
                location.href="/mall/items/item_save?id=" + data.id+"&page="+recodePage;
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
                                    table.reload('tableTset', {
                                    });
                                } else {
                                    table.reload('tableTset', {
                                    });
                                }
                            }, 1500);
                        } else {
                            layer.msg(data.msg);
                            setTimeout(function() {
                                if (data.url) {
                                    table.reload('tableTset', {
                                    });
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
                                    table.reload('tableTset', {
                                    });
                                } else {
                                    table.reload('tableTset', {
                                    });
                                }
                            }, 1500);
                        } else {
                            layer.msg(data.msg);
                            setTimeout(function() {
                                if (data.url) {
                                    table.reload('tableTset', {
                                    });
                                }
                            }, 1500);
                        }

                    });
                });
            }else if (obj.event === 'no_show') {
                layer.confirm('确定不显示这条数据？', { icon: 3, title: '提示' }, function(index) {
                    layer.close(index);
                    $.post('<?php echo url("mall/items/no_show"); ?>', { 'id': data.id }, function(data) {
                        if (data.code == 1) {
                            if (data.url) {
                                layer.msg(data.msg + ' 页面即将自动跳转~');
                            } else {
                                layer.msg(data.msg);
                            }
                            setTimeout(function() {
                                if (data.url) {
                                    table.reload('tableTset', {
                                    });
                                } else {
                                    table.reload('tableTset', {
                                    });
                                }
                            }, 1500);
                        } else {
                            layer.msg(data.msg);
                            setTimeout(function() {
                                if (data.url) {
                                    table.reload('tableTset', {
                                    });
                                }
                            }, 1500);
                        }

                    });
                });
            }else if (obj.event === 'show') {
                layer.confirm('确定显示这条数据？', { icon: 3, title: '提示' }, function(index) {
                    layer.close(index);
                    $.post('<?php echo url("mall/items/show"); ?>', { 'id': data.id }, function(data) {
                        if (data.code == 1) {
                            if (data.url) {
                                layer.msg(data.msg + ' 页面即将自动跳转~');
                            } else {
                                layer.msg(data.msg);
                            }
                            setTimeout(function() {
                                if (data.url) {
                                    table.reload('tableTset', {
                                    });
                                } else {
                                    table.reload('tableTset', {
                                    });
                                }
                            }, 1500);
                        } else {
                            layer.msg(data.msg);
                            setTimeout(function() {
                                if (data.url) {
                                    table.reload('tableTset', {
                                    });
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
                        $('#type').append("<option value='0'>请选择</option>");
                        for( var k in category){
                            $("#type").append("<option value=" + category[k].id + ">" + category[k].cname + "</option>");
                        }
                        form.render("select");
                    }
                }
            });
        });

        form.on('select(type)', function(data){
            var val=data.value;
            $.ajax({
                type : "post",
                url : "/admin/Item/category_select",
                data: {pid:val},
                dataType: 'json',
                success:function(res){
                    if( res.code == 1 ){
                        var category = res.data;
                        var option = '';
                        $('#type_three').empty();
                        $('#type_three').append("<option value='0'>请选择</option>");
                        for( var k in category){
                            $("#type_three").append("<option value=" + category[k].id + ">" + category[k].cname + "</option>");
                        }
                        form.render("select");
                    }
                }
            });
        });
    });
</script>
